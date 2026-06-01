<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Uom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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

    /**
     * Sinh barcode tự động — trả về JSON
     * GET /master/product/generate-barcode
     */
    public function generateBarcode()
    {
        // Tạo EAN-13 style barcode: prefix 200 (nội bộ) + timestamp ngẫu nhiên
        do {
            $barcode = '200' . str_pad(random_int(0, 9999999999), 10, '0', STR_PAD_LEFT);
            // Tính check digit EAN-13
            $barcode = $this->appendEan13Check($barcode);
        } while (Product::where('barcode', $barcode)->exists());

        return response()->json(['barcode' => $barcode]);
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
            'image'               => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ], $this->messages());

        $data = $request->except(['_token', 'image']);
        $data['code'] = strtoupper(trim($request->code));

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

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
            'image'               => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ], $this->messages());

        $data = $request->except(['_token', '_method', 'image']);
        $data['code'] = strtoupper(trim($request->code));

        if ($request->hasFile('image')) {
            // Xóa ảnh cũ nếu có
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        // Xóa ảnh nếu user tick "xóa ảnh"
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
        if ($product->stocks()->exists()) {
            return redirect()->route('master.product.index')
                ->with('error', "Không thể xóa \"{$product->name}\" vì đang có tồn kho.");
        }

        // Xóa ảnh khi xóa product
        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }

        $name = $product->name;
        $product->delete();

        return redirect()->route('master.product.index')
            ->with('success', "Đã xóa hàng hóa \"{$name}\" thành công.");
    }

    // ===== PRIVATE HELPERS =====

    /**
     * Tính check digit EAN-13 và gắn vào chuỗi 12 số
     */
    private function appendEan13Check(string $twelveDigits): string
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int) $twelveDigits[$i];
            $sum  += ($i % 2 === 0) ? $digit : $digit * 3;
        }
        $check = (10 - ($sum % 10)) % 10;
        return $twelveDigits . $check;
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
            'image.image'      => 'File ảnh không hợp lệ.',
            'image.max'        => 'Ảnh không được vượt quá 2MB.',
        ];
    }
}