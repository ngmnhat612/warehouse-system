<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Lot;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockLedger;
use App\Models\StockReceipt;
use App\Models\StockReceiptDetail;
use App\Models\Supplier;
use App\Models\Uom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockReceiptController extends Controller
{
    // ──────────────────────────────────────────────────────────────────────────
    // DANH SÁCH
    // ──────────────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = StockReceipt::with(['supplier', 'creator'])
            ->withCount('details');

        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('reference_no', 'like', "%{$search}%");
            });
        }

        if ($request->receipt_type) {
            $query->where('receipt_type', $request->receipt_type);
        }

        if ($request->status !== null && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($request->date_from) {
            $query->where('receipt_date', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->where('receipt_date', '<=', $request->date_to);
        }

        $receipts       = $query->orderByDesc('created_at')->paginate(20)->withQueryString();
        $totalCount     = StockReceipt::count();
        $pendingCount   = StockReceipt::where('status', 2)->count();
        $completedCount = StockReceipt::where('status', 4)->count();
        $cancelledCount = StockReceipt::where('status', 5)->count();

        return view('receipts.index', compact(
            'receipts', 'totalCount', 'pendingCount', 'completedCount', 'cancelledCount'
        ));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // FORM TẠO MỚI
    // ──────────────────────────────────────────────────────────────────────────

    public function create()
    {
        $products  = Product::with('uom')->where('status', 1)->orderBy('code')->get();
        $suppliers = Supplier::orderBy('name')->get();
        $locations = Location::orderBy('code')->get();
        $uoms      = Uom::orderBy('name')->get();

        return view('receipts.form', compact('products', 'suppliers', 'locations', 'uoms'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // LƯU PHIẾU MỚI
    // ──────────────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $this->validateReceipt($request);

        DB::transaction(function () use ($request) {
            // Sinh mã tự động nếu để trống
            $code = $request->code
                ? strtoupper(trim($request->code))
                : $this->generateCode();

            $receipt = StockReceipt::create([
                'code'         => $code,
                'receipt_type' => $request->receipt_type,
                'supplier_id'  => $request->supplier_id ?: null,
                'reference_no' => $request->reference_no ?: null,
                'receipt_date' => $request->receipt_date,
                'status'       => 1, // Draft
                'note'         => $request->note ?: null,
                'created_by'   => Auth::id(),
            ]);

            $this->saveDetails($receipt, $request->details ?? []);
        });

        $action = $request->input('action');

        return $action === 'save_and_new'
            ? redirect()->route('receipts.create')->with('success', 'Đã tạo phiếu nhập thành công.')
            : redirect()->route('receipts.index')->with('success', 'Đã tạo phiếu nhập thành công.');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // XEM CHI TIẾT
    // ──────────────────────────────────────────────────────────────────────────

    public function show(StockReceipt $receipt)
    {
        $receipt->load([
            'supplier',
            'creator',
            'confirmer',
            'details.product.uom',
            'details.location',
            'details.lot',
            'details.uom',
        ]);

        return view('receipts.show', compact('receipt'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // FORM CHỈNH SỬA
    // ──────────────────────────────────────────────────────────────────────────

    public function edit(StockReceipt $receipt)
    {
        // Chỉ cho sửa khi còn Draft
        if ($receipt->status !== 1) {
            return redirect()->route('receipts.show', $receipt)
                ->with('error', 'Chỉ có thể chỉnh sửa phiếu ở trạng thái Draft.');
        }

        $receipt->load(['details.product', 'details.location', 'details.lot', 'details.uom']);
        $products  = Product::with('uom')->where('status', 1)->orderBy('code')->get();
        $suppliers = Supplier::orderBy('name')->get();
        $locations = Location::orderBy('code')->get();
        $uoms      = Uom::orderBy('name')->get();

        return view('receipts.form', compact('receipt', 'products', 'suppliers', 'locations', 'uoms'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // CẬP NHẬT PHIẾU
    // ──────────────────────────────────────────────────────────────────────────

    public function update(Request $request, StockReceipt $receipt)
    {
        if ($receipt->status !== 1) {
            return redirect()->route('receipts.show', $receipt)
                ->with('error', 'Chỉ có thể chỉnh sửa phiếu ở trạng thái Draft.');
        }

        $this->validateReceipt($request, isUpdate: true);

        DB::transaction(function () use ($request, $receipt) {
            $receipt->update([
                'receipt_type' => $request->receipt_type,
                'supplier_id'  => $request->supplier_id ?: null,
                'reference_no' => $request->reference_no ?: null,
                'receipt_date' => $request->receipt_date,
                'note'         => $request->note ?: null,
            ]);

            // Xóa chi tiết cũ và ghi lại
            $receipt->details()->delete();
            $this->saveDetails($receipt, $request->details ?? []);
        });

        return redirect()->route('receipts.show', $receipt)
            ->with('success', "Đã cập nhật phiếu {$receipt->code} thành công.");
    }

    // ──────────────────────────────────────────────────────────────────────────
    // XÓA PHIẾU (chỉ Draft)
    // ──────────────────────────────────────────────────────────────────────────

    public function destroy(StockReceipt $receipt)
    {
        if ($receipt->status !== 1) {
            return redirect()->route('receipts.index')
                ->with('error', "Không thể xóa phiếu {$receipt->code} vì không ở trạng thái Draft.");
        }

        $code = $receipt->code;

        DB::transaction(function () use ($receipt) {
            $receipt->details()->delete();
            $receipt->delete();
        });

        return redirect()->route('receipts.index')
            ->with('success', "Đã xóa phiếu {$code} thành công.");
    }

    // ──────────────────────────────────────────────────────────────────────────
    // DUYỆT PHIẾU → Completed & cập nhật tồn kho
    // ──────────────────────────────────────────────────────────────────────────

    public function confirm(StockReceipt $receipt)
    {
        if (!in_array($receipt->status, [1, 2])) {
            return redirect()->route('receipts.show', $receipt)
                ->with('error', 'Phiếu không ở trạng thái có thể duyệt.');
        }

        DB::transaction(function () use ($receipt) {
            $receipt->load('details.product');

            foreach ($receipt->details as $detail) {
                $qty = $detail->actual_qty ?? $detail->expected_qty;

                if ($qty <= 0) {
                    continue;
                }

                // 1. Tìm hoặc tạo lot nếu có lot_number
                $lotId = null;
                if ($detail->lot_id) {
                    $lotId = $detail->lot_id;
                }

                // 2. Upsert bản ghi stock
                $stock = Stock::firstOrNew([
                    'product_id'  => $detail->product_id,
                    'location_id' => $detail->location_id ?? $this->defaultLocationId(),
                    'lot_id'      => $lotId,
                    'serial_id'   => null,
                ]);

                $balanceBefore = $stock->quantity ?? 0;
                $stock->quantity      = ($stock->quantity ?? 0) + $qty;
                $stock->supplier_id   = $receipt->supplier_id;
                $stock->received_date = $receipt->receipt_date;
                $stock->expiry_date   = $detail->expiry_date;
                $stock->status        = 1;
                $stock->updated_at    = now();
                $stock->save();

                // 3. Ghi stock_ledger
                StockLedger::create([
                    'product_id'       => $detail->product_id,
                    'stock_id'         => $stock->id,
                    'lot_id'           => $lotId,
                    'serial_id'        => null,
                    'location_id'      => $stock->location_id,
                    'transaction_type' => 'RECEIPT',
                    'reference_id'     => $receipt->id,
                    'reference_type'   => 'stock_receipt',
                    'reference_code'   => $receipt->code,
                    'direction'        => 1, // In
                    'quantity'         => $qty,
                    'balance_after'    => $stock->quantity,
                    'created_by'       => Auth::id(),
                    'note'             => "Nhập kho từ phiếu {$receipt->code}",
                    'transaction_date' => now(),
                ]);

                // 4. Cập nhật actual_qty vào detail
                $detail->update(['actual_qty' => $qty]);
            }

            // 5. Cập nhật trạng thái phiếu
            $receipt->update([
                'status'       => 4, // Completed
                'confirmed_by' => Auth::id(),
            ]);
        });

        return redirect()->route('receipts.show', $receipt)
            ->with('success', "Phiếu {$receipt->code} đã được duyệt và cập nhật tồn kho.");
    }

    // ──────────────────────────────────────────────────────────────────────────
    // HỦY PHIẾU
    // ──────────────────────────────────────────────────────────────────────────

    public function cancel(StockReceipt $receipt)
    {
        if ($receipt->status === 4) {
            return redirect()->route('receipts.show', $receipt)
                ->with('error', 'Không thể hủy phiếu đã hoàn thành. Vui lòng tạo phiếu điều chỉnh.');
        }

        if ($receipt->status === 5) {
            return redirect()->route('receipts.show', $receipt)
                ->with('error', 'Phiếu đã được hủy trước đó.');
        }

        $receipt->update(['status' => 5]);

        return redirect()->route('receipts.show', $receipt)
            ->with('success', "Đã hủy phiếu {$receipt->code}.");
    }

    // ──────────────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Validate dữ liệu phiếu nhập.
     */
    private function validateReceipt(Request $request, bool $isUpdate = false): void
    {
        $codeRule = $isUpdate
            ? 'nullable|string|max:50'
            : 'nullable|string|max:50|unique:stock_receipts,code';

        $request->validate([
            'code'                        => $codeRule,
            'receipt_type'                => 'required|in:1,2,3',
            'supplier_id'                 => 'nullable|exists:suppliers,id',
            'reference_no'                => 'nullable|string|max:100',
            'receipt_date'                => 'required|date',
            'note'                        => 'nullable|string|max:1000',
            'details'                     => 'required|array|min:1',
            'details.*.product_id'        => 'required|exists:products,id',
            'details.*.uom_id'            => 'required|exists:uoms,id',
            'details.*.expected_qty'      => 'required|numeric|min:0.001',
            'details.*.actual_qty'        => 'nullable|numeric|min:0',
            'details.*.location_id'       => 'nullable|exists:locations,id',
            'details.*.lot_number'        => 'nullable|string|max:50',
            'details.*.expiry_date'       => 'nullable|date',
        ], [
            'code.unique'                 => 'Mã phiếu đã tồn tại.',
            'receipt_type.required'       => 'Vui lòng chọn loại nhập.',
            'receipt_date.required'       => 'Vui lòng chọn ngày nhập.',
            'supplier_id.exists'          => 'Nhà cung cấp không hợp lệ.',
            'details.required'            => 'Phiếu nhập phải có ít nhất một hàng hóa.',
            'details.min'                 => 'Phiếu nhập phải có ít nhất một hàng hóa.',
            'details.*.product_id.required' => 'Vui lòng chọn hàng hóa.',
            'details.*.product_id.exists'   => 'Hàng hóa không hợp lệ.',
            'details.*.uom_id.required'     => 'Vui lòng chọn đơn vị tính.',
            'details.*.expected_qty.required' => 'Vui lòng nhập số lượng dự kiến.',
            'details.*.expected_qty.min'      => 'Số lượng phải lớn hơn 0.',
        ]);
    }

    /**
     * Lưu các dòng chi tiết phiếu nhập.
     * Tự tạo/tìm Lot nếu hàng hóa có tracking_type = Lot.
     */
    private function saveDetails(StockReceipt $receipt, array $details): void
    {
        foreach ($details as $row) {
            if (empty($row['product_id']) || empty($row['expected_qty'])) {
                continue;
            }

            $product = Product::find($row['product_id']);
            $lotId   = null;

            // Tự tạo/tìm lot nếu có lot_number và product tracking = Lot
            if (
                !empty($row['lot_number']) &&
                in_array($product?->tracking_type, [
                    Product::TRACKING_LOT,
                    Product::TRACKING_LOT_AND_SERIAL,
                ])
            ) {
                $lot = Lot::firstOrCreate(
                    [
                        'product_id' => $row['product_id'],
                        'lot_number' => trim($row['lot_number']),
                    ],
                    [
                        'supplier_id'  => $receipt->supplier_id,
                        'received_date' => $receipt->receipt_date,
                        'expiry_date'   => $row['expiry_date'] ?? null,
                        'status'        => Lot::STATUS_ACTIVE,
                    ]
                );
                $lotId = $lot->id;
            }

            StockReceiptDetail::create([
                'stock_receipt_id' => $receipt->id,
                'product_id'       => $row['product_id'],
                'uom_id'           => $row['uom_id'],
                'lot_id'           => $lotId,
                'location_id'      => $row['location_id'] ?: null,
                'expected_qty'     => $row['expected_qty'],
                'actual_qty'       => $row['actual_qty'] ?: null,
                'expiry_date'      => $row['expiry_date'] ?: null,
                'qc_status'        => 0,
                'supplier_id'      => $receipt->supplier_id,
            ]);
        }
    }

    /**
     * Sinh mã phiếu theo format NK-YYYYMM-XXXX.
     */
    private function generateCode(): string
    {
        $prefix = 'NK-' . now()->format('Ym') . '-';
        $last   = StockReceipt::where('code', 'like', $prefix . '%')
                      ->orderByDesc('code')
                      ->value('code');

        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Lấy vị trí mặc định (vị trí đầu tiên trong DB).
     */
    private function defaultLocationId(): int
    {
        return Location::orderBy('id')->value('id') ?? 1;
    }
}