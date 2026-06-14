@extends('layouts.app')

@section('title', (isset($transformation) ? 'Sửa phiếu' : 'Tạo phiếu') . ' tách/ghép hàng hóa — Warehouse System')

@section('breadcrumb')
  <li class="breadcrumb-item">Nghiệp vụ kho</li>
  <li class="breadcrumb-item"><a href="{{ route('transformations.index') }}">Tách / Ghép hàng hóa</a></li>
  <li class="breadcrumb-item active">{{ isset($transformation) ? $transformation->code : 'Tạo mới' }}</li>
@endsection

@section('content')

@php
  $isEdit = isset($transformation);
  $action = $isEdit ? route('transformations.update', $transformation->id) : route('transformations.store');
@endphp

{{-- HEADER --}}
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="mb-0 fw-semibold">
      {{ $isEdit ? 'Sửa phiếu tách/ghép hàng hóa' : 'Tạo phiếu tách/ghép hàng hóa mới' }}
    </h4>
    <small class="text-body-secondary">
      {{ $isEdit ? $transformation->code : 'Chọn BOM để tự động điền danh sách hàng hóa' }}
    </small>
  </div>
  <a href="{{ route('transformations.index') }}" class="btn btn-outline-secondary">
    <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-left') }}"></use></svg>
    Quay lại
  </a>
</div>

