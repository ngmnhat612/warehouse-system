<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\StockLedger;
use App\Models\InventoryFreeze;
use App\Models\InventoryFreezeDetail;
use App\Models\Location;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

use Carbon\Carbon;

/**
 * StockService — Lớp dịch vụ lõi cho toàn bộ hệ thống quản lý kho.
 *
 * Mọi thao tác thay đổi tồn kho (increase, decrease, reserve, release)
 * đều đi qua class này. Không được cập nhật bảng `stock` trực tiếp từ
 * Controller hay Service khác.
 *
 * Sử dụng trong các Service cấp cao hơn:
 *   - ReceiptService   → increase()
 *   - IssueService     → reserve(), release(), decrease()
 *   - TransferService  → decrease() + increase() cùng reference_id
 *   - ScrapService     → decrease()
 *   - AdjustService    → increase() hoặc decrease() tùy diff_qty
 */
class StockService
{
    // =========================================================================
    // CONSTANTS — Transaction Types & Directions
    // =========================================================================

    // transaction_type trong stock_ledger
    const TYPE_RECEIPT    = 'RECEIPT';
    const TYPE_ISSUE      = 'ISSUE';
    const TYPE_TRANSFER   = 'TRANSFER';
    const TYPE_SCRAP      = 'SCRAP';
    const TYPE_ADJUST     = 'ADJUST';
    const TYPE_SPLIT      = 'SPLIT';
    const TYPE_BUNDLE     = 'BUNDLE';
    const TYPE_RETURN     = 'RETURN';

    // direction trong stock_ledger
    const DIR_IN  = 1;
    const DIR_OUT = 2;

    // Status bảng stock
    const STOCK_STATUS_NORMAL     = 1;
    const STOCK_STATUS_QUARANTINE = 2;
    const STOCK_STATUS_EXPIRED    = 3;

    // =========================================================================
    // PUBLIC API — 4 phương thức dùng chung
    // =========================================================================

    /**
     * Tăng tồn kho (hàng VÀO kho).
     *
     * Dùng cho: Nhập kho (RECEIPT), Chuyển kho phía nhận (TRANSFER IN),
     *           Tách/Ghép phần Produce, Điều chỉnh tăng (ADJUST +)
     *
     * @param  array  $params  {
     *     product_id, location_id, quantity,
     *     lot_id?, serial_id?,
     *     supplier_id?, manufacture_date?, received_date?, expiry_date?,
     *     transaction_type,   // StockService::TYPE_*
     *     reference_id,       // ID phiếu gốc
     *     reference_type,     // 'stock_receipt' | 'stock_transfer' | ...
     *     reference_code,     // Mã phiếu gốc
     *     note?,
     *     created_by?,        // mặc định Auth::id()
     * }
     * @return Stock  Dòng stock sau khi cập nhật
     * @throws \Exception  Khi đang đóng băng hoặc dữ liệu không hợp lệ
     */
    public function increase(array $params): Stock
    {
        $this->validateParams($params, requireQty: true);
        $this->checkFreeze($params['location_id'], $params['product_id']);

        return $this->executeWithLock($params, function () use ($params) {
            $stock = $this->findOrCreateStock($params);

            $stock->quantity += $params['quantity'];
            $stock->updated_at = now();
            $stock->save();

            $this->writeLedger($stock, $params, self::DIR_IN, $stock->quantity);

            return $stock->fresh();
        });
    }

