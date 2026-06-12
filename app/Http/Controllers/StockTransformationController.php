<?php

namespace App\Http\Controllers;

use App\Models\Bom;
use App\Models\Location;
use App\Models\StockTransformation;
use App\Models\StockTransformationDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class StockTransformationController extends Controller
{
    // ── Danh sách ─────────────────────────────────────────────
    public function index(Request $request)
    {
        Gate::authorize('transformation.view');

        $query = StockTransformation::with(['createdBy', 'bom']);

        if ($request->filled('search')) {
            $query->where('code', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('transformation_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('transformation_date', '<=', $request->date_to);
        }

        $transformations = $query->latest()->paginate(20)->withQueryString();

        return view('transformations.index', [
            'transformations' => $transformations,
            'totalCount'      => StockTransformation::count(),
            'pendingCount'    => StockTransformation::where('status', StockTransformation::STATUS_PENDING)->count(),
            'completedCount'  => StockTransformation::where('status', StockTransformation::STATUS_COMPLETED)->count(),
            'cancelledCount'  => StockTransformation::where('status', StockTransformation::STATUS_CANCELLED)->count(),
        ]);
    }

    // ── Form tạo mới ──────────────────────────────────────────
    public function create()
    {
        Gate::authorize('transformation.create');

        return view('transformations.form', $this->formData());
    }

    // ── Lưu mới ───────────────────────────────────────────────
    public function store(Request $request)
    {
        Gate::authorize('transformation.create');

        $this->validateForm($request);

        DB::transaction(function () use ($request) {
            $tf = StockTransformation::create([
                'code'                => $request->code ?: $this->generateCode(),
                'type'                => $request->type,
                'bom_id'              => $request->bom_id,
                'multiplier'          => $request->multiplier ?? 1,
                'transformation_date' => $request->transformation_date,
                'note'                => $request->note,
                'status'              => StockTransformation::STATUS_DRAFT,
                'created_by'          => Auth::id(),
            ]);

            $this->syncDetails($tf, $request);
        });

        if ($request->action === 'save_and_new') {
            return redirect()->route('transformations.create')
                ->with('success', 'Đã tạo phiếu thành công.');
        }

        return redirect()->route('transformations.index')
            ->with('success', 'Đã tạo phiếu tách/ghép thành công.');
    }

    // ── Form chỉnh sửa ────────────────────────────────────────
    public function edit(StockTransformation $transformation)
    {
        Gate::authorize('transformation.create');

        if ((int) $transformation->status !== StockTransformation::STATUS_DRAFT) {
            return redirect()->route('transformations.show', $transformation)
                ->with('error', 'Chỉ có thể chỉnh sửa phiếu ở trạng thái Nháp.');
        }

        $transformation->load([
            'bom.consumeLines.product.uom',
            'bom.consumeLines.product.stocks',
            'bom.consumeLines.product.lots.serials',
            'bom.produceLines.product.uom',
            'consumeDetails.product.uom',
            'consumeDetails.product.stocks',
            'consumeDetails.product.lots.serials',
            'consumeDetails.uom',
            'consumeDetails.location',
            'consumeDetails.lot.serials',
            'consumeDetails.serial',
            'produceDetails.product.uom',
            'produceDetails.uom',
            'produceDetails.location',
            'produceDetails.lot',
            'produceDetails.serial',
        ]);

        return view('transformations.form', array_merge(
            $this->formData(),
            ['transformation' => $transformation]
        ));
    }

    // ── Cập nhật ──────────────────────────────────────────────
    public function update(Request $request, StockTransformation $transformation)
    {
        Gate::authorize('transformation.create');

        if ((int) $transformation->status !== StockTransformation::STATUS_DRAFT) {
            return redirect()->route('transformations.show', $transformation)
                ->with('error', 'Chỉ có thể chỉnh sửa phiếu ở trạng thái Nháp.');
        }

        $this->validateForm($request, isEdit: true);

        DB::transaction(function () use ($request, $transformation) {
            $transformation->update([
                'multiplier'          => $request->multiplier ?? 1,
                'transformation_date' => $request->transformation_date,
                'note'                => $request->note,
            ]);

            $transformation->details()->delete();
            $this->syncDetails($transformation, $request);
        });

        return redirect()->route('transformations.show', $transformation)
            ->with('success', 'Đã cập nhật phiếu thành công.');
    }

    // ── Chi tiết ──────────────────────────────────────────────
    public function show(StockTransformation $transformation)
    {
        Gate::authorize('transformation.view');

        $transformation->load([
            'bom', 'createdBy', 'confirmedBy',
            'consumeDetails.product.uom',
            'consumeDetails.uom',
            'consumeDetails.location',
            'consumeDetails.lot',
            'consumeDetails.serial',
            'produceDetails.product.uom',
            'produceDetails.uom',
            'produceDetails.location',
            'produceDetails.lot',
            'produceDetails.serial',
        ]);

        return view('transformations.show', compact('transformation'));
    }

    // ── Gửi duyệt (staff được phép) ───────────────────────────
    public function submit(StockTransformation $transformation)
    {
        Gate::authorize('transformation.create');

        if ((int) $transformation->status !== StockTransformation::STATUS_DRAFT) {
            return redirect()->route('transformations.show', $transformation)
                ->with('error', 'Chỉ có thể gửi duyệt phiếu đang ở trạng thái Nháp.');
        }

        if ($transformation->details()->count() === 0) {
            return redirect()->route('transformations.show', $transformation)
                ->with('error', 'Phiếu chưa có hàng hóa.');
        }

        $stockErrors = $this->checkStock($transformation);
        if (!empty($stockErrors)) {
            return redirect()->route('transformations.show', $transformation)
                ->with('error', 'Không đủ tồn kho: ' . implode('; ', $stockErrors));
        }

        $transformation->update(['status' => StockTransformation::STATUS_PENDING]);

        return redirect()->route('transformations.show', $transformation)
            ->with('success', "Phiếu {$transformation->code} đã được gửi duyệt.");
    }

    // ── Duyệt (chỉ manager) ────────────────────────────────────
    public function approve(StockTransformation $transformation)
    {
        Gate::authorize('transformation.approve');

        if ((int) $transformation->status !== StockTransformation::STATUS_PENDING) {
            return redirect()->route('transformations.show', $transformation)
                ->with('error', 'Chỉ có thể duyệt phiếu đang ở trạng thái Chờ duyệt.');
        }

        $stockErrors = $this->checkStock($transformation);
        if (!empty($stockErrors)) {
            return redirect()->route('transformations.show', $transformation)
                ->with('error', 'Không đủ tồn kho: ' . implode('; ', $stockErrors));
        }

        try {
            DB::transaction(function () use ($transformation) {
                $stockService = app(\App\Services\StockService::class);
                $transformation->loadMissing('consumeDetails');

                foreach ($transformation->consumeDetails as $d) {
                    $stockService->reserve([
                        'product_id'       => $d->product_id,
                        'location_id'      => $d->location_id,
                        'lot_id'           => $d->lot_id,
                        'serial_id'        => $d->serial_id,
                        'quantity'         => (float) $d->quantity,
                        'transaction_type' => \App\Services\StockService::TYPE_SPLIT,
                        'reference_id'     => $transformation->id,
                        'reference_type'   => 'stock_transformation',
                        'reference_code'   => $transformation->code,
                    ]);
                }

                $transformation->update([
                    'status'       => StockTransformation::STATUS_APPROVED,
                    'confirmed_by' => Auth::id(),
                ]);
            });
        } catch (\Exception $e) {
            return redirect()->route('transformations.show', $transformation)
                ->with('error', 'Không thể duyệt phiếu: ' . $e->getMessage());
        }

        return redirect()->route('transformations.show', $transformation)
            ->with('success', "Phiếu {$transformation->code} đã được duyệt. Tồn kho đầu vào đã được giữ chỗ.");
    }

    // ── Xác nhận thực hiện ────────────────────────────────────
    public function confirm(StockTransformation $transformation)
    {
        Gate::authorize('transformation.confirm');

        if ((int) $transformation->status !== StockTransformation::STATUS_APPROVED) {
            return redirect()->route('transformations.show', $transformation)
                ->with('error', 'Chỉ có thể xác nhận phiếu đang ở trạng thái Đã duyệt.');
        }

        try {
            DB::transaction(function () use ($transformation) {
                $stockService = app(\App\Services\StockService::class);
                $transformation->loadMissing('consumeDetails');

                // Giải phóng reserved_qty trước khi decrease thật
                foreach ($transformation->consumeDetails as $d) {
                    try {
                        $stockService->release([
                            'product_id'       => $d->product_id,
                            'location_id'      => $d->location_id,
                            'lot_id'           => $d->lot_id,
                            'serial_id'        => $d->serial_id,
                            'quantity'         => (float) $d->quantity,
                            'transaction_type' => \App\Services\StockService::TYPE_SPLIT,
                            'reference_id'     => $transformation->id,
                            'reference_type'   => 'stock_transformation',
                            'reference_code'   => $transformation->code,
                        ]);
                    } catch (\Exception $e) {
                        // Bỏ qua nếu reserved_qty đã được giải phóng từ trước
                    }
                }

                // Trừ tồn consume + cộng tồn produce + tạo Lot/Serial mới
                $stockService->applyTransformation($transformation);

                $transformation->update(['status' => StockTransformation::STATUS_COMPLETED]);
            });
        } catch (\Exception $e) {
            return redirect()->route('transformations.show', $transformation)
                ->with('error', 'Lỗi khi xác nhận: ' . $e->getMessage());
        }

        return redirect()->route('transformations.show', $transformation)
            ->with('success', "Phiếu {$transformation->code} đã hoàn thành và tồn kho được cập nhật.");
    }

    // ── Hủy ───────────────────────────────────────────────────
    public function cancel(StockTransformation $transformation)
    {
        Gate::authorize('transformation.create');

        if (!in_array((int) $transformation->status, [
            StockTransformation::STATUS_DRAFT,
            StockTransformation::STATUS_PENDING,
            StockTransformation::STATUS_APPROVED,
        ])) {
            return redirect()->route('transformations.show', $transformation)
                ->with('error', 'Không thể hủy phiếu ở trạng thái hiện tại.');
        }

        try {
            DB::transaction(function () use ($transformation) {
                // Nếu đã Duyệt → phải giải phóng reserved_qty trước khi hủy
                if ((int) $transformation->status === StockTransformation::STATUS_APPROVED) {
                    $stockService = app(\App\Services\StockService::class);
                    $transformation->loadMissing('consumeDetails');

                    foreach ($transformation->consumeDetails as $d) {
                        try {
                            $stockService->release([
                                'product_id'       => $d->product_id,
                                'location_id'      => $d->location_id,
                                'lot_id'           => $d->lot_id,
                                'serial_id'        => $d->serial_id,
                                'quantity'         => (float) $d->quantity,
                                'transaction_type' => \App\Services\StockService::TYPE_SPLIT,
                                'reference_id'     => $transformation->id,
                                'reference_type'   => 'stock_transformation',
                                'reference_code'   => $transformation->code,
                            ]);
                        } catch (\Exception $e) {
                            // Bỏ qua nếu reserved_qty đã được giải phóng
                        }
                    }
                }

                $transformation->update(['status' => StockTransformation::STATUS_CANCELLED]);
            });
        } catch (\Exception $e) {
            return redirect()->route('transformations.show', $transformation)
                ->with('error', 'Không thể hủy phiếu: ' . $e->getMessage());
        }

        return redirect()->route('transformations.index')
            ->with('success', "Đã hủy phiếu {$transformation->code}.");
    }

    // ── Xóa vĩnh viễn (chỉ khi Nháp) ─────────────────────────
    public function destroy(StockTransformation $transformation)
    {
        Gate::authorize('transformation.create');

        if ((int) $transformation->status !== StockTransformation::STATUS_DRAFT) {
            return redirect()->route('transformations.show', $transformation)
                ->with('error', 'Chỉ có thể xóa phiếu ở trạng thái Nháp.');
        }

        $code = $transformation->code;

        DB::transaction(function () use ($transformation) {
            $transformation->details()->delete();
            $transformation->delete();
        });

        return redirect()->route('transformations.index')
            ->with('success', "Đã xóa phiếu {$code}.");
    }

    // ── In phiếu PDF ──────────────────────────────────────────
    public function print(StockTransformation $transformation)
    {
        Gate::authorize('transformation.view');

        if ((int) $transformation->status !== StockTransformation::STATUS_COMPLETED) {
            return redirect()->route('transformations.show', $transformation)
                ->with('error', 'Chỉ có thể in phiếu đã hoàn thành.');
        }

        $transformation->load([
            'bom', 'createdBy', 'confirmedBy',
            'consumeDetails.product.uom', 'consumeDetails.uom',
            'consumeDetails.location', 'consumeDetails.lot', 'consumeDetails.serial',
            'produceDetails.product.uom', 'produceDetails.uom',
            'produceDetails.location', 'produceDetails.lot', 'produceDetails.serial',
        ]);

        return view('transformations.print', compact('transformation'));
    }

    /**
     * Trả về danh sách vị trí có tồn kho của sản phẩm (dùng cho AJAX).
     * GET /api/locations-by-product/{product}
     */
    public function locationsByProduct(\App\Models\Product $product): \Illuminate\Http\JsonResponse
    {
        $locations = \App\Models\Stock::with('location')
            ->where('product_id', $product->id)
            ->where('quantity', '>', 0)
            ->get()
            ->map(fn($s) => [
                'id'   => $s->location_id,
                'code' => $s->location?->code ?? "#{$s->location_id}",
                'name' => $s->location?->name ?? '',
                'qty'  => (float) $s->quantity,
            ])
            ->unique('id')
            ->values();
 
        return response()->json(['locations' => $locations]);
    }

    // ── Helpers ───────────────────────────────────────────────

    private function formData(): array
    {
        return [
            'locations' => Location::orderBy('code')->get(),
            'boms'      => Bom::with([
                'consumeLines.product.uom',
                'consumeLines.product.stocks',
                'consumeLines.product.lots' => fn($q) => $q->where('status', 1),
                'consumeLines.product.lots.serials' => fn($q) => $q->where('status', 1),
                'consumeLines.uom',
                'produceLines.product.uom',
                'produceLines.uom',
            ])->active()->orderBy('code')->get(),
        ];
    }

    private function generateCode(): string
    {
        $prefix = 'TG-' . date('Ym') . '-';
        $last   = StockTransformation::where('code', 'like', $prefix . '%')
                    ->orderByDesc('code')->value('code');
        $seq    = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    private function validateForm(Request $request, bool $isEdit = false): void
    {
        $rules = [
            'bom_id'                => 'required|exists:boms,id',
            'multiplier'            => 'required|numeric|min:0.001',
            'transformation_date'   => 'required|date',
            'consume'               => 'required|array|min:1',
            'consume.*.product_id'  => 'required|exists:products,id',
            'consume.*.uom_id'      => 'required|exists:uoms,id',
            'consume.*.quantity'    => 'required|numeric|min:0.001',
            'consume.*.location_id' => 'required|exists:locations,id',
            'produce'               => 'required|array|min:1',
            'produce.*.product_id'  => 'required|exists:products,id',
            'produce.*.uom_id'      => 'required|exists:uoms,id',
            'produce.*.quantity'    => 'required|numeric|min:0.001',
            'produce.*.location_id' => 'required|exists:locations,id',
        ];

        if (!$isEdit) {
            $rules['type'] = 'required|in:1,2';
            $rules['code'] = 'nullable|string|max:50|unique:stock_transformations,code';
        }

        $request->validate($rules);
    }

    private function syncDetails(StockTransformation $tf, Request $request): void
    {
        foreach ($request->input('consume', []) as $row) {
            $tf->details()->create([
                'product_id'  => $row['product_id'],
                'uom_id'      => $row['uom_id'],
                'quantity'    => $row['quantity'],
                'location_id' => $row['location_id'],
                'lot_id'      => $row['lot_id'] ?? null,
                'serial_id'   => $row['serial_id'] ?? null,
                'bom_qty'     => $row['bom_qty'] ?? null,
                'direction'   => StockTransformationDetail::DIR_CONSUME,
            ]);
        }

        foreach ($request->input('produce', []) as $row) {
            $tf->details()->create([
                'product_id'    => $row['product_id'],
                'uom_id'        => $row['uom_id'],
                'quantity'      => $row['quantity'],
                'location_id'   => $row['location_id'],
                'lot_number'    => $row['lot_number'] ?? null,
                'serial_number' => $row['serial_number'] ?? null,
                'expiry_date'   => $row['expiry_date'] ?? null,
                'bom_qty'       => $row['bom_qty'] ?? null,
                'direction'     => StockTransformationDetail::DIR_PRODUCE,
            ]);
        }
    }

    private function checkStock(StockTransformation $transformation): array
    {
        $errors = [];

        $transformation->load('consumeDetails.product.stocks');

        foreach ($transformation->consumeDetails as $d) {
            $available = (float) ($d->product?->stocks->sum(fn($s) => $s->quantity - $s->reserved_qty) ?? 0);
            if ($d->quantity > $available) {
                $errors[] = sprintf(
                    '%s: cần %s, tồn %s',
                    $d->product?->name ?? "SP#{$d->product_id}",
                    number_format($d->quantity, 3),
                    number_format($available, 3)
                );
            }
        }

        return $errors;
    }
}