<form method="POST" action="{{ $action }}" id="transformationForm">
  @csrf
  @if($isEdit) @method('PUT') @endif

  <div class="row g-4">

    {{-- ── CỘT TRÁI ─────────────────────────────────────────────── --}}
    <div class="col-lg-4">
      <div class="card mb-4">
        <div class="card-header fw-semibold">
          <svg class="icon me-1 text-primary"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-description') }}"></use></svg>
          Thông tin phiếu
        </div>
        <div class="card-body">

          {{-- Mã phiếu --}}
          <div class="mb-3">
            <label class="form-label form-label-sm">Mã phiếu</label>
            <input type="text"
                   class="form-control form-control-sm text-uppercase @error('code') is-invalid @enderror"
                   name="code"
                   value="{{ old('code', $transformation->code ?? '') }}"
                   placeholder="Tự sinh nếu trống"
                   maxlength="50"
                   {{ $isEdit ? 'readonly' : '' }}>
            @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          {{-- BOM — BẮT BUỘC --}}
          <div class="mb-3">
            <label class="form-label form-label-sm">
              Công thức BOM <span class="text-danger">*</span>
            </label>
            <select class="form-select form-select-sm @error('bom_id') is-invalid @enderror"
                    name="bom_id" id="bomSelect" required
                    onchange="onBomChange(this)"
                    {{ $isEdit ? 'style=pointer-events:none;background:var(--cui-secondary-bg)' : '' }}>
              <option value="">— Chọn công thức BOM —</option>
              @foreach($boms as $bom)
                <option value="{{ $bom->id }}"
                  data-type="{{ $bom->type }}"
                  {{ old('bom_id', $transformation->bom_id ?? '') == $bom->id ? 'selected' : '' }}>
                  {{ $bom->code }} — {{ $bom->name }}
                  ({{ $bom->type == 1 ? 'Tách hàng' : 'Ghép hàng' }})
                </option>
              @endforeach
            </select>

            @error('bom_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text">Loại BOM quyết định Tách / Ghép hàng hóa.</div>
          </div>

          {{-- Loại phiếu (readonly, từ BOM) --}}
          <div class="mb-3" id="typeDisplay" style="{{ old('bom_id', $transformation->bom_id ?? '') ? '' : 'display:none' }}">
            <label class="form-label form-label-sm">Loại thao tác</label>
            <div id="typeTag" class="d-inline-flex align-items-center gap-1 px-2 py-1 rounded-2 border small fw-medium">
              —
            </div>
            <input type="hidden" name="type" id="typeHidden" value="{{ old('type', $transformation->type ?? '') }}">
          </div>

          {{-- Số lượng tách/ghép --}}
          <div class="mb-3">
            <label class="form-label form-label-sm">
              Hệ số thực hiện <span class="text-danger">*</span>
            </label>
            <div class="input-group input-group-sm">
              <input type="number" class="form-control @error('multiplier') is-invalid @enderror"
                     name="multiplier" id="multiplierInput"
                     value="{{ old('multiplier', $transformation->multiplier ?? 1) }}"
                     min="0.001" step="0.001" required
                     oninput="onMultiplierChange()">
              <span class="input-group-text">× BOM</span>
            </div>
            @error('multiplier')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text">
              Ví dụ: BOM có 10 kg → hệ số 2 → cần 20 kg đầu vào.
            </div>
          </div>

          {{-- Ngày thực hiện --}}
          <div class="mb-3">
            <label class="form-label form-label-sm">Ngày thực hiện <span class="text-danger">*</span></label>
            <input type="date"
                   class="form-control form-control-sm @error('transformation_date') is-invalid @enderror"
                   name="transformation_date"
                   value="{{ old('transformation_date', isset($transformation->transformation_date)
                     ? \Carbon\Carbon::parse($transformation->transformation_date)->format('Y-m-d')
                     : date('Y-m-d')) }}"
                   required>
            @error('transformation_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          {{-- Ghi chú --}}
          <div class="mb-0">
            <label class="form-label form-label-sm">Ghi chú</label>
            <textarea class="form-control form-control-sm" name="note" rows="3"
                      placeholder="Lý do tách/ghép, ghi chú thêm...">{{ old('note', $transformation->note ?? '') }}</textarea>
          </div>

        </div>
      </div>

      {{-- Nút lưu --}}
      <div class="d-grid gap-2">
        <button type="submit" class="btn btn-primary" name="action" value="save">
          <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-save') }}"></use></svg>
          {{ $isEdit ? 'Cập nhật phiếu' : 'Lưu phiếu' }}
        </button>
        @if(!$isEdit)
          <button type="submit" class="btn btn-outline-primary" name="action" value="save_and_new">
            <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use></svg>
            Lưu &amp; tạo phiếu mới
          </button>
        @endif
        <a href="{{ route('transformations.index') }}" class="btn btn-outline-secondary">Hủy</a>
      </div>
    </div>

    {{-- ── CỘT PHẢI ─────────────────────────────────────────────── --}}
    <div class="col-lg-8">

      {{-- Placeholder khi chưa chọn BOM --}}
      <div id="bomPlaceholder" class="{{ old('bom_id', $transformation->bom_id ?? '') ? 'd-none' : '' }}">
        <div class="card">
          <div class="card-body text-center py-5 text-body-secondary">
            <svg class="icon icon-3xl d-block mx-auto mb-3 opacity-25">
              <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-layers') }}"></use>
            </svg>
            <p class="mb-0">Vui lòng chọn <strong>Công thức BOM</strong> ở bên trái<br>để hiển thị danh sách hàng hóa đầu vào / đầu ra.</p>
          </div>
        </div>
      </div>

      {{-- Bảng hàng hóa (hiện sau khi chọn BOM) --}}
      <div id="detailPanel" class="{{ old('bom_id', $transformation->bom_id ?? '') ? '' : 'd-none' }}">

        {{-- ── ĐẦU VÀO ──────────────────────────────────────────── --}}
        <div class="card mb-4">
          <div class="card-header fw-semibold">
            <svg class="icon me-1 text-danger"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-thick-bottom') }}"></use></svg>
            Hàng hóa đầu vào
            <span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle ms-1" id="consumeBadge">—</span>
          </div>
          <div class="card-body p-0">
            <div id="lotSerialAlertConsumeContainer"></div>
            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0" id="consumeTable">
                <thead class="table-light">
                  <tr>
                    <th style="width:32px" class="text-center">#</th>
                    <th>Hàng hóa</th>
                    <th style="width:72px">ĐVT</th>
                    <th style="width:115px">SL theo BOM</th>
                    <th style="width:115px">SL thực tế <span class="text-danger">*</span></th>
                    <th style="width:85px">Tồn kho</th>
                    <th style="width:140px">Vị trí <span class="text-danger">*</span></th>
                    <th style="width:120px">
                      Số Lot
                      <svg class="icon icon-sm text-body-secondary" data-coreui-toggle="tooltip" title="Bắt buộc với hàng theo Lô hoặc Lô+Serial">
                        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-info') }}"></use>
                      </svg>
                    </th>
                    <th style="width:120px">
                      Số Serial
                      <svg class="icon icon-sm text-body-secondary" data-coreui-toggle="tooltip" title="Bắt buộc với hàng theo Serial hoặc Lô+Serial">
                        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-info') }}"></use>
                      </svg>
                    </th>
                  </tr>
                </thead>
                <tbody id="consumeBody">
                  {{-- Điền bởi JS hoặc server khi edit --}}
                  @if($isEdit)
                    @foreach($transformation->consumeDetails as $i => $d)
                      @php $tracking = (int)($d->product?->tracking_type ?? 1); @endphp
                      <tr>
                        <td class="text-center text-body-secondary small">{{ $i+1 }}</td>
                        <td>
                          <div class="fw-medium small">{{ $d->product?->name }}</div>
                          <div class="text-body-secondary" style="font-size:.75rem">{{ $d->product?->code }}</div>
                          <input type="hidden" name="consume[{{ $i }}][product_id]" value="{{ $d->product_id }}">
                          <input type="hidden" name="consume[{{ $i }}][tracking]" value="{{ $tracking }}">
                        </td>
                        <td>
                          <input type="hidden" name="consume[{{ $i }}][uom_id]" value="{{ $d->uom_id }}">
                          <span class="text-body-secondary small">{{ $d->uom?->name ?? '—' }}</span>
                        </td>
                        <td class="text-end text-body-secondary small">
                          {{ number_format($d->bom_qty ?? $d->quantity, 3) }}
                        </td>
                        <td>
                          <input type="number"
                                 class="form-control form-control-sm text-end qty-consume @error('consume.'.$i.'.quantity') is-invalid @enderror"
                                 name="consume[{{ $i }}][quantity]"
                                 value="{{ old('consume.'.$i.'.quantity', $d->quantity) }}"
                                 min="0.001" step="0.001" required
                                 oninput="updateSummary()" data-stock="{{ $d->product?->stocks->sum('quantity') ?? 0 }}">
                        </td>
                        <td class="text-end small">
                          @php $stock = $d->product?->stocks->sum('quantity') ?? 0 @endphp
                          <span class="{{ $stock < $d->quantity ? 'text-danger fw-semibold' : 'text-success' }}">
                            {{ number_format($stock, 3) }}
                          </span>
                        </td>
                        <td>
                          <select class="form-select form-select-sm location-select-consume"
                                  name="consume[{{ $i }}][location_id]"
                                  data-product-id="{{ $d->product_id }}"
                                  data-selected="{{ $d->location_id }}"
                                  required>
                            <option value="{{ $d->location_id }}" selected>
                              {{ $d->location?->code ?? '— Đang tải... —' }}
                            </option>
                          </select>
                        </td>
                        {{-- Lot --}}
                        <td>
                          @if(in_array($tracking, [2, 4]))
                            <select class="form-select form-select-sm lot-select"
                                    name="consume[{{ $i }}][lot_id]"
                                    onchange="onLotChange(this)">
                              <option value="">— Chọn lô —</option>
                              @foreach($d->product?->lots?->where('status', 1) ?? [] as $lot)
                                <option value="{{ $lot->id }}"
                                        data-serials="{{ $lot->serials->pluck('serial_number', 'id')->toJson() }}"
                                        {{ $d->lot_id == $lot->id ? 'selected' : '' }}>
                                  {{ $lot->lot_number }}
                                </option>
                              @endforeach
                            </select>
                          @else
                            <input type="hidden" name="consume[{{ $i }}][lot_id]" value="">
                            <input class="form-control form-control-sm bg-body-secondary" placeholder="—" readonly>
                          @endif
                        </td>
                        {{-- Serial --}}
                        <td>
                          @if(in_array($tracking, [3, 4]))
                            <select class="form-select form-select-sm serial-select"
                                    name="consume[{{ $i }}][serial_id]">
                              <option value="">— Chọn serial —</option>
                              @if($d->lot_id)
                                @foreach($d->lot?->serials ?? [] as $s)
                                  <option value="{{ $s->id }}" {{ $d->serial_id == $s->id ? 'selected' : '' }}>
                                    {{ $s->serial_number }}
                                  </option>
                                @endforeach
                              @endif
                            </select>
                          @else
                            <input type="hidden" name="consume[{{ $i }}][serial_id]" value="">
                            <input class="form-control form-control-sm bg-body-secondary" placeholder="—" readonly>
                          @endif
                        </td>
                      </tr>
                    @endforeach
                  @endif
                </tbody>
              </table>
            </div>
          </div>
          <div class="card-footer py-2 text-body-secondary small d-flex justify-content-between">
            <span>Số dòng: <strong id="consumeCount">{{ $isEdit ? $transformation->consumeDetails->count() : 0 }}</strong></span>
            <span>Tổng SL: <strong id="consumeTotal">0</strong></span>
          </div>
        </div>

        {{-- ── MŨI TÊN ──────────────────────────────────────────── --}}
        <div class="text-center mb-4">
          <div class="d-inline-flex align-items-center gap-3 px-4 py-2 rounded-3 bg-body-secondary">
            <svg class="icon icon-xl text-body-secondary">
              <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-thick-bottom') }}"></use>
            </svg>
            <span class="fw-semibold text-body-secondary" id="arrowLabel">Biến đổi thành</span>
            <svg class="icon icon-xl text-body-secondary">
              <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-thick-bottom') }}"></use>
            </svg>
          </div>
        </div>

        {{-- ── ĐẦU RA ───────────────────────────────────────────── --}}
        <div class="card">
          <div class="card-header fw-semibold">
            <svg class="icon me-1 text-success"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-thick-top') }}"></use></svg>
            Hàng hóa đầu ra
            <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle ms-1" id="produceBadge">—</span>
          </div>
          <div class="card-body p-0">
            <div id="lotSerialAlertProduceContainer"></div>
            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0" id="produceTable">
                <thead class="table-light">
                  <tr>
                    <th style="width:32px" class="text-center">#</th>
                    <th>Hàng hóa</th>
                    <th style="width:72px">ĐVT</th>
                    <th style="width:115px">SL theo BOM</th>
                    <th style="width:115px">SL thực tế <span class="text-danger">*</span></th>
                    <th style="width:140px">Vị trí đích <span class="text-danger">*</span></th>
                    <th style="width:120px">Số Lot mới</th>
                    <th style="width:120px">Số Serial mới</th>
                    <th style="width:120px">Hạn sử dụng</th>
                  </tr>
                </thead>
                <tbody id="produceBody">
                  @if($isEdit)
                    @foreach($transformation->produceDetails as $i => $d)
                      @php $tracking = (int)($d->product?->tracking_type ?? 1); @endphp
                      <tr>
                        <td class="text-center text-body-secondary small">{{ $i+1 }}</td>
                        <td>
                          <div class="fw-medium small">{{ $d->product?->name }}</div>
                          <div class="text-body-secondary" style="font-size:.75rem">{{ $d->product?->code }}</div>
                          <input type="hidden" name="produce[{{ $i }}][product_id]" value="{{ $d->product_id }}">
                          <input type="hidden" name="produce[{{ $i }}][tracking]" value="{{ $tracking }}">
                        </td>
                        <td>
                          <input type="hidden" name="produce[{{ $i }}][uom_id]" value="{{ $d->uom_id }}">
                          <span class="text-body-secondary small">{{ $d->uom?->name ?? '—' }}</span>
                        </td>
                        <td class="text-end text-body-secondary small">
                          {{ number_format($d->bom_qty ?? $d->quantity, 3) }}
                        </td>
                        <td>
                          <input type="number"
                                 class="form-control form-control-sm text-end qty-produce"
                                 name="produce[{{ $i }}][quantity]"
                                 value="{{ old('produce.'.$i.'.quantity', $d->quantity) }}"
                                 min="0.001" step="0.001" required
                                 oninput="updateSummary()">
                        </td>
                        <td>
                          <select class="form-select form-select-sm" name="produce[{{ $i }}][location_id]" required>
                            <option value="">— Chọn —</option>
                            @foreach($locations as $loc)
                              <option value="{{ $loc->id }}" {{ $d->location_id == $loc->id ? 'selected' : '' }}>{{ $loc->code }}</option>
                            @endforeach
                          </select>
                        </td>
                        {{-- Lot mới --}}
                        <td>
                          <input type="text"
                                 class="form-control form-control-sm lot-new {{ in_array($tracking, [1,3]) ? 'bg-body-secondary' : '' }}"
                                 name="produce[{{ $i }}][lot_number]"
                                 value="{{ old('produce.'.$i.'.lot_number', $d->lot_number ?? $d->lot?->lot_number ?? '') }}"
                                 placeholder="{{ in_array($tracking, [1,3]) ? '—' : 'Số lô mới' }}"
                                 maxlength="100"
                                 {{ in_array($tracking, [1,3]) ? 'readonly' : '' }}>
                        </td>
                        {{-- Serial mới --}}
                        <td>
                          <input type="text"
                                 class="form-control form-control-sm serial-new {{ in_array($tracking, [1,2]) ? 'bg-body-secondary' : '' }}"
                                 name="produce[{{ $i }}][serial_number]"
                                 value="{{ old('produce.'.$i.'.serial_number', $d->serial_number ?? $d->serial?->serial_number ?? '') }}"
                                 placeholder="{{ in_array($tracking, [1,2]) ? '—' : 'Mã serial mới' }}"
                                 maxlength="100"
                                 {{ in_array($tracking, [1,2]) ? 'readonly' : '' }}>
                        </td>
                        <td>
                          <input type="date" class="form-control form-control-sm"
                                 name="produce[{{ $i }}][expiry_date]"
                                 value="{{ $d->expiry_date ? \Carbon\Carbon::parse($d->expiry_date)->format('Y-m-d') : '' }}">
                        </td>
                      </tr>
                    @endforeach
                  @endif
                </tbody>
              </table>
            </div>
          </div>
          <div class="card-footer py-2 text-body-secondary small d-flex justify-content-between">
            <span>Số dòng: <strong id="produceCount">{{ $isEdit ? $transformation->produceDetails->count() : 0 }}</strong></span>
            <span>Tổng SL: <strong id="produceTotal">0</strong></span>
          </div>
        </div>

        {{-- Cảnh báo --}}
        <div id="stockWarning" class="alert alert-danger d-none mt-3">
          <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use></svg>
          <strong>Không đủ tồn kho!</strong>
          <span id="stockWarningDetail"></span>
        </div>
        <div id="qtyMismatch" class="alert alert-warning d-none mt-2">
          <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-info') }}"></use></svg>
          <strong>Lưu ý:</strong> Tổng SL đầu vào và đầu ra chênh lệch nhau.
        </div>

      </div>{{-- end detailPanel --}}
    </div>{{-- end col-lg-8 --}}
  </div>{{-- end row --}}
</form>

@endsection

@php
  $locationsJson = $locations->map(fn($l) => [
    'id'   => $l->id,
    'code' => $l->code,
    'name' => $l->name ?? '',
  ])->values();

  // BOM data với đầy đủ lines và tracking
  $bomsJson = $boms->map(function($bom) {
    return [
      'id'   => $bom->id,
      'code' => $bom->code,
      'name' => $bom->name,
      'type' => (int) $bom->type,
      'consume' => $bom->consumeLines->map(fn($d) => [
        'product_id'   => $d->product_id,
        'product_code' => $d->product?->code,
        'product_name' => $d->product?->name,
        'uom_id'       => $d->uom_id,
        'uom_name'     => $d->uom?->name ?? '—',
        'qty'          => (float)$d->qty,
        'tracking'     => (int)($d->product?->tracking_type ?? 1),
        'stock'        => (float)($d->product?->stocks->sum('quantity') ?? 0),
        'lots'         => $d->product?->lots->where('status', 1)->map(fn($lot) => [
          'id'     => $lot->id,
          'number' => $lot->lot_number,
          'serials'=> $lot->serials->map(fn($s) => ['id' => $s->id, 'number' => $s->serial_number])->values()->toArray(),
        ])->values()->toArray() ?? [],
      ])->values(),
      'produce' => $bom->produceLines->map(fn($d) => [
        'product_id'   => $d->product_id,
        'product_code' => $d->product?->code,
        'product_name' => $d->product?->name,
        'uom_id'       => $d->uom_id,
        'uom_name'     => $d->uom?->name ?? '—',
        'qty'          => (float)$d->qty,
        'tracking'     => (int)($d->product?->tracking_type ?? 1),
      ])->values(),
    ];
  })->values();
@endphp

@push('scripts')
<script>
const BOMS      = @json($bomsJson);
const LOCATIONS = @json($locationsJson);

// Tracking constants
const T_NONE   = 1;
const T_LOT    = 2;
const T_SERIAL = 3;
const T_BOTH   = 4;

// ── Khi thay đổi BOM ─────────────────────────────────────────────
function onBomChange(sel) {
  const bomId = parseInt(sel.value);
  if (!bomId) {
    document.getElementById('bomPlaceholder').classList.remove('d-none');
    document.getElementById('detailPanel').classList.add('d-none');
    document.getElementById('typeDisplay').style.display = 'none';
    document.getElementById('typeHidden').value = '';
    return;
  }

  const bom = BOMS.find(b => b.id === bomId);
  if (!bom) return;

  // Loại phiếu từ BOM
  document.getElementById('typeHidden').value = bom.type;
  const typeTag = document.getElementById('typeTag');
  if (bom.type === 1) {
    typeTag.className = 'd-inline-flex align-items-center gap-1 px-2 py-1 rounded-2 border small fw-medium bg-warning-subtle text-warning-emphasis border-warning-subtle';
    typeTag.innerHTML = '<svg class="icon icon-sm"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-fork') }}"></use></svg> Tách hàng';
  } else {
    typeTag.className = 'd-inline-flex align-items-center gap-1 px-2 py-1 rounded-2 border small fw-medium bg-info-subtle text-info-emphasis border-info-subtle';
    typeTag.innerHTML = '<svg class="icon icon-sm"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-layers') }}"></use></svg> Ghép hàng';
  }
  document.getElementById('typeDisplay').style.display = '';

  // Badge + arrow
  document.getElementById('consumeBadge').textContent = bom.type === 2 ? 'Nhiều nguồn' : 'Nguồn gốc';
  document.getElementById('produceBadge').textContent = bom.type === 1 ? 'Nhiều sản phẩm' : 'Sản phẩm ghép';
  document.getElementById('arrowLabel').textContent   = bom.type === 1 ? 'Tách thành' : 'Ghép thành';

  // Render bảng
  const mult = parseFloat(document.getElementById('multiplierInput').value) || 1;
  renderConsumeTable(bom, mult);
  renderProduceTable(bom, mult);

  document.getElementById('bomPlaceholder').classList.add('d-none');
  document.getElementById('detailPanel').classList.remove('d-none');
  updateSummary();
}