    /**
     * Giảm tồn kho (hàng RA khỏi kho).
     *
     * Dùng cho: Xuất kho hoàn tất (ISSUE COMPLETED), Chuyển kho phía nguồn
     *           (TRANSFER OUT), Hủy hàng (SCRAP), Tách/Ghép phần Consume,
     *           Điều chỉnh giảm (ADJUST -)
     *
     * Lưu ý: Phương thức này KHÔNG tự giải phóng reserved_qty.
     *        Khi hoàn tất phiếu xuất, hãy gọi release() TRƯỚC, sau đó decrease().
     *        Hoặc dùng issueComplete() trong IssueService (đã bao gồm cả 2 bước).
     *
     * @throws \Exception  Khi không đủ available_qty
     */
    public function decrease(array $params): Stock
    {
        $this->validateParams($params, requireQty: true);
        $this->checkFreeze($params['location_id'], $params['product_id']);

        return $this->executeWithLock($params, function () use ($params) {
            $stock = $this->findStock($params);

            // Kiểm tra đủ available_qty (quantity - reserved_qty)
            $available = $stock->quantity - $stock->reserved_qty;
            if ($params['quantity'] > $available) {
                throw new \Exception(
                    "Không đủ tồn kho khả dụng. " .
                    "Yêu cầu: {$params['quantity']}, " .
                    "Khả dụng: {$available} " .
                    "(Tổng: {$stock->quantity}, Đang giữ: {$stock->reserved_qty})"
                );
            }

            $stock->quantity -= $params['quantity'];
            $stock->updated_at = now();
            $stock->save();

            $this->writeLedger($stock, $params, self::DIR_OUT, $stock->quantity);

            return $stock->fresh();
        });
    }

    /**
     * Giữ chỗ tồn kho (tăng reserved_qty).
     *
     * Dùng cho: Phiếu xuất kho được APPROVED — giữ chỗ hàng trước khi
     *           nhân viên ra lấy thực tế.
     *
     * Không ghi stock_ledger vì tổng tồn kho chưa thay đổi.
     * available_qty = quantity - reserved_qty (computed column, tự động giảm).
     *
     * @throws \Exception  Khi không đủ available_qty để reserve
     */
    public function reserve(array $params): Stock
    {
        $this->validateParams($params, requireQty: true);
        $this->checkFreeze($params['location_id'], $params['product_id']);

        return $this->executeWithLock($params, function () use ($params) {
            $stock = $this->findStock($params);

            $available = $stock->quantity - $stock->reserved_qty;
            if ($params['quantity'] > $available) {
                throw new \Exception(
                    "Không đủ hàng để giữ chỗ. " .
                    "Yêu cầu giữ: {$params['quantity']}, " .
                    "Khả dụng: {$available}"
                );
            }

            $stock->reserved_qty += $params['quantity'];
            $stock->updated_at = now();
            $stock->save();

            // Không ghi ledger — reserve chỉ là "đặt cọc", chưa xuất thực tế
            return $stock->fresh();
        });
    }

    /**
     * Giải phóng reserved_qty (khi phiếu xuất bị HỦY hoặc hoàn tất).
     *
     * Dùng cho:
     *   - Phiếu xuất bị CANCELLED → giải phóng toàn bộ reserved_qty đã giữ
     *   - Ngay trước khi gọi decrease() khi phiếu COMPLETED
     *
     * Không ghi stock_ledger.
     *
     * @throws \Exception  Khi released_qty > reserved_qty hiện tại
     */
    public function release(array $params): Stock
    {
        $this->validateParams($params, requireQty: true);

        return $this->executeWithLock($params, function () use ($params) {
            $stock = $this->findStock($params);

            if ($params['quantity'] > $stock->reserved_qty) {
                throw new \Exception(
                    "Không thể giải phóng {$params['quantity']} — " .
                    "reserved_qty hiện tại chỉ là {$stock->reserved_qty}"
                );
            }

            $stock->reserved_qty -= $params['quantity'];
            $stock->updated_at = now();
            $stock->save();

            return $stock->fresh();
        });
    }

    // =========================================================================
    // HELPER — Tìm / Tạo dòng stock
    // =========================================================================

    /**
     * Tìm dòng stock theo khóa tự nhiên (product + location + lot + serial).
     * Bắt buộc phải tồn tại — nếu không tìm thấy sẽ throw exception.
     *
     * @throws \Exception
     */
    public function findStock(array $params): Stock
    {
        $stock = Stock::where('product_id', $params['product_id'])
            ->where('location_id', $params['location_id'])
            ->where('lot_id', $params['lot_id'] ?? null)
            ->where('serial_id', $params['serial_id'] ?? null)
            ->lockForUpdate()   // UPDLOCK — tránh race condition
            ->first();

        if (! $stock) {
            throw new \Exception(
                "Không tìm thấy dòng tồn kho: " .
                "product_id={$params['product_id']}, " .
                "location_id={$params['location_id']}, " .
                "lot_id=" . ($params['lot_id'] ?? 'null') . ", " .
                "serial_id=" . ($params['serial_id'] ?? 'null')
            );
        }

        return $stock;
    }

