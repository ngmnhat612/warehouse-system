<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Uom;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['category', 'uom']);

        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->tracking_type) {
            $query->where('tracking_type', $request->tracking_type);
        }

        if ($request->status !== null && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $products    = $query->orderBy('code')->paginate(15)->withQueryString();
        $totalCount  = Product::count();
        $activeCount = Product::where('status', 1)->count();
        $categories  = Category::active()->orderBy('name')->get();
        $uoms        = Uom::active()->orderBy('name')->get();

        return view('master.product.index', compact(
            'products', 'totalCount', 'activeCount', 'categories', 'uoms'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code'                => 'required|string|max:50|unique:products,code',
            'name'                => 'required|string|max:200',
            'category_id'         => 'nullable|exists:categories,id',
            'uom_id'              => 'required|exists:uoms,id',
            'uom_purchase_id'     => 'nullable|exists:uoms,id',
            'weight'              => 'nullable|numeric|min:0',
            'volume'              => 'nullable|numeric|min:0',
            'barcode'             => 'nullable|string|max:100|unique:products,barcode',
            'min_stock'           => 'nullable|numeric|min:0',
            'max_stock'           => 'nullable|numeric|min:0',
            'alert_before_expiry' => 'nullable|integer|min:0',
            'tracking_type'       => 'required|in:1,2,3,4',
            'stock_rotation'      => 'required|in:1,2,3',
            'description'         => 'nullable|string',
            'status'              => 'required|in:0,1',
        ], $this->messages());

        $data = $request->except(['_token', 'image']);

        // Upload ảnh nếu có
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        $data['code'] = strtoupper(trim($request->code));

        Product::create($data);

        return redirect()->route('master.product.index')
            ->with('success', "Đã thêm hàng hóa \"{$request->name}\" thành công.");
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'code'                => "required|string|max:50|unique:products,code,{$product->id}",
            'name'                => 'required|string|max:200',
            'category_id'         => 'nullable|exists:categories,id',
            'uom_id'              => 'required|exists:uoms,id',
            'uom_purchase_id'     => 'nullable|exists:uoms,id',
            'weight'              => 'nullable|numeric|min:0',
            'volume'              => 'nullable|numeric|min:0',
            'barcode'             => "nullable|string|max:100|unique:products,barcode,{$product->id}",
            'min_stock'           => 'nullable|numeric|min:0',
            'max_stock'           => 'nullable|numeric|min:0',
            'alert_before_expiry' => 'nullable|integer|min:0',
            'tracking_type'       => 'required|in:1,2,3,4',
            'stock_rotation'      => 'required|in:1,2,3',
            'description'         => 'nullable|string',
            'status'              => 'required|in:0,1',
        ], $this->messages());

        $data = $request->except(['_token', '_method', 'image']);
        $data['code'] = strtoupper(trim($request->code));

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        $product->update($data);

        return redirect()->route('master.product.index')
            ->with('success', "Đã cập nhật hàng hóa \"{$product->name}\" thành công.");
    }

    public function destroy(Product $product)
    {
        if ($product->stocks()->exists()) {
            return redirect()->route('master.product.index')
                ->with('error', "Không thể xóa \"{$product->name}\" vì đang có tồn kho.");
        }

        $name = $product->name;
        $product->delete();

        return redirect()->route('master.product.index')
            ->with('success', "Đã xóa hàng hóa \"{$name}\" thành công.");
    }

    private function messages(): array
    {
        return [
            'code.required'    => 'Vui lòng nhập mã hàng hóa.',
            'code.unique'      => 'Mã hàng hóa đã tồn tại.',
            'name.required'    => 'Vui lòng nhập tên hàng hóa.',
            'uom_id.required'  => 'Vui lòng chọn đơn vị tính.',
            'barcode.unique'   => 'Barcode đã được sử dụng.',
            'tracking_type.required' => 'Vui lòng chọn kiểu theo dõi.',
        ];
    }
}