// ── Khi thay đổi hệ số ───────────────────────────────────────────
function onMultiplierChange() {
  const bomId = parseInt(document.getElementById('bomSelect').value);
  if (!bomId) return;
  const bom  = BOMS.find(b => b.id === bomId);
  if (!bom) return;
  const mult = parseFloat(document.getElementById('multiplierInput').value) || 1;

  // Cập nhật SL BOM display và SL thực tế
  document.querySelectorAll('#consumeBody tr').forEach((tr, i) => {
    const line = bom.consume[i];
    if (!line) return;
    const computed = roundQty(line.qty * mult);
    const bomCell  = tr.querySelector('.bom-qty-cell');
    const qtyInput = tr.querySelector('.qty-consume');
    if (bomCell)  bomCell.textContent = fmtQty(computed);
    if (qtyInput) qtyInput.value = computed;
    updateStockWarningCell(tr, computed, line.stock);
  });
  document.querySelectorAll('#produceBody tr').forEach((tr, i) => {
    const line = bom.produce[i];
    if (!line) return;
    const computed = roundQty(line.qty * mult);
    const bomCell  = tr.querySelector('.bom-qty-cell');
    const qtyInput = tr.querySelector('.qty-produce');
    if (bomCell)  bomCell.textContent = fmtQty(computed);
    if (qtyInput) qtyInput.value = computed;
  });

  updateSummary();
}