    /**
     * Tìm hoặc tạo mới dòng stock (dùng khi nhập kho lần đầu).
     */
    public function findOrCreateStock(array $params): Stock
    {
        try {
            return Stock::firstOrCreate(
                [
                    'product_id'  => $params['product_id'],
                    'location_id' => $params['location_id'],
                    'lot_id'      => $params['lot_id'] ?? null,
                    'serial_id'   => $params['serial_id'] ?? null,
                ],
                [
                    'quantity'         => 0,
                    'reserved_qty'     => 0,
                    'supplier_id'      => $params['supplier_id'] ?? null,
                    'manufacture_date' => $params['manufacture_date'] ?? null,
                    'received_date'    => $params['received_date'] ?? now()->toDateString(),
                    'expiry_date'      => $params['expiry_date'] ?? null,
                    'status'           => self::STOCK_STATUS_NORMAL,
                    'updated_at'       => now(),
                ]
            );
        } catch (\Illuminate\Database\QueryException $e) {
            // Race condition: bản ghi vừa được tạo bởi request song song → đọc lại
            return Stock::where('product_id', $params['product_id'])
                ->where('location_id', $params['location_id'])
                ->where('lot_id', $params['lot_id'] ?? null)
                ->where('serial_id', $params['serial_id'] ?? null)
                ->lockForUpdate()
                ->firstOrFail();
        }
    }
    // =========================================================================
    // HELPER — Ghi stock_ledger
    // =========================================================================

    /**
     * Ghi một dòng vào bảng stock_ledger.
     * Được gọi tự động bởi increase() và decrease().
     */
    private function writeLedger(Stock $stock, array $params, int $direction, float $balanceAfter): StockLedger
    {
        return StockLedger::create([
            'product_id'       => $stock->product_id,
            'stock_id'         => $stock->id,
            'lot_id'           => $stock->lot_id,
            'serial_id'        => $stock->serial_id,
            'location_id'      => $stock->location_id,
            'transaction_type' => $params['transaction_type'],
            'reference_id'     => $params['reference_id'],
            'reference_type'   => $params['reference_type'],
            'reference_code'   => $params['reference_code'] ?? null,
            'direction'        => $direction,
            'quantity'         => $params['quantity'],   // luôn dương
            'balance_after'    => $balanceAfter,
            'transaction_date' => now(),
            'created_by'       => $params['created_by'] ?? Auth::id(),
            'note'             => $params['note'] ?? null,
        ]);
    }

    // =========================================================================
    // HELPER — Concurrency Control
    // =========================================================================

    /**
     * Chạy callback trong DB Transaction + Cache Lock.
     *
     * Cache Lock ngăn 2 request đồng thời trên cùng (product + location + lot + serial).
     * DB Transaction + lockForUpdate() trong findStock() đảm bảo tính nhất quán CSDL.
     *
     * @throws \Exception  Khi không thể lấy lock (đang có request khác xử lý)
     */
    private function executeWithLock(array $params, callable $callback): Stock
    {
        $lockKey = $this->buildLockKey($params);

        $lock = Cache::lock($lockKey, 5); // TTL 5 giây

        if (! $lock->get()) {
            throw new \Exception(
                "Hệ thống đang xử lý yêu cầu khác cho mặt hàng này. Vui lòng thử lại."
            );
        }

        try {
            return DB::transaction(function () use ($callback) {
                return $callback();
            });
        } finally {
            $lock->release();
        }
    }

    /**
     * Tạo Cache Lock key theo khóa tự nhiên của dòng stock.
     */
    private function buildLockKey(array $params): string
    {
        return sprintf(
            'stock_lock:p%d:l%d:lot%s:ser%s',
            $params['product_id'],
            $params['location_id'],
            $params['lot_id'] ?? '0',
            $params['serial_id'] ?? '0'
        );
    }

    // =========================================================================
    // HELPER — Kiểm tra đóng băng (Inventory Freeze)
    // =========================================================================

