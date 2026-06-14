<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Lot;
use App\Models\Product;
use App\Models\Scrap;
use App\Models\ScrapDetail;
use App\Models\Serial;
use App\Models\Stock;
use App\Models\Uom;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;

class ScrapController extends Controller
{
    public function __construct(private StockService $stockService) {}

    // ──────────────────────────────────────────────────────────────────────────
    // DANH SÁCH
    // ──────────────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        Gate::authorize('scrap.view');

        $query = Scrap::with(['createdBy'])->withCount('details');

        if ($search = $request->search) {
            $query->where('code', 'like', "%{$search}%");
        }
        if ($request->status !== null && $request->status !== '') {
            $query->where('status', $request->status);
        }
        if ($request->date_from) $query->where('scrap_date', '>=', $request->date_from);
        if ($request->date_to)   $query->where('scrap_date', '<=', $request->date_to);

        $scraps         = $query->orderByDesc('created_at')->paginate(20)->withQueryString();
        $totalCount     = Scrap::count();
        $pendingCount   = Scrap::where('status', Scrap::STATUS_PENDING)->count();
        $completedCount = Scrap::where('status', Scrap::STATUS_COMPLETED)->count();
        $cancelledCount = Scrap::where('status', Scrap::STATUS_CANCELLED)->count();

        return view('scrap.index', compact(
            'scraps', 'totalCount', 'pendingCount', 'completedCount', 'cancelledCount'
        ));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // FORM TẠO MỚI
    // ──────────────────────────────────────────────────────────────────────────