// ── Render bảng đầu vào ──────────────────────────────────────────
function renderConsumeTable(bom, mult) {
  const tbody = document.getElementById('consumeBody');
  tbody.innerHTML = '';

  bom.consume.forEach((line, i) => {
    const computed = roundQty(line.qty * mult);
    const needLot    = [T_LOT, T_BOTH].includes(line.tracking);
    const needSerial = [T_SERIAL, T_BOTH].includes(line.tracking);
    const stockOk    = line.stock >= computed;

    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td class="text-center text-body-secondary small">${i + 1}</td>
      <td>
        <div class="fw-medium small">${line.product_name}</div>
        <div class="text-body-secondary" style="font-size:.75rem">${line.product_code}</div>
        <input type="hidden" name="consume[${i}][product_id]" value="${line.product_id}">
        <input type="hidden" name="consume[${i}][uom_id]" value="${line.uom_id}">
        <input type="hidden" name="consume[${i}][tracking]" value="${line.tracking}">
      </td>
      <td><span class="text-body-secondary small">${line.uom_name}</span></td>
      <td class="text-end text-body-secondary small bom-qty-cell">${fmtQty(computed)}</td>
      <td>
        <input type="number" class="form-control form-control-sm text-end qty-consume"
               name="consume[${i}][quantity]" value="${computed}"
               min="0.001" step="0.001" required
               oninput="updateSummary()"
               data-stock="${line.stock}">
      </td>
      <td class="text-end small">
        <span class="${stockOk ? 'text-success' : 'text-danger fw-semibold'} stock-cell">${fmtQty(line.stock)}</span>
      </td>
      <td>
        <select class="form-select form-select-sm location-select-consume"
                name="consume[${i}][location_id]"
                data-product-id="${line.product_id}"
                required
                onchange="updateSummary()">
          <option value="">— Đang tải... —</option>
        </select>
      </td>
      <td>${needLot ? lotSelectHtml(i, line.lots, 'consume') : '<input class="form-control form-control-sm bg-body-secondary" placeholder="—" readonly><input type="hidden" name="consume['+i+'][lot_id]" value="">'}</td>
      <td>${needSerial ? '<select class="form-select form-select-sm serial-select" name="consume['+i+'][serial_id]"><option value="">— Chọn serial —</option></select>' : '<input class="form-control form-control-sm bg-body-secondary" placeholder="—" readonly><input type="hidden" name="consume['+i+'][serial_id]" value="">'}</td>
    `;
    tbody.appendChild(tr);

    // Init serial dropdown
    if (needLot && needSerial) {
      const lotSel = tr.querySelector('.lot-select');
      if (lotSel) {
        lotSel.addEventListener('change', function() { onLotChange(this); });
      }
    }

    // Fetch locations có hàng tồn kho cho product này
    const locSel = tr.querySelector('.location-select-consume');
    if (locSel) {
      fetchLocationsForProduct(locSel, line.product_id, null);
    }
  });

  document.getElementById('consumeCount').textContent = bom.consume.length;
}

// ── Render bảng đầu ra ───────────────────────────────────────────
function renderProduceTable(bom, mult) {
  const tbody = document.getElementById('produceBody');
  tbody.innerHTML = '';

  bom.produce.forEach((line, i) => {
    const computed   = roundQty(line.qty * mult);
    const needLot    = [T_LOT, T_BOTH].includes(line.tracking);
    const needSerial = [T_SERIAL, T_BOTH].includes(line.tracking);

    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td class="text-center text-body-secondary small">${i + 1}</td>
      <td>
        <div class="fw-medium small">${line.product_name}</div>
        <div class="text-body-secondary" style="font-size:.75rem">${line.product_code}</div>
        <input type="hidden" name="produce[${i}][product_id]" value="${line.product_id}">
        <input type="hidden" name="produce[${i}][uom_id]" value="${line.uom_id}">
        <input type="hidden" name="produce[${i}][tracking]" value="${line.tracking}">
      </td>
      <td><span class="text-body-secondary small">${line.uom_name}</span></td>
      <td class="text-end text-body-secondary small bom-qty-cell">${fmtQty(computed)}</td>
      <td>
        <input type="number" class="form-control form-control-sm text-end qty-produce"
               name="produce[${i}][quantity]" value="${computed}"
               min="0.001" step="0.001" required
               oninput="updateSummary()">
      </td>
      <td>
        <select class="form-select form-select-sm" name="produce[${i}][location_id]" required>
          <option value="">— Chọn —</option>
          ${locationOptions()}
        </select>
      </td>
      <td>
        <input type="text"
               class="form-control form-control-sm lot-new ${needLot ? '' : 'bg-body-secondary'}"
               name="produce[${i}][lot_number]"
               placeholder="${needLot ? 'Số lô mới' : '—'}"
               maxlength="100" ${needLot ? '' : 'readonly'}>
      </td>
      <td>
        <input type="text"
               class="form-control form-control-sm serial-new ${needSerial ? '' : 'bg-body-secondary'}"
               name="produce[${i}][serial_number]"
               placeholder="${needSerial ? 'Mã serial mới' : '—'}"
               maxlength="100" ${needSerial ? '' : 'readonly'}>
      </td>
      <td>
        <input type="date" class="form-control form-control-sm"
               name="produce[${i}][expiry_date]">
      </td>
    `;
    tbody.appendChild(tr);
  });

  document.getElementById('produceCount').textContent = bom.produce.length;
}

