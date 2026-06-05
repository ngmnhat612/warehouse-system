<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Lot;
use App\Models\Product;
use App\Models\Stock;
use App\Services\StockService;
use App\Models\StockLedger;
use App\Models\StockTransfer;
use App\Models\StockTransferDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class StockTransferController extends Controller
{
    public function __construct(private StockService $stockService) {}

    // ──────────────────────────────────────────────────────────────────────────
    // DANH SÁCH
    // ──────────────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = StockTransfer::with(['createdBy', 'confirmedBy'])
            ->withCount('details');

        if ($search = $request->search) {
            $query->where('code', 'like', "%{$search}%");
        }

        if ($request->transfer_type) {
            $query->where('transfer_type', $request->transfer_type);
        }

        if ($request->status !== null && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($request->date_from) {
            $query->where('transfer_date', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->where('transfer_date', '<=', $request->date_to);
        }

        $transfers      = $query->orderByDesc('created_at')->paginate(20)->withQueryString();
        $totalCount     = StockTransfer::count();
        $pendingCount   = StockTransfer::where('status', StockTransfer::STATUS_PENDING)->count();
        $completedCount = StockTransfer::where('status', StockTransfer::STATUS_COMPLETED)->count();
        $cancelledCount = StockTransfer::where('status', StockTransfer::STATUS_CANCELLED)->count();

        return view('transfers.index', compact(
            'transfers', 'totalCount', 'pendingCount', 'completedCount', 'cancelledCount'
        ));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // FORM TẠO MỚI
    // ──────────────────────────────────────────────────────────────────────────

    public function create()
    {
        $products  = Product::with('uom')->where('status', 1)->orderBy('code')->get();
        $locations = Location::where('status', 1)->orderBy('code')->get();

        $lots = Lot::where('status', Lot::STATUS_ACTIVE)
            ->select('id', 'product_id', 'lot_number', 'expiry_date')
            ->orderBy('lot_number')
            ->get()
            ->groupBy('product_id');

        $productsJson  = $products->map(fn($p) => [
            'id'     => $p->id,
            'code'   => $p->code,
            'name'   => $p->name,
            'uom'    => $p->uom?->name ?? '—',
            'uom_id' => $p->uom_id,
        ])->values();

        $locationsJson = $locations->map(fn($l) => [
            'id'   => $l->id,
            'code' => $l->code,
            'name' => $l->name ?? '',
        ])->values();

        $lotsJson = $lots->map(fn($g) => $g->values());

        return view('transfers.form', compact(
            'products', 'locations', 'lots',
            'productsJson', 'locationsJson', 'lotsJson'
        ));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // LƯU PHIẾU MỚI
    // ──────────────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $this->validateTransfer($request);

        $warnings = $this->checkStockSufficiencyFromRequest($request->input('details', []));

        DB::transaction(function () use ($request) {
            $code = $request->code
                ? strtoupper(trim($request->code))
                : $this->generateCode();

            $transfer = StockTransfer::create([
                'code'          => $code,
                'transfer_type' => $request->transfer_type,
                'transfer_date' => $request->transfer_date,
                'status'        => StockTransfer::STATUS_DRAFT,
                'note'          => $request->note ?: null,
                'created_by'    => Auth::id(),
            ]);

            $this->saveDetails($transfer, $request->details ?? []);
        });

        $action = $request->input('action');
        $route  = $action === 'save_and_new'
            ? redirect()->route('transfers.create')
            : redirect()->route('transfers.index');

        if (!empty($warnings)) {
            $route->with('warning', 'Phiếu đã được tạo nhưng một số mặt hàng không đủ tồn kho tại vị trí nguồn: ' . implode('; ', $warnings));
        } else {
            $route->with('success', 'Đã tạo phiếu chuyển kho thành công.');
        }

        return $route;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // XEM CHI TIẾT
    // ──────────────────────────────────────────────────────────────────────────

    public function show(StockTransfer $transfer)
    {
        $transfer->load([
            'createdBy',
            'confirmedBy',
            'details.product.uom',
            'details.fromLocation',
            'details.toLocation',
            'details.lot',
            'details.uom',
        ]);

        return view('transfers.show', compact('transfer'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // IN PHIẾU CHUYỂN KHO (PDF / Browser Print)
    // ──────────────────────────────────────────────────────────────────────────
    public function printPdf(StockTransfer $transfer)
    {
        $transfer->load([
            'createdBy',
            'confirmedBy',
            'details.product.uom',
            'details.fromLocation',
            'details.toLocation',
            'details.lot',
            'details.uom',
        ]);

        return view('transfers.print', compact('transfer'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // FORM CHỈNH SỬA
    // ──────────────────────────────────────────────────────────────────────────

    public function edit(StockTransfer $transfer)
    {
        if ((int) $transfer->status !== StockTransfer::STATUS_DRAFT) {
            return redirect()->route('transfers.show', $transfer)
                ->with('error', 'Chỉ có thể chỉnh sửa phiếu ở trạng thái Nháp.');
        }

        $transfer->load(['details.product', 'details.fromLocation', 'details.toLocation', 'details.lot', 'details.uom']);

        $products  = Product::with('uom')->where('status', 1)->orderBy('code')->get();
        $locations = Location::where('status', 1)->orderBy('code')->get();

        $lots = Lot::where('status', Lot::STATUS_ACTIVE)
            ->select('id', 'product_id', 'lot_number', 'expiry_date')
            ->orderBy('lot_number')
            ->get()
            ->groupBy('product_id');

        $productsJson  = $products->map(fn($p) => [
            'id'     => $p->id,
            'code'   => $p->code,
            'name'   => $p->name,
            'uom'    => $p->uom?->name ?? '—',
            'uom_id' => $p->uom_id,
        ])->values();

        $locationsJson = $locations->map(fn($l) => [
            'id'   => $l->id,
            'code' => $l->code,
            'name' => $l->name ?? '',
        ])->values();

        $lotsJson = $lots->map(fn($g) => $g->values());

        return view('transfers.form', compact(
            'transfer', 'products', 'locations', 'lots',
            'productsJson', 'locationsJson', 'lotsJson'
        ));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // CẬP NHẬT PHIẾU
    // ──────────────────────────────────────────────────────────────────────────

    public function update(Request $request, StockTransfer $transfer)
    {
        if ((int) $transfer->status !== StockTransfer::STATUS_DRAFT) {
            return redirect()->route('transfers.show', $transfer)
                ->with('error', 'Chỉ có thể chỉnh sửa phiếu ở trạng thái Nháp.');
        }

        $this->validateTransfer($request, isUpdate: true);

        $warnings = $this->checkStockSufficiencyFromRequest($request->input('details', []));

        DB::transaction(function () use ($request, $transfer) {
            $transfer->update([
                'transfer_type' => $request->transfer_type,
                'transfer_date' => $request->transfer_date,
                'note'          => $request->note ?: null,
            ]);

            $transfer->details()->delete();
            $this->saveDetails($transfer, $request->details ?? []);
        });

        $redirect = redirect()->route('transfers.show', $transfer);

        if (!empty($warnings)) {
            return $redirect->with('warning', 'Phiếu đã được cập nhật nhưng một số mặt hàng không đủ tồn kho tại vị trí nguồn: ' . implode('; ', $warnings));
        }

        return $redirect->with('success', "Đã cập nhật phiếu {$transfer->code} thành công.");
    }

    // ──────────────────────────────────────────────────────────────────────────
    // XÓA PHIẾU (chỉ Draft)
    // ──────────────────────────────────────────────────────────────────────────

    public function destroy(StockTransfer $transfer)
    {
        if ((int) $transfer->status !== StockTransfer::STATUS_DRAFT) {
            return redirect()->route('transfers.index')
                ->with('error', "Không thể xóa phiếu {$transfer->code} vì không ở trạng thái Nháp.");
        }

        $code = $transfer->code;

        DB::transaction(function () use ($transfer) {
            $transfer->details()->delete();
            $transfer->delete();
        });

        return redirect()->route('transfers.index')
            ->with('success', "Đã xóa phiếu {$code} thành công.");
    }

    // ──────────────────────────────────────────────────────────────────
    // PENDING → APPROVED (Duyệt phiếu)
    // ──────────────────────────────────────────────────────────────────
    public function approve(StockTransfer $transfer)
    {
        Gate::authorize('transfer.approve');

        if ((int) $transfer->status !== StockTransfer::STATUS_PENDING) {
            return redirect()->route('transfers.show', $transfer)
                ->with('error', 'Chỉ có thể duyệt phiếu đang ở trạng thái Chờ duyệt.');
        }

        $transfer->update([
            'status'      => StockTransfer::STATUS_APPROVED,
            'approved_by' => Auth::id(),
        ]);

        return redirect()->route('transfers.show', $transfer)
            ->with('success', "Phiếu {$transfer->code} đã được duyệt. Tiến hành xác nhận để cập nhật tồn kho.");
    }

    // ──────────────────────────────────────────────────────────────────
    // APPROVED → COMPLETED (Xác nhận & cập nhật tồn kho)
    // ──────────────────────────────────────────────────────────────────
    public function confirm(StockTransfer $transfer)
    {
        // Không cần Gate::authorize — staff cũng xác nhận được
        if ((int) $transfer->status !== StockTransfer::STATUS_APPROVED) {
            return redirect()->route('transfers.show', $transfer)
                ->with('error', 'Chỉ có thể xác nhận phiếu đã ở trạng thái Đã duyệt.');
        }

        $transfer->load('details.product');

        $errors = $this->checkStockSufficiency($transfer);
        if (!empty($errors)) {
            return redirect()->route('transfers.show', $transfer)
                ->with('error', 'Không đủ tồn kho tại vị trí nguồn: ' . implode('; ', $errors));
        }

        try {
            DB::transaction(function () use ($transfer) {
                foreach ($transfer->details as $detail) {
                    $qty = $detail->quantity;
                    if ($qty <= 0) continue;

                    $baseParams = [
                        'product_id'       => $detail->product_id,
                        'lot_id'           => $detail->lot_id,
                        'serial_id'        => $detail->serial_id ?? null,
                        'transaction_type' => StockService::TYPE_TRANSFER,
                        'reference_id'     => $transfer->id,
                        'reference_type'   => 'stock_transfer',
                        'reference_code'   => $transfer->code,
                        'note'             => "Chuyển kho phiếu {$transfer->code}",
                        'created_by'       => Auth::id(),
                    ];

                    $this->stockService->decrease(array_merge($baseParams, [
                        'location_id' => $detail->from_location_id,
                        'quantity'    => $qty,
                    ]));

                    $this->stockService->increase(array_merge($baseParams, [
                        'location_id'   => $detail->to_location_id,
                        'quantity'      => $qty,
                        'received_date' => $transfer->transfer_date,
                    ]));
                }

                $transfer->update([
                    'status'       => StockTransfer::STATUS_COMPLETED,
                    'confirmed_by' => Auth::id(),
                ]);
            });
        } catch (\Exception $e) {
            return redirect()->route('transfers.show', $transfer)
                ->with('error', 'Lỗi khi xác nhận chuyển kho: ' . $e->getMessage());
        }

        return redirect()->route('transfers.show', $transfer)
            ->with('success', "Phiếu {$transfer->code} đã hoàn tất. Tồn kho đã được cập nhật.");
    }

    // ──────────────────────────────────────────────────────────────────────────
    // HỦY PHIẾU
    // ──────────────────────────────────────────────────────────────────────────

    public function cancel(StockTransfer $transfer)
    {
        if ((int) $transfer->status === StockTransfer::STATUS_COMPLETED) {
            return redirect()->route('transfers.show', $transfer)
                ->with('error', 'Không thể hủy phiếu đã hoàn thành.');
        }

        if ((int) $transfer->status === StockTransfer::STATUS_CANCELLED) {
            return redirect()->route('transfers.show', $transfer)
                ->with('error', 'Phiếu đã được hủy trước đó.');
        }

        $transfer->update(['status' => StockTransfer::STATUS_CANCELLED]);

        return redirect()->route('transfers.show', $transfer)
            ->with('success', "Đã hủy phiếu {$transfer->code}.");
    }

    /**
     * AJAX: Trả về danh sách vị trí có tồn kho khả dụng cho một sản phẩm.
     */
    public function stockLocations(Request $request)
    {
        $request->validate(['product_id' => 'required|integer|exists:products,id']);

        $locations = Stock::with('location')
            ->where('product_id', $request->product_id)
            ->whereHas('location', fn($q) => $q->where('type', 1)->where('status', 1))
            ->whereRaw('quantity - reserved_qty > 0')
            ->get()
            ->map(fn($s) => [
                'location_id'   => $s->location_id,
                'code'          => $s->location?->code,
                'name'          => $s->location?->name ?? '',
                'lot_id'        => $s->lot_id,
                'available_qty' => round($s->quantity - $s->reserved_qty, 3),
            ]);

        return response()->json($locations);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ──────────────────────────────────────────────────────────────────────────

    private function validateTransfer(Request $request, bool $isUpdate = false): void
    {
        $codeRule = $isUpdate
            ? 'nullable|string|max:50'
            : 'nullable|string|max:50|unique:stock_transfers,code';

        $request->validate([
            'code'                          => $codeRule,
            'transfer_type'                 => 'required|in:1,2,3',
            'transfer_date'                 => 'required|date',
            'note'                          => 'nullable|string|max:1000',
            'details'                       => 'required|array|min:1',
            'details.*.product_id'          => 'required|exists:products,id',
            'details.*.uom_id'              => 'required|exists:uoms,id',
            'details.*.quantity'            => 'required|numeric|min:0.001',
            'details.*.from_location_id'    => 'required|exists:locations,id',
            'details.*.to_location_id'      => 'required|exists:locations,id|different:details.*.from_location_id',
            'details.*.lot_id'              => 'nullable|exists:lots,id',
            'details.*.note'                => 'nullable|string|max:200',
        ], [
            'code.unique'                           => 'Mã phiếu đã tồn tại.',
            'transfer_type.required'                => 'Vui lòng chọn loại chuyển kho.',
            'transfer_date.required'                => 'Vui lòng chọn ngày chuyển.',
            'details.required'                      => 'Phiếu chuyển kho phải có ít nhất một hàng hóa.',
            'details.min'                           => 'Phiếu chuyển kho phải có ít nhất một hàng hóa.',
            'details.*.product_id.required'         => 'Vui lòng chọn hàng hóa.',
            'details.*.uom_id.required'             => 'Vui lòng chọn đơn vị tính.',
            'details.*.quantity.required'           => 'Vui lòng nhập số lượng.',
            'details.*.quantity.min'                => 'Số lượng phải lớn hơn 0.',
            'details.*.from_location_id.required'   => 'Vui lòng chọn vị trí nguồn.',
            'details.*.to_location_id.required'     => 'Vui lòng chọn vị trí đích.',
            'details.*.to_location_id.different'    => 'Vị trí đích phải khác vị trí nguồn.',
        ]);
    }

    private function saveDetails(StockTransfer $transfer, array $details): void
    {
        foreach ($details as $row) {
            if (empty($row['product_id']) || empty($row['quantity'])) {
                continue;
            }

            StockTransferDetail::create([
                'stock_transfer_id' => $transfer->id,
                'product_id'        => $row['product_id'],
                'uom_id'            => $row['uom_id'],
                'lot_id'            => $row['lot_id'] ?: null,
                'serial_id'         => null,
                'from_location_id'  => $row['from_location_id'],
                'to_location_id'    => $row['to_location_id'],
                'quantity'          => $row['quantity'],
                'note'              => $row['note'] ?: null,
            ]);
        }
    }

    /**
     * Kiểm tra tồn kho tại vị trí nguồn trước khi xác nhận.
     */
    private function checkStockSufficiency(StockTransfer $transfer): array
    {
        $errors = [];

        // Gom nhóm SL theo (product_id, from_location_id, lot_id)
        // Dùng array key phức hợp để tránh lỗi explode khi lot_id = null
        $needed = [];
        foreach ($transfer->details as $detail) {
            $key = $detail->product_id . '|' . $detail->from_location_id . '|' . ($detail->lot_id ?? '');
            $needed[$key] = ($needed[$key] ?? 0) + (float) $detail->quantity;
        }

        foreach ($needed as $key => $qty) {
            [$productId, $locationId, $lotId] = explode('|', $key);
            $lotId = $lotId !== '' ? (int) $lotId : null;

            // Dùng getAvailableQty() của StockService — đã được kiểm chứng đúng
            $available = $this->stockService->getAvailableQty(
                (int) $productId,
                (int) $locationId,
                $lotId
            );

            if ($available < $qty) {
                $product  = Product::find($productId);
                $location = Location::find($locationId);
                $errors[] = sprintf(
                    '%s tại %s: cần %.3f, khả dụng %.3f',
                    $product?->name ?? "ID {$productId}",
                    $location?->code ?? "Loc {$locationId}",
                    $qty,
                    $available
                );
            }
        }

        return $errors;
    }

    /**
     * Kiểm tra tồn kho từ raw request details (dùng cho store/update — phiếu chưa lưu).
     * Trả về mảng warning string, không throw exception.
     */
    private function checkStockSufficiencyFromRequest(array $details): array
    {
        $warnings = [];

        // Gom nhóm SL theo (product_id, from_location_id, lot_id)
        $needed = [];
        foreach ($details as $row) {
            if (empty($row['product_id']) || empty($row['quantity']) || empty($row['from_location_id'])) {
                continue;
            }
            $key = $row['product_id'] . '|' . $row['from_location_id'] . '|' . ($row['lot_id'] ?? '');
            $needed[$key] = ($needed[$key] ?? 0) + (float) $row['quantity'];
        }

        foreach ($needed as $key => $qty) {
            [$productId, $locationId, $lotId] = explode('|', $key);
            $lotId = $lotId !== '' ? (int) $lotId : null;

            $available = $this->stockService->getAvailableQty(
                (int) $productId,
                (int) $locationId,
                $lotId
            );

            if ($available < $qty) {
                $product  = Product::find($productId);
                $location = Location::find($locationId);
                $warnings[] = sprintf(
                    '%s tại %s: cần %.3f, khả dụng %.3f',
                    $product?->name ?? "ID {$productId}",
                    $location?->code ?? "Loc {$locationId}",
                    $qty,
                    $available
                );
            }
        }

        return $warnings;
    }

    /**
     * Sinh mã phiếu theo format CK-YYYYMM-XXXX.
     */
    private function generateCode(): string
    {
        $prefix = 'CK-' . now()->format('Ym') . '-';
        $last   = StockTransfer::where('code', 'like', $prefix . '%')
                      ->orderByDesc('code')
                      ->value('code');

        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    // ──────────────────────────────────────────────────────────────────
    // DRAFT → PENDING (Gửi duyệt)
    // ──────────────────────────────────────────────────────────────────
    public function submit(StockTransfer $transfer)
    {
        if ((int) $transfer->status !== StockTransfer::STATUS_DRAFT) {
            return redirect()->route('transfers.show', $transfer)
                ->with('error', 'Chỉ có thể gửi duyệt phiếu đang ở trạng thái Nháp.');
        }

        if ($transfer->details()->count() === 0) {
            return redirect()->route('transfers.show', $transfer)
                ->with('error', 'Phiếu chưa có hàng hóa. Vui lòng thêm ít nhất một dòng.');
        }

        $transfer->update(['status' => StockTransfer::STATUS_PENDING]);

        return redirect()->route('transfers.show', $transfer)
            ->with('success', "Phiếu {$transfer->code} đã được gửi duyệt.");
    }
}