    public function create()
    {
        Gate::authorize('scrap.create');

        [$products, $productsJson, $locations, $locationsJson, $uoms] = $this->formData();
        return view('scrap.form', compact('products', 'productsJson', 'locations', 'locationsJson', 'uoms'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // LƯU PHIẾU MỚI
    // ──────────────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        Gate::authorize('scrap.create');

        $this->validateScrap($request);

        DB::transaction(function () use ($request) {
            $scrap = Scrap::create([
                'code'       => $request->code ? strtoupper(trim($request->code)) : $this->generateCode(),
                'scrap_date' => $request->scrap_date,
                'status'     => Scrap::STATUS_DRAFT,
                'note'       => $request->note ?: null,
                'created_by' => Auth::id(),
            ]);
            $this->saveDetails($scrap, $request->details ?? []);
        });

        return redirect()->route('scraps.index')->with('success', 'Đã tạo phiếu hủy hàng thành công.');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // XEM CHI TIẾT
    // ──────────────────────────────────────────────────────────────────────────

    public function show(Scrap $scrap)
    {
        Gate::authorize('scrap.view');

        $scrap->load(['createdBy', 'approvedBy', 'details.product.uom', 'details.location', 'details.lot', 'details.serial', 'details.uom']);
        return view('scrap.show', compact('scrap'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // IN PHIẾU HỦY HÀNG (PDF / Browser Print)
    // ──────────────────────────────────────────────────────────────────────────

    public function printPdf(Scrap $scrap)
    {
        Gate::authorize('scrap.view');

        $scrap->load([
            'createdBy',
            'approvedBy',
            'details.product.uom',
            'details.location',
            'details.lot',
            'details.serial',
            'details.uom',
        ]);

        return view('scrap.print', compact('scrap'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // FORM CHỈNH SỬA
    // ──────────────────────────────────────────────────────────────────────────

    public function edit(Scrap $scrap)
    {
        Gate::authorize('scrap.create');

        if ($scrap->status !== Scrap::STATUS_DRAFT) {
            return redirect()->route('scraps.show', $scrap)
                ->with('error', 'Chỉ có thể chỉnh sửa phiếu ở trạng thái Nháp.');
        }
        $scrap->load(['details.product', 'details.location', 'details.lot', 'details.serial', 'details.uom']);
        [$products, $productsJson, $locations, $locationsJson, $uoms] = $this->formData();
        return view('scrap.form', compact('scrap', 'products', 'productsJson', 'locations', 'locationsJson', 'uoms'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // CẬP NHẬT PHIẾU
    // ──────────────────────────────────────────────────────────────────────────

    public function update(Request $request, Scrap $scrap)
    {
        Gate::authorize('scrap.create');

        if ($scrap->status !== Scrap::STATUS_DRAFT) {
            return redirect()->route('scraps.show', $scrap)
                ->with('error', 'Chỉ có thể chỉnh sửa phiếu ở trạng thái Nháp.');
        }
        $this->validateScrap($request, isUpdate: true);
        DB::transaction(function () use ($request, $scrap) {
            $scrap->update(['scrap_date' => $request->scrap_date, 'note' => $request->note ?: null]);
            $scrap->details()->delete();
            $this->saveDetails($scrap, $request->details ?? []);
        });
        return redirect()->route('scraps.show', $scrap)
            ->with('success', "Đã cập nhật phiếu {$scrap->code} thành công.");
    }

    // ──────────────────────────────────────────────────────────────────────────
    // XÓA PHIẾU (chỉ Draft)
    // ──────────────────────────────────────────────────────────────────────────

    public function destroy(Scrap $scrap)
    {
        Gate::authorize('scrap.create');

        if ($scrap->status !== Scrap::STATUS_DRAFT) {
            return redirect()->route('scraps.index')
                ->with('error', "Không thể xóa phiếu {$scrap->code} vì không ở trạng thái Nháp.");
        }
        $code = $scrap->code;
        DB::transaction(function () use ($scrap) {
            $scrap->details()->delete();
            $scrap->delete();
        });
        return redirect()->route('scraps.index')->with('success', "Đã xóa phiếu {$code} thành công.");
    }

    // ──────────────────────────────────────────────────────────────────────────
    // DRAFT → PENDING
    // ──────────────────────────────────────────────────────────────────────────

    public function submit(Scrap $scrap)
    {
        Gate::authorize('scrap.create');

        if ($scrap->status !== Scrap::STATUS_DRAFT) {
            return redirect()->route('scraps.show', $scrap)
                ->with('error', 'Chỉ có thể gửi duyệt phiếu đang ở trạng thái Nháp.');
        }
        if ($scrap->details()->count() === 0) {
            return redirect()->route('scraps.show', $scrap)
                ->with('error', 'Phiếu chưa có hàng hóa. Vui lòng thêm ít nhất một dòng.');
        }
        $scrap->update(['status' => Scrap::STATUS_PENDING]);
        return redirect()->route('scraps.show', $scrap)
            ->with('success', "Phiếu {$scrap->code} đã được gửi duyệt.");
    }

    // PENDING (2) → APPROVED (3): Duyệt phiếu + giữ chỗ tồn kho
    public function approve(Scrap $scrap)
    {
        Gate::authorize('scrap.approve');

        if ($scrap->status !== Scrap::STATUS_PENDING) {
            return redirect()->route('scraps.show', $scrap)
                ->with('error', 'Chỉ có thể duyệt phiếu đang ở trạng thái Chờ duyệt.');
        }

        $scrap->load('details');

        try {
            DB::transaction(function () use ($scrap) {
                foreach ($scrap->details as $detail) {
                    if ($detail->quantity <= 0) continue;

                    $this->stockService->reserve([
                        'product_id'       => $detail->product_id,
                        'location_id'      => $detail->location_id,
                        'lot_id'           => $detail->lot_id,
                        'serial_id'        => $detail->serial_id,
                        'quantity'         => (float) $detail->quantity,
                        'transaction_type' => StockService::TYPE_SCRAP,
                        'reference_id'     => $scrap->id,
                        'reference_type'   => 'scrap',
                        'reference_code'   => $scrap->code,
                    ]);
                }

                $scrap->update([
                    'status'      => Scrap::STATUS_APPROVED,
                    'approved_by' => Auth::id(),
                ]);
            });
        } catch (\Exception $e) {
            return redirect()->route('scraps.show', $scrap)
                ->with('error', 'Không đủ tồn kho để giữ chỗ: ' . $e->getMessage());
        }

        return redirect()->route('scraps.show', $scrap)
            ->with('success', "Phiếu {$scrap->code} đã được duyệt. Tồn kho 'Đang giữ' đã được cập nhật. Vui lòng xác nhận để trừ tồn kho.");
    }

    // APPROVED (3) → COMPLETED (4): Giải phóng giữ chỗ + Trừ kho + Cộng kho ảo [SCRAP] + Serial
    public function confirm(Scrap $scrap)
    {
        Gate::authorize('scrap.confirm');

        if ($scrap->status !== Scrap::STATUS_APPROVED) {
            return redirect()->route('scraps.show', $scrap)
                ->with('error', 'Chỉ có thể xác nhận phiếu đang ở trạng thái Đã duyệt.');
        }

        $scrapLocation = Location::where('type', Location::TYPE_SCRAP)
            ->whereNull('parent_id')
            ->first();

        if (! $scrapLocation) {
            return redirect()->route('scraps.show', $scrap)
                ->with('error', 'Không tìm thấy vị trí kho ảo kiểu Scrap. Vui lòng liên hệ quản trị viên.');
        }

        $scrap->load('details.product');

        try {
            DB::transaction(function () use ($scrap, $scrapLocation) {
                foreach ($scrap->details as $detail) {
                    if ($detail->quantity <= 0) continue;

                    $baseParams = [
                        'product_id'       => $detail->product_id,
                        'lot_id'           => $detail->lot_id,
                        'serial_id'        => $detail->serial_id ?? null,
                        'transaction_type' => StockService::TYPE_SCRAP,
                        'reference_id'     => $scrap->id,
                        'reference_type'   => 'scrap',
                        'reference_code'   => $scrap->code,
                        'note'             => "Hủy hàng - phiếu {$scrap->code}" . ($detail->reason ? ". Lý do: {$detail->reason}" : ''),
                        'created_by'       => Auth::id(),
                    ];

                    // Giải phóng reserved_qty trước khi decrease thật
                    try {
                        $this->stockService->release([
                            'product_id'       => $detail->product_id,
                            'location_id'      => $detail->location_id,
                            'lot_id'           => $detail->lot_id,
                            'serial_id'        => $detail->serial_id,
                            'quantity'         => (float) $detail->quantity,
                            'transaction_type' => StockService::TYPE_SCRAP,
                            'reference_id'     => $scrap->id,
                            'reference_type'   => 'scrap',
                            'reference_code'   => $scrap->code,
                        ]);
                    } catch (\Exception $e) {
                        // Bỏ qua nếu reserved_qty đã được giải phóng từ trước
                    }

                    $this->stockService->decrease(array_merge($baseParams, [
                        'location_id' => $detail->location_id,
                        'quantity'    => $detail->quantity,
                    ]));

                    $this->stockService->increase(array_merge($baseParams, [
                        'location_id' => $scrapLocation->id,
                        'quantity'    => $detail->quantity,
                    ]));

                    if (in_array($detail->product?->tracking_type, [
                        Product::TRACKING_SERIAL,
                        Product::TRACKING_LOT_AND_SERIAL,
                    ]) && $detail->serial_id) {
                        Serial::where('id', $detail->serial_id)
                            ->update(['status' => Serial::STATUS_DEFECTIVE]);
                    }
                }

                $scrap->update(['status' => Scrap::STATUS_COMPLETED]);
            });
        } catch (\Exception $e) {
            return redirect()->route('scraps.show', $scrap)
                ->with('error', 'Lỗi khi xác nhận hủy hàng: ' . $e->getMessage());
        }

        return redirect()->route('scraps.show', $scrap)
            ->with('success', "Phiếu {$scrap->code} đã hoàn thành. Tồn kho đã được trừ.");
    }

    // HỦY PHIẾU
    public function cancel(Scrap $scrap)
    {
        Gate::authorize('scrap.create');

        if ($scrap->status === Scrap::STATUS_COMPLETED) {
            return redirect()->route('scraps.show', $scrap)
                ->with('error', 'Không thể hủy phiếu đã hoàn thành.');
        }
        if ($scrap->status === Scrap::STATUS_CANCELLED) {
            return redirect()->route('scraps.show', $scrap)
                ->with('error', 'Phiếu đã được hủy trước đó.');
        }

        try {
            DB::transaction(function () use ($scrap) {
                // Nếu đã Duyệt → phải giải phóng reserved_qty trước khi hủy
                if ($scrap->status === Scrap::STATUS_APPROVED) {
                    $scrap->load('details');

                    foreach ($scrap->details as $detail) {
                        if ($detail->quantity <= 0) continue;

                        try {
                            $this->stockService->release([
                                'product_id'       => $detail->product_id,
                                'location_id'      => $detail->location_id,
                                'lot_id'           => $detail->lot_id,
                                'serial_id'        => $detail->serial_id,
                                'quantity'         => (float) $detail->quantity,
                                'transaction_type' => StockService::TYPE_SCRAP,
                                'reference_id'     => $scrap->id,
                                'reference_type'   => 'scrap',
                                'reference_code'   => $scrap->code,
                            ]);
                        } catch (\Exception $e) {
                            // Bỏ qua nếu reserved_qty đã được giải phóng
                        }
                    }
                }

                $scrap->update(['status' => Scrap::STATUS_CANCELLED]);
            });
        } catch (\Exception $e) {
            return redirect()->route('scraps.show', $scrap)
                ->with('error', 'Không thể hủy phiếu: ' . $e->getMessage());
        }

        return redirect()->route('scraps.show', $scrap)
            ->with('success', "Đã hủy phiếu {$scrap->code}.");
    }

    // ──────────────────────────────────────────────────────────────────────────
    // API: Lấy vị trí tồn kho có hàng của một sản phẩm (kèm Lot/Serial)
    // GET /scraps/stock-locations/{productId}
    // ──────────────────────────────────────────────────────────────────────────

    public function stockLocations(int $productId)
    {
        Gate::authorize('scrap.view');
        
        $stocks = Stock::with(['location', 'lot', 'serial'])
            ->where('product_id', $productId)
            ->whereHas('location', function ($q) {
                $q->where('type', Location::TYPE_INTERNAL);
            })
            ->where(function ($q) {
                $q->where('available_qty', '>', 0)
                ->orWhereRaw('(quantity - reserved_qty) > 0');
            })
            ->get()
            ->map(fn($s) => [
                'location_id'   => $s->location_id,
                'location_code' => $s->location?->code ?? '?',
                'location_name' => $s->location?->name ?? '',
                'lot_id'        => $s->lot_id,
                'lot_number'    => $s->lot?->lot_number,
                'expiry_date'   => $s->lot?->expiry_date?->format('Y-m-d'),
                'serial_id'     => $s->serial_id,
                'serial_number' => $s->serial?->serial_number,
                'available_qty' => (float) $s->available_qty,
            ]);

        return response()->json($stocks);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ──────────────────────────────────────────────────────────────────────────

    private function formData(): array
    {
        $products     = Product::with('uom')->where('status', 1)->orderBy('code')->get();
        $productsJson = $products->map(fn($p) => [
            'id'       => $p->id,
            'code'     => $p->code,
            'name'     => $p->name,
            'uom'      => $p->uom?->name ?? '—',
            'uom_id'   => $p->uom_id,
            'tracking' => (int) ($p->tracking_type ?? 1), // 1=none, 2=lot, 3=serial, 4=lot+serial
        ])->values();

        $locations     = Location::where('type', 1)->orderBy('code')->get();
        $locationsJson = $locations->map(fn($l) => [
            'id'   => $l->id,
            'code' => $l->code,
            'name' => $l->name ?? '',
        ])->values();

        $uoms = Uom::orderBy('name')->get();

        return [$products, $productsJson, $locations, $locationsJson, $uoms];
    }

    private function validateScrap(Request $request, bool $isUpdate = false): void
    {
        $request->validate([
            'code'                       => $isUpdate ? 'nullable|string|max:50' : 'nullable|string|max:50|unique:scraps,code',
            'scrap_date'                 => 'required|date',
            'note'                       => 'nullable|string|max:1000',
            'details'                    => 'required|array|min:1',
            'details.*.product_id'       => 'required|exists:products,id',
            'details.*.uom_id'           => 'required|exists:uoms,id',
            'details.*.quantity'         => 'required|numeric|min:0.001',
            'details.*.location_id'      => 'required|exists:locations,id',
            'details.*.lot_id'           => 'nullable|exists:lots,id',
            'details.*.serial_id'        => 'nullable|exists:serials,id',
            'details.*.reason'           => 'nullable|string|max:200',
        ], [
            'code.unique'                    => 'Mã phiếu đã tồn tại.',
            'scrap_date.required'            => 'Vui lòng chọn ngày hủy.',
            'details.required'               => 'Phiếu hủy phải có ít nhất một hàng hóa.',
            'details.*.product_id.required'  => 'Vui lòng chọn hàng hóa.',
            'details.*.uom_id.required'      => 'Vui lòng chọn đơn vị tính.',
            'details.*.quantity.required'    => 'Vui lòng nhập số lượng.',
            'details.*.quantity.min'         => 'Số lượng phải lớn hơn 0.',
            'details.*.location_id.required' => 'Vui lòng chọn vị trí kho.',
        ]);

        // Kiểm tra serial trùng trong cùng một phiếu (theo product_id)
        $seen = []; // [product_id__serial_id => rowIndex]
        foreach (($request->details ?? []) as $i => $row) {
            $serialId = $row['serial_id'] ?? null;
            if (!$serialId) continue;
            $key = ($row['product_id'] ?? '') . '__' . $serialId;
            if (isset($seen[$key])) {
                $firstRow = $seen[$key] + 1;
                $curRow   = $i + 1;
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "details.{$i}.serial_id" => "Dòng {$curRow}: Serial đã được chọn ở dòng {$firstRow} trong cùng phiếu.",
                ]);
            }
            $seen[$key] = $i;
        }
    }

    private function saveDetails(Scrap $scrap, array $details): void
    {
        foreach ($details as $row) {
            if (empty($row['product_id']) || empty($row['quantity'])) continue;

            $product  = Product::find($row['product_id']);
            $tracking = (int) ($product?->tracking_type ?? Product::TRACKING_NONE);

            // Chỉ lưu lot_id/serial_id khi tracking_type yêu cầu
            $lotId    = in_array($tracking, [Product::TRACKING_LOT, Product::TRACKING_LOT_AND_SERIAL])
                        ? ($row['lot_id'] ?: null) : null;
            $serialId = in_array($tracking, [Product::TRACKING_SERIAL, Product::TRACKING_LOT_AND_SERIAL])
                        ? ($row['serial_id'] ?: null) : null;

            ScrapDetail::create([
                'scrap_id'    => $scrap->id,
                'product_id'  => $row['product_id'],
                'uom_id'      => $row['uom_id'],
                'lot_id'      => $lotId,
                'serial_id'   => $serialId,
                'location_id' => $row['location_id'],
                'quantity'    => $row['quantity'],
                'reason'      => $row['reason'] ?: null,
            ]);
        }
    }

    private function generateCode(): string
    {
        $prefix = 'HH-' . now()->format('Ym') . '-';
        $last   = Scrap::where('code', 'like', $prefix . '%')->orderByDesc('code')->value('code');
        $seq    = $last ? ((int) substr($last, -4)) + 1 : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}