// ── Fetch vị trí có tồn kho theo product ────────────────────────────
function fetchLocationsForProduct(selectEl, productId, selectedId) {
  fetch(`/transformations/locations-by-product/${productId}`)
    .then(r => r.json())
    .then(data => {
      const locs = data.locations ?? [];
      selectEl.innerHTML =
        '<option value="">— Chọn vị trí —</option>' +
        locs.map(l =>
          `<option value="${l.id}" ${l.id == selectedId ? 'selected' : ''}>${l.code}${l.name ? ' — ' + l.name : ''}</option>`
        ).join('') +
        // Nếu selectedId không có trong danh sách (hàng đã xuất hết) vẫn hiển thị
        (selectedId && !locs.find(l => l.id == selectedId)
          ? `<option value="${selectedId}" selected>[Vị trí #${selectedId}]</option>`
          : '');
    })
    .catch(() => {
      // Fallback: hiển thị tất cả locations nếu API lỗi
      selectEl.innerHTML = '<option value="">— Chọn vị trí —</option>' + locationOptions();
    });
}

// ── Lot select HTML ───────────────────────────────────────────────
function lotSelectHtml(i, lots, side) {
  const opts = lots.map(l =>
    `<option value="${l.id}" data-serials='${JSON.stringify(l.serials)}'>${l.number}</option>`
  ).join('');
  return `<select class="form-select form-select-sm lot-select" name="${side}[${i}][lot_id]" onchange="onLotChange(this)">
    <option value="">— Chọn lô —</option>
    ${opts}
  </select>`;
}

