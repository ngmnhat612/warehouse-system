<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Lot;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockLedger;
use App\Models\StockTransfer;
use App\Models\StockTransferDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockTransferController extends Controller
{
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

        return view('transfers.form', compact('products', 'locations', 'lots'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // LƯU PHIẾU MỚI
    // ──────────────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $this->validateTransfer($request);

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

        return $action === 'save_and_new'
            ? redirect()->route('transfers.create')->with('success', 'Đã tạo phiếu chuyển kho thành công.')
            : redirect()->route('transfers.index')->with('success', 'Đã tạo phiếu chuyển kho thành công.');
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
    // FORM CHỈNH SỬA
    // ──────────────────────────────────────────────────────────────────────────

    public function edit(StockTransfer $transfer)
    {
        if ($transfer->status !== StockTransfer::STATUS_DRAFT) {
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

        return view('transfers.form', compact('transfer', 'products', 'locations', 'lots'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // CẬP NHẬT PHIẾU
    // ──────────────────────────────────────────────────────────────────────────

    public function update(Request $request, StockTransfer $transfer)
    {
        if ($transfer->status !== StockTransfer::STATUS_DRAFT) {
            return redirect()->route('transfers.show', $transfer)
                ->with('error', 'Chỉ có thể chỉnh sửa phiếu ở trạng thái Nháp.');
        }

        $this->validateTransfer($request, isUpdate: true);

        DB::transaction(function () use ($request, $transfer) {
            $transfer->update([
                'transfer_type' => $request->transfer_type,
                'transfer_date' => $request->transfer_date,
                'note'          => $request->note ?: null,
            ]);

            $transfer->details()->delete();
            $this->saveDetails($transfer, $request->details ?? []);
        });

        return redirect()->route('transfers.show', $transfer)
            ->with('success', "Đã cập nhật phiếu {$transfer->code} thành công.");
    }

    // ──────────────────────────────────────────────────────────────────────────
    // XÓA PHIẾU (chỉ Draft)
    // ──────────────────────────────────────────────────────────────────────────

    public function destroy(StockTransfer $transfer)
    {
        if ($transfer->status !== StockTransfer::STATUS_DRAFT) {
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

    // ──────────────────────────────────────────────────────────────────────────
    // XÁC NHẬN PHIẾU → Completed & cập nhật tồn kho
    // ──────────────────────────────────────────────────────────────────────────

    public function confirm(StockTransfer $transfer)
    {
        if (!in_array($transfer->status, [StockTransfer::STATUS_DRAFT, StockTransfer::STATUS_PENDING])) {
            return redirect()->route('transfers.show', $transfer)
                ->with('error', 'Phiếu không ở trạng thái có thể xác nhận.');
        }

        $transfer->load('details.product');

        // Kiểm tra tồn kho tại vị trí nguồn
        $errors = $this->checkStockSufficiency($transfer);
        if (!empty($errors)) {
            return redirect()->route('transfers.show', $transfer)
                ->with('error', 'Không đủ tồn kho tại vị trí nguồn: ' . implode('; ', $errors));
        }

        DB::transaction(function () use ($transfer) {
            foreach ($transfer->details as $detail) {
                $qty = $detail->quantity;
                if ($qty <= 0) {
                    continue;
                }

                // ── Tìm stock tại vị trí NGUỒN ──
                $fromStock = Stock::where('product_id', $detail->product_id)
                    ->where('location_id', $detail->from_location_id)
                    ->when($detail->lot_id, fn($q) => $q->where('lot_id', $detail->lot_id))
                    ->when(!$detail->lot_id, fn($q) => $q->whereNull('lot_id'))
                    ->first();

                if (!$fromStock) {
                    throw new \Exception("Không tìm thấy tồn kho cho sản phẩm ID {$detail->product_id} tại vị trí nguồn.");
                }

                // Trừ tồn kho nguồn
                $fromStock->quantity -= $qty;
                $fromStock->updated_at = now();
                $fromStock->save();

                // ── Cập nhật stock tại vị trí ĐÍCH ──
                $toStock = Stock::where('product_id', $detail->product_id)
                    ->where('location_id', $detail->to_location_id)
                    ->when($detail->lot_id, fn($q) => $q->where('lot_id', $detail->lot_id))
                    ->when(!$detail->lot_id, fn($q) => $q->whereNull('lot_id'))
                    ->first();

                if ($toStock) {
                    $toStock->quantity += $qty;
                    $toStock->updated_at = now();
                    $toStock->save();
                } else {
                    // Tạo dòng stock mới tại vị trí đích
                    $toStock = Stock::create([
                        'product_id'     => $detail->product_id,
                        'location_id'    => $detail->to_location_id,
                        'lot_id'         => $detail->lot_id,
                        'serial_id'      => $detail->serial_id,
                        'quantity'       => $qty,
                        'reserved_qty'   => 0,
                        'supplier_id'    => $fromStock->supplier_id,
                        'manufacture_date' => $fromStock->manufacture_date,
                        'received_date'  => $fromStock->received_date,
                        'expiry_date'    => $fromStock->expiry_date,
                        'status'         => $fromStock->status,
                        'updated_at'     => now(),
                    ]);
                }

                // ── Ghi stock_ledger: OUT tại nguồn ──
                StockLedger::create([
                    'product_id'       => $detail->product_id,
                    'stock_id'         => $fromStock->id,
                    'lot_id'           => $detail->lot_id,
                    'serial_id'        => $detail->serial_id ?? null,
                    'location_id'      => $detail->from_location_id,
                    'transaction_type' => 'TRANSFER',
                    'reference_id'     => $transfer->id,
                    'reference_type'   => 'stock_transfer',
                    'reference_code'   => $transfer->code,
                    'direction'        => 2, // OUT
                    'quantity'         => $qty,
                    'balance_after'    => $fromStock->quantity,
                    'created_by'       => Auth::id(),
                    'note'             => "Chuyển kho từ {$detail->fromLocation?->code} - phiếu {$transfer->code}",
                    'transaction_date' => now(),
                ]);

                // ── Ghi stock_ledger: IN tại đích ──
                StockLedger::create([
                    'product_id'       => $detail->product_id,
                    'stock_id'         => $toStock->id,
                    'lot_id'           => $detail->lot_id,
                    'serial_id'        => $detail->serial_id ?? null,
                    'location_id'      => $detail->to_location_id,
                    'transaction_type' => 'TRANSFER',
                    'reference_id'     => $transfer->id,
                    'reference_type'   => 'stock_transfer',
                    'reference_code'   => $transfer->code,
                    'direction'        => 1, // IN
                    'quantity'         => $qty,
                    'balance_after'    => $toStock->quantity,
                    'created_by'       => Auth::id(),
                    'note'             => "Chuyển kho đến {$detail->toLocation?->code} - phiếu {$transfer->code}",
                    'transaction_date' => now(),
                ]);
            }

            $transfer->update([
                'status'       => StockTransfer::STATUS_COMPLETED,
                'confirmed_by' => Auth::id(),
            ]);
        });

        return redirect()->route('transfers.show', $transfer)
            ->with('success', "Phiếu {$transfer->code} đã được xác nhận và cập nhật tồn kho.");
    }

    // ──────────────────────────────────────────────────────────────────────────
    // HỦY PHIẾU
    // ──────────────────────────────────────────────────────────────────────────

    public function cancel(StockTransfer $transfer)
    {
        if ($transfer->status === StockTransfer::STATUS_COMPLETED) {
            return redirect()->route('transfers.show', $transfer)
                ->with('error', 'Không thể hủy phiếu đã hoàn thành. Vui lòng tạo phiếu chuyển ngược lại.');
        }

        if ($transfer->status === StockTransfer::STATUS_CANCELLED) {
            return redirect()->route('transfers.show', $transfer)
                ->with('error', 'Phiếu đã được hủy trước đó.');
        }

        $transfer->update(['status' => StockTransfer::STATUS_CANCELLED]);

        return redirect()->route('transfers.show', $transfer)
            ->with('success', "Đã hủy phiếu {$transfer->code}.");
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

        // Gom nhóm SL theo product_id + from_location_id + lot_id
        $needed = [];
        foreach ($transfer->details as $detail) {
            $key = "{$detail->product_id}_{$detail->from_location_id}_{$detail->lot_id}";
            $needed[$key] = ($needed[$key] ?? 0) + $detail->quantity;
        }

        foreach ($needed as $key => $qty) {
            [$productId, $locationId, $lotId] = explode('_', $key);

            $available = Stock::where('product_id', $productId)
                ->where('location_id', $locationId)
                ->when($lotId, fn($q) => $q->where('lot_id', $lotId))
                ->when(!$lotId, fn($q) => $q->whereNull('lot_id'))
                ->sum('quantity');

            if ($available < $qty) {
                $product  = Product::find($productId);
                $location = Location::find($locationId);
                $errors[] = sprintf(
                    '%s tại %s: cần %.3f, tồn kho %.3f',
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
}