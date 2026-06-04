<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CategoryController extends Controller
{
    /**
     * Danh sách danh mục — hỗ trợ 2 chế độ: flat list (paginate) và tree view (all)
     */
    public function index(Request $request)
    {
        $viewMode = $request->get('view', 'list'); // 'list' hoặc 'tree'

        $query = Category::with('parent');

        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
            $viewMode = 'list'; // khi search, luôn dùng flat list
        }

        if ($request->status !== null && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($request->parent_id === 'root') {
            $query->whereNull('parent_id');
        } elseif ($request->parent_id) {
            $query->where('parent_id', $request->parent_id);
        }

        $totalCount  = Category::count();
        $activeCount = Category::where('status', 1)->count();

        // Danh mục cha cho dropdown — tất cả danh mục (không giới hạn root)
        $parentOptions = Category::active()->orderBy('name')->get();

        if ($viewMode === 'tree') {
            // Tree view: lấy tất cả, không paginate, chỉ gốc + eager load con
            $categories = Category::with('allChildren.allChildren')
                ->whereNull('parent_id')
                ->orderBy('name')
                ->get();
            $paginator = null;
        } else {
            // Flat list: paginate
            $categories = $query
                ->orderByRaw('CASE WHEN parent_id IS NULL THEN 0 ELSE 1 END')
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString();
            $paginator = $categories;
        }

        return view('master.category.index', compact(
            'categories', 'totalCount', 'activeCount', 'parentOptions', 'viewMode', 'paginator'
        ));
    }

    /**
     * Tạo mới danh mục
     */
    public function store(Request $request)
    {
        Gate::authorize('master.create');

        $request->validate([
            'code'        => 'required|string|max:50|unique:categories,code',
            'name'        => 'required|string|max:200',
            'parent_id'   => 'nullable|exists:categories,id',
            'description' => 'nullable|string|max:500',
            'status'      => 'required|in:0,1',
        ], [
            'code.required' => 'Vui lòng nhập mã danh mục.',
            'code.unique'   => 'Mã danh mục đã tồn tại.',
            'code.max'      => 'Mã danh mục không quá 50 ký tự.',
            'name.required' => 'Vui lòng nhập tên danh mục.',
            'name.max'      => 'Tên danh mục không quá 200 ký tự.',
            'parent_id.exists' => 'Danh mục cha không hợp lệ.',
        ]);

        Category::create([
            'code'        => strtoupper(trim($request->code)),
            'name'        => $request->name,
            'parent_id'   => $request->parent_id ?: null,
            'description' => $request->description,
            'status'      => $request->status,
        ]);

        return redirect()->route('master.category.index', ['view' => $request->input('return_view', 'list')])
            ->with('success', "Đã thêm danh mục \"{$request->name}\" thành công.");
    }

    /**
     * Cập nhật danh mục
     */
    public function update(Request $request, Category $category)
    {
        Gate::authorize('master.edit');

        $request->validate([
            'code'        => "required|string|max:50|unique:categories,code,{$category->id}",
            'name'        => 'required|string|max:200',
            'parent_id'   => 'nullable|exists:categories,id',
            'description' => 'nullable|string|max:500',
            'status'      => 'required|in:0,1',
        ], [
            'code.required' => 'Vui lòng nhập mã danh mục.',
            'code.unique'   => 'Mã danh mục đã tồn tại.',
            'name.required' => 'Vui lòng nhập tên danh mục.',
        ]);

        // Tránh circular reference: không cho chọn chính nó hoặc con cháu làm cha
        if ($request->parent_id) {
            $descendantIds = $category->getDescendantIds();
            if ($request->parent_id == $category->id || in_array($request->parent_id, $descendantIds)) {
                return redirect()->route('master.category.index')
                    ->with('error', 'Không thể chọn danh mục con làm danh mục cha.');
            }
        }

        $category->update([
            'code'        => strtoupper(trim($request->code)),
            'name'        => $request->name,
            'parent_id'   => $request->parent_id ?: null,
            'description' => $request->description,
            'status'      => $request->status,
        ]);

        return redirect()->route('master.category.index', ['view' => $request->input('return_view', 'list')])
            ->with('success', "Đã cập nhật danh mục \"{$category->name}\" thành công.");
    }

    /**
     * Xóa danh mục
     */
    public function destroy(Category $category)
    {
        Gate::authorize('master.delete');

        if ($category->hasChildren()) {
            return redirect()->route('master.category.index')
                ->with('error', "Không thể xóa \"{$category->name}\" vì có danh mục con.");
        }

        if ($category->products()->exists()) {
            return redirect()->route('master.category.index')
                ->with('error', "Không thể xóa \"{$category->name}\" vì đang được sử dụng bởi hàng hóa.");
        }

        $name = $category->name;
        $category->delete();

        return redirect()->route('master.category.index')
            ->with('success', "Đã xóa danh mục \"{$name}\" thành công.");
    }
}