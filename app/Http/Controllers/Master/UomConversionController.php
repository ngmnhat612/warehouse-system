<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Uom;
use App\Models\UomConversion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UomConversionController extends Controller
{
    public function index(Request $request)
    {
        $query = UomConversion::with(['fromUom', 'toUom']);

        if ($search = $request->search) {
            $query->whereHas('fromUom', fn($q) => $q->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('toUom',  fn($q) => $q->where('name', 'like', "%{$search}%"));
        }

        if ($request->status !== null && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $conversions = $query->orderBy('from_uom_id')->paginate(15)->withQueryString();
        $totalCount  = UomConversion::count();
        $activeCount = UomConversion::where('status', 1)->count();
        $uoms        = Uom::active()->orderBy('name')->get();

        return view('master.uom_conversion.index', compact(
            'conversions', 'totalCount', 'activeCount', 'uoms'
        ));
    }

    public function store(Request $request)
    {
        Gate::authorize('master.create');

        $request->validate([
            'from_uom_id' => 'required|exists:uoms,id',
            'to_uom_id'   => [
                'required',
                'exists:uoms,id',
                'different:from_uom_id',
                // Không trùng cặp đã tồn tại
                function ($attr, $value, $fail) use ($request) {
                    $exists = UomConversion::where('from_uom_id', $request->from_uom_id)
                                           ->where('to_uom_id', $value)
                                           ->exists();
                    if ($exists) {
                        $fail('Quy đổi giữa hai đơn vị này đã tồn tại.');
                    }
                },
            ],
            'factor' => 'required|numeric|min:0.000001',
            'status' => 'required|in:0,1',
        ], [
            'from_uom_id.required'  => 'Vui lòng chọn đơn vị nguồn.',
            'to_uom_id.required'    => 'Vui lòng chọn đơn vị đích.',
            'to_uom_id.different'   => 'Đơn vị nguồn và đích không được giống nhau.',
            'factor.required'       => 'Vui lòng nhập hệ số quy đổi.',
            'factor.min'            => 'Hệ số phải lớn hơn 0.',
        ]);

        UomConversion::create($request->only('from_uom_id', 'to_uom_id', 'factor', 'status'));

        return redirect()->route('master.uom_conversion.index')
            ->with('success', 'Đã thêm quy đổi đơn vị thành công.');
    }

    public function update(Request $request, UomConversion $uom_conversion)
    {
        Gate::authorize('master.edit');

        $request->validate([
            'from_uom_id' => 'required|exists:uoms,id',
            'to_uom_id'   => [
                'required',
                'exists:uoms,id',
                'different:from_uom_id',
                function ($attr, $value, $fail) use ($request, $uom_conversion) {
                    $exists = UomConversion::where('from_uom_id', $request->from_uom_id)
                                           ->where('to_uom_id', $value)
                                           ->where('id', '!=', $uom_conversion->id)
                                           ->exists();
                    if ($exists) {
                        $fail('Quy đổi giữa hai đơn vị này đã tồn tại.');
                    }
                },
            ],
            'factor' => 'required|numeric|min:0.000001',
            'status' => 'required|in:0,1',
        ], [
            'from_uom_id.required' => 'Vui lòng chọn đơn vị nguồn.',
            'to_uom_id.required'   => 'Vui lòng chọn đơn vị đích.',
            'to_uom_id.different'  => 'Đơn vị nguồn và đích không được giống nhau.',
            'factor.required'      => 'Vui lòng nhập hệ số quy đổi.',
            'factor.min'           => 'Hệ số phải lớn hơn 0.',
        ]);

        $uom_conversion->update($request->only('from_uom_id', 'to_uom_id', 'factor', 'status'));

        return redirect()->route('master.uom_conversion.index')
            ->with('success', 'Đã cập nhật quy đổi đơn vị thành công.');
    }

    public function destroy(UomConversion $uom_conversion)
    {
        Gate::authorize('master.delete');
        
        $uom_conversion->delete();

        return redirect()->route('master.uom_conversion.index')
            ->with('success', 'Đã xóa quy đổi đơn vị thành công.');
    }
}