// ── Khi chọn Lot → populate Serial dropdown ───────────────────────
function onLotChange(sel) {
  const tr       = sel.closest('tr');
  const serialSel = tr.querySelector('.serial-select');
  if (!serialSel) return;

  const opt = sel.options[sel.selectedIndex];
  let serials = [];
  try { serials = JSON.parse(opt.dataset.serials || '[]'); } catch(e){}

  serialSel.innerHTML = '<option value="">— Chọn serial —</option>' +
    serials.map(s => `<option value="${s.id}">${s.number}</option>`).join('');
}

// ── Location options ──────────────────────────────────────────────
function locationOptions() {
  return LOCATIONS.map(l =>
    `<option value="${l.id}">${l.code}${l.name ? ' — ' + l.name : ''}</option>`
  ).join('');
}

// ── Tồn kho warning cell ─────────────────────────────────────────
function updateStockWarningCell(tr, needed, available) {
  const cell = tr.querySelector('.stock-cell');
  if (!cell) return;
  if (available < needed) {
    cell.className = 'text-danger fw-semibold stock-cell';
  } else {
    cell.className = 'text-success stock-cell';
  }
}

// ── Tổng SL & cảnh báo ────────────────────────────────────────────
function sumQty(cls) {
  return [...document.querySelectorAll('.' + cls)]
    .reduce((s, el) => s + (parseFloat(el.value) || 0), 0);
}

