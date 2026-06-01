<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Product;
use App\Models\ReorderRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReorderRuleController extends Controller
{
    public function index(Request $request)
    {
        // Join stock để hiển thị tồn hiện tại ngay trên danh sách
        $query = ReorderRule::with(['product', 'location'])
            ->select('reorder_rules.*')
            ->selectSub(
                DB::table('stock')
                    ->selectRaw('COALESCE(SUM(available_qty), 0)')
                    ->whereColumn('stock.product_id', 'reorder_rules.product_id')
                    ->whereColumn('stock.location_id', 'reorder_rules.location_id'),
                'current_stock'
            );

        // ── Tìm kiếm ──────────────────────────────────────────────────────────
        if ($search = $request->search) {
            $query->whereHas('product', fn($p) =>
                $p->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
            );
        }

        if ($request->status !== null && $request->status !== '') {
            $query->where('reorder_rules.status', $request->status);
        }

        // Lọc chỉ các rule đang dưới ngưỡng
        if ($request->below_min) {
            $query->havingRaw('current_stock < reorder_rules.min_qty');
        }

        $rules       = $query->orderBy('reorder_rules.id')->paginate(15)->withQueryString();
        $totalCount  = ReorderRule::count();
        $activeCount = ReorderRule::where('status', 1)->count();
        $belowCount = DB::table('reorder_rules')
            ->where('reorder_rules.status', 1)
            ->whereRaw('
                COALESCE((
                    SELECT SUM(available_qty)
                    FROM stock
                    WHERE stock.product_id  = reorder_rules.product_id
                      AND stock.location_id = reorder_rules.location_id
                ), 0) < reorder_rules.min_qty
            ')
            ->count();

        $products  = Product::active()->orderBy('code')->get(['id', 'code', 'name']);
        $locations = Location::where('status', 1)
                              ->where('type', 1)   // Internal only
                              ->orderBy('name')
                              ->get(['id', 'code', 'name']);

        return view('master.reorder_rule.index',
            compact('rules', 'totalCount', 'activeCount', 'belowCount', 'products', 'locations'));
    }

    public function store(Request $request)
    {
        $this->validateRule($request);

        ReorderRule::create([
            'product_id'  => $request->product_id,
            'location_id' => $request->location_id,
            'min_qty'     => $request->min_qty,
            'max_qty'     => $request->max_qty,
            'alert_email' => $request->alert_email ?: null,
            'note'        => $request->note,
            'status'      => $request->status,
        ]);

        return redirect()->route('master.reorder_rule.index')
            ->with('success', 'Đã thêm reorder rule thành công.');
    }

    public function update(Request $request, ReorderRule $reorder_rule)
    {
        $this->validateRule($request, $reorder_rule->id);

        $reorder_rule->update([
            'product_id'  => $request->product_id,
            'location_id' => $request->location_id,
            'min_qty'     => $request->min_qty,
            'max_qty'     => $request->max_qty,
            'alert_email' => $request->alert_email ?: null,
            'note'        => $request->note,
            'status'      => $request->status,
        ]);

        return redirect()->route('master.reorder_rule.index')
            ->with('success', 'Đã cập nhật reorder rule thành công.');
    }

    public function destroy(ReorderRule $reorder_rule)
    {
        $reorder_rule->delete();

        return redirect()->route('master.reorder_rule.index')
            ->with('success', 'Đã xóa reorder rule.');
    }

    // ===== PRIVATE =====

    private function validateRule(Request $request, ?int $ignoreId = null): void
    {
        // Unique (product_id, location_id) — bỏ qua bản ghi đang sửa
        $uniqueRule = 'unique:reorder_rules,product_id';
        if ($ignoreId) {
            $uniqueRule .= ",{$ignoreId},id,location_id,{$request->location_id}";
        }

        $request->validate([
            'product_id'  => ['required', 'exists:products,id', $uniqueRule],
            'location_id' => 'required|exists:locations,id',
            'min_qty'     => 'required|numeric|min:0',
            'max_qty'     => 'required|numeric|gte:min_qty',
            'alert_email' => 'nullable|email|max:200',
            'note'        => 'nullable|string|max:500',
            'status'      => 'required|in:0,1',
        ], [
            'product_id.required'  => 'Vui lòng chọn hàng hóa.',
            'product_id.unique'    => 'Hàng hóa này đã có reorder rule tại vị trí đã chọn.',
            'location_id.required' => 'Vui lòng chọn vị trí.',
            'min_qty.required'     => 'Vui lòng nhập ngưỡng tối thiểu.',
            'max_qty.gte'          => 'Ngưỡng tối đa phải ≥ ngưỡng tối thiểu.',
            'alert_email.email'    => 'Email không hợp lệ.',
        ]);
    }
}