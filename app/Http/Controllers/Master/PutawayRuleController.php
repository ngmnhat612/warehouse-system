<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\PutawayRule;
use Illuminate\Http\Request;

class PutawayRuleController extends Controller
{
    public function index(Request $request)
    {
        $query = PutawayRule::with(['product', 'category', 'locationDest']);

        // ── Tìm kiếm ──────────────────────────────────────────────────────────
        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('product', fn($p) =>
                    $p->where('code', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%")
                )->orWhereHas('category', fn($c) =>
                    $c->where('name', 'like', "%{$search}%")
                );
            });
        }

        if ($request->apply_on) {
            if ($request->apply_on === 'product') {
                $query->whereNotNull('product_id');
            } elseif ($request->apply_on === 'category') {
                $query->whereNotNull('category_id');
            }
        }

        if ($request->status !== null && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $rules       = $query->orderBy('priority')->orderBy('id')->paginate(15)->withQueryString();
        $totalCount  = PutawayRule::count();
        $activeCount = PutawayRule::where('status', 1)->count();

        // Dữ liệu cho form inline
        $products  = Product::active()->orderBy('code')->get(['id', 'code', 'name']);
        $categories = Category::where('status', 1)->orderBy('name')->get(['id', 'name']);
        $locations  = Location::where('status', 1)
                               ->where('type', 1)          // chỉ vị trí Internal
                               ->orderBy('name')
                               ->get(['id', 'code', 'name']);

        return view('master.putaway_rule.index',
            compact('rules', 'totalCount', 'activeCount', 'products', 'categories', 'locations'));
    }

    public function store(Request $request)
    {
        $this->validateRule($request);

        PutawayRule::create([
            'product_id'      => $request->apply_on === 'product'   ? $request->product_id   : null,
            'category_id'     => $request->apply_on === 'category'  ? $request->category_id  : null,
            'location_dest_id' => $request->location_dest_id,
            'priority'        => $request->priority ?? 10,
            'note'            => $request->note,
            'status'          => $request->status,
        ]);

        return redirect()->route('master.putaway_rule.index')
            ->with('success', 'Đã thêm putaway rule thành công.');
    }

    public function update(Request $request, PutawayRule $putaway_rule)
    {
        $this->validateRule($request);

        $putaway_rule->update([
            'product_id'       => $request->apply_on === 'product'  ? $request->product_id  : null,
            'category_id'      => $request->apply_on === 'category' ? $request->category_id : null,
            'location_dest_id' => $request->location_dest_id,
            'priority'         => $request->priority ?? 10,
            'note'             => $request->note,
            'status'           => $request->status,
        ]);

        return redirect()->route('master.putaway_rule.index')
            ->with('success', 'Đã cập nhật putaway rule thành công.');
    }

    public function destroy(PutawayRule $putaway_rule)
    {
        $putaway_rule->delete();

        return redirect()->route('master.putaway_rule.index')
            ->with('success', 'Đã xóa putaway rule.');
    }

    // ===== PRIVATE =====

    private function validateRule(Request $request): void
    {
        $request->validate([
            'apply_on'         => 'required|in:product,category',
            'product_id'       => 'required_if:apply_on,product|nullable|exists:products,id',
            'category_id'      => 'required_if:apply_on,category|nullable|exists:categories,id',
            'location_dest_id' => 'required|exists:locations,id',
            'priority'         => 'nullable|integer|min:1|max:999',
            'note'             => 'nullable|string|max:500',
            'status'           => 'required|in:0,1',
        ], [
            'apply_on.required'          => 'Vui lòng chọn loại áp dụng (hàng hóa / nhóm).',
            'product_id.required_if'     => 'Vui lòng chọn hàng hóa.',
            'category_id.required_if'    => 'Vui lòng chọn nhóm hàng hóa.',
            'location_dest_id.required'  => 'Vui lòng chọn vị trí đích.',
        ]);
    }
}