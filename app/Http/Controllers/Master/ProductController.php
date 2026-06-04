<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Uom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Gate;

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

    /**
     * Sinh barcode tự động — trả về JSON
     */
    public function generateBarcode()
    {
        do {
            $twelveDigits = '200' . str_pad(random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
            $barcode = $this->appendEan13Check($twelveDigits);
        } while (Product::where('barcode', $barcode)->exists());

        return response()->json(['barcode' => $barcode]);
    }

    public function store(Request $request)
    {
        Gate::authorize('master.create');

        $validator = Validator::make($request->all(), [
            'code'                => 'required|string|max:50|unique:products,code',
            'name'                => 'required|string|max:200',
            'category_id'         => 'nullable|exists:categories,id',
            'uom_id'              => 'required|exists:uoms,id',
            'uom_purchase_id'     => 'nullable|exists:uoms,id',
            'weight'              => 'nullable|numeric|min:0',
            'volume'              => 'nullable|numeric|min:0',
            'barcode'             => 'nullable|string|max:100|unique:products,barcode',
            'alert_before_expiry' => 'nullable|integer|min:0',
            'tracking_type'       => 'required|in:1,2,3,4',
            'stock_rotation'      => 'required|in:1,2,3',
            'description'         => 'nullable|string',
            'status'              => 'required|in:0,1',
            'image'               => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ], $this->messages());

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('product_form_action', 'create');
        }

        $data = $request->except(['_token', 'image']);
        $data['code'] = strtoupper(trim($request->code));

        // Không lưu min_stock/max_stock — dùng reorder_rules thay thế
        unset($data['min_stock'], $data['max_stock']);

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->storeImage(
                $request->file('image'),
                $request->name,
                $request->code
            );
        }

        $product = Product::create($data);

        // Chuyển sang trang reorder rule với product đã chọn sẵn
        return redirect()
            ->route('master.reorder_rule.index', ['new_product_id' => $product->id])
            ->with('success', "Đã thêm hàng hóa \"{$product->name}\" thành công.")
            ->with('suggest_reorder_rule', true)
            ->with('new_product_name', $product->name);
    }

    public function update(Request $request, Product $product)
    {
        Gate::authorize('master.edit');

        $validator = Validator::make($request->all(), [
            'code'                => "required|string|max:50|unique:products,code,{$product->id}",
            'name'                => 'required|string|max:200',
            'category_id'         => 'nullable|exists:categories,id',
            'uom_id'              => 'required|exists:uoms,id',
            'uom_purchase_id'     => 'nullable|exists:uoms,id',
            'weight'              => 'nullable|numeric|min:0',
            'volume'              => 'nullable|numeric|min:0',
            'barcode'             => "nullable|string|max:100|unique:products,barcode,{$product->id}",
            'alert_before_expiry' => 'nullable|integer|min:0',
            'tracking_type'       => 'required|in:1,2,3,4',
            'stock_rotation'      => 'required|in:1,2,3',
            'description'         => 'nullable|string',
            'status'              => 'required|in:0,1',
            'image'               => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ], $this->messages());

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('product_form_action', 'update:' . $product->id);
        }

        $data = $request->except(['_token', '_method', 'image', 'remove_image']);

        // Readonly fields
        $data['code']    = $product->code;
        $data['barcode'] = $product->barcode;

        // Không lưu min_stock/max_stock
        unset($data['min_stock'], $data['max_stock']);

        if ($request->hasFile('image')) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            $data['image_path'] = $this->storeImage(
                $request->file('image'),
                $request->name,
                $product->code
            );
        }

        if ($request->boolean('remove_image') && $product->image_path) {
            Storage::disk('public')->delete($product->image_path);
            $data['image_path'] = null;
        }

        $product->update($data);

        return redirect()->route('master.product.index')
            ->with('success', "Đã cập nhật hàng hóa \"{$product->name}\" thành công.");
    }

    public function destroy(Product $product)
    {
        Gate::authorize('master.delete');

        if ($product->stocks()->exists()) {
            return redirect()->route('master.product.index')
                ->with('error', "Không thể xóa \"{$product->name}\" vì đang có tồn kho.");
        }

        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }

        $name = $product->name;
        $product->delete();

        return redirect()->route('master.product.index')
            ->with('success', "Đã xóa hàng hóa \"{$name}\" thành công.");
    }

    // ===== PRIVATE HELPERS =====

    private function appendEan13Check(string $twelveDigits): string
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int) $twelveDigits[$i];
            $sum += ($i % 2 === 0) ? $digit * 1 : $digit * 3;
        }
        $check = (10 - ($sum % 10)) % 10;
        return $twelveDigits . $check;
    }

    private function messages(): array
    {
        return [
            'code.required'          => 'Vui lòng nhập mã hàng hóa.',
            'code.unique'            => 'Mã hàng hóa đã tồn tại.',
            'name.required'          => 'Vui lòng nhập tên hàng hóa.',
            'uom_id.required'        => 'Vui lòng chọn đơn vị tính.',
            'barcode.unique'         => 'Barcode đã được sử dụng.',
            'tracking_type.required' => 'Vui lòng chọn kiểu theo dõi.',
            'image.image'            => 'File ảnh không hợp lệ.',
            'image.max'              => 'Ảnh không được vượt quá 2MB.',
        ];
    }

    private function storeImage($file, string $productName, string $productCode): string
    {
        $extension = $file->getClientOriginalExtension();
        $slug      = Str::slug($productName);
        $filename  = $slug . '_' . strtoupper($productCode) . '.' . $extension;
        return $file->storeAs('products', $filename, 'public');
    }
}