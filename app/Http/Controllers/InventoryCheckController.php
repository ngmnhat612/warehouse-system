<?php

namespace App\Http\Controllers;

use App\Models\InventoryCheck;
use App\Models\InventoryCheckLine;
use App\Models\InventoryFreeze;
use App\Models\InventoryFreezeDetail;
use App\Models\Location;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentDetail;
use App\Models\StockLedger;
use App\Models\User;
use App\Exports\InventoryCheckExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Imports\InventoryCheckImport;
use Illuminate\Support\Facades\Gate;

class InventoryCheckController extends Controller
{
    // ──────────────────────────────────────────────────────────────────────────
    // DANH SÁCH
    // ──────────────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        Gate::authorize('stocktake.view');

        $query = InventoryCheck::with(['createdBy', 'assignedTo'])
            ->withCount('lines');

        if ($search = $request->search) {
            $query->where('code', 'like', "%{$search}%");
        }

        if ($request->check_type) {
            $query->where('check_type', $request->check_type);
        }

        if ($request->status !== null && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($request->date_from) {
            $query->where('check_date', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->where('check_date', '<=', $request->date_to);
        }

        $checks         = $query->orderByDesc('created_at')->paginate(20)->withQueryString();
        $totalCount     = InventoryCheck::count();
        $inProgressCount= InventoryCheck::where('status', InventoryCheck::STATUS_IN_PROGRESS)->count();
        $doneCount      = InventoryCheck::where('status', InventoryCheck::STATUS_DONE)->count();
        $cancelledCount = InventoryCheck::where('status', InventoryCheck::STATUS_CANCELLED)->count();

        return view('stocktakes.index', compact(
            'checks', 'totalCount', 'inProgressCount', 'doneCount', 'cancelledCount'
        ));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // FORM TẠO MỚI
    // ──────────────────────────────────────────────────────────────────────────

    public function create()
    {
        Gate::authorize('stocktake.create');

        $users     = User::orderBy('name')->get();
        $locations = Location::where('status', 1)->where('type', 1)->orderBy('code')->get(); // Internal only
        $products  = Product::where('status', 1)->orderBy('code')->get();

        return view('stocktakes.form', compact('users', 'locations', 'products'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // LƯU PHIẾU MỚI (DRAFT)
    // ──────────────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        Gate::authorize('stocktake.create');

        $request->validate([
            'check_type'  => 'required|in:1,2,3',
            'check_date'  => 'required|date',
            'assigned_to' => 'nullable|exists:users,id',
            'note'        => 'nullable|string|max:1000',
            // Scope selectors
            'location_ids' => 'nullable|array',
            'location_ids.*' => 'exists:locations,id',
            'product_ids'  => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
        ], [
            'check_type.required' => 'Vui lòng chọn loại kiểm kê.',
            'check_date.required' => 'Vui lòng chọn ngày kiểm kê.',
        ]);

        if ($request->check_type == 2 && empty($request->location_ids)) {
            return back()->withErrors(['location_ids' => 'Kiểm kê theo khu vực cần chọn ít nhất một vị trí.'])->withInput();
        }
        if ($request->check_type == 3 && empty($request->product_ids)) {
            return back()->withErrors(['product_ids' => 'Kiểm kê theo mặt hàng cần chọn ít nhất một sản phẩm.'])->withInput();
        }

        $check = DB::transaction(function () use ($request) {
            $code = 'KK-' . now()->format('Ym') . '-' . str_pad(
                (InventoryCheck::where('code', 'like', 'KK-' . now()->format('Ym') . '-%')
                    ->count() + 1),
                4, '0', STR_PAD_LEFT
            );

            return InventoryCheck::create([
                'code'        => $code,
                'check_type'  => $request->check_type,
                'check_date'  => $request->check_date,
                'assigned_to' => $request->assigned_to ?: null,
                'status'      => InventoryCheck::STATUS_DRAFT,
                'note'        => $request->note ?: null,
                'created_by'  => Auth::id(),
                // Store scope as JSON in note for now — passed to activate
                '_location_ids' => $request->location_ids,
                '_product_ids'  => $request->product_ids,
            ]);
        });

        // Re-do without fake fields
        DB::transaction(function () use ($request, $check) {
            $check->update([
                'note' => $request->note ?: null,
            ]);
        });

        // Store scope selections in session for the activate step
        session([
            "check_{$check->id}_location_ids" => $request->location_ids,
            "check_{$check->id}_product_ids"  => $request->product_ids,
        ]);

        return redirect()->route('stocktakes.show', $check)
            ->with('success', "Đã tạo phiếu kiểm kê {$check->code}.");
    }

    // ──────────────────────────────────────────────────────────────────────────
    // XEM CHI TIẾT
    // ──────────────────────────────────────────────────────────────────────────

    public function show(InventoryCheck $stocktake)
    {
        Gate::authorize('stocktake.view');

        $stocktake->load([
            'createdBy',
            'assignedTo',
            'freeze.details.location',
            'freeze.details.product',
            'lines.product.uom',
            'lines.location',
            'lines.lot',
            'lines.countedBy',
            'adjustments',
        ]);

        $countedLines    = $stocktake->lines->whereNotNull('actual_qty')->count();
        $totalLines      = $stocktake->lines->count();
        $diffLines       = $stocktake->lines->filter(fn($l) => $l->actual_qty !== null && $l->diff_qty != 0)->count();

        return view('stocktakes.show', compact(
            'stocktake', 'countedLines', 'totalLines', 'diffLines'
        ));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // KÍCH HOẠT KIỂM KÊ → snapshot tồn kho + đóng băng
    // ──────────────────────────────────────────────────────────────────────────

    public function activate(Request $request, InventoryCheck $stocktake)
    {
        Gate::authorize('stocktake.create');

        if ($stocktake->status !== InventoryCheck::STATUS_DRAFT) {
            return redirect()->route('stocktakes.show', $stocktake)
                ->with('error', 'Phiếu không ở trạng thái Nháp.');
        }

        DB::transaction(function () use ($request, $stocktake) {

            // ── 1. Xác định phạm vi kiểm kê ──
            $locationIds = session("check_{$stocktake->id}_location_ids", $request->location_ids ?? []);
            $productIds  = session("check_{$stocktake->id}_product_ids",  $request->product_ids ?? []);

            // ── 2. Snapshot tồn kho → tạo inventory_check_lines ──
            $stockQuery = Stock::with(['product.uom', 'location', 'lot'])
                ->where('quantity', '>', 0);

            if ($stocktake->check_type == 2 && !empty($locationIds)) {
                $stockQuery->whereIn('location_id', $locationIds);
            } elseif ($stocktake->check_type == 3 && !empty($productIds)) {
                $stockQuery->whereIn('product_id', $productIds);
            }
            // check_type == 1 (Toàn kho) → không lọc thêm

            $stocks = $stockQuery->get();

            foreach ($stocks as $stock) {
                InventoryCheckLine::create([
                    'inventory_check_id' => $stocktake->id,
                    'product_id'         => $stock->product_id,
                    'lot_id'             => $stock->lot_id,
                    'serial_id'          => $stock->serial_id,
                    'location_id'        => $stock->location_id,
                    'uom_id'             => $stock->product?->uom_id,
                    'system_qty'         => $stock->quantity,
                    'actual_qty'         => null,
                ]);
            }

            // ── 3. Tạo inventory_freeze + inventory_freeze_details ──
            $freeze = InventoryFreeze::create([
                'check_id'   => $stocktake->id,
                'check_type' => $stocktake->check_type,
                'frozen_by'  => Auth::id(),
                'frozen_at'  => now(),
                'reason'     => "Kiểm kê phiếu {$stocktake->code}",
            ]);

            if ($stocktake->check_type == 1) {
                // Toàn kho
                InventoryFreezeDetail::create([
                    'freeze_id'    => $freeze->id,
                    'freeze_scope' => InventoryFreezeDetail::SCOPE_ALL,
                    'location_id'  => null,
                    'product_id'   => null,
                ]);
            } elseif ($stocktake->check_type == 2 && !empty($locationIds)) {
                foreach ($locationIds as $locId) {
                    InventoryFreezeDetail::create([
                        'freeze_id'    => $freeze->id,
                        'freeze_scope' => InventoryFreezeDetail::SCOPE_LOCATION,
                        'location_id'  => $locId,
                        'product_id'   => null,
                    ]);
                }
            } elseif ($stocktake->check_type == 3 && !empty($productIds)) {
                foreach ($productIds as $prodId) {
                    InventoryFreezeDetail::create([
                        'freeze_id'    => $freeze->id,
                        'freeze_scope' => InventoryFreezeDetail::SCOPE_PRODUCT,
                        'location_id'  => null,
                        'product_id'   => $prodId,
                    ]);
                }
            }

            // ── 4. Cập nhật trạng thái phiếu ──
            $stocktake->update([
                'status' => InventoryCheck::STATUS_IN_PROGRESS,
            ]);
        });

        // Xóa session scope
        session()->forget(["check_{$stocktake->id}_location_ids", "check_{$stocktake->id}_product_ids"]);

        return redirect()->route('stocktakes.show', $stocktake)
            ->with('success', "Đã kích hoạt kiểm kê {$stocktake->code}. Tồn kho đã được snapshot và kho đã đóng băng.");
    }

    // ──────────────────────────────────────────────────────────────────────────
    // CẬP NHẬT SỐ LƯỢNG THỰC TẾ (từng dòng — AJAX hoặc form)
    // ──────────────────────────────────────────────────────────────────────────

    public function updateLine(Request $request, InventoryCheck $stocktake, InventoryCheckLine $line)
    {
        Gate::authorize('stocktake.create');

        if ($stocktake->status !== InventoryCheck::STATUS_IN_PROGRESS) {
            return response()->json(['error' => 'Phiếu không ở trạng thái Đang kiểm kê.'], 422);
        }

        if ($line->inventory_check_id !== $stocktake->id) {
            return response()->json(['error' => 'Dòng không thuộc phiếu này.'], 403);
        }

        $request->validate([
            'actual_qty' => 'required|numeric|min:0',
        ]);

        $line->update([
            'actual_qty' => $request->actual_qty,
            'counted_by' => Auth::id(),
            'counted_at' => now(),
        ]);

        return response()->json([
            'success'   => true,
            'actual_qty' => $line->actual_qty,
            'diff_qty'  => $line->diff_qty,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // CẬP NHẬT HÀNG LOẠT (form submit tất cả)
    // ──────────────────────────────────────────────────────────────────────────

    public function updateLines(Request $request, InventoryCheck $stocktake)
    {
        Gate::authorize('stocktake.create');

        if ($stocktake->status !== InventoryCheck::STATUS_IN_PROGRESS) {
            return redirect()->route('stocktakes.show', $stocktake)
                ->with('error', 'Phiếu không ở trạng thái Đang kiểm kê.');
        }

        $request->validate([
            'lines'           => 'required|array',
            'lines.*.id'      => 'required|exists:inventory_check_lines,id',
            'lines.*.actual_qty' => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($request, $stocktake) {
            foreach ($request->lines as $row) {
                if ($row['actual_qty'] === null || $row['actual_qty'] === '') {
                    continue;
                }

                $line = InventoryCheckLine::where('id', $row['id'])
                    ->where('inventory_check_id', $stocktake->id)
                    ->first();

                if ($line) {
                    $line->update([
                        'actual_qty' => $row['actual_qty'],
                        'counted_by' => Auth::id(),
                        'counted_at' => now(),
                    ]);
                }
            }
        });

        return redirect()->route('stocktakes.show', $stocktake)
            ->with('success', 'Đã lưu số lượng kiểm kê.');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // TẢI TEMPLATE EXCEL ĐỂ ĐIỀN OFFLINE
    // ──────────────────────────────────────────────────────────────────────────

    public function downloadTemplate(InventoryCheck $stocktake)
    {
        Gate::authorize('stocktake.view');

        if ($stocktake->status !== InventoryCheck::STATUS_IN_PROGRESS) {
            return redirect()->route('stocktakes.show', $stocktake)
                ->with('error', 'Chỉ tải template khi phiếu đang ở trạng thái Đang kiểm kê.');
        }

        if ($stocktake->lines()->count() === 0) {
            return redirect()->route('stocktakes.show', $stocktake)
                ->with('error', 'Phiếu chưa có dòng kiểm kê. Vui lòng kích hoạt phiếu trước.');
        }

        $filename = 'Template_KiemKe_' . $stocktake->code . '_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new \App\Exports\InventoryCheckTemplateExport($stocktake),
            $filename
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // UPLOAD FILE EXCEL ĐÃ ĐIỀN → CẬP NHẬT actual_qty
    // ──────────────────────────────────────────────────────────────────────────

    public function importExcel(Request $request, InventoryCheck $stocktake)
    {
        Gate::authorize('stocktake.create');

        if ($stocktake->status !== InventoryCheck::STATUS_IN_PROGRESS) {
            return redirect()->route('stocktakes.show', $stocktake)
                ->with('error', 'Chỉ nhập Excel khi phiếu đang ở trạng thái Đang kiểm kê.');
        }

        $request->validate([
            'excel_file' => [
                'required',
                'file',
                'mimes:xlsx,xls',
                'max:10240', // 10 MB
            ],
        ], [
            'excel_file.required' => 'Vui lòng chọn file Excel.',
            'excel_file.mimes'    => 'File phải là định dạng .xlsx hoặc .xls.',
            'excel_file.max'      => 'File không được vượt quá 10MB.',
        ]);

        try {
            $importer = new \App\Imports\InventoryCheckImport($stocktake->id);

            Excel::import($importer, $request->file('excel_file'));

            $message = "Đã cập nhật {$importer->updatedCount} dòng.";

            if ($importer->skippedCount > 0) {
                $message .= " Bỏ qua {$importer->skippedCount} dòng (trống hoặc không hợp lệ).";
            }

            if (!empty($importer->errors)) {
                // Trả lỗi chi tiết qua session
                $errorMessages = collect($importer->errors)->pluck('message')->join(' | ');
                return redirect()->route('stocktakes.show', $stocktake)
                    ->with('warning', $message . ' Lỗi: ' . $errorMessages);
            }

            return redirect()->route('stocktakes.show', $stocktake)
                ->with('success', $message);

        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = collect($e->failures())
                ->map(fn($f) => "Dòng {$f->row()}: " . implode(', ', $f->errors()))
                ->join(' | ');

            return redirect()->route('stocktakes.show', $stocktake)
                ->with('error', 'Lỗi dữ liệu trong file Excel: ' . $failures);

        } catch (\Exception $e) {
            return redirect()->route('stocktakes.show', $stocktake)
                ->with('error', 'Không thể đọc file Excel: ' . $e->getMessage());
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // HOÀN THÀNH KIỂM KÊ → đánh dấu DONE (chưa điều chỉnh stock)
    // ──────────────────────────────────────────────────────────────────────────

    public function complete(InventoryCheck $stocktake)
    {
        Gate::authorize('stocktake.create');

        if ($stocktake->status !== InventoryCheck::STATUS_IN_PROGRESS) {
            return redirect()->route('stocktakes.show', $stocktake)
                ->with('error', 'Phiếu không ở trạng thái Đang kiểm kê.');
        }

        $uncounted = $stocktake->lines()->whereNull('actual_qty')->count();
        if ($uncounted > 0) {
            return redirect()->route('stocktakes.show', $stocktake)
                ->with('error', "Còn {$uncounted} dòng chưa nhập số lượng thực tế. Vui lòng nhập đủ trước khi hoàn thành.");
        }

        $stocktake->update([
            'status'       => InventoryCheck::STATUS_DONE,
            'completed_at' => now(),
        ]);

        return redirect()->route('stocktakes.show', $stocktake)
            ->with('success', "Đã hoàn thành kiểm kê {$stocktake->code}. Tiến hành tạo phiếu điều chỉnh nếu có chênh lệch.");
    }

    // ──────────────────────────────────────────────────────────────────────────
    // TẠO PHIẾU ĐIỀU CHỈNH TỪ KẾT QUẢ KIỂM KÊ
    // ──────────────────────────────────────────────────────────────────────────

    public function createAdjustment(InventoryCheck $stocktake)
    {
        Gate::authorize('stocktake.adjust');

        if (!in_array($stocktake->status, [InventoryCheck::STATUS_IN_PROGRESS, InventoryCheck::STATUS_DONE])) {
            return redirect()->route('stocktakes.show', $stocktake)
                ->with('error', 'Chỉ tạo phiếu điều chỉnh khi kiểm kê Đang thực hiện hoặc Hoàn thành.');
        }

        // ── THÊM MỚI: bắt buộc nhập đủ actual_qty trước khi tạo điều chỉnh ──
        $uncounted = $stocktake->lines()->whereNull('actual_qty')->count();
        if ($uncounted > 0) {
            return redirect()->route('stocktakes.show', $stocktake)
                ->with('error', "Còn {$uncounted} dòng chưa nhập số lượng thực tế. Vui lòng nhập đủ trước khi tạo phiếu điều chỉnh.");
        }

        // Chỉ lấy các dòng có chênh lệch
        $diffLines = $stocktake->lines()
            ->with(['product.uom', 'location', 'lot'])
            ->whereNotNull('actual_qty')
            ->whereRaw('actual_qty <> system_qty')
            ->get();

        if ($diffLines->isEmpty()) {
            return redirect()->route('stocktakes.show', $stocktake)
                ->with('info', 'Không có chênh lệch nào. Không cần tạo phiếu điều chỉnh.');
        }

        $adjustment = DB::transaction(function () use ($stocktake, $diffLines) {
            $code = 'DC-' . now()->format('Ym') . '-' . str_pad(
                (StockAdjustment::where('code', 'like', 'DC-' . now()->format('Ym') . '-%')
                    ->count() + 1),
                4, '0', STR_PAD_LEFT
            );

            $adjustment = StockAdjustment::create([
                'code'               => $code,
                'inventory_check_id' => $stocktake->id,
                'status'             => StockAdjustment::STATUS_DRAFT,
                'adjustment_date'    => now()->toDateString(),
                'created_by'         => Auth::id(),
                'note'               => "Điều chỉnh từ kiểm kê {$stocktake->code}",
            ]);

            foreach ($diffLines as $line) {
                StockAdjustmentDetail::create([
                    'stock_adjustment_id'    => $adjustment->id,
                    'inventory_check_line_id'=> $line->id,
                    'product_id'             => $line->product_id,
                    'lot_id'                 => $line->lot_id,
                    'serial_id'              => $line->serial_id,
                    'uom_id'                 => $line->uom_id,
                    'location_id'            => $line->location_id,
                    'system_qty'             => $line->system_qty,
                    'actual_qty'             => $line->actual_qty,
                ]);
            }

            return $adjustment;
        });

        return redirect()->route('stocktakes.adjustment.show', [$stocktake, $adjustment])
            ->with('success', "Đã tạo phiếu điều chỉnh {$adjustment->code} với {$diffLines->count()} dòng chênh lệch.");
    }

    // ──────────────────────────────────────────────────────────────────────────
    // XEM PHIẾU ĐIỀU CHỈNH
    // ──────────────────────────────────────────────────────────────────────────

    public function showAdjustment(InventoryCheck $stocktake, StockAdjustment $adjustment)
    {
        Gate::authorize('stocktake.view');

        $adjustment->load([
            'createdBy',
            'approvedBy',
            'confirmedBy',
            'details.product.uom',
            'details.location',
            'details.lot',
            'inventoryCheck',
        ]);

        return view('stocktakes.adjustment', compact('stocktake', 'adjustment'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // ÁP DỤNG ĐIỀU CHỈNH → cập nhật stock + stock_ledger
    // ──────────────────────────────────────────────────────────────────────────

    public function applyAdjustment(InventoryCheck $stocktake, StockAdjustment $adjustment)
    {
        Gate::authorize('stocktake.adjust');

        if ($adjustment->status !== StockAdjustment::STATUS_DRAFT &&
            $adjustment->status !== StockAdjustment::STATUS_APPROVED) {
            return redirect()->route('stocktakes.adjustment.show', [$stocktake, $adjustment])
                ->with('error', 'Phiếu điều chỉnh không ở trạng thái có thể áp dụng.');
        }

        DB::transaction(function () use ($stocktake, $adjustment) {

            $adjustment->load([
                'details.product',
                'details.location',
            ]);

            foreach ($adjustment->details as $detail) {
                $diff = (float) $detail->actual_qty - (float) $detail->system_qty;
                if ($diff == 0) continue;

                $stock = Stock::where('product_id', $detail->product_id)
                    ->where('location_id', $detail->location_id)
                    ->when($detail->lot_id,  fn($q) => $q->where('lot_id', $detail->lot_id))
                    ->when(!$detail->lot_id, fn($q) => $q->whereNull('lot_id'))
                    ->lockForUpdate()
                    ->first();

                if ($stock) {
                    $stock->quantity = $stock->quantity + $diff;

                    if ($stock->quantity < 0) {
                        $previousQty = $stock->quantity - $diff;
                        throw new \Exception(
                            "Tồn kho âm sau điều chỉnh: {$detail->product->code} " .
                            "tại {$detail->location->code}. " .
                            "Tồn hiện tại: {$previousQty}, Diff: {$diff}"
                        );
                    }

                    $stock->updated_at = now();
                    $stock->save();
                } else {
                    if ($detail->actual_qty <= 0) continue;

                    $stock = Stock::create([
                        'product_id'   => $detail->product_id,
                        'location_id'  => $detail->location_id,
                        'lot_id'       => $detail->lot_id,
                        'serial_id'    => $detail->serial_id,
                        'quantity'     => (float) $detail->actual_qty,
                        'reserved_qty' => 0,
                        'status'       => Stock::STATUS_NORMAL,
                        'updated_at'   => now(),
                    ]);
                }

                StockLedger::create([
                    'product_id'       => $detail->product_id,
                    'stock_id'         => $stock->id,
                    'lot_id'           => $detail->lot_id,
                    'serial_id'        => $detail->serial_id,
                    'location_id'      => $detail->location_id,
                    'transaction_type' => 'ADJUST',
                    'reference_id'     => $adjustment->id,
                    'reference_type'   => 'stock_adjustment',
                    'reference_code'   => $adjustment->code,
                    'direction'        => $diff > 0 ? 1 : 2,
                    'quantity'         => abs($diff),
                    'balance_after'    => $stock->quantity,
                    'created_by'       => Auth::id(),
                    'note'             => "Điều chỉnh kiểm kê {$stocktake->code}",
                    'transaction_date' => now(),
                ]);
            }

            $freeze = $stocktake->freeze;
            if ($freeze && $freeze->isActive()) {
                $freeze->unfreeze();
            }

            $adjustment->update([
                'status'       => StockAdjustment::STATUS_APPLIED,
                'confirmed_by' => Auth::id(),
            ]);
        });

        return redirect()->route('stocktakes.adjustment.show', [$stocktake, $adjustment])
            ->with('success', "Đã áp dụng phiếu điều chỉnh {$adjustment->code}. Tồn kho đã được cập nhật.");
    }

    // ──────────────────────────────────────────────────────────────────────────
    // GỠ ĐÓNG BĂNG
    // ──────────────────────────────────────────────────────────────────────────

    public function unfreeze(InventoryCheck $stocktake)
    {
        Gate::authorize('stocktake.unfreeze');

        if (!in_array($stocktake->status, [InventoryCheck::STATUS_IN_PROGRESS, InventoryCheck::STATUS_DONE])) {
            return redirect()->route('stocktakes.show', $stocktake)
                ->with('error', 'Chỉ gỡ đóng băng khi kiểm kê đang thực hiện hoặc đã hoàn thành.');
        }

        $freeze = $stocktake->freeze;
        if (!$freeze || !$freeze->isActive()) {
            return redirect()->route('stocktakes.show', $stocktake)
                ->with('info', 'Kho không đang bị đóng băng.');
        }

        $freeze->unfreeze();

        return redirect()->route('stocktakes.show', $stocktake)
            ->with('success', "Đã gỡ đóng băng kho. Hoạt động xuất/nhập bình thường.");
    }

    // ──────────────────────────────────────────────────────────────────────────
    // HỦY PHIẾU
    // ──────────────────────────────────────────────────────────────────────────

    public function cancel(InventoryCheck $stocktake)
    {
        Gate::authorize('stocktake.create');

        if ($stocktake->status === InventoryCheck::STATUS_DONE) {
            return redirect()->route('stocktakes.show', $stocktake)
                ->with('error', 'Không thể hủy phiếu kiểm kê đã hoàn thành.');
        }

        if ($stocktake->status === InventoryCheck::STATUS_CANCELLED) {
            return redirect()->route('stocktakes.show', $stocktake)
                ->with('error', 'Phiếu đã hủy trước đó.');
        }

        DB::transaction(function () use ($stocktake) {
            // Gỡ đóng băng nếu đang active
            $freeze = $stocktake->freeze;
            if ($freeze && $freeze->isActive()) {
                $freeze->unfreeze();
            }

            $stocktake->update(['status' => InventoryCheck::STATUS_CANCELLED]);
        });

        return redirect()->route('stocktakes.show', $stocktake)
            ->with('success', "Đã hủy phiếu kiểm kê {$stocktake->code}.");
    }

    public function exportPdf(InventoryCheck $stocktake)
    {
        Gate::authorize('stocktake.view');

        $stocktake->load([
            'createdBy',
            'assignedTo',
            'lines.product.uom',
            'lines.location',
            'lines.lot',
        ]);

        $lines         = $stocktake->lines;
        $totalLines    = $lines->count();
        $countedLines  = $lines->whereNotNull('actual_qty')->count();
        $matchLines    = $lines->filter(fn($l) => $l->actual_qty !== null && $l->diff_qty == 0)->count();
        $diffLines     = $lines->filter(fn($l) => $l->actual_qty !== null && $l->diff_qty != 0)->count();
        $uncountedLines= $totalLines - $countedLines;
        $diffLinesList = $lines->filter(fn($l) => $l->actual_qty !== null && $l->diff_qty != 0)->values();

        $pdf = Pdf::loadView('stocktakes.pdf', compact(
            'stocktake', 'lines',
            'totalLines', 'matchLines', 'diffLines', 'uncountedLines', 'diffLinesList',
        ) + ['check' => $stocktake])
            ->setPaper('a4', 'landscape');

        return $pdf->download('KiemKe_' . $stocktake->code . '_' . now()->format('Ymd') . '.pdf');
    }
}