    /**
     * Kiểm tra xem location hoặc product có đang trong vùng đóng băng không.
     *
     * @throws \Exception  HTTP 403 — Khu vực/Mặt hàng đang trong trạng thái kiểm kê
     */
    public function checkFreeze(int $locationId, int $productId): void
    {
        // Lấy tất cả ancestor IDs của location hiện tại (bao gồm chính nó)
        $locationAndAncestorIds = $this->getLocationAndAncestorIds($locationId);

        $frozen = InventoryFreezeDetail::query()
            ->join('inventory_freezes', 'inventory_freeze_details.freeze_id', '=', 'inventory_freezes.id')
            ->whereNull('inventory_freezes.unfrozen_at')
            ->where(function ($q) use ($locationAndAncestorIds, $productId) {
                // Toàn kho bị freeze
                $q->where('inventory_freeze_details.freeze_scope', InventoryFreezeDetail::SCOPE_ALL)
                // Freeze theo location: khớp với location hiện tại HOẶC bất kỳ location cha nào
                ->orWhere(function ($q2) use ($locationAndAncestorIds) {
                    $q2->where('inventory_freeze_details.freeze_scope', InventoryFreezeDetail::SCOPE_LOCATION)
                        ->whereIn('inventory_freeze_details.location_id', $locationAndAncestorIds);
                })
                // Freeze theo product: không đổi
                ->orWhere(function ($q2) use ($productId) {
                    $q2->where('inventory_freeze_details.freeze_scope', InventoryFreezeDetail::SCOPE_PRODUCT)
                        ->where('inventory_freeze_details.product_id', $productId);
                });
            })
            ->exists();

        if ($frozen) {
            throw new \Exception(
                "Khu vực hoặc mặt hàng đang trong trạng thái kiểm kê. " .
                "Vui lòng chờ kiểm kê hoàn tất trước khi thực hiện giao dịch.",
                403
            );
        }
    }

    /**
     * Trả về mảng gồm $locationId và tất cả ancestor IDs của nó.
     * Dùng để kiểm tra freeze theo cây location.
     */
    private function getLocationAndAncestorIds(int $locationId): array
    {
        $ids = [$locationId];

        $location = Location::select('id', 'parent_id')->find($locationId);

        while ($location && $location->parent_id !== null) {
            $ids[] = $location->parent_id;
            $location = Location::select('id', 'parent_id')->find($location->parent_id);
        }

        return $ids;
    }

    // =========================================================================
    // HELPER — Validate params
    // =========================================================================

    /**
     * Kiểm tra các trường bắt buộc trong $params.
     *
     * @throws \InvalidArgumentException
     */
    private function validateParams(array $params, bool $requireQty = true): void
    {
        $required = ['product_id', 'location_id', 'transaction_type', 'reference_id', 'reference_type'];
        if ($requireQty) {
            $required[] = 'quantity';
        }

        foreach ($required as $field) {
            if (empty($params[$field]) && $params[$field] !== 0) {
                throw new \InvalidArgumentException("StockService: Thiếu trường bắt buộc [{$field}]");
            }
        }

        if ($requireQty && $params['quantity'] <= 0) {
            throw new \InvalidArgumentException("StockService: quantity phải lớn hơn 0");
        }

        if (! in_array($params['transaction_type'], [
            self::TYPE_RECEIPT, self::TYPE_ISSUE, self::TYPE_TRANSFER,
            self::TYPE_SCRAP,   self::TYPE_ADJUST, self::TYPE_SPLIT,
            self::TYPE_BUNDLE,  self::TYPE_RETURN,
        ])) {
            throw new \InvalidArgumentException(
                "StockService: transaction_type không hợp lệ [{$params['transaction_type']}]"
            );
        }
    }

    // =========================================================================
    // UTILITY — Tính toán tồn kho (dùng cho UI / Báo cáo)
    // =========================================================================

    /**
     * Lấy available_qty của một dòng stock cụ thể.
     * (Trường available_qty là computed column trên SQL Server, nhưng
     *  phương thức này dùng được kể cả khi model chưa map computed column.)
     */
    public function getAvailableQty(int $productId, int $locationId, ?int $lotId = null, ?int $serialId = null): float
    {
        $stock = Stock::where('product_id', $productId)
            ->where('location_id', $locationId)
            ->where('lot_id', $lotId)
            ->where('serial_id', $serialId)
            ->first();

        if (! $stock) {
            return 0.0;
        }

        return max(0, $stock->quantity - $stock->reserved_qty);
    }

