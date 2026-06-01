<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Lot;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockIssue;
use App\Models\StockIssueDetail;
use App\Models\StockLedger;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockIssueController extends Controller
{
    // ──────────────────────────────────────────────────────────────────────────
    // DANH SÁCH
    // ──────────────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = StockIssue::with(['requester', 'creator'])
            ->withCount('details');

        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('reference_no', 'like', "%{$search}%");
            });
        }

        if ($request->issue_type) {
            $query->where('issue_type', $request->issue_type);
        }

        if ($request->status !== null && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($request->date_from) {
            $query->where('issue_date', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->where('issue_date', '<=', $request->date_to);
        }

        $issues         = $query->orderByDesc('created_at')->paginate(20)->withQueryString();
        $totalCount     = StockIssue::count();
        $pendingCount   = StockIssue::where('status', 2)->count();
        $completedCount = StockIssue::where('status', 4)->count();
        $cancelledCount = StockIssue::where('status', 5)->count();

        return view('issues.index', compact(
            'issues', 'totalCount', 'pendingCount', 'completedCount', 'cancelledCount'
        ));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // FORM TẠO MỚI
    // ──────────────────────────────────────────────────────────────────────────

    public function create()
    {
        $products  = Product::with('uom')->where('status', 1)->orderBy('code')->get();
        $locations = Location::orderBy('code')->get();
        $users     = User::orderBy('name')->get();

        // Lots nhóm theo product_id để dùng trong JS
        $lots = Lot::where('status', Lot::STATUS_ACTIVE)
            ->select('id', 'product_id', 'lot_number', 'expiry_date')
            ->orderBy('lot_number')
            ->get()
            ->groupBy('product_id');

        return view('issues.form', compact('products', 'locations', 'users', 'lots'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // LƯU PHIẾU MỚI
    // ──────────────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $this->validateIssue($request);

        DB::transaction(function () use ($request) {
            $code = $request->code
                ? strtoupper(trim($request->code))
                : $this->generateCode();

            $issue = StockIssue::create([
                'code'                 => $code,
                'issue_type'           => $request->issue_type,
                'requester_id'         => $request->requester_id ?: null,
                'reference_no'         => $request->reference_no ?: null,
                'issue_date'           => $request->issue_date,
                'expected_return_date' => $request->expected_return_date ?: null,
                'status'               => 1, // Draft
                'note'                 => $request->note ?: null,
                'created_by'           => Auth::id(),
            ]);

            $this->saveDetails($issue, $request->details ?? []);
        });

        $action = $request->input('action');

        return $action === 'save_and_new'
            ? redirect()->route('issues.create')->with('success', 'Đã tạo phiếu xuất thành công.')
            : redirect()->route('issues.index')->with('success', 'Đã tạo phiếu xuất thành công.');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // XEM CHI TIẾT
    // ──────────────────────────────────────────────────────────────────────────

    public function show(StockIssue $issue)
    {
        $issue->load([
            'requester',
            'creator',
            'confirmer',
            'details.product.uom',
            'details.location',
            'details.lot',
            'details.uom',
        ]);

        return view('issues.show', compact('issue'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // FORM CHỈNH SỬA
    // ──────────────────────────────────────────────────────────────────────────

    public function edit(StockIssue $issue)
    {
        if ($issue->status !== 1) {
            return redirect()->route('issues.show', $issue)
                ->with('error', 'Chỉ có thể chỉnh sửa phiếu ở trạng thái Draft.');
        }

        $issue->load(['details.product', 'details.location', 'details.lot', 'details.uom']);
        $products  = Product::with('uom')->where('status', 1)->orderBy('code')->get();
        $locations = Location::orderBy('code')->get();
        $users     = User::orderBy('name')->get();

        $lots = Lot::where('status', Lot::STATUS_ACTIVE)
            ->select('id', 'product_id', 'lot_number', 'expiry_date')
            ->orderBy('lot_number')
            ->get()
            ->groupBy('product_id');

        return view('issues.form', compact('issue', 'products', 'locations', 'users', 'lots'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // CẬP NHẬT PHIẾU
    // ──────────────────────────────────────────────────────────────────────────

    public function update(Request $request, StockIssue $issue)
    {
        if ($issue->status !== 1) {
            return redirect()->route('issues.show', $issue)
                ->with('error', 'Chỉ có thể chỉnh sửa phiếu ở trạng thái Draft.');
        }

        $this->validateIssue($request, isUpdate: true);

        DB::transaction(function () use ($request, $issue) {
            $issue->update([
                'issue_type'           => $request->issue_type,
                'requester_id'         => $request->requester_id ?: null,
                'reference_no'         => $request->reference_no ?: null,
                'issue_date'           => $request->issue_date,
                'expected_return_date' => $request->expected_return_date ?: null,
                'note'                 => $request->note ?: null,
            ]);

            $issue->details()->delete();
            $this->saveDetails($issue, $request->details ?? []);
        });

        return redirect()->route('issues.show', $issue)
            ->with('success', "Đã cập nhật phiếu {$issue->code} thành công.");
    }

    // ──────────────────────────────────────────────────────────────────────────
    // XÓA PHIẾU (chỉ Draft)
    // ──────────────────────────────────────────────────────────────────────────

    public function destroy(StockIssue $issue)
    {
        if ($issue->status !== 1) {
            return redirect()->route('issues.index')
                ->with('error', "Không thể xóa phiếu {$issue->code} vì không ở trạng thái Draft.");
        }

        $code = $issue->code;

        DB::transaction(function () use ($issue) {
            $issue->details()->delete();
            $issue->delete();
        });

        return redirect()->route('issues.index')
            ->with('success', "Đã xóa phiếu {$code} thành công.");
    }

    // ──────────────────────────────────────────────────────────────────────────
    // DUYỆT PHIẾU → Completed & trừ tồn kho
    // ──────────────────────────────────────────────────────────────────────────

    public function confirm(StockIssue $issue)
    {
        if (!in_array($issue->status, [1, 2])) {
            return redirect()->route('issues.show', $issue)
                ->with('error', 'Phiếu không ở trạng thái có thể duyệt.');
        }

        // Kiểm tra tồn kho trước khi duyệt
        $issue->load('details.product');
        $errors = $this->checkStockSufficiency($issue);

        if (!empty($errors)) {
            return redirect()->route('issues.show', $issue)
                ->with('error', 'Không đủ tồn kho: ' . implode('; ', $errors));
        }

        DB::transaction(function () use ($issue) {
            foreach ($issue->details as $detail) {
                $qty = $detail->quantity;

                if ($qty <= 0) {
                    continue;
                }

                // Tìm bản ghi stock phù hợp (ưu tiên đúng lot, đúng location)
                $stock = Stock::where('product_id', $detail->product_id)
                    ->where('location_id', $detail->location_id)
                    ->when($detail->lot_id, fn($q) => $q->where('lot_id', $detail->lot_id))
                    ->first();

                if (!$stock) {
                    // Fallback: bất kỳ stock nào của sản phẩm có đủ hàng
                    $stock = Stock::where('product_id', $detail->product_id)
                        ->where('quantity', '>=', $qty)
                        ->orderBy('id')
                        ->firstOrFail();
                }

                $stock->quantity   -= $qty;
                $stock->updated_at  = now();
                $stock->save();

                // Ghi stock_ledger
                StockLedger::create([
                    'product_id'       => $detail->product_id,
                    'stock_id'         => $stock->id,
                    'lot_id'           => $detail->lot_id,
                    'serial_id'        => $detail->serial_id ?? null,
                    'location_id'      => $detail->location_id,
                    'transaction_type' => 'ISSUE',
                    'reference_id'     => $issue->id,
                    'reference_type'   => 'stock_issue',
                    'reference_code'   => $issue->code,
                    'direction'        => 2, // Out
                    'quantity'         => $qty,
                    'balance_after'    => $stock->quantity,
                    'created_by'       => Auth::id(),
                    'note'             => "Xuất kho từ phiếu {$issue->code}",
                    'transaction_date' => now(),
                ]);
            }

            $issue->update([
                'status'       => 4, // Completed
                'confirmed_by' => Auth::id(),
            ]);
        });

        return redirect()->route('issues.show', $issue)
            ->with('success', "Phiếu {$issue->code} đã được duyệt và trừ tồn kho.");
    }

    // ──────────────────────────────────────────────────────────────────────────
    // HỦY PHIẾU
    // ──────────────────────────────────────────────────────────────────────────

    public function cancel(StockIssue $issue)
    {
        if ($issue->status === 4) {
            return redirect()->route('issues.show', $issue)
                ->with('error', 'Không thể hủy phiếu đã hoàn thành. Vui lòng tạo phiếu điều chỉnh.');
        }

        if ($issue->status === 5) {
            return redirect()->route('issues.show', $issue)
                ->with('error', 'Phiếu đã được hủy trước đó.');
        }

        $issue->update(['status' => 5]);

        return redirect()->route('issues.show', $issue)
            ->with('success', "Đã hủy phiếu {$issue->code}.");
    }

    // ──────────────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ──────────────────────────────────────────────────────────────────────────

    private function validateIssue(Request $request, bool $isUpdate = false): void
    {
        $codeRule = $isUpdate
            ? 'nullable|string|max:50'
            : 'nullable|string|max:50|unique:stock_issues,code';

        $request->validate([
            'code'                        => $codeRule,
            'issue_type'                  => 'required|in:1,2,3,4',
            'requester_id'                => 'nullable|exists:users,id',
            'reference_no'                => 'nullable|string|max:100',
            'issue_date'                  => 'required|date',
            'expected_return_date'        => 'nullable|date|after_or_equal:issue_date',
            'note'                        => 'nullable|string|max:1000',
            'details'                     => 'required|array|min:1',
            'details.*.product_id'        => 'required|exists:products,id',
            'details.*.uom_id'            => 'required|exists:uoms,id',
            'details.*.quantity'          => 'required|numeric|min:0.001',
            'details.*.location_id'       => 'required|exists:locations,id',
            'details.*.lot_id'            => 'nullable|exists:lots,id',
            'details.*.note'              => 'nullable|string|max:200',
        ], [
            'code.unique'                     => 'Mã phiếu đã tồn tại.',
            'issue_type.required'             => 'Vui lòng chọn loại xuất.',
            'issue_date.required'             => 'Vui lòng chọn ngày xuất.',
            'expected_return_date.after_or_equal' => 'Hạn trả phải sau hoặc bằng ngày xuất.',
            'details.required'                => 'Phiếu xuất phải có ít nhất một hàng hóa.',
            'details.min'                     => 'Phiếu xuất phải có ít nhất một hàng hóa.',
            'details.*.product_id.required'   => 'Vui lòng chọn hàng hóa.',
            'details.*.uom_id.required'       => 'Vui lòng chọn đơn vị tính.',
            'details.*.quantity.required'     => 'Vui lòng nhập số lượng.',
            'details.*.quantity.min'          => 'Số lượng phải lớn hơn 0.',
            'details.*.location_id.required'  => 'Vui lòng chọn vị trí kho.',
        ]);
    }

    /**
     * Lưu các dòng chi tiết phiếu xuất.
     */
    private function saveDetails(StockIssue $issue, array $details): void
    {
        foreach ($details as $row) {
            if (empty($row['product_id']) || empty($row['quantity'])) {
                continue;
            }

            StockIssueDetail::create([
                'stock_issue_id' => $issue->id,
                'product_id'     => $row['product_id'],
                'uom_id'         => $row['uom_id'],
                'lot_id'         => $row['lot_id'] ?: null,
                'serial_id'      => null,
                'location_id'    => $row['location_id'],
                'quantity'       => $row['quantity'],
                'note'           => $row['note'] ?: null,
            ]);
        }
    }

    /**
     * Kiểm tra tồn kho đủ không trước khi duyệt.
     * Trả về mảng thông báo lỗi (rỗng nếu đủ hàng).
     */
    private function checkStockSufficiency(StockIssue $issue): array
    {
        $errors = [];

        // Gom nhóm SL cần xuất theo product_id + location_id + lot_id
        $needed = [];
        foreach ($issue->details as $detail) {
            $key = "{$detail->product_id}_{$detail->location_id}_{$detail->lot_id}";
            $needed[$key] = ($needed[$key] ?? 0) + $detail->quantity;
        }

        foreach ($needed as $key => $qty) {
            [$productId, $locationId, $lotId] = explode('_', $key);

            $available = Stock::where('product_id', $productId)
                ->where('location_id', $locationId)
                ->when($lotId, fn($q) => $q->where('lot_id', $lotId))
                ->sum('quantity');

            if ($available < $qty) {
                $product = Product::find($productId);
                $errors[] = sprintf(
                    '%s: cần %.3f, tồn kho %.3f',
                    $product?->name ?? "ID {$productId}",
                    $qty,
                    $available
                );
            }
        }

        return $errors;
    }

    /**
     * Sinh mã phiếu theo format XK-YYYYMM-XXXX.
     */
    private function generateCode(): string
    {
        $prefix = 'XK-' . now()->format('Ym') . '-';
        $last   = StockIssue::where('code', 'like', $prefix . '%')
                      ->orderByDesc('code')
                      ->value('code');

        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}