function updateSummary() {
  const cTotal = sumQty('qty-consume');
  const pTotal = sumQty('qty-produce');
  document.getElementById('consumeTotal').textContent = fmtQty(cTotal);
  document.getElementById('produceTotal').textContent = fmtQty(pTotal);

  // Kiểm tra từng dòng tồn kho
  let stockErrors = [];
  document.querySelectorAll('#consumeBody tr').forEach((tr, i) => {
    const inp = tr.querySelector('.qty-consume');
    if (!inp) return;
    const needed    = parseFloat(inp.value) || 0;
    const available = parseFloat(inp.dataset.stock) || 0;
    updateStockWarningCell(tr, needed, available);
    if (needed > available) {
      const name = tr.querySelector('.fw-medium')?.textContent?.trim() ?? `Dòng ${i+1}`;
      stockErrors.push(`${name}: cần ${fmtQty(needed)}, tồn ${fmtQty(available)}`);
    }
  });

  const warnStock = document.getElementById('stockWarning');
  if (stockErrors.length) {
    document.getElementById('stockWarningDetail').textContent = ' ' + stockErrors.join('; ');
    warnStock.classList.remove('d-none');
  } else {
    warnStock.classList.add('d-none');
  }

  const warnQty = document.getElementById('qtyMismatch');
  if (cTotal > 0 && pTotal > 0 && Math.abs(cTotal - pTotal) > 0.001) {
    warnQty.classList.remove('d-none');
  } else {
    warnQty.classList.add('d-none');
  }
}