    /**
     * Tổng tồn kho của một sản phẩm trên toàn kho (tất cả vị trí, tất cả lot/serial).
     */
    public function getTotalQty(int $productId): array
    {
        $row = Stock::where('product_id', $productId)
            ->selectRaw('
                SUM(quantity)     as total_qty,
                SUM(reserved_qty) as total_reserved,
                SUM(quantity - reserved_qty) as total_available
            ')
            ->first();

        return [
            'total_qty'       => (float) ($row->total_qty ?? 0),
            'total_reserved'  => (float) ($row->total_reserved ?? 0),
            'total_available' => (float) ($row->total_available ?? 0),
        ];
    }

    /**
     * Gợi ý lot/serial theo chiến lược FEFO hoặc FIFO (dùng khi tạo phiếu xuất).
     *
     * @param  string  $strategy  'FEFO' | 'FIFO'
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function suggestStockForIssue(int $productId, float $neededQty, string $strategy = 'FEFO'): \Illuminate\Support\Collection
    {
        $query = Stock::with(['lot', 'serial', 'location'])
            ->where('product_id', $productId)
            ->where('quantity', '>', 0)
            ->whereRaw('quantity - reserved_qty > 0')
            ->whereHas('location', fn ($q) => $q->where('type', 1)); // Internal locations only

        // Sắp xếp theo chiến lược
        $query = match ($strategy) {
            'FEFO' => $query->orderByRaw("
                CASE
                    WHEN lot_id IS NOT NULL THEN (SELECT expiry_date FROM lots WHERE id = lot_id)
                    WHEN serial_id IS NOT NULL THEN (SELECT expiry_date FROM serials WHERE id = serial_id)
                    ELSE expiry_date
                END ASC,
                CASE
                    WHEN (
                        CASE
                            WHEN lot_id IS NOT NULL THEN (SELECT expiry_date FROM lots WHERE id = lot_id)
                            WHEN serial_id IS NOT NULL THEN (SELECT expiry_date FROM serials WHERE id = serial_id)
                            ELSE expiry_date
                        END
                    ) IS NULL THEN 1 ELSE 0
                END ASC
            "),
            'FIFO' => $query->orderBy('received_date', 'asc'),
            default => $query,
        };

        $suggestions = collect();
        $remaining = $neededQty;

        foreach ($query->get() as $stock) {
            if ($remaining <= 0) break;

            $available = $stock->quantity - $stock->reserved_qty;
            $take = min($available, $remaining);

            $suggestions->push([
                'stock_id'    => $stock->id,
                'location_id' => $stock->location_id,
                'lot_id'      => $stock->lot_id,
                'serial_id'   => $stock->serial_id,
                'qty_suggest' => $take,
                'expiry_date' => $stock->expiry_date
                    ?? ($stock->lot?->expiry_date)
                    ?? ($stock->serial?->expiry_date),
            ]);

            $remaining -= $take;
        }

        if ($remaining > 0) {
            throw new \Exception(
                "Không đủ tồn kho. Còn thiếu: {$remaining}"
            );
        }

        return $suggestions;
    }

    /**
     * Gợi ý vị trí lưu trữ theo putaway_rule khi nhập kho.
     *
     * @return int|null  location_id được gợi ý, null nếu không có rule
     */
    public function suggestPutawayLocation(int $productId, int $categoryId): ?int
    {
        $rule = DB::table('putaway_rules')
            ->where('status', 1)
            ->where(function ($q) use ($productId, $categoryId) {
                $q->where('product_id', $productId)
                ->orWhere(function ($q2) use ($categoryId) {
                    $q2->whereNull('product_id')
                        ->where('category_id', $categoryId);
                });
            })
            ->orderByRaw("CASE WHEN product_id = ? THEN 0 ELSE 1 END ASC", [$productId])
            ->orderBy('priority', 'asc')
            ->first();

        return $rule?->location_dest_id;
    }
}
