@extends('layouts.app')

@section('title', (isset($scrap) ? 'Sửa phiếu hủy' : 'Tạo phiếu hủy hàng') . ' — Warehouse System')

@section('breadcrumb')
<li class="breadcrumb-item">Nghiệp vụ kho</li>
<li class="breadcrumb-item"><a href="{{ route('scraps.index') }}">Hủy hàng</a></li>
<li class="breadcrumb-item active">{{ isset($scrap) ? $scrap->code : 'Tạo mới' }}</li>
@endsection

@section('content')

@php
$isEdit = isset($scrap);
$action = $isEdit ? route('scraps.update', $scrap->id) : route('scraps.store');
@endphp

{{-- HEADER --}}
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-semibold">{{ $isEdit ? 'Sửa phiếu hủy' : 'Tạo phiếu hủy hàng mới' }}</h4>
        <small class="text-body-secondary">{{ $isEdit ? $scrap->code : 'Điền thông tin và thêm hàng hóa cần hủy' }}</small>
    </div>
    <a href="{{ route('scraps.index') }}" class="btn btn-outline-secondary btn-sm">
        <svg class="icon me-1">
            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-left') }}"></use>
        </svg>
        Quay lại
    </a>
</div>

<form method="POST" action="{{ $action }}" id="scrapForm">
    @csrf
    @if($isEdit) @method('PUT') @endif

    {{-- THÔNG TIN PHIẾU --}}
    <div class="card mb-3">
        <div class="card-header fw-semibold py-2">
            <svg class="icon me-1 text-primary">
                <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-description') }}"></use>
            </svg>
            Thông tin phiếu hủy
        </div>
        <div class="card-body py-3">
            @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                <svg class="icon me-2"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use></svg>
                <strong>Vui lòng kiểm tra lại:</strong>
                <ul class="mb-0 mt-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
            </div>
            @endif

            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Mã phiếu</label>
                    <input type="text" class="form-control form-control-sm text-uppercase @error('code') is-invalid @enderror"
                        name="code" value="{{ old('code', $scrap->code ?? '') }}"
                        placeholder="Tự sinh nếu trống" maxlength="50" {{ $isEdit ? 'readonly' : '' }}>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Ngày hủy <span class="text-danger">*</span></label>
                    <input type="date" class="form-control form-control-sm @error('scrap_date') is-invalid @enderror"
                        name="scrap_date"
                        value="{{ old('scrap_date', isset($scrap->scrap_date) ? \Carbon\Carbon::parse($scrap->scrap_date)->format('Y-m-d') : date('Y-m-d')) }}"
                        required>
                    @error('scrap_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-8">
                    <label class="form-label form-label-sm mb-1">Ghi chú / Lý do hủy tổng quát</label>
                    <input type="text" class="form-control form-control-sm" name="note"
                        value="{{ old('note', $scrap->note ?? '') }}"
                        placeholder="VD: Hàng hết hạn sử dụng, hư hỏng..." maxlength="500">
                </div>
            </div>
        </div>
    </div>

    {{-- CHI TIẾT HÀNG HÓA --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center py-2">
            <span class="fw-semibold">
                <svg class="icon me-1 text-danger">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-list') }}"></use>
                </svg>
                Danh sách hàng hóa cần hủy
            </span>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="addRow()">
                <svg class="icon me-1">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use>
                </svg>
                Thêm dòng
            </button>
        </div>

        <div class="card-body p-0">
            <div id="lotSerialAlertContainer"></div>

            @error('details')
            <div class="alert alert-danger m-3 mb-0">{{ $message }}</div>
            @enderror

            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:36px" class="text-center">#</th>
                            <th style="min-width:210px">Hàng hóa <span class="text-danger">*</span></th>
                            <th style="width:65px">ĐVT</th>
                            <th style="width:100px">Số lượng <span class="text-danger">*</span></th>
                            <th style="width:170px">Vị trí kho <span class="text-danger">*</span></th>
                            <th style="width:130px">Số Lot/Batch</th>
                            <th style="width:130px">Số Serial</th>
                            <th style="min-width:160px">Lý do hủy</th>
                            <th style="width:40px"></th>
                        </tr>
                    </thead>
                    <tbody id="detailBody">
                        {{-- Edit mode: render existing rows --}}
                        @if($isEdit && $scrap->details->count())
                        @foreach($scrap->details as $i => $d)
                        @php $tk = (int)($d->product?->tracking_type ?? 1); @endphp
                        <tr class="detail-row">
                            <td class="text-center text-body-secondary small row-num">{{ $i + 1 }}</td>
                            <td>
                                <select name="details[{{ $i }}][product_id]"
                                    class="form-select form-select-sm product-select" required
                                    onchange="onProductChange(this)">
                                    <option value="">— Chọn —</option>
                                    @foreach($products as $p)
                                    <option value="{{ $p->id }}"
                                        data-uom="{{ $p->uom?->name ?? '—' }}"
                                        data-uom-id="{{ $p->uom_id }}"
                                        data-tracking="{{ (int)($p->tracking_type ?? 1) }}"
                                        @selected($d->product_id == $p->id)>
                                        {{ $p->code }} — {{ $p->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="hidden" name="details[{{ $i }}][uom_id]" class="uom-hidden" value="{{ $d->uom_id }}">
                                <span class="uom-label text-body-secondary small">{{ $d->uom?->name ?? '—' }}</span>
                            </td>
                            <td>
                                <input type="number" name="details[{{ $i }}][quantity]"
                                    class="form-control form-control-sm text-end qty-input"
                                    value="{{ $d->quantity }}" min="0.001" step="0.001" required
                                    onchange="onQtyChange(this)">
                            </td>
                            <td>
                                <input type="hidden" name="details[{{ $i }}][location_id]" class="location-hidden" value="{{ $d->location_id }}">
                                <select class="form-select form-select-sm location-select" onchange="onLocationChange(this)">
                                    <option value="{{ $d->location_id }}" selected>
                                        {{ $d->location?->code ?? '—' }}{{ $d->location?->name ? ' — '.$d->location->name : '' }}
                                    </option>
                                </select>
                            </td>
                            <td>
                                <input type="hidden" name="details[{{ $i }}][lot_id]" class="lot-hidden" value="{{ $d->lot_id }}">
                                <select class="form-select form-select-sm lot-select {{ !in_array($tk,[2,4]) ? 'bg-body-secondary' : '' }}"
                                    {{ !in_array($tk,[2,4]) ? 'disabled' : '' }}
                                    onchange="onLotChange(this)">
                                    <option value="">— Không —</option>
                                    @if($d->lot)<option value="{{ $d->lot->id }}" selected>{{ $d->lot->lot_number }}</option>@endif
                                </select>
                            </td>
                            <td>
                                <input type="hidden" name="details[{{ $i }}][serial_id]" class="serial-hidden" value="{{ $d->serial_id }}">
                                <select class="form-select form-select-sm serial-select {{ !in_array($tk,[3,4]) ? 'bg-body-secondary' : '' }}"
                                    {{ !in_array($tk,[3,4]) ? 'disabled' : '' }}
                                    onchange="onSerialChange(this)">
                                    <option value="">— Không —</option>
                                    @if($d->serial)<option value="{{ $d->serial->id }}" selected>{{ $d->serial->serial_number }}</option>@endif
                                </select>
                            </td>
                            <td>
                                <input type="text" name="details[{{ $i }}][reason]"
                                    class="form-control form-control-sm"
                                    value="{{ $d->reason }}" placeholder="Hư hỏng, hết hạn...">
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row" title="Xóa dòng">
                                    <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use></svg>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                        @endif
                    </tbody>
                </table>
            </div>

            <div id="emptyHint" class="text-center text-body-secondary py-5 {{ ($isEdit && $scrap->details->count()) ? 'd-none' : '' }}">
                <svg class="icon icon-3xl d-block mx-auto mb-2 opacity-25">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-list') }}"></use>
                </svg>
                Chưa có hàng hóa nào. Nhấn <strong>+ Thêm dòng</strong> để bắt đầu.
            </div>
        </div>

        <div class="card-footer d-flex justify-content-between align-items-center">
            <span class="small text-body-secondary">
                Tổng dòng: <strong id="rowCount">{{ $isEdit ? $scrap->details->count() : 0 }}</strong>
            </span>
            <div class="d-flex gap-2">
                <a href="{{ route('scraps.index') }}" class="btn btn-outline-secondary btn-sm">Hủy bỏ</a>
                <button type="submit" class="btn btn-danger btn-sm">
                    <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-save') }}"></use></svg>
                    Lưu phiếu
                </button>
            </div>
        </div>
    </div>

</form>

@push('scripts')
<script>
// ── Constants ─────────────────────────────────────────────────────────────────
const PRODUCTS  = @json($productsJson);   // [{id, code, name, uom, uom_id, tracking}]
const STOCK_LOC_URL = '{{ route("scraps.stock-locations", ["productId" => "__PID__"]) }}';

const TRACKING_NONE           = 1;
const TRACKING_LOT            = 2;
const TRACKING_SERIAL         = 3;
const TRACKING_LOT_AND_SERIAL = 4;

let rowIndex = {{ $isEdit ? $scrap->details->count() : 0 }};

// ── DOM refs ──────────────────────────────────────────────────────────────────
const detailBody = document.getElementById('detailBody');
const emptyHint  = document.getElementById('emptyHint');
const rowCountEl = document.getElementById('rowCount');

// ── Utilities ─────────────────────────────────────────────────────────────────
function syncUI() {
    let n = 0;
    detailBody.querySelectorAll('.row-num').forEach(el => el.textContent = ++n);
    rowCountEl.textContent = n;
    emptyHint?.classList.toggle('d-none', n > 0);
}

function trackingOfRow(tr) {
    const opt = tr.querySelector('.product-select option:checked');
    return parseInt(opt?.dataset?.tracking) || TRACKING_NONE;
}

function stockLocUrl(productId) {
    return STOCK_LOC_URL.replace('__PID__', productId);
}

// ── applyTracking: lock/unlock Lot & Serial cells ────────────────────────────
function applyTracking(tr, tracking) {
    const lotSel    = tr.querySelector('.lot-select');
    const serialSel = tr.querySelector('.serial-select');
    const lotHid    = tr.querySelector('.lot-hidden');
    const serialHid = tr.querySelector('.serial-hidden');

    [lotSel, serialSel].forEach(el => {
        if (!el) return;
        el.disabled = false;
        el.classList.remove('bg-body-secondary', 'is-invalid');
        el.innerHTML = '<option value="">— Không —</option>';
    });
    if (lotHid)    lotHid.value    = '';
    if (serialHid) serialHid.value = '';

    const lockLot    = ![TRACKING_LOT, TRACKING_LOT_AND_SERIAL].includes(tracking);
    const lockSerial = ![TRACKING_SERIAL, TRACKING_LOT_AND_SERIAL].includes(tracking);

    if (lockLot && lotSel) {
        lotSel.disabled = true;
        lotSel.classList.add('bg-body-secondary');
    }
    if (lockSerial && serialSel) {
        serialSel.disabled = true;
        serialSel.classList.add('bg-body-secondary');
    }
}

// ── fillLotSerial: populate lot/serial selects from stock data ───────────────
function fillLotSerial(tr, stocks, locationId, selLotId, selSerialId) {
    const tracking  = trackingOfRow(tr);
    const lotSel    = tr.querySelector('.lot-select');
    const serialSel = tr.querySelector('.serial-select');
    const lotHid    = tr.querySelector('.lot-hidden');
    const serialHid = tr.querySelector('.serial-hidden');

    const byLoc = locationId ? stocks.filter(s => s.location_id == locationId) : stocks;

    // -- Lot --
    if ([TRACKING_LOT, TRACKING_LOT_AND_SERIAL].includes(tracking) && lotSel) {
        const seen = new Set();
        const opts = byLoc
            .filter(s => s.lot_id && !seen.has(s.lot_id) && seen.add(s.lot_id))
            .map(s => {
                const exp = s.expiry_date ? ` (HSD: ${s.expiry_date})` : '';
                const sel = s.lot_id == selLotId ? ' selected' : '';
                return `<option value="${s.lot_id}"${sel}>${s.lot_number}${exp}</option>`;
            });
        lotSel.innerHTML = '<option value="">— Chọn Lot —</option>' + opts.join('');
        if (lotHid) lotHid.value = lotSel.value;
    }

    // -- Serial --
    if ([TRACKING_SERIAL, TRACKING_LOT_AND_SERIAL].includes(tracking) && serialSel) {
        const opts = byLoc
            .filter(s => s.serial_id)
            .map(s => {
                const sel = s.serial_id == selSerialId ? ' selected' : '';
                return `<option value="${s.serial_id}"${sel}>${s.serial_number}</option>`;
            });
        serialSel.innerHTML = '<option value="">— Chọn Serial —</option>' + opts.join('');
        if (serialHid) serialHid.value = serialSel.value;
    }
}

// ── loadStockLocations: fetch rồi điền location + lot/serial ─────────────────
function loadStockLocations(productId, tr, selLocationId, selLotId, selSerialId) {
    const locSel = tr.querySelector('.location-select');
    const locHid = tr.querySelector('.location-hidden');
    const tracking = trackingOfRow(tr);

    locSel.innerHTML = '<option value="">Đang tải...</option>';
    locSel.disabled  = true;

    fetch(stockLocUrl(productId))
        .then(r => r.json())
        .then(stocks => {
            locSel.disabled = false;

            if (!stocks.length) {
                locSel.innerHTML = '<option value="">— Không có tồn kho —</option>';
                if (locHid) locHid.value = '';
                applyTracking(tr, tracking);
                return;
            }

            // Unique locations
            const seenLoc = new Set();
            const locOpts = stocks
                .filter(s => !seenLoc.has(s.location_id) && seenLoc.add(s.location_id))
                .map(s => {
                    const label = s.location_code + (s.location_name ? ' — ' + s.location_name : '') + ` (${s.available_qty})`;
                    const sel   = s.location_id == selLocationId ? ' selected' : '';
                    return `<option value="${s.location_id}"${sel}>${label}</option>`;
                });
            locSel.innerHTML = '<option value="">— Chọn vị trí —</option>' + locOpts.join('');

            // Tự chọn vị trí đầu tiên nếu chưa có
            if (!selLocationId && stocks.length) locSel.value = stocks[0].location_id;
            if (locHid) locHid.value = locSel.value;

            // Điền lot/serial theo vị trí đã chọn
            fillLotSerial(tr, stocks, locSel.value, selLotId, selSerialId);
        })
        .catch(() => {
            locSel.disabled  = false;
            locSel.innerHTML = '<option value="">— Lỗi tải dữ liệu —</option>';
        });
}

// ── Event: chọn sản phẩm ─────────────────────────────────────────────────────
function onProductChange(sel) {
    const tr        = sel.closest('tr');
    const opt       = sel.options[sel.selectedIndex];
    const tracking  = parseInt(opt?.dataset?.tracking) || TRACKING_NONE;
    const productId = sel.value;

    // Cập nhật ĐVT
    tr.querySelector('.uom-label').textContent = opt?.dataset?.uom || '—';
    const uomHid = tr.querySelector('.uom-hidden');
    if (uomHid) uomHid.value = opt?.dataset?.uomId || '';

    // Áp tracking (reset lot/serial)
    applyTracking(tr, tracking);

    // Reset location
    const locSel = tr.querySelector('.location-select');
    const locHid = tr.querySelector('.location-hidden');
    if (!productId) {
        locSel.innerHTML = '<option value="">— Chọn sản phẩm trước —</option>';
        if (locHid) locHid.value = '';
        return;
    }

    loadStockLocations(productId, tr, null, null, null);
}

// ── Event: chọn vị trí → reload lot/serial ───────────────────────────────────
function onLocationChange(locSel) {
    const tr  = locSel.closest('tr');
    const hid = tr.querySelector('.location-hidden');
    if (hid) hid.value = locSel.value;

    const productId = tr.querySelector('.product-select').value;
    if (!productId) return;

    fetch(stockLocUrl(productId))
        .then(r => r.json())
        .then(stocks => fillLotSerial(tr, stocks, locSel.value, null, null))
        .catch(() => {});
}

// ── Event: chọn Lot → reload serial (tracking=4) ─────────────────────────────
function onLotChange(lotSel) {
    const tr  = lotSel.closest('tr');
    const hid = tr.querySelector('.lot-hidden');
    if (hid) hid.value = lotSel.value;

    if (trackingOfRow(tr) !== TRACKING_LOT_AND_SERIAL) return;

    const productId  = tr.querySelector('.product-select').value;
    const locationId = tr.querySelector('.location-hidden').value;
    const lotId      = lotSel.value;
    if (!productId) return;

    fetch(stockLocUrl(productId))
        .then(r => r.json())
        .then(stocks => {
            const serialSel = tr.querySelector('.serial-select');
            const serialHid = tr.querySelector('.serial-hidden');
            const filtered  = stocks.filter(s =>
                (!locationId || s.location_id == locationId) &&
                (!lotId || s.lot_id == lotId) &&
                s.serial_id
            );
            if (!serialSel) return;
            serialSel.innerHTML = '<option value="">— Chọn Serial —</option>' +
                filtered.map(s => `<option value="${s.serial_id}">${s.serial_number}</option>`).join('');
            if (serialHid) serialHid.value = serialSel.value;
        }).catch(() => {});
}

// ── Event: chọn Serial ────────────────────────────────────────────────────────
function onSerialChange(serialSel) {
    const hid = serialSel.closest('tr').querySelector('.serial-hidden');
    if (hid) hid.value = serialSel.value;
    checkDuplicateSerials();
}

// Kiểm tra serial trùng toàn bảng, highlight đỏ realtime
function checkDuplicateSerials() {
    const map = {};
    detailBody.querySelectorAll('.detail-row').forEach(tr => {
        const serialSel = tr.querySelector('.serial-select');
        if (!serialSel || serialSel.disabled || !serialSel.value) return;
        const productId = tr.querySelector('.product-select')?.value;
        const key = productId + '__' + serialSel.value;
        if (!map[key]) map[key] = [];
        map[key].push(serialSel);
    });

    // Reset trước
    detailBody.querySelectorAll('.serial-select').forEach(el => {
        el.classList.remove('is-invalid');
        el.title = '';
    });

    // Đánh dấu trùng
    let hasDup = false;
    Object.values(map).forEach(sels => {
        if (sels.length > 1) {
            hasDup = true;
            sels.forEach(el => {
                el.classList.add('is-invalid');
                el.title = 'Serial này đã được chọn ở dòng khác';
            });
        }
    });
    return hasDup;
}

// ── Event: nhập Số lượng → tự nhân dòng cho Serial/Lot&Serial ────────────────
function onQtyChange(input) {
    const tr       = input.closest('tr');
    const tracking = trackingOfRow(tr);
    const qty      = parseInt(input.value) || 1;

    // Chỉ xử lý khi tracking có Serial và qty > 1
    if (![TRACKING_SERIAL, TRACKING_LOT_AND_SERIAL].includes(tracking) || qty <= 1) return;

    // Snapshot thông tin dòng gốc trước khi reset
    const productId  = tr.querySelector('.product-select').value;
    const locationId = tr.querySelector('.location-hidden').value;
    const lotId      = tr.querySelector('.lot-hidden').value;
    const reasonVal  = tr.querySelector('input[name$="[reason]"]').value;

    // Reset dòng gốc về qty = 1
    input.value = 1;

    // Thêm (qty - 1) dòng mới, mỗi dòng qty = 1, giữ product + location + lot
    for (let n = 1; n < qty; n++) {
        const newTr = buildRow(rowIndex++, productId, locationId, lotId, reasonVal);
        detailBody.appendChild(newTr);
        initRow(newTr);
    }

    syncUI();
}

// ── Build một dòng mới với product/location/lot được set sẵn ─────────────────
function buildRow(i, productId, locationId, lotId, reason) {
    const productOptions = PRODUCTS.map(p =>
        `<option value="${p.id}" data-uom="${p.uom}" data-uom-id="${p.uom_id}" data-tracking="${p.tracking}"
            ${p.id == productId ? ' selected' : ''}>${p.code} — ${p.name}</option>`
    ).join('');

    const tracking = productId
        ? (PRODUCTS.find(p => p.id == productId)?.tracking ?? TRACKING_NONE)
        : TRACKING_NONE;

    const lockLot    = ![TRACKING_LOT, TRACKING_LOT_AND_SERIAL].includes(tracking);
    const lockSerial = ![TRACKING_SERIAL, TRACKING_LOT_AND_SERIAL].includes(tracking);

    const tr = document.createElement('tr');
    tr.className = 'detail-row';
    tr.innerHTML = `
      <td class="text-center text-body-secondary small row-num"></td>
      <td>
        <select class="form-select form-select-sm product-select" name="details[${i}][product_id]"
            required onchange="onProductChange(this)">
          <option value="">— Chọn hàng hóa —</option>
          ${productOptions}
        </select>
      </td>
      <td>
        <input type="hidden" name="details[${i}][uom_id]" class="uom-hidden"
            value="${productId ? (PRODUCTS.find(p => p.id == productId)?.uom_id ?? '') : ''}">
        <span class="uom-label text-body-secondary small">
            ${productId ? (PRODUCTS.find(p => p.id == productId)?.uom ?? '—') : '—'}
        </span>
      </td>
      <td>
        <input type="number" class="form-control form-control-sm text-end qty-input"
            name="details[${i}][quantity]" value="1" min="0.001" step="0.001" required
            onchange="onQtyChange(this)">
      </td>
      <td>
        <input type="hidden" name="details[${i}][location_id]" class="location-hidden" value="${locationId || ''}">
        <select class="form-select form-select-sm location-select" onchange="onLocationChange(this)">
          <option value="">Đang tải...</option>
        </select>
      </td>
      <td>
        <input type="hidden" name="details[${i}][lot_id]" class="lot-hidden" value="${lotId || ''}">
        <select class="form-select form-select-sm lot-select ${lockLot ? 'bg-body-secondary' : ''}"
            ${lockLot ? 'disabled' : ''} onchange="onLotChange(this)">
          <option value="">— Không —</option>
        </select>
      </td>
      <td>
        <input type="hidden" name="details[${i}][serial_id]" class="serial-hidden" value="">
        <select class="form-select form-select-sm serial-select ${lockSerial ? 'bg-body-secondary' : ''}"
            ${lockSerial ? 'disabled' : ''} onchange="onSerialChange(this)">
          <option value="">— Không —</option>
        </select>
      </td>
      <td>
        <input type="text" name="details[${i}][reason]" class="form-control form-control-sm"
            value="${reason || ''}" placeholder="Hư hỏng, hết hạn...">
      </td>
      <td>
        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row" title="Xóa dòng">
          <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use></svg>
        </button>
      </td>`;

    // Load location từ tồn kho nếu đã có productId
    if (productId) {
        // Chạy sau khi DOM append (setTimeout 0 để đảm bảo tr đã vào DOM)
        setTimeout(() => loadStockLocations(productId, tr, locationId, lotId, null), 0);
    } else {
        tr.querySelector('.location-select').innerHTML = '<option value="">— Chọn sản phẩm trước —</option>';
    }

    return tr;
}

// ── addRow: thêm dòng trống ───────────────────────────────────────────────────
function addRow() {
    const tr = buildRow(rowIndex++, null, null, null, null);
    detailBody.appendChild(tr);
    initRow(tr);
    syncUI();
    tr.querySelector('.product-select')?.focus();
}

// ── initRow: gắn event listeners ─────────────────────────────────────────────
function initRow(tr) {
    tr.querySelector('.btn-remove-row')?.addEventListener('click', () => {
        tr.remove();
        syncUI();
    });
}

// ── Init edit mode rows ───────────────────────────────────────────────────────
detailBody.querySelectorAll('.detail-row').forEach(tr => {
    initRow(tr);
    const productId  = tr.querySelector('.product-select')?.value;
    const locationId = tr.querySelector('.location-hidden')?.value;
    const lotId      = tr.querySelector('.lot-hidden')?.value;
    const serialId   = tr.querySelector('.serial-hidden')?.value;
    if (productId) loadStockLocations(productId, tr, locationId, lotId, serialId);
});
syncUI();

// ── Validate trước submit ─────────────────────────────────────────────────────
document.getElementById('scrapForm').addEventListener('submit', function(e) {
    let errors = [];

    // Bước 1: Validate bắt buộc nhập lot/serial
    detailBody.querySelectorAll('.detail-row').forEach((tr, i) => {
        const tracking  = trackingOfRow(tr);
        const lotSel    = tr.querySelector('.lot-select');
        const serialSel = tr.querySelector('.serial-select');
        [lotSel, serialSel].forEach(el => el?.classList.remove('is-invalid'));

        if ([TRACKING_LOT, TRACKING_LOT_AND_SERIAL].includes(tracking) && !lotSel?.value) {
            lotSel?.classList.add('is-invalid');
            errors.push(`Dòng ${i+1}: Hàng theo Lô phải chọn Số Lot.`);
        }
        if ([TRACKING_SERIAL, TRACKING_LOT_AND_SERIAL].includes(tracking) && !serialSel?.value) {
            serialSel?.classList.add('is-invalid');
            errors.push(`Dòng ${i+1}: Hàng theo Serial phải chọn Số Serial.`);
        }
    });

    // Bước 2: Validate serial trùng (cùng sản phẩm)
    const serialMap = {};
    detailBody.querySelectorAll('.detail-row').forEach((tr, i) => {
        const serialSel = tr.querySelector('.serial-select');
        if (!serialSel || serialSel.disabled || !serialSel.value) return;
        const productId = tr.querySelector('.product-select')?.value;
        const key = productId + '__' + serialSel.value;
        if (!serialMap[key]) {
            serialMap[key] = { firstRow: i + 1, sels: [] };
        }
        serialMap[key].sels.push({ sel: serialSel, row: i + 1 });
    });
    Object.values(serialMap).forEach(({ firstRow, sels }) => {
        if (sels.length > 1) {
            sels.forEach(({ sel, row }) => {
                sel.classList.add('is-invalid');
                const serialText = sel.options[sel.selectedIndex]?.text ?? '';
                if (row !== firstRow) {
                    errors.push(`Dòng ${row}: Serial <strong>${serialText}</strong> đã được chọn ở dòng ${firstRow}.`);
                }
            });
        }
    });

    const container = document.getElementById('lotSerialAlertContainer');
    if (errors.length) {
        e.preventDefault();
        container.innerHTML = `
            <div class="alert alert-danger alert-dismissible mx-3 mt-3">
              <svg class="icon me-2"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use></svg>
              <strong>Vui lòng kiểm tra:</strong>
              <ul class="mb-0 mt-1">${errors.map(a => `<li>${a}</li>`).join('')}</ul>
              <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
            </div>`;
        container.scrollIntoView({ behavior: 'smooth', block: 'center' });
    } else {
        container.innerHTML = '';
    }
});
</script>
@endpush

@endsection