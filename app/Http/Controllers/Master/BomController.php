<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Bom;
use App\Models\BomDetail;
use App\Models\Product;
use App\Models\Uom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class BomController extends Controller
{
    public function index(Request $request)
    {
        $query = Bom::withCount(['consumeLines', 'produceLines']);

        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->status !== null && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $boms        = $query->orderBy('code')->paginate(15)->withQueryString();
        $totalCount  = Bom::count();
        $activeCount = Bom::where('status', 1)->count();

        return view('master.bom.index', compact('boms', 'totalCount', 'activeCount'));
    }

    /**
     * Form tạo mới — trang riêng vì có inline detail
     */
    public function create()
    {
        Gate::authorize('master.create');

        $products = Product::active()->orderBy('code')->get();
        $uoms     = Uom::active()->orderBy('name')->get();

        return view('master.bom.form', compact('products', 'uoms'));
    }

    public function store(Request $request)
    {
        Gate::authorize('master.create');

        $this->validateBom($request);
        $this->validateDetails($request);
        $this->validateNoCircularReference($request);

        DB::transaction(function () use ($request) {
            $bom = Bom::create([
                'code'   => strtoupper(trim($request->code)),
                'name'   => $request->name,
                'type'   => $request->type,
                'note'   => $request->note,
                'status' => $request->status,
            ]);

            $this->syncDetails($bom, $request);
        });

        return redirect()->route('master.bom.index')
            ->with('success', "Đã thêm BOM \"{$request->name}\" thành công.");
    }

    /**
     * Form chỉnh sửa — trang riêng
     */
    public function edit(Bom $bom)
    {
        Gate::authorize('master.edit');

        $bom->load(['details.product', 'details.uom']);
        $products = Product::active()->orderBy('code')->get();
        $uoms     = Uom::active()->orderBy('name')->get();

        return view('master.bom.form', compact('bom', 'products', 'uoms'));
    }

    public function update(Request $request, Bom $bom)
    {
        Gate::authorize('master.edit');

        $this->validateBom($request, $bom->id);
        $this->validateDetails($request);
        $this->validateNoCircularReference($request, $bom->id);

        DB::transaction(function () use ($request, $bom) {
            $bom->update([
                'code'   => strtoupper(trim($request->code)),
                'name'   => $request->name,
                'type'   => $request->type,
                'note'   => $request->note,
                'status' => $request->status,
            ]);

            // Xóa toàn bộ detail cũ rồi insert lại
            $bom->details()->delete();
            $this->syncDetails($bom, $request);
        });

        return redirect()->route('master.bom.index')
            ->with('success', "Đã cập nhật BOM \"{$bom->name}\" thành công.");
    }

    public function destroy(Bom $bom)
    {
        Gate::authorize('master.delete');

        $name = $bom->name;
        $bom->delete(); // cascade xóa bom_details

        return redirect()->route('master.bom.index')
            ->with('success', "Đã xóa BOM \"{$name}\" thành công.");
    }

    // ===== PRIVATE HELPERS =====

    private function validateBom(Request $request, ?int $ignoreId = null): void
    {
        $uniqueCode = $ignoreId
            ? "required|string|max:50|unique:boms,code,{$ignoreId}"
            : 'required|string|max:50|unique:boms,code';

        $request->validate([
            'code'   => $uniqueCode,
            'name'   => 'required|string|max:200',
            'type'   => 'required|in:1,2',
            'status' => 'required|in:0,1',
        ], [
            'code.required' => 'Vui lòng nhập mã BOM.',
            'code.unique'   => 'Mã BOM đã tồn tại.',
            'name.required' => 'Vui lòng nhập tên công thức.',
            'type.required' => 'Vui lòng chọn loại BOM.',
        ]);
    }

    private function validateDetails(Request $request): void
    {
        $request->validate([
            'details'                  => 'required|array|min:2',
            'details.*.product_id'     => 'required|exists:products,id',
            'details.*.line_type'      => 'required|in:1,2',
            'details.*.qty'            => 'required|numeric|min:0.001',
            'details.*.uom_id'         => 'required|exists:uoms,id',
        ], [
            'details.required'              => 'BOM phải có ít nhất 1 dòng Consume và 1 dòng Produce.',
            'details.min'                   => 'BOM phải có ít nhất 2 dòng.',
            'details.*.product_id.required' => 'Vui lòng chọn hàng hóa.',
            'details.*.qty.required'        => 'Vui lòng nhập số lượng.',
            'details.*.qty.min'             => 'Số lượng phải lớn hơn 0.',
            'details.*.uom_id.required'     => 'Vui lòng chọn đơn vị tính.',
        ]);

        // Phải có ít nhất 1 Consume và 1 Produce
        $types = collect($request->details)->pluck('line_type')->map(fn($v) => (int) $v);

        if (!$types->contains(BomDetail::TYPE_CONSUME) || !$types->contains(BomDetail::TYPE_PRODUCE)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'details' => 'BOM phải có ít nhất 1 dòng Consume (đầu vào) và 1 dòng Produce (đầu ra).',
            ]);
        }
    }

    /**
     * Validate ngăn chặn cấu hình vòng lặp (circular reference) trong BOM.
     *
     * Gọi Bom::detectCircularReference() — hàm đệ quy DFS trên graph BOM.
     * Nếu phát hiện cycle, abort 422 với thông báo chi tiết path.
     */
    private function validateNoCircularReference(Request $request, ?int $currentBomId = null): void
    {
        $details = collect($request->details);

        $produceIds = $details
            ->where('line_type', BomDetail::TYPE_PRODUCE)
            ->pluck('product_id')
            ->map('intval')
            ->unique()
            ->values()
            ->toArray();

        $consumeIds = $details
            ->where('line_type', BomDetail::TYPE_CONSUME)
            ->pluck('product_id')
            ->map('intval')
            ->unique()
            ->values()
            ->toArray();

        $result = Bom::detectCircularReference(
            produceProductIds: $produceIds,
            consumeProductIds: $consumeIds,
            excludeBomId:      $currentBomId
        );

        if ($result['has_cycle']) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'details' => "Phát hiện vòng lặp trong cấu hình BOM. {$result['path']}",
            ]);
        }
    }

    private function syncDetails(Bom $bom, Request $request): void
    {
        $rows = [];
        foreach ($request->details as $line) {
            $rows[] = [
                'bom_id'     => $bom->id,
                'product_id' => $line['product_id'],
                'line_type'  => $line['line_type'],
                'qty'        => $line['qty'],
                'uom_id'     => $line['uom_id'],
                'note'       => $line['note'] ?? null,
            ];
        }
        BomDetail::insert($rows);
    }
}