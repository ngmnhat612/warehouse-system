@extends('layouts.app')

@section('title', (isset($transfer) ? 'Sửa phiếu chuyển kho' : 'Tạo phiếu chuyển kho') . ' — Warehouse System')

@section('breadcrumb')
<li class="breadcrumb-item">Nghiệp vụ kho</li>
<li class="breadcrumb-item"><a href="{{ route('transfers.index') }}">Chuyển kho</a></li>
<li class="breadcrumb-item active">{{ isset($transfer) ? $transfer->code : 'Tạo mới' }}</li>
@endsection

@section('content')

@php
$isEdit = isset($transfer);
$action = $isEdit ? route('transfers.update', $transfer->id) : route('transfers.store');
@endphp

{{-- HEADER --}}
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-semibold">{{ $isEdit ? 'Sửa phiếu chuyển kho' : 'Tạo phiếu chuyển kho mới' }}</h4>
        <small
            class="text-body-secondary">{{ $isEdit ? $transfer->code : 'Điền thông tin và thêm hàng hóa cần chuyển' }}</small>
    </div>
    <a href="{{ route('transfers.index') }}" class="btn btn-outline-secondary btn-sm">
        <svg class="icon me-1">
            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-left') }}"></use>
        </svg>
        Quay lại
    </a>
</div>

