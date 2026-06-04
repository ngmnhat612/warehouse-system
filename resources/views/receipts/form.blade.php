@extends('layouts.app')

@section('title', (isset($receipt) ? 'Sửa phiếu nhập' : 'Tạo phiếu nhập') . ' — Warehouse System')

@section('breadcrumb')
<li class="breadcrumb-item">Nghiệp vụ kho</li>
<li class="breadcrumb-item"><a href="{{ route('receipts.index') }}">Nhập kho</a></li>
<li class="breadcrumb-item active">{{ isset($receipt) ? $receipt->code : 'Tạo mới' }}</li>
@endsection

@section('content')

@php
$isEdit = isset($receipt);
$action = $isEdit ? route('receipts.update', $receipt->id) : route('receipts.store');
@endphp

{{-- HEADER --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-semibold">{{ $isEdit ? 'Sửa phiếu nhập' : 'Tạo phiếu nhập mới' }}</h4>
        <small
            class="text-body-secondary">{{ $isEdit ? $receipt->code : 'Điền thông tin và thêm hàng hóa cần nhập' }}</small>
    </div>
    <a href="{{ route('receipts.index') }}" class="btn btn-outline-secondary">
        <svg class="icon me-1">
            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-left') }}"></use>
        </svg>
        Quay lại
    </a>
</div>

<form method="POST" action="{{ $action }}" id="receiptForm">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="row g-4">

        {{-- CỘT TRÁI: Thông tin phiếu --}}
        <div class="col-lg-4">

            {{-- Thông tin chung --}}
            <div class="card mb-4">
                <div class="card-header fw-semibold">
                    <svg class="icon me-1 text-primary">
                        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-description') }}"></use>
                    </svg>
                    Thông tin phiếu
                </div>
                <div class="card-body">

                    <div class="mb-3">
                        <label class="form-label">Mã phiếu <span class="text-danger">*</span></label>
                        <input type="text" class="form-control text-uppercase @error('code') is-invalid @enderror"
                            name="code" value="{{ old('code', $receipt->code ?? '') }}" placeholder="VD: NK-2024-001"
                            maxlength="50" {{ $isEdit ? 'readonly required' : '' }}>
                        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @if(!$isEdit)
                        <div class="form-text">Để trống để hệ thống tự sinh mã.</div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Loại nhập <span class="text-danger">*</span></label>
                        <select class="form-select @error('receipt_type') is-invalid @enderror" name="receipt_type"
                            id="receiptType" required>
                            <option value="1"
                                {{ old('receipt_type', $receipt->receipt_type ?? 1) == 1 ? 'selected' : '' }}>Từ nhà
                                cung cấp</option>
                            <option value="2"
                                {{ old('receipt_type', $receipt->receipt_type ?? 1) == 2 ? 'selected' : '' }}>Trả hàng
                                từ SX / bảo trì</option>
                            <option value="3"
                                {{ old('receipt_type', $receipt->receipt_type ?? 1) == 3 ? 'selected' : '' }}>Khác
                            </option>
                        </select>
                        @error('receipt_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3" id="supplierGroup">
                        <label class="form-label">Nhà cung cấp</label>
                        <select class="form-select @error('supplier_id') is-invalid @enderror" name="supplier_id">
                            <option value="">— Chọn NCC —</option>
                            @foreach ($suppliers as $sup)
                            <option value="{{ $sup->id }}"
                                {{ old('supplier_id', $receipt->supplier_id ?? '') == $sup->id ? 'selected' : '' }}>
                                {{ $sup->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('supplier_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Số tham chiếu</label>
                        <input type="text" class="form-control @error('reference_no') is-invalid @enderror"
                            name="reference_no" value="{{ old('reference_no', $receipt->reference_no ?? '') }}"
                            placeholder="Số PO / chứng từ liên quan" maxlength="100">
                        @error('reference_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Ngày nhập <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('receipt_date') is-invalid @enderror"
                            name="receipt_date"
                            value="{{ old('receipt_date', isset($receipt->receipt_date) ? \Carbon\Carbon::parse($receipt->receipt_date)->format('Y-m-d') : date('Y-m-d')) }}"
                            required>
                        @error('receipt_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    @if($isEdit)
                    <div class="mb-3">
                        <label class="form-label">Trạng thái</label>
                        <select class="form-select" name="status">
                            <option value="1" {{ ($receipt->status ?? 1) == 1 ? 'selected' : '' }}>Draft</option>
                            <option value="2" {{ ($receipt->status ?? 1) == 2 ? 'selected' : '' }}>Chờ duyệt</option>
                            <option value="3" {{ ($receipt->status ?? 1) == 3 ? 'selected' : '' }}>Đã duyệt</option>
                            <option value="4" {{ ($receipt->status ?? 1) == 4 ? 'selected' : '' }}>Hoàn thành</option>
                            <option value="5" {{ ($receipt->status ?? 1) == 5 ? 'selected' : '' }}>Đã hủy</option>
                        </select>
                    </div>
                    @endif

                    <div class="mb-0">
                        <label class="form-label">Ghi chú</label>
                        <textarea class="form-control" name="note" rows="3"
                            placeholder="Ghi chú thêm nếu có...">{{ old('note', $receipt->note ?? '') }}</textarea>
                    </div>

                </div>
            </div>

            {{-- Nút lưu --}}
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg" name="action" value="save">
                    <svg class="icon me-1">
                        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-save') }}"></use>
                    </svg>
                    {{ $isEdit ? 'Cập nhật phiếu' : 'Lưu phiếu nhập' }}
                </button>
                @if(!$isEdit)
                <button type="submit" class="btn btn-outline-primary" name="action" value="save_and_new">
                    <svg class="icon me-1">
                        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use>
                    </svg>
                    Lưu & tạo phiếu mới
                </button>
                @endif
                <a href="{{ route('receipts.index') }}" class="btn btn-outline-secondary">Hủy</a>
            </div>

        </div>

        {{-- CỘT PHẢI: Chi tiết hàng hóa --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">
                        <svg class="icon me-1 text-primary">
                            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-list') }}"></use>
                        </svg>
                        Chi tiết hàng hóa
                    </span>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addRow()">
                        <svg class="icon me-1">
                            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use>
                        </svg>
                        Thêm dòng
                    </button>
                </div>

                <div class="card-body p-0">
                    <div id="lotSerialAlertContainer"></div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0" id="detailTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:36px"></th>
                                    <th>Hàng hóa <span class="text-danger">*</span></th>
                                    <th style="width:100px">ĐVT</th>
                                    <th style="width:110px">SL dự kiến <span class="text-danger">*</span></th>
                                    <th style="width:110px">SL thực nhận</th>
                                    <th style="width:130px">Vị trí kho</th>
                                    <th style="width:110px">Lot / Batch</th>
                                    <th style="width:105px">Hạn dùng</th>
                                    <th style="width:36px"></th>
                                </tr>
                            </thead>
                            <tbody id="detailBody">

                                @if($isEdit && $receipt->details->count())
                                @foreach($receipt->details as $i => $detail)
                                <tr>
                                    <td class="text-center text-body-secondary small">{{ $i + 1 }}</td>
                                    <td>
                                        <select class="form-select form-select-sm product-select"
                                            name="details[{{ $i }}][product_id]" required
                                            onchange="onProductChange(this)">
                                            <option value="">— Chọn hàng hóa —</option>
                                            @foreach($products as $p)
                                            <option value="{{ $p->id }}" data-uom="{{ $p->uom?->name }}"
                                                data-uom-id="{{ $p->uom_id }}"
                                                data-tracking="{{ (int)($p->tracking_type ?? 1) }}"
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
                                        <input type="number" class="form-control form-control-sm text-end"
                                            name="details[{{ $i }}][expected_qty]" value="{{ $detail->expected_qty }}"
                                            min="0.001" step="0.001" required>
                                    </td>
                                    <td>
                                        <input type="number"
                                            class="form-control form-control-sm text-end actual-qty-input"
                                            name="details[{{ $i }}][actual_qty]" value="{{ $detail->actual_qty }}"
                                            min="0" step="0.001">
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm"
                                            name="details[{{ $i }}][location_id]">
                                            <option value="">— Chọn —</option>
                                            @foreach($locations as $loc)
                                            <option value="{{ $loc->id }}"
                                                {{ $detail->location_id == $loc->id ? 'selected' : '' }}>
                                                {{ $loc->code }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm lot-serial-input"
                                            name="details[{{ $i }}][lot_number]"
                                            value="{{ $detail->lot?->lot_number ?? ($detail->serial?->serial_number ?? '') }}"
                                            placeholder="Số lot" maxlength="100">
                                    </td>
                                    <td>
                                        <input type="date" class="form-control form-control-sm"
                                            name="details[{{ $i }}][expiry_date]"
                                            value="{{ $detail->expiry_date ? \Carbon\Carbon::parse($detail->expiry_date)->format('Y-m-d') : '' }}">
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-danger p-1"
                                            onclick="removeRow(this)" title="Xóa dòng">
                                            <svg class="icon">
                                                <use
                                                    xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}">
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

                    {{-- Empty state --}}
                    <div id="emptyDetail" class="text-center text-body-secondary py-5">
                        <svg class="icon icon-3xl d-block mx-auto mb-2 opacity-25">
                            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-list') }}"></use>
                        </svg>
                        Chưa có hàng hóa nào.<br>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addRow()">
                            <svg class="icon me-1">
                                <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use>
                            </svg>
                            Thêm dòng đầu tiên
                        </button>
                    </div>
                </div>

                <div class="card-footer text-body-secondary small d-flex justify-content-between">
                    <span>Tổng số dòng: <strong
                            id="rowCount">{{ $isEdit ? $receipt->details->count() : 0 }}</strong></span>
                    <span>Tổng SL dự kiến: <strong id="totalExpected">0</strong></span>
                </div>
            </div>
        </div>

    </div>{{-- end row --}}
</form>

@endsection

@push('scripts')
<script>
// ── Dữ liệu từ controller ──────────────────────────────────────────
const PRODUCTS = @json($productsJson);
const LOCATIONS = @json($locationsJson);

let rowIndex = <?php echo $isEdit ? $receipt->details->count() : 0; ?>;

// ── Áp dụng trạng thái tracking cho một <tr> ──────────────────────
function applyTracking(tr, tracking) {
    const lotInput = tr.querySelector('.lot-serial-input');
    const actualInput = tr.querySelector('.actual-qty-input');
    if (!lotInput) return;

    if (tracking === 1) {
        // Không quản lý lô — khóa ô lot
        lotInput.disabled = true;
        lotInput.value = '';
        lotInput.placeholder = '—';
        lotInput.classList.add('bg-body-secondary');
        if (actualInput) actualInput.removeAttribute('readonly');
    } else if (tracking === 2) {
        // Quản lý theo Lot
        lotInput.disabled = false;
        lotInput.placeholder = 'Số lot / batch';
        lotInput.classList.remove('bg-body-secondary');
        if (actualInput) actualInput.removeAttribute('readonly');
    } else if (tracking === 3) {
        // Quản lý theo Serial — actual_qty luôn = 1
        lotInput.disabled = false;
        lotInput.placeholder = 'Mã serial';
        lotInput.classList.remove('bg-body-secondary');
        if (actualInput) {
            actualInput.value = 1;
            actualInput.setAttribute('readonly', 'readonly');
            actualInput.classList.add('bg-body-secondary');
        }
    }
}

// ── Template một dòng chi tiết ─────────────────────────────────────
function rowTemplate(i) {
    const productOptions = PRODUCTS.map(p =>
        `<option value="${p.id}"
            data-uom="${p.uom}"
            data-uom-id="${p.uom_id}"
            data-tracking="${p.tracking}">${p.code} — ${p.name}</option>`
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
      <input type="number" class="form-control form-control-sm text-end"
             name="details[${i}][expected_qty]"
             min="0.001" step="0.001" required placeholder="0"
             oninput="updateTotals()"
             onchange="onExpectedQtyChange(this)">
    </td>
    <td>
      <input type="number" class="form-control form-control-sm text-end actual-qty-input"
             name="details[${i}][actual_qty]"
             min="0" step="0.001" placeholder="0">
    </td>
    <td>
      <select class="form-select form-select-sm" name="details[${i}][location_id]">
        <option value="">— Chọn —</option>
        ${locationOptions}
      </select>
    </td>
    <td>
      <input type="text" class="form-control form-control-sm lot-serial-input"
             name="details[${i}][lot_number]"
             placeholder="Số lot" maxlength="100" disabled
             oninput="clearLotError(this)">
    </td>
    <td>
      <input type="date" class="form-control form-control-sm"
             name="details[${i}][expiry_date]">
    </td>
    <td>
      <button type="button" class="btn btn-sm btn-outline-danger p-1"
              onclick="removeRow(this)" title="Xóa dòng">
        <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use></svg>
      </button>
    </td>
  </tr>`;
}

// ── Khi chọn hàng hóa → điền ĐVT + áp tracking ───────────────────
function onProductChange(sel) {
    const opt = sel.options[sel.selectedIndex];
    const tr = sel.closest('tr');
    const tracking = parseInt(opt.dataset.tracking) || 1;
    const productId = opt.value;

    tr.querySelector('.uom-label').textContent = opt.dataset.uom || '—';
    tr.querySelector('.uom-hidden').value = opt.dataset.uomId || '';

    applyTracking(tr, tracking);

    // ── AJAX: Gợi ý vị trí Putaway ────────────────────────────────
    const locationSel = tr.querySelector('select[name$="[location_id]"]');
    if (!productId || !locationSel) return;

    tr.querySelector('.putaway-badge')?.remove();

    const spinner = document.createElement('small');
    spinner.className = 'text-body-secondary putaway-badge d-block mt-1';
    spinner.innerHTML =
        `<span class="spinner-border spinner-border-sm me-1" role="status"></span>Đang gợi ý vị trí…`;
    locationSel.closest('td').appendChild(spinner);

    fetch(`{{ route('receipts.suggest-putaway') }}?product_id=${productId}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(r => r.json())
        .then(data => {
            tr.querySelector('.putaway-badge')?.remove();
            if (!data.location_id) return;

            locationSel.value = data.location_id;

            const badge = document.createElement('small');
            badge.className = 'text-body-secondary putaway-badge d-block mt-1';
            badge.innerHTML =
                `<svg class="icon icon-sm me-1">` +
                `<use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-location-pin') }}"></use>` +
                `</svg>Gợi ý tự động`;
            locationSel.closest('td').appendChild(badge);
        })
        .catch(() => {
            tr.querySelector('.putaway-badge')?.remove();
        });
}

// ── Khi thay đổi SL dự kiến trên sản phẩm Serial ─────────────────
function onExpectedQtyChange(input) {
    updateTotals();
    const tr = input.closest('tr');
    const sel = tr.querySelector('.product-select');
    const opt = sel.options[sel.selectedIndex];
    const tracking = parseInt(opt.dataset.tracking) || 1;
    const qty = parseInt(input.value) || 1;

    if (tracking !== 3 || qty <= 1) return;

    // Nhân bản thành qty dòng riêng biệt (mỗi dòng = 1 serial)
    const locVal = tr.querySelector('select[name$="[location_id]"]').value;
    const expiryVal = tr.querySelector('input[name$="[expiry_date]"]').value;
    const prodVal = sel.value;

    input.value = 1; // dòng hiện tại = 1

    for (let n = 1; n < qty; n++) {
        document.getElementById('detailBody').insertAdjacentHTML('beforeend', rowTemplate(rowIndex));
        const newTr = document.getElementById('detailBody').lastElementChild;
        rowIndex++;

        // Copy sản phẩm
        const newSel = newTr.querySelector('.product-select');
        newSel.value = prodVal;
        onProductChange(newSel);

        // Copy vị trí & hạn dùng
        newTr.querySelector('select[name$="[location_id]"]').value = locVal;
        newTr.querySelector('input[name$="[expiry_date]"]').value = expiryVal;
        newTr.querySelector('input[name$="[expected_qty]"]').value = 1;
    }

    syncRowNumbers();
    toggleEmptyState();
    updateTotals();
}

// ── Thêm dòng mới ──────────────────────────────────────────────────
function addRow() {
    document.getElementById('detailBody').insertAdjacentHTML('beforeend', rowTemplate(rowIndex));
    rowIndex++;
    syncRowNumbers();
    toggleEmptyState();
    updateTotals();
}

// ── Xóa dòng ──────────────────────────────────────────────────────
function removeRow(btn) {
    btn.closest('tr').remove();
    syncRowNumbers();
    toggleEmptyState();
    updateTotals();
}

// ── Cập nhật STT các dòng ─────────────────────────────────────────
function syncRowNumbers() {
    document.querySelectorAll('#detailBody tr').forEach((tr, i) => {
        tr.querySelector('td:first-child').textContent = i + 1;
    });
}

// ── Ẩn/hiện empty state ───────────────────────────────────────────
function toggleEmptyState() {
    const rows = document.querySelectorAll('#detailBody tr').length;
    document.getElementById('emptyDetail').style.display = rows ? 'none' : '';
    document.getElementById('rowCount').textContent = rows;
}

// ── Cập nhật tổng SL dự kiến ──────────────────────────────────────
function updateTotals() {
    let total = 0;
    document.querySelectorAll('input[name$="[expected_qty]"]').forEach(inp => {
        total += parseFloat(inp.value) || 0;
    });
    document.getElementById('totalExpected').textContent =
        total.toLocaleString('vi-VN', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 3
        });
}

// ── Hiện/ẩn NCC theo loại nhập ────────────────────────────────────
document.getElementById('receiptType').addEventListener('change', function() {
    document.getElementById('supplierGroup').style.display = this.value == '1' ? '' : 'none';
});

// ── Khởi tạo ──────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const type = document.getElementById('receiptType').value;
    if (type != '1') document.getElementById('supplierGroup').style.display = 'none';

    // Áp lại tracking cho các dòng edit-mode (server-render)
    document.querySelectorAll('#detailBody tr').forEach(tr => {
        const sel = tr.querySelector('.product-select');
        if (sel && sel.value) onProductChange(sel);
    });

    toggleEmptyState();
    updateTotals();

    @if(!$isEdit)
    addRow();
    @endif
});
// ── Xóa lỗi khi user bắt đầu gõ ──────────────────────────────────
function clearLotError(input) {
    input.classList.remove('is-invalid');
    const container = document.getElementById('lotSerialAlertContainer');
    if (container && !document.querySelector('.lot-serial-input.is-invalid')) {
        container.innerHTML = '';
    }
}

// ── Validate Lot/Serial bắt buộc ─────────────────────────────────
function validateLotSerial() {
    const errors = [];

    document.querySelectorAll('#detailBody tr').forEach((tr, i) => {
        const sel = tr.querySelector('.product-select');
        const opt = sel?.options[sel.selectedIndex];
        const tracking = parseInt(opt?.dataset?.tracking) || 1;
        const lotInput = tr.querySelector('.lot-serial-input');

        if (!lotInput) return;
        lotInput.classList.remove('is-invalid');

        if (tracking === 2 && !lotInput.value.trim()) {
            lotInput.classList.add('is-invalid');
            errors.push(
                `Dòng ${i + 1}: Hàng quản lý theo <strong>Lô (Lot)</strong> — vui lòng nhập Số Lot/Batch.`);
        } else if (tracking === 3 && !lotInput.value.trim()) {
            lotInput.classList.add('is-invalid');
            errors.push(`Dòng ${i + 1}: Hàng quản lý theo <strong>Serial</strong> — vui lòng nhập Mã Serial.`);
        }
    });

    return errors;
}

// ── Intercept submit ──────────────────────────────────────────────
document.getElementById('receiptForm').addEventListener('submit', function(e) {
    const errors = validateLotSerial();
    if (!errors.length) return; // pass — cho submit

    e.preventDefault();

    const container = document.getElementById('lotSerialAlertContainer');
    container.innerHTML = `
        <div class="alert alert-danger alert-dismissible mx-3 mt-3 mb-0" role="alert">
            <svg class="icon me-1 flex-shrink-0">
                <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use>
            </svg>
            <strong>Chưa nhập đủ thông tin Lot / Serial:</strong>
            <ul class="mb-0 mt-1 ps-4">
                ${errors.map(msg => `<li>${msg}</li>`).join('')}
            </ul>
            <button type="button" class="btn-close" data-coreui-dismiss="alert" aria-label="Close"></button>
        </div>`;

    container.scrollIntoView({
        behavior: 'smooth',
        block: 'nearest'
    });
    document.querySelector('.lot-serial-input.is-invalid')?.focus();
});
</script>
@endpush
