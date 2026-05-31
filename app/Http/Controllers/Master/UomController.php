<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Uom;
use Illuminate\Http\Request;

class UomController extends Controller
{
    /**
     * Danh sách đơn vị tính (có search + filter + pagination)
     */
    public function index(Request $request)
    {
        $query = Uom::query();

        // Tìm kiếm theo tên
        if ($search = $request->search) {
            $query->where('name', 'like', "%{$search}%");
        }

        // Lọc theo trạng thái
        if ($request->status !== null && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $uoms        = $query->orderBy('name')->paginate(15)->withQueryString();
        $totalCount  = Uom::count();
        $activeCount = Uom::where('status', 1)->count();

        return view('master.uom.index', compact('uoms', 'totalCount', 'activeCount'));
    }

    /**
     * Tạo mới đơn vị tính
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'   => 'required|string|max:50|unique:uoms,name',
            'status' => 'required|in:0,1',
        ], [
            'name.required' => 'Vui lòng nhập tên đơn vị tính.',
            'name.max'      => 'Tên đơn vị tính không quá 50 ký tự.',
            'name.unique'   => 'Tên đơn vị tính đã tồn tại.',
            'status.required' => 'Vui lòng chọn trạng thái.',
        ]);

        Uom::create([
            'name'   => $request->name,
            'status' => $request->status,
        ]);

        return redirect()->route('uom.index')
            ->with('success', "Đã thêm đơn vị tính \"{$request->name}\" thành công.");
    }

    /**
     * Cập nhật đơn vị tính
     */
    public function update(Request $request, Uom $uom)
    {
        $request->validate([
            'name'   => "required|string|max:50|unique:uoms,name,{$uom->id}",
            'status' => 'required|in:0,1',
        ], [
            'name.required' => 'Vui lòng nhập tên đơn vị tính.',
            'name.max'      => 'Tên đơn vị tính không quá 50 ký tự.',
            'name.unique'   => 'Tên đơn vị tính đã tồn tại.',
        ]);

        $uom->update([
            'name'   => $request->name,
            'status' => $request->status,
        ]);

        return redirect()->route('uom.index')
            ->with('success', "Đã cập nhật đơn vị tính \"{$uom->name}\" thành công.");
    }

    /**
     * Xóa đơn vị tính
     */
    public function destroy(Uom $uom)
    {
        // Kiểm tra xem đơn vị tính có đang được dùng không
        if ($uom->products()->exists()) {
            return redirect()->route('uom.index')
                ->with('error', "Không thể xóa \"{$uom->name}\" vì đang được sử dụng bởi hàng hóa.");
        }

        $name = $uom->name;
        $uom->delete();

        return redirect()->route('uom.index')
            ->with('success', "Đã xóa đơn vị tính \"{$name}\" thành công.");
    }
}
