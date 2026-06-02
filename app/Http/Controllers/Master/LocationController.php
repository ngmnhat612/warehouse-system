<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────
    //  INDEX — Table view (existing, unchanged logic)
    // ─────────────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $query = Location::with('parent');

        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->where('name',    'like', "%{$search}%")
                  ->orWhere('code',    'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->parent_id === 'root') {
            $query->whereNull('parent_id');
        } elseif ($request->parent_id) {
            $query->where('parent_id', $request->parent_id);
        }

        if ($request->status !== null && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $locations     = $query->orderBy('type')->orderBy('code')->paginate(20)->withQueryString();
        $totalCount    = Location::count();
        $activeCount   = Location::where('status', 1)->count();
        $internalCount = Location::where('type', Location::TYPE_INTERNAL)->count();

        // Build tree roots (dùng cho tab Tree view)
        $treeRoots = $this->buildTree();

        // Chỉ lấy vị trí Internal làm parent (vị trí ảo không có con)
        $parentOptions = Location::where('type', Location::TYPE_INTERNAL)
                                 ->active()
                                 ->orderBy('code')
                                 ->get();

        return view('master.location.index', compact(
            'locations', 'totalCount', 'activeCount', 'internalCount',
            'parentOptions', 'treeRoots'
        ));
    }

    // ─────────────────────────────────────────────────────────────────────
    //  TREE — Build nested collection (roots + eager-loaded children)
    //  Returns: Collection<Location> (chỉ các node gốc, con đã được load)
    // ─────────────────────────────────────────────────────────────────────
    private function buildTree(): \Illuminate\Support\Collection
    {
        // Eager-load toàn bộ cây đệ quy (children.children.children...)
        $all = Location::with('children')->orderBy('type')->orderBy('code')->get();

        // Chỉ giữ lại các node gốc (parent_id = null)
        return $all->whereNull('parent_id')->values();
    }

    // ─────────────────────────────────────────────────────────────────────
    //  BARCODE — In nhãn barcode cho một vị trí
    //  Route: GET /master/location/{location}/barcode
    //  Name:  master.location.barcode
    // ─────────────────────────────────────────────────────────────────────
    public function barcode(Location $location)
    {
        $location->load('parent');

        return view('master.location.barcode', compact('location'));
    }

    // ─────────────────────────────────────────────────────────────────────
    //  STORE
    // ─────────────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'code'           => 'required|string|max:50|unique:locations,code',
            'name'           => 'required|string|max:100',
            'type'           => 'required|in:1,2,3,4,5',
            'parent_id'      => 'nullable|exists:locations,id',
            'barcode'        => 'nullable|string|max:100|unique:locations,barcode',
            'capacity_limit' => 'nullable|numeric|min:0',
            'status'         => 'required|in:0,1',
        ], $this->messages());

        if ($request->type > 1 && $request->parent_id) {
            return redirect()->route('master.location.index')
                ->with('error', 'Vị trí ảo (Virtual) không thể có vị trí cha.');
        }

        Location::create([
            'code'           => strtoupper(trim($request->code)),
            'name'           => $request->name,
            'type'           => $request->type,
            'parent_id'      => ($request->type == 1) ? ($request->parent_id ?: null) : null,
            'barcode'        => $request->barcode,
            'capacity_limit' => $request->capacity_limit,
            'status'         => $request->status,
        ]);

        return redirect()->route('master.location.index')
            ->with('success', "Đã thêm vị trí \"{$request->name}\" thành công.");
    }

    // ─────────────────────────────────────────────────────────────────────
    //  UPDATE
    // ─────────────────────────────────────────────────────────────────────
    public function update(Request $request, Location $location)
    {
        $request->validate([
            'code'           => "required|string|max:50|unique:locations,code,{$location->id}",
            'name'           => 'required|string|max:100',
            'type'           => 'required|in:1,2,3,4,5',
            'parent_id'      => 'nullable|exists:locations,id',
            'barcode'        => "nullable|string|max:100|unique:locations,barcode,{$location->id}",
            'capacity_limit' => 'nullable|numeric|min:0',
            'status'         => 'required|in:0,1',
        ], $this->messages());

        if ($request->parent_id) {
            $descendantIds = $location->getDescendantIds();
            if ($request->parent_id == $location->id || in_array($request->parent_id, $descendantIds)) {
                return redirect()->route('master.location.index')
                    ->with('error', 'Không thể chọn vị trí con làm vị trí cha.');
            }
        }

        $location->update([
            'code'           => strtoupper(trim($request->code)),
            'name'           => $request->name,
            'type'           => $request->type,
            'parent_id'      => ($request->type == 1) ? ($request->parent_id ?: null) : null,
            'barcode'        => $request->barcode,
            'capacity_limit' => $request->capacity_limit,
            'status'         => $request->status,
        ]);

        return redirect()->route('master.location.index')
            ->with('success', "Đã cập nhật vị trí \"{$location->name}\" thành công.");
    }

    // ─────────────────────────────────────────────────────────────────────
    //  DESTROY
    // ─────────────────────────────────────────────────────────────────────
    public function destroy(Location $location)
    {
        if ($location->hasChildren()) {
            return redirect()->route('master.location.index')
                ->with('error', "Không thể xóa \"{$location->name}\" vì có vị trí con.");
        }

        if ($location->hasStock()) {
            return redirect()->route('master.location.index')
                ->with('error', "Không thể xóa \"{$location->name}\" vì đang có tồn kho.");
        }

        $systemCodes = ['VIRTUAL-SUP', 'VIRTUAL-CUS', 'VIRTUAL-SCR', 'VIRTUAL-QUA', 'WH'];
        if (in_array($location->code, $systemCodes)) {
            return redirect()->route('master.location.index')
                ->with('error', "Không thể xóa vị trí hệ thống \"{$location->name}\".");
        }

        $name = $location->name;
        $location->delete();

        return redirect()->route('master.location.index')
            ->with('success', "Đã xóa vị trí \"{$name}\" thành công.");
    }

    // ─────────────────────────────────────────────────────────────────────
    private function messages(): array
    {
        return [
            'code.required'  => 'Vui lòng nhập mã vị trí.',
            'code.unique'    => 'Mã vị trí đã tồn tại.',
            'name.required'  => 'Vui lòng nhập tên vị trí.',
            'type.required'  => 'Vui lòng chọn loại vị trí.',
            'barcode.unique' => 'Barcode vị trí đã tồn tại.',
        ];
    }
}