<form method="POST" action="{{ $action }}" id="transferForm">
    @csrf
    @if($isEdit) @method('PUT') @endif

    {{-- ── THÔNG TIN PHIẾU (1 hàng ngang) ── --}}
    <div class="card mb-3">
        <div class="card-header fw-semibold py-2">
            <svg class="icon me-1 text-primary">
                <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-description') }}"></use>
            </svg>
            Thông tin phiếu
        </div>
        <div class="card-body py-3">
            <div class="row g-3">

                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Mã phiếu</label>
                    <input type="text"
                        class="form-control form-control-sm text-uppercase @error('code') is-invalid @enderror"
                        name="code" value="{{ old('code', $transfer->code ?? '') }}" placeholder="Tự sinh nếu trống"
                        maxlength="50" {{ $isEdit ? 'readonly' : '' }}>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Loại chuyển kho <span
                            class="text-danger">*</span></label>
                    <select class="form-select form-select-sm @error('transfer_type') is-invalid @enderror"
                        name="transfer_type" required>
                        <option value="1"
                            {{ old('transfer_type', $transfer->transfer_type ?? 1) == 1 ? 'selected' : '' }}>Sắp xếp kho
                        </option>
                        <option value="2"
                            {{ old('transfer_type', $transfer->transfer_type ?? 1) == 2 ? 'selected' : '' }}>Từ
                            Quarantine</option>
                        <option value="3"
                            {{ old('transfer_type', $transfer->transfer_type ?? 1) == 3 ? 'selected' : '' }}>Khác
                        </option>
                    </select>
                    @error('transfer_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Ngày chuyển <span class="text-danger">*</span></label>
                    <input type="date" class="form-control form-control-sm @error('transfer_date') is-invalid @enderror"
                        name="transfer_date"
                        value="{{ old('transfer_date', isset($transfer->transfer_date) ? \Carbon\Carbon::parse($transfer->transfer_date)->format('Y-m-d') : date('Y-m-d')) }}"
                        required>
                    @error('transfer_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Ghi chú</label>
                    <input type="text" class="form-control form-control-sm" name="note"
                        value="{{ old('note', $transfer->note ?? '') }}" placeholder="Ghi chú nếu có..."
                        maxlength="500">
                </div>

            </div>{{-- end row g-3 --}}
        </div>{{-- end card-body --}}
    </div>{{-- end card --}}

    {{-- ── CHI TIẾT HÀNG HÓA ── --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center py-2">
            <span class="fw-semibold">
                <svg class="icon me-1 text-primary">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-list') }}"></use>
                </svg>
                Chi tiết hàng hóa cần chuyển
            </span>
            <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddRow">
                <svg class="icon me-1">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use>
                </svg>
                Thêm dòng
            </button>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0" id="detailTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width:36px"></th>
                            <th>Hàng hóa <span class="text-danger">*</span></th>
                            <th style="width:90px">ĐVT</th>
                            <th style="width:110px">Số lượng <span class="text-danger">*</span></th>
                            <th style="width:130px">Vị trí nguồn <span class="text-danger">*</span></th>
                            <th style="width:130px">Vị trí đích <span class="text-danger">*</span></th>
                            <th style="width:110px">
                                Số Lot/Batch
                                <svg class="icon icon-sm text-body-secondary" title="Tự điền khi chọn vị trí nguồn">
                                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-info') }}">
                                    </use>
                                </svg>
                            </th>
                            <th style="width:110px">
                                Số Serial
                                <svg class="icon icon-sm text-body-secondary" title="Tự điền khi chọn vị trí nguồn">
                                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-info') }}">
                                    </use>
                                </svg>
                            </th>
                            <th style="width:180px">Ghi chú</th>
                            <th style="width:36px"></th>
                        </tr>
                    </thead>
                    <tbody id="detailBody">

                        @if($isEdit && $transfer->details->count())
                        @foreach($transfer->details as $i => $detail)
                        <tr>
                            <td class="text-center text-body-secondary small">{{ $i + 1 }}</td>
                            <td>
                                <select class="form-select form-select-sm product-select"
                                    name="details[{{ $i }}][product_id]" required onchange="onProductChange(this)">
                                    <option value="">— Chọn hàng hóa —</option>
                                    @foreach($products as $p)
                                    <option value="{{ $p->id }}" data-uom="{{ $p->uom?->name }}"
                                        data-uom-id="{{ $p->uom_id }}"
                                        data-tracking="{{ (int) ($p->tracking_type ?? 1) }}"
                                        {{ $detail->product_id == $p->id ? 'selected' : '' }}>
                                        {{ $p->code }} — {{ $p->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="hidden" name="details[{{ $i }}][uom_id]" class="uom-hidden"
                                    value="{{ $detail->uom_id }}">
                                <span
                                    class="uom-label text-body-secondary small">{{ $detail->uom?->name ?? '—' }}</span>
                            </td>
                            <td>
                                <input type="number" class="form-control form-control-sm text-end qty-input"
                                    name="details[{{ $i }}][quantity]" value="{{ $detail->quantity }}" min="0.001"
                                    step="0.001" required oninput="updateTotals()" onchange="onQuantityChange(this)">
                            </td>
                            <td>
                                <select class="form-select form-select-sm from-location-select" required
                                    onchange="validateLocationPair(this); onFromLocationChange(this)">
                                    <option value="">— Chọn —</option>
                                    @foreach($locations as $loc)
                                    <option value="{{ $loc->id }}" data-location-id="{{ $loc->id }}"
                                        {{ $detail->from_location_id == $loc->id ? 'selected' : '' }}>
                                        {{ $loc->code }}
                                    </option>
                                    @endforeach
                                </select>
                                <input type="hidden" class="from-location-id-hidden"
                                    name="details[{{ $i }}][from_location_id]" value="{{ $detail->from_location_id }}">
                            </td>
                            <td>
                                <select class="form-select form-select-sm to-location-select"
                                    name="details[{{ $i }}][to_location_id]" required
                                    onchange="validateLocationPair(this)">
                                    <option value="">— Chọn —</option>
                                    @foreach($locations as $loc)
                                    <option value="{{ $loc->id }}"
                                        {{ $detail->to_location_id == $loc->id ? 'selected' : '' }}>
                                        {{ $loc->code }}
                                    </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm lot-display" readonly
                                    placeholder="—" value="{{ $detail->lot?->lot_number ?? '' }}">
                                <input type="hidden" class="lot-id-hidden" name="details[{{ $i }}][lot_id]"
                                    value="{{ $detail->lot_id }}">
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-1">
                                    <input type="text" class="form-control form-control-sm serial-display flex-grow-1"
                                        readonly placeholder="—" value="{{ $detail->serial?->serial_number ?? '' }}">
                                    <span class="text-danger serial-dup-icon d-none" title="Trùng số serial!">
                                        <svg class="icon icon-sm">
                                            <use
                                                xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}">
                                            </use>
                                        </svg>
                                    </span>
                                </div>
                                <input type="hidden" class="serial-id-hidden" name="details[{{ $i }}][serial_id]"
                                    value="{{ $detail->serial_id }}">
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm" name="details[{{ $i }}][note]"
                                    value="{{ $detail->note ?? '' }}" placeholder="Ghi chú..." maxlength="200">
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-danger p-1 btn-remove-row"
                                    title="Xóa dòng">
                                    <svg class="icon">
                                        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}">
                                        </use>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                        @endif

                    </tbody>
                </table>
            </div>

            <div id="emptyDetail" class="text-center text-body-secondary py-5"
                style="{{ ($isEdit && $transfer->details->count()) ? 'display:none' : '' }}">
                <svg class="icon icon-3xl d-block mx-auto mb-2 opacity-25">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-transfer') }}"></use>
                </svg>
                Chưa có hàng hóa nào.<br>
                <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="btnAddRowEmpty">
                    <svg class="icon me-1">
                        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use>
                    </svg>
                    Thêm dòng đầu tiên
                </button>
            </div>
        </div>

        <div class="card-footer text-body-secondary small d-flex justify-content-between">
            <span>Tổng số dòng: <strong id="rowCount">{{ $isEdit ? $transfer->details->count() : 0 }}</strong></span>
            <span>Tổng SL chuyển: <strong id="totalQty">0</strong></span>
        </div>
    </div>

    {{-- Cảnh báo vị trí trùng --}}
    <div class="alert alert-warning d-none mt-3" id="locationWarning">
        <svg class="icon me-1">
            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use>
        </svg>
        <strong>Cảnh báo:</strong> Một số dòng có vị trí nguồn và vị trí đích giống nhau.
    </div>
    {{-- Cảnh báo serial trùng --}}
    <div class="alert alert-danger alert-dismissible d-none mt-2" id="serialDupAlert" role="alert">
        <svg class="icon me-1">
            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use>
        </svg>
        <strong>Có số Serial bị trùng giữa các dòng.</strong> Vui lòng kiểm tra các ô được đánh dấu đỏ.
        <button type="button" class="btn-close" data-coreui-dismiss="alert" aria-label="Close"></button>
    </div>
    {{-- ── NÚT LƯU ── --}}
    <div class="d-flex gap-2 justify-content-end mt-3">
        <a href="{{ route('transfers.index') }}" class="btn btn-outline-secondary">Hủy</a>
        @if(!$isEdit)
        <button type="submit" class="btn btn-outline-primary" name="action" value="save_and_new">
            <svg class="icon me-1">
                <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use>
            </svg>
            Lưu & Tạo phiếu mới
        </button>
        @endif
        <button type="submit" class="btn btn-primary" name="action" value="save">
            <svg class="icon me-1">
                <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-save') }}"></use>
            </svg>
            {{ $isEdit ? 'Cập nhật phiếu' : 'Lưu phiếu chuyển kho' }}
        </button>
    </div>
    </div>
    </div>

</form>

@endsection

@php
$productsJs = $products->map(fn($p) => [
'id' => $p->id,
'code' => $p->code,
'name' => $p->name,
'uom' => $p->uom?->name ?? '—',
'uom_id' => $p->uom_id,
])->values();

$locationsJs = $locations->map(fn($l) => [
'id' => $l->id,
'code' => $l->code,
'name' => $l->name ?? '',
])->values();

$lotsJs = ($lots ?? collect())->map(fn($g) => $g->values());
@endphp

@push('scripts')
<script>
const PRODUCTS = @json($productsJson);
const LOCATIONS = @json($locationsJson);

const TRACKING_SERIAL = 3;
const TRACKING_LOT_AND_SERIAL = 4;

let rowIndex = <?php echo $isEdit ? $transfer->details->count() : 0; ?>;

// ── Template dòng chi tiết ─────────────────────────────────────────
function rowTemplate(i) {
    const productOptions = PRODUCTS.map(p =>
        `<option value="${p.id}" data-uom="${p.uom}" data-uom-id="${p.uom_id}" data-tracking="${p.tracking}">
      ${p.code} — ${p.name}
    </option>`
    ).join('');

    const locationOptions = LOCATIONS.map(l =>
        `<option value="${l.id}">${l.code}${l.name ? ' — ' + l.name : ''}</option>`
    ).join('');

    return `
  <tr>
    <td class="text-center text-body-secondary small">${i + 1}</td>
    <td>
      <select class="form-select form-select-sm product-select"
              name="details[${i}][product_id]" required
              onchange="onProductChange(this)">
        <option value="">— Chọn hàng hóa —</option>
        ${productOptions}
      </select>
    </td>
    <td>
      <input type="hidden" name="details[${i}][uom_id]" class="uom-hidden" value="">
      <span class="uom-label text-body-secondary small">—</span>
    </td>
    <td>
      <input type="number" class="form-control form-control-sm text-end qty-input"
             name="details[${i}][quantity]"
             min="0.001" step="0.001" required placeholder="0"
             oninput="updateTotals()" onchange="onQuantityChange(this)">
    </td>
    <td>
      <select class="form-select form-select-sm from-location-select" required
              onchange="validateLocationPair(this); onFromLocationChange(this)">
        <option value="">— Nguồn —</option>
        ${locationOptions}
      </select>
      <input type="hidden" class="from-location-id-hidden" name="details[${i}][from_location_id]" value="">
    </td>
    <td>
      <select class="form-select form-select-sm to-location-select"
              name="details[${i}][to_location_id]" required
              onchange="validateLocationPair(this)">
        <option value="">— Đích —</option>
        ${locationOptions}
      </select>
    </td>
    <td>
      <input type="text" class="form-control form-control-sm lot-display" readonly placeholder="—" value="">
      <input type="hidden" class="lot-id-hidden" name="details[${i}][lot_id]" value="">
    </td>
    <td>
      <div class="d-flex align-items-center gap-1">
        <input type="text" class="form-control form-control-sm serial-display flex-grow-1" readonly placeholder="—" value="">
        <span class="text-danger serial-dup-icon d-none" title="Trùng số serial!">
          <svg class="icon icon-sm"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use></svg>
        </span>
      </div>
      <input type="hidden" class="serial-id-hidden" name="details[${i}][serial_id]" value="">
    </td>
    <td>
      <input type="text" class="form-control form-control-sm"
             name="details[${i}][note]"
             placeholder="Ghi chú..." maxlength="200">
    </td>
    <td>
      <button type="button" class="btn btn-sm btn-outline-danger p-1 btn-remove-row"
              title="Xóa dòng">
        <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use></svg>
      </button>
    </td>
  </tr>`;
}

function addRow() {
    const tbody = document.getElementById('detailBody');
    tbody.insertAdjacentHTML('beforeend', rowTemplate(rowIndex));
    rowIndex++;
    const newRow = tbody.lastElementChild;
    newRow.querySelector('.btn-remove-row').addEventListener('click', () => removeRow(newRow.querySelector(
        '.btn-remove-row')));
    syncRowNumbers();
    toggleEmptyState();
    updateTotals();
}

function removeRow(btn) {
    btn.closest('tr').remove();
    syncRowNumbers();
    toggleEmptyState();
    updateTotals();
    checkAllLocationPairs();
}

function syncRowNumbers() {
    document.querySelectorAll('#detailBody tr').forEach((tr, i) => {
        tr.querySelector('td:first-child').textContent = i + 1;
    });
}

function toggleEmptyState() {
    const rows = document.querySelectorAll('#detailBody tr').length;
    document.getElementById('emptyDetail').style.display = rows ? 'none' : '';
    document.getElementById('rowCount').textContent = rows;
}

function updateTotals() {
    let total = 0;
    document.querySelectorAll('input[name$="[quantity]"]').forEach(inp => {
        total += parseFloat(inp.value) || 0;
    });
    document.getElementById('totalQty').textContent =
        total.toLocaleString('vi-VN', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 3
        });
}

// ── Khi chọn hàng hóa → điền ĐVT và lots ────────────────────────
function onProductChange(sel) {
    const opt = sel.options[sel.selectedIndex];
    const tr = sel.closest('tr');
    const productId = parseInt(opt.value);

    tr.querySelector('.uom-label').textContent = opt.dataset.uom || '—';
    tr.querySelector('.uom-hidden').value = opt.dataset.uomId || '';

    const fromSel = tr.querySelector('.from-location-select');
    const display = tr.querySelector('.lot-serial-display');
    const lotHidden = tr.querySelector('.lot-id-hidden');
    const serialHidden = tr.querySelector('.serial-id-hidden');

    if (!productId) {
        // Reset nếu chưa chọn sản phẩm
        if (fromSel) fromSel.innerHTML = '<option value="">— Nguồn —</option>';
        const lotDisplay = tr.querySelector('.lot-display');
        const serialDisplay = tr.querySelector('.serial-display');
        if (lotDisplay) lotDisplay.value = '';
        if (serialDisplay) serialDisplay.value = '';
        if (lotHidden) lotHidden.value = '';
        if (serialHidden) serialHidden.value = '';
        return;
    }

    // Gọi AJAX lấy vị trí có tồn kho
    if (fromSel) {
        fromSel.innerHTML = '<option value="">Đang tải...</option>';
        fromSel.disabled = true;
    }

    fetch(`{{ route('transfers.stock-locations') }}?product_id=${productId}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(r => r.json())
        .then(data => {
            // Populate vị trí nguồn (kèm data lot/serial tương ứng)
            if (fromSel) {
                fromSel.disabled = false;
                if (data.length === 0) {
                    fromSel.innerHTML = '<option value="">— Không có tồn kho —</option>';
                } else {
                    fromSel.innerHTML = '<option value="">— Chọn vị trí nguồn —</option>' +
                        data.map((s, idx) =>
                            `<option value="${s.location_id}_${s.lot_id ?? ''}_${s.serial_id ?? ''}_${idx}"` +
                            ` data-location-id="${s.location_id}"` +
                            ` data-lot-id="${s.lot_id ?? ''}"` +
                            ` data-lot-number="${s.lot_number ?? ''}"` +
                            ` data-serial-id="${s.serial_id ?? ''}"` +
                            ` data-serial-number="${s.serial_number ?? ''}">` +
                            `${s.code}${s.name ? ' — ' + s.name : ''}` +
                            `${s.serial_number ? ' — SN:' + s.serial_number : ''}` +
                            `${s.lot_number ? ' — Lot:' + s.lot_number : ''}` +
                            ` (KD: ${s.available_qty})</option>`
                        ).join('');
                }
            }

            // Reset hiển thị Lot/Serial — sẽ được điền khi chọn vị trí nguồn
            const lotDisplay = tr.querySelector('.lot-display');
            const serialDisplay = tr.querySelector('.serial-display');
            if (lotDisplay) lotDisplay.value = '';
            if (serialDisplay) serialDisplay.value = '';
            if (lotHidden) lotHidden.value = '';
            if (serialHidden) serialHidden.value = '';

            const fromLocHidden = tr.querySelector('.from-location-id-hidden');
            if (fromLocHidden) fromLocHidden.value = '';
        })
        .catch(() => {
            if (fromSel) {
                fromSel.disabled = false;
                fromSel.innerHTML = '<option value="">— Lỗi tải dữ liệu —</option>';
            }
        });
}

// ── Khởi tạo dòng có sẵn (Edit): gọi AJAX để lấy vị trí khả dụng,
//    nhưng giữ nguyên from_location_id / lot_id / serial_id đã lưu ──
function initExistingRow(tr) {
    const productSel = tr.querySelector('.product-select');
    const productId = parseInt(productSel?.value);
    if (!productId) return;

    const fromSel = tr.querySelector('.from-location-select');
    const fromLocHidden = tr.querySelector('.from-location-id-hidden');
    const lotHidden = tr.querySelector('.lot-id-hidden');
    const serialHidden = tr.querySelector('.serial-id-hidden');
    const display = tr.querySelector('.lot-serial-display');
    if (!fromSel) return;

    const savedLocationId = fromLocHidden?.value || '';
    const savedLotId = lotHidden?.value || '';
    const savedSerialId = serialHidden?.value || '';

    fromSel.innerHTML = '<option value="">Đang tải...</option>';
    fromSel.disabled = true;

    fetch(`{{ route('transfers.stock-locations') }}?product_id=${productId}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(r => r.json())
        .then(data => {
            fromSel.disabled = false;

            if (data.length === 0) {
                fromSel.innerHTML = '<option value="">— Không có tồn kho —</option>';
                return;
            }

            let matchedValue = '';
            const optionsHtml = data.map((s, idx) => {
                const value = `${s.location_id}_${s.lot_id ?? ''}_${s.serial_id ?? ''}_${idx}`;
                const isMatch =
                    String(s.location_id) === String(savedLocationId) &&
                    String(s.lot_id ?? '') === String(savedLotId) &&
                    String(s.serial_id ?? '') === String(savedSerialId);
                if (isMatch) matchedValue = value;

                return `<option value="${value}"` +
                    ` data-location-id="${s.location_id}"` +
                    ` data-lot-id="${s.lot_id ?? ''}"` +
                    ` data-lot-number="${s.lot_number ?? ''}"` +
                    ` data-serial-id="${s.serial_id ?? ''}"` +
                    ` data-serial-number="${s.serial_number ?? ''}"` +
                    `${isMatch ? ' selected' : ''}>` +
                    `${s.code}${s.name ? ' — ' + s.name : ''}` +
                    `${s.serial_number ? ' — SN:' + s.serial_number : ''}` +
                    `${s.lot_number ? ' — Lot:' + s.lot_number : ''}` +
                    ` (KD: ${s.available_qty})</option>`;
            }).join('');

            fromSel.innerHTML = '<option value="">— Chọn vị trí nguồn —</option>' + optionsHtml;

            if (matchedValue) {
                fromSel.value = matchedValue;
            }
            // Giữ nguyên hidden lot/serial/location đã lưu, display giữ nguyên giá trị hiện có
        })
        .catch(() => {
            fromSel.disabled = false;
            fromSel.innerHTML = '<option value="">— Lỗi tải dữ liệu —</option>';
        });
}

// ── Kiểm tra vị trí nguồn ≠ vị trí đích ─────────────────────────
function validateLocationPair(sel) {
    const tr = sel.closest('tr');
    const fromSel = tr.querySelector('.from-location-select');
    const toSel = tr.querySelector('.to-location-select');
    const fromOpt = fromSel.options[fromSel.selectedIndex];
    const fromVal = fromOpt?.dataset.locationId || '';
    const toVal = toSel.value;

    if (fromVal && toVal && fromVal === toVal) {
        toSel.classList.add('is-invalid');
        fromSel.classList.add('is-invalid');
    } else {
        toSel.classList.remove('is-invalid');
        fromSel.classList.remove('is-invalid');
    }
    checkAllLocationPairs();
}

// ── Khi chọn vị trí nguồn → tự điền Lot/Serial tương ứng ────────
function onFromLocationChange(sel) {
    const tr = sel.closest('tr');
    const opt = sel.options[sel.selectedIndex];
    const lotDisplay = tr.querySelector('.lot-display');
    const serialDisplay = tr.querySelector('.serial-display');
    const lotHidden = tr.querySelector('.lot-id-hidden');
    const serialHidden = tr.querySelector('.serial-id-hidden');
    const fromLocHidden = tr.querySelector('.from-location-id-hidden');
    if (!lotHidden || !serialHidden || !fromLocHidden) return;

    const locationId = opt?.dataset.locationId || '';
    const lotId = opt?.dataset.lotId || '';
    const lotNumber = opt?.dataset.lotNumber || '';
    const serialId = opt?.dataset.serialId || '';
    const serialNumber = opt?.dataset.serialNumber || '';

    fromLocHidden.value = locationId;
    lotHidden.value = lotId;
    serialHidden.value = serialId;
    if (lotDisplay) lotDisplay.value = lotNumber || '';
    if (serialDisplay) serialDisplay.value = serialNumber || '';
    // Xóa trạng thái lỗi trùng serial khi chọn lại vị trí nguồn
    tr.querySelector('.serial-display')?.classList.remove('is-invalid');
    tr.querySelector('.serial-dup-icon')?.classList.add('d-none');
}

// ── Nếu hàng serial-tracking và SL > 1 → tách thành nhiều dòng (mỗi dòng = 1 serial) ──
function onQuantityChange(input) {
    const tr = input.closest('tr');
    const productSel = tr.querySelector('.product-select');
    const opt = productSel.options[productSel.selectedIndex];
    const tracking = parseInt(opt?.dataset.tracking || '1');
    const qty = parseInt(input.value) || 0;

    if ((tracking === TRACKING_SERIAL || tracking === TRACKING_LOT_AND_SERIAL) && qty > 1) {
        input.value = 1;
        updateTotals();

        const extra = qty - 1;
        for (let k = 0; k < extra; k++) {
            duplicateRowForSerial(tr);
        }
    }
}

// Tạo thêm dòng mới với cùng hàng hóa + vị trí đích, SL = 1, chờ chọn serial nguồn riêng
function duplicateRowForSerial(sourceTr) {
    const productId = sourceTr.querySelector('.product-select').value;
    const toLocationId = sourceTr.querySelector('.to-location-select').value;

    addRow();

    const tbody = document.getElementById('detailBody');
    const newTr = tbody.lastElementChild;

    const newProductSel = newTr.querySelector('.product-select');
    newProductSel.value = productId;
    onProductChange(newProductSel);

    const newToSel = newTr.querySelector('.to-location-select');
    if (toLocationId) newToSel.value = toLocationId;

    newTr.querySelector('.qty-input').value = 1;
    updateTotals();
}

function checkAllLocationPairs() {
    const hasWarning = document.querySelector('.from-location-select.is-invalid, .to-location-select.is-invalid') !==
        null;
    document.getElementById('locationWarning').classList.toggle('d-none', !hasWarning);
}

// ── Chặn submit nếu trùng Serial giữa các dòng ──────────────────
document.getElementById('transferForm')?.addEventListener('submit', function(e) {
    // Reset trạng thái lỗi cũ
    document.querySelectorAll('#detailBody .serial-display.is-invalid')
        .forEach(el => el.classList.remove('is-invalid'));
    document.querySelectorAll('#detailBody .serial-dup-icon')
        .forEach(el => el.classList.add('d-none'));
    document.getElementById('serialDupAlert')?.classList.add('d-none');

    // Gom nhóm theo serial_id
    const groups = new Map();
    document.querySelectorAll('#detailBody tr').forEach(tr => {
        const serialId = tr.querySelector('.serial-id-hidden')?.value;
        if (!serialId) return;
        if (!groups.has(serialId)) groups.set(serialId, []);
        groups.get(serialId).push(tr);
    });

    let hasDup = false;
    groups.forEach(rows => {
        if (rows.length > 1) {
            hasDup = true;
            rows.forEach(tr => {
                tr.querySelector('.serial-display')?.classList.add('is-invalid');
                tr.querySelector('.serial-dup-icon')?.classList.remove('d-none');
            });
        }
    });

    if (hasDup) {
        e.preventDefault();
        const dupAlert = document.getElementById('serialDupAlert');
        if (dupAlert) {
            dupAlert.classList.remove('d-none');
            dupAlert.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest'
            });
        }
    }
});

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('btnAddRow')?.addEventListener('click', addRow);
    document.getElementById('btnAddRowEmpty')?.addEventListener('click', addRow);

    toggleEmptyState();
    updateTotals();
    @if(!$isEdit)
    addRow();
    @else
    document.querySelectorAll('#detailBody tr').forEach(tr => initExistingRow(tr));
    @endif
});
</script>
@endpush