// ── Validate Lot/Serial trước khi submit ─────────────────────────
function validateLotSerial() {
  const errors = [];

  document.querySelectorAll('#consumeBody tr').forEach((tr, i) => {
    const tracking = parseInt(tr.querySelector('input[name$="[tracking]"]')?.value) || T_NONE;
    const lotSel   = tr.querySelector('.lot-select');
    const serSel   = tr.querySelector('.serial-select');
    const name     = tr.querySelector('.fw-medium')?.textContent?.trim() ?? `Dòng ${i+1}`;

    if ([T_LOT, T_BOTH].includes(tracking) && lotSel && !lotSel.value) {
      lotSel.classList.add('is-invalid');
      errors.push(`Đầu vào "${name}": chưa chọn Số Lô.`);
    }
    if ([T_SERIAL, T_BOTH].includes(tracking) && serSel && !serSel.value) {
      serSel.classList.add('is-invalid');
      errors.push(`Đầu vào "${name}": chưa chọn Số Serial.`);
    }
  });

  document.querySelectorAll('#produceBody tr').forEach((tr, i) => {
    const tracking = parseInt(tr.querySelector('input[name$="[tracking]"]')?.value) || T_NONE;
    const lotNew   = tr.querySelector('.lot-new');
    const serNew   = tr.querySelector('.serial-new');
    const name     = tr.querySelector('.fw-medium')?.textContent?.trim() ?? `Dòng ${i+1}`;

    if ([T_LOT, T_BOTH].includes(tracking) && lotNew && !lotNew.readOnly && !lotNew.value.trim()) {
      lotNew.classList.add('is-invalid');
      errors.push(`Đầu ra "${name}": chưa nhập Số Lô mới.`);
    }
    if ([T_SERIAL, T_BOTH].includes(tracking) && serNew && !serNew.readOnly && !serNew.value.trim()) {
      serNew.classList.add('is-invalid');
      errors.push(`Đầu ra "${name}": chưa nhập Số Serial mới.`);
    }
  });

  return errors;
}

// ── Submit ────────────────────────────────────────────────────────
document.getElementById('transformationForm').addEventListener('submit', function(e) {
  // Kiểm tra BOM
  if (!document.getElementById('bomSelect').value) {
    e.preventDefault();
    alert('Vui lòng chọn Công thức BOM trước khi lưu.');
    document.getElementById('bomSelect').focus();
    return;
  }

  const errors = validateLotSerial();
  if (!errors.length) return;

  e.preventDefault();
  const html = `<div class="alert alert-danger alert-dismissible mx-3 mt-3 mb-0" role="alert">
    <strong>Chưa nhập đủ thông tin Lot / Serial:</strong>
    <ul class="mb-0 mt-1 ps-4">${errors.map(m => `<li>${m}</li>`).join('')}</ul>
    <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
  </div>`;
  document.getElementById('lotSerialAlertConsumeContainer').innerHTML = html;
  document.getElementById('lotSerialAlertConsumeContainer').scrollIntoView({behavior:'smooth', block:'nearest'});
});

// ── Helpers ───────────────────────────────────────────────────────
function fmtQty(v) {
  return parseFloat(v).toLocaleString('vi-VN', {maximumFractionDigits: 3});
}
function roundQty(v) {
  return Math.round(v * 1000) / 1000;
}

// ── Init labels từ BOM mà KHÔNG render lại rows (dùng khi edit) ──
function initLabelsOnly(bomId) {
  const bom = BOMS.find(b => b.id === bomId);
  if (!bom) return;

  const typeTag = document.getElementById('typeTag');
  if (bom.type === 1) {
    typeTag.className = 'd-inline-flex align-items-center gap-1 px-2 py-1 rounded-2 border small fw-medium bg-warning-subtle text-warning-emphasis border-warning-subtle';
    typeTag.innerHTML = `<svg class="icon icon-sm"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-fork') }}"></use></svg> Tách hàng`;
  } else {
    typeTag.className = 'd-inline-flex align-items-center gap-1 px-2 py-1 rounded-2 border small fw-medium bg-info-subtle text-info-emphasis border-info-subtle';
    typeTag.innerHTML = `<svg class="icon icon-sm"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-layers') }}"></use></svg> Ghép hàng`;
  }
  document.getElementById('typeDisplay').style.display = '';
  document.getElementById('typeHidden').value = bom.type;
  document.getElementById('consumeBadge').textContent = bom.type === 2 ? 'Nhiều nguồn' : 'Nguồn gốc';
  document.getElementById('produceBadge').textContent = bom.type === 1 ? 'Nhiều sản phẩm' : 'Sản phẩm ghép';
  document.getElementById('arrowLabel').textContent   = bom.type === 1 ? 'Tách thành' : 'Ghép thành';
  document.getElementById('bomPlaceholder').classList.add('d-none');
  document.getElementById('detailPanel').classList.remove('d-none');
}

// ── Init ──────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  @if($isEdit)
    // Khi edit: CHỈ init labels/badges, KHÔNG render lại rows
    // (rows đã được server render với đầy đủ location/lot/serial)
    initLabelsOnly({{ $transformation->bom_id }});
    // Gắn sự kiện onLotChange cho lot-select đã render sẵn
    document.querySelectorAll('#consumeBody .lot-select').forEach(sel => {
      sel.addEventListener('change', function() { onLotChange(this); });
    });
    // Load locations theo product cho từng row đầu vào
    document.querySelectorAll('#consumeBody .location-select-consume').forEach(sel => {
      const productId  = sel.dataset.productId;
      const selectedId = sel.dataset.selected;
      if (productId) fetchLocationsForProduct(sel, productId, selectedId);
    });
  @endif
  updateSummary();
});
</script>
@endpush