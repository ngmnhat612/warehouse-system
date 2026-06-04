<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $query = Supplier::query();

        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('tax_code', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->status !== null && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $suppliers   = $query->orderBy('code')->paginate(15)->withQueryString();
        $totalCount  = Supplier::count();
        $activeCount = Supplier::where('status', 1)->count();

        return view('master.supplier.index', compact('suppliers', 'totalCount', 'activeCount'));
    }

    public function store(Request $request)
    {
        Gate::authorize('master.create');

        $request->validate([
            'code'     => 'required|string|max:50|unique:suppliers,code',
            'name'     => 'required|string|max:200',
            'tax_code' => 'nullable|string|max:20|unique:suppliers,tax_code',
            'phone'    => 'nullable|string|max:20',
            'email'    => 'nullable|email|max:200',
            'address'  => 'nullable|string|max:500',
            'status'   => 'required|in:0,1',
        ], $this->messages());

        Supplier::create([
            'code'     => strtoupper(trim($request->code)),
            'name'     => $request->name,
            'tax_code' => $request->tax_code,
            'phone'    => $request->phone,
            'email'    => $request->email,
            'address'  => $request->address,
            'status'   => $request->status,
        ]);

        return redirect()->route('master.supplier.index')
            ->with('success', "Đã thêm nhà cung cấp \"{$request->name}\" thành công.");
    }

    public function update(Request $request, Supplier $supplier)
    {
        Gate::authorize('master.edit');

        $request->validate([
            'code'     => "required|string|max:50|unique:suppliers,code,{$supplier->id}",
            'name'     => 'required|string|max:200',
            'tax_code' => "nullable|string|max:20|unique:suppliers,tax_code,{$supplier->id}",
            'phone'    => 'nullable|string|max:20',
            'email'    => 'nullable|email|max:200',
            'address'  => 'nullable|string|max:500',
            'status'   => 'required|in:0,1',
        ], $this->messages());

        $supplier->update([
            'code'     => strtoupper(trim($request->code)),
            'name'     => $request->name,
            'tax_code' => $request->tax_code,
            'phone'    => $request->phone,
            'email'    => $request->email,
            'address'  => $request->address,
            'status'   => $request->status,
        ]);

        return redirect()->route('master.supplier.index')
            ->with('success', "Đã cập nhật nhà cung cấp \"{$supplier->name}\" thành công.");
    }

    public function destroy(Supplier $supplier)
    {
        Gate::authorize('master.delete');
        
        if ($supplier->stockReceipts()->exists()) {
            return redirect()->route('master.supplier.index')
                ->with('error', "Không thể xóa \"{$supplier->name}\" vì đang có phiếu nhập kho liên quan.");
        }

        $name = $supplier->name;
        $supplier->delete();

        return redirect()->route('master.supplier.index')
            ->with('success', "Đã xóa nhà cung cấp \"{$name}\" thành công.");
    }

    private function messages(): array
    {
        return [
            'code.required'    => 'Vui lòng nhập mã nhà cung cấp.',
            'code.unique'      => 'Mã nhà cung cấp đã tồn tại.',
            'name.required'    => 'Vui lòng nhập tên nhà cung cấp.',
            'tax_code.unique'  => 'Mã số thuế đã tồn tại.',
            'email.email'      => 'Email không hợp lệ.',
        ];
    }
}