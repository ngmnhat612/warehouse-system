@extends('layouts.app')

@section('title', (isset($issue) ? 'Sửa phiếu xuất' : 'Tạo phiếu xuất') . ' — Warehouse System')

@section('breadcrumb')
<li class="breadcrumb-item">Nghiệp vụ kho</li>
<li class="breadcrumb-item"><a href="{{ route('issues.index') }}">Xuất kho</a></li>
<li class="breadcrumb-item active">{{ isset($issue) ? $issue->code : 'Tạo mới' }}</li>
@endsection

@section('content')

@php
$isEdit = isset($issue);
$action = $isEdit ? route('issues.update', $issue->id) : route('issues.store');
@endphp

{{-- HEADER --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-semibold">{{ $isEdit ? 'Sửa phiếu xuất' : 'Tạo phiếu xuất mới' }}</h4>
        <small
            class="text-body-secondary">{{ $isEdit ? $issue->code : 'Điền thông tin và thêm hàng hóa cần xuất' }}</small>
    </div>
    <a href="{{ route('issues.index') }}" class="btn btn-outline-secondary">
        <svg class="icon me-1">
            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-left') }}"></use>
        </svg>
        Quay lại
    </a>
</div>

<form method="POST" action="{{ $action }}" id="issueForm">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="row g-4">

        {{-- CỘT TRÁI: Thông tin phiếu --}}
        <div class="col-lg-4">

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
                            name="code" value="{{ old('code', $issue->code ?? '') }}" placeholder="VD: XK-2024-001"
                            {{ $isEdit ? 'required readonly' : '' }} maxlength="50">
                        @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        @if(!$isEdit)
                        <div class="form-text">Để trống để hệ thống tự sinh mã.</div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Loại xuất <span class="text-danger">*</span></label>
                        <select class="form-select @error('issue_type') is-invalid @enderror" name="issue_type"
                            id="issueType" required>
                            <option value="1" {{ old('issue_type', $issue->issue_type ?? 1) == 1 ? 'selected' : '' }}>
                                Sản xuất</option>
                            <option value="2" {{ old('issue_type', $issue->issue_type ?? 1) == 2 ? 'selected' : '' }}>
                                Bảo trì</option>
                            <option value="3" {{ old('issue_type', $issue->issue_type ?? 1) == 3 ? 'selected' : '' }}>
                                Mượn</option>
                            <option value="4" {{ old('issue_type', $issue->issue_type ?? 1) == 4 ? 'selected' : '' }}>
                                Khác</option>
                        </select>
                        @error('issue_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Người yêu cầu</label>
                        <select class="form-select @error('requester_id') is-invalid @enderror" name="requester_id">
                            <option value="">— Chọn người yêu cầu —</option>
                            @foreach ($users as $user)
                            <option value="{{ $user->id }}"
                                {{ old('requester_id', $issue->requester_id ?? '') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('requester_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Số tham chiếu</label>
                        <input type="text" class="form-control @error('reference_no') is-invalid @enderror"
                            name="reference_no" value="{{ old('reference_no', $issue->reference_no ?? '') }}"
                            placeholder="Số lệnh SX / công việc" maxlength="100">
                        @error('reference_no') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Ngày xuất <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('issue_date') is-invalid @enderror"
                            name="issue_date"
                            value="{{ old('issue_date', isset($issue->issue_date) ? \Carbon\Carbon::parse($issue->issue_date)->format('Y-m-d') : date('Y-m-d')) }}"
                            required>
                        @error('issue_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Hạn trả — chỉ hiện khi Mượn --}}
                    <div class="mb-3" id="returnDateGroup"
                        style="{{ old('issue_type', $issue->issue_type ?? 1) == 3 ? '' : 'display:none' }}">
                        <label class="form-label">Hạn trả hàng</label>
                        <input type="date" class="form-control @error('expected_return_date') is-invalid @enderror"
                            name="expected_return_date"
                            value="{{ old('expected_return_date', isset($issue->expected_return_date) ? \Carbon\Carbon::parse($issue->expected_return_date)->format('Y-m-d') : '') }}">
                        @error('expected_return_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">Chỉ áp dụng cho loại xuất Mượn.</div>
                    </div>

                    @if($isEdit)
                    <div class="mb-3">
                        <label class="form-label">Trạng thái</label>
                        <select class="form-select" name="status">
                            <option value="1" {{ ($issue->status ?? 1) == 1 ? 'selected' : '' }}>Nháp</option>
                            <option value="2" {{ ($issue->status ?? 1) == 2 ? 'selected' : '' }}>Chờ duyệt</option>
                            <option value="3" {{ ($issue->status ?? 1) == 3 ? 'selected' : '' }}>Đã duyệt</option>
                            <option value="4" {{ ($issue->status ?? 1) == 4 ? 'selected' : '' }}>Hoàn thành</option>
                            <option value="5" {{ ($issue->status ?? 1) == 5 ? 'selected' : '' }}>Đã hủy</option>
                        </select>
                    </div>
                    @endif

                    <div class="mb-0">
                        <label class="form-label">Ghi chú</label>
                        <textarea class="form-control" name="note" rows="3"
                            placeholder="Ghi chú thêm nếu có...">{{ old('note', $issue->note ?? '') }}</textarea>
                    </div>

                </div>
            </div>

            {{-- Nút lưu --}}
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg" name="action" value="save">
                    <svg class="icon me-1">
                        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-save') }}"></use>
                    </svg>
                    {{ $isEdit ? 'Cập nhật phiếu' : 'Lưu phiếu xuất' }}
                </button>
                @if(!$isEdit)
                <button type="submit" class="btn btn-outline-primary" name="action" value="save_and_new">
                    <svg class="icon me-1">
                        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use>
                    </svg>
                    Lưu & tạo phiếu mới
                </button>
                @endif
                <a href="{{ route('issues.index') }}" class="btn btn-outline-secondary">Hủy</a>
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
                    <div class="table-responsive">
                        <table class="table align-middle mb-0" id="detailTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:36px"></th>
                                    <th>Hàng hóa <span class="text-danger">*</span></th>
                                    <th style="width:100px">ĐVT</th>
                                    <th style="width:110px">Số lượng <span class="text-danger">*</span></th>
                                    <th style="width:130px">Vị trí kho <span class="text-danger">*</span></th>
                                    <th style="width:110px">Lot / Batch</th>
                                    <th style="width:90px">Tồn hiện</th>
                                    <th style="width:200px">Ghi chú</th>
                                    <th style="width:36px"></th>
                                </tr>
                            </thead>
                            <tbody id="detailBody">

                                @if($isEdit && $issue->details->count())
                                @foreach($issue->details as $i => $detail)
                                <tr data-tracking="{{ $detail->product->tracking_type ?? 1 }}">
                                    <td class="text-center text-body-secondary small">{{ $i + 1 }}</td>
                                    <td>
                                        <select class="form-select form-select-sm product-select"
                                            name="details[{{ $i }}][product_id]" required
                                            onchange="onProductChange(this)">
                                            <option value="">— Chọn hàng hóa —</option>
                                            @foreach($products as $p)
                                            <option value="{{ $p->id }}" data-uom="{{ $p->uom?->name }}"
                                                data-uom-id="{{ $p->uom_id }}" data-stock="{{ $p->total_stock }}"
                                                data-tracking="{{ $p->tracking_type }}"
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
                                            name="details[{{ $i }}][quantity]" value="{{ $detail->quantity }}"
                                            min="0.001" step="0.001" required oninput="handleQtyInput(this)">
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm location-select"
                                            name="details[${i}][location_id]" required
                                            onchange="onLocationChange(this)">
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
                                        <select class="form-select form-select-sm" name="details[{{ $i }}][lot_id]">
                                            <option value="">— Không chọn —</option>
                                            @if($detail->product)
                                            @foreach($detail->product->lots()->active()->get() as $lot)
                                            <option value="{{ $lot->id }}"
                                                {{ $detail->lot_id == $lot->id ? 'selected' : '' }}>
                                                {{ $lot->lot_number }}
                                            </option>
                                            @endforeach
                                            @endif
                                        </select>
                                        <input type="hidden" name="details[{{ $i }}][serial_id]"
                                            value="{{ $detail->serial_id ?? '' }}">
                                    </td>
                                    <td class="text-end small stock-display text-body-secondary">
                                        {{ number_format($detail->product?->total_stock ?? 0, 0) }}
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm"
                                            name="details[{{ $i }}][note]" value="{{ $detail->note ?? '' }}"
                                            placeholder="Ghi chú..." maxlength="200">
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

                    <div id="emptyDetail" class="text-center text-body-secondary py-5"
                        style="{{ ($isEdit && $issue->details->count()) ? 'display:none' : '' }}">
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
                            id="rowCount">{{ $isEdit ? $issue->details->count() : 0 }}</strong></span>
                    <span>Tổng SL xuất: <strong id="totalQty">0</strong></span>
                </div>
            </div>

            {{-- Cảnh báo tồn kho --}}
            <div class="alert alert-warning d-none mt-3" id="stockWarning">
                <svg class="icon me-1">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use>
                </svg>
                <strong>Cảnh báo:</strong> Một số dòng có số lượng xuất vượt quá tồn kho hiện tại.
            </div>
        </div>

    </div>
</form>

@endsection

@push('scripts')
<script>
const PRODUCTS = @json($productsJson);
const LOCATIONS = @json($locationsJson);

// Lots indexed by product_id
const LOTS = @json($lots ?? []);

let rowIndex = <?php echo $isEdit ? $issue->details->count() : 0; ?>;

// ── Template dòng chi tiết ─────────────────────────────────────────
function rowTemplate(i) {
    const productOptions = PRODUCTS.map(p =>
        `<option value="${p.id}" data-uom="${p.uom}" data-uom-id="${p.uom_id}" data-stock="${p.stock}" data-tracking="${p.tracking_type}">
      ${p.code} — ${p.name}
    </option>`
    ).join('');

    const locationOptions = LOCATIONS.map(l =>
        `<option value="${l.id}">${l.code}${l.name ? ' — ' + l.name : ''}</option>`
    ).join('');

    return `
  <tr data-tracking="1">
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
             oninput="handleQtyInput(this)">
    </td>
    <td>
      <select class="form-select form-select-sm location-select" name="details[${i}][location_id]"
              required onchange="onLocationChange(this)">
        <option value="">— Chọn sản phẩm trước —</option>
      </select>
    </td>
    <td>
      <input type="hidden" name="details[${i}][lot_id]"    class="lot-id-hidden"    value="">
      <input type="hidden" name="details[${i}][serial_id]" class="serial-id-hidden" value="">
      <select class="form-select form-select-sm lot-select">
        <option value="">— Chọn vị trí trước —</option>
      </select>
    </td>
    <td class="text-end small stock-display text-body-secondary">—</td>
    <td>
      <input type="text" class="form-control form-control-sm"
             name="details[${i}][note]"
             placeholder="Ghi chú..." maxlength="200">
    </td>
    <td>
      <button type="button" class="btn btn-sm btn-outline-danger p-1"
              onclick="removeRow(this)" title="Xóa dòng">
        <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use></svg>
      </button>
    </td>
  </tr>`;
}

window.addRow = function() {
    document.getElementById('detailBody').insertAdjacentHTML('beforeend', rowTemplate(rowIndex));
    rowIndex++;
    syncRowNumbers();
    toggleEmptyState();
    updateTotals();
}

window.removeRow = function(btn) {
    btn.closest('tr').remove();
    syncRowNumbers();
    toggleEmptyState();
    updateTotals();
    checkAllStock();
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

// ── Khi chọn hàng hóa → điền ĐVT, tồn kho tổng, fetch vị trí/lot ─
window.onProductChange = function(sel) {
    const opt = sel.options[sel.selectedIndex];
    const tr = sel.closest('tr');
    const productId = parseInt(opt.value);

    tr.dataset.tracking = opt.dataset.tracking || 1;

    // ĐVT
    tr.querySelector('.uom-label').textContent = opt.dataset.uom || '—';
    tr.querySelector('.uom-hidden').value = opt.dataset.uomId || '';

    // Tồn kho tổng (cột hiển thị)
    const stock = parseFloat(opt.dataset.stock) || 0;
    const stockEl = tr.querySelector('.stock-display');
    stockEl.textContent = stock.toLocaleString('vi-VN');
    stockEl.dataset.stock = stock;

    // Reset location & lot
    const locationSel = tr.querySelector('.location-select');
    const lotSel = tr.querySelector('.lot-select');
    locationSel.innerHTML = '<option value="">⏳ Đang tải vị trí...</option>';
    locationSel.disabled = true;
    lotSel.innerHTML = '<option value="">— Chọn vị trí trước —</option>';

    if (!productId) {
        locationSel.innerHTML = '<option value="">— Chọn sản phẩm trước —</option>';
        locationSel.disabled = false;
        return;
    }

    // Fetch vị trí có tồn kho khả dụng
    fetch(`/issues/stock-locations/${productId}`)
        .then(r => r.json())
        .then(stocks => {
            // Gom nhóm theo location_id
            const locMap = {};
            stocks.forEach(s => {
                if (!locMap[s.location_id]) {
                    locMap[s.location_id] = {
                        id: s.location_id,
                        code: s.location_code,
                        name: s.location_name,
                        available_qty: 0,
                        lots: [],
                        serials: [],
                    };
                }
                locMap[s.location_id].available_qty += s.available_qty;
                if (s.lot_id) {
                    locMap[s.location_id].lots.push({
                        id: s.lot_id,
                        lot_number: s.lot_number,
                        expiry_date: s.expiry_date,
                        available_qty: s.available_qty,
                    });
                }
                if (s.serial_id) {
                    locMap[s.location_id].serials.push({
                        id: s.serial_id,
                        serial_number: s.serial_number,
                        available_qty: s.available_qty,
                    });
                }
            });

            // Lưu stockMap vào tr để dùng khi đổi location
            tr.dataset.stockMap = JSON.stringify(locMap);

            const locs = Object.values(locMap);
            if (locs.length === 0) {
                locationSel.innerHTML = '<option value="">⚠ Không có tồn kho khả dụng</option>';
            } else {
                locationSel.innerHTML =
                    '<option value="">— Chọn vị trí lấy hàng —</option>' +
                    locs.map(l =>
                        `<option value="${l.id}">` +
                        `${l.code}${l.name ? ' — ' + l.name : ''} ` +
                        `(Khả dụng: ${l.available_qty.toLocaleString('vi-VN')})` +
                        `</option>`
                    ).join('');

                // Tự động chọn vị trí đầu tiên (gợi ý FIFO/FEFO đã được sort ở service)
                if (locs.length === 1) {
                    locationSel.value = locs[0].id;
                    onLocationChange(locationSel);
                }
            }
            locationSel.disabled = false;
        })
        .catch(() => {
            locationSel.innerHTML = '<option value="">— Lỗi tải vị trí —</option>';
            locationSel.disabled = false;
        });

    checkStock(tr.querySelector('.qty-input'));
}

// ── Khi chọn vị trí → cập nhật lot/serial tương ứng ──────────────
window.onLocationChange = function(locationSel) {
    const tr = locationSel.closest('tr');
    const lotSel = tr.querySelector('.lot-select');
    const lotHidden = tr.querySelector('.lot-id-hidden');
    const serialHidden = tr.querySelector('.serial-id-hidden');
    const locationId = parseInt(locationSel.value);
    const stockMap = tr.dataset.stockMap ? JSON.parse(tr.dataset.stockMap) : {};

    // Reset hidden fields
    if (lotHidden) lotHidden.value = '';
    if (serialHidden) serialHidden.value = '';

    if (!locationId || !stockMap[locationId]) {
        lotSel.innerHTML = '<option value="">— Không chọn —</option>';
        return;
    }

    const lots = stockMap[locationId].lots || [];
    const serials = stockMap[locationId].serials || [];
    let html = '<option value="">— Không chọn —</option>';

    if (lots.length) {
        html += '<optgroup label="Lot / Batch">' +
            lots.map(l =>
                `<option value="lot:${l.id}">` +
                `${l.lot_number}` +
                `${l.expiry_date ? ' · HSD: ' + l.expiry_date : ''}` +
                ` · Tồn: ${l.available_qty.toLocaleString('vi-VN')}` +
                `</option>`
            ).join('') +
            '</optgroup>';
    }

    if (serials.length) {
        html += '<optgroup label="Serial">' +
            serials.map(s =>
                `<option value="serial:${s.id}">` +
                `${s.serial_number}` +
                ` · Tồn: ${s.available_qty.toLocaleString('vi-VN')}` +
                `</option>`
            ).join('') +
            '</optgroup>';
    }

    lotSel.innerHTML = html;

    // Tự động chọn nếu chỉ có 1 option (lot hoặc serial)
    const allItems = [...lots, ...serials];
    if (allItems.length === 1) {
        lotSel.selectedIndex = 1;
        lotSel.dispatchEvent(new Event('change'));
    }
}

// ── Khi chọn lot/serial → cập nhật hidden fields ──────────────────
document.addEventListener('change', function(e) {
    if (!e.target.classList.contains('lot-select')) return;
    const tr = e.target.closest('tr');
    const lotHidden = tr.querySelector('.lot-id-hidden');
    const serialHidden = tr.querySelector('.serial-id-hidden');
    const val = e.target.value; // "lot:5" | "serial:12" | ""

    if (lotHidden) lotHidden.value = '';
    if (serialHidden) serialHidden.value = '';

    if (val.startsWith('lot:')) {
        if (lotHidden) lotHidden.value = val.split(':')[1];
    } else if (val.startsWith('serial:')) {
        if (serialHidden) serialHidden.value = val.split(':')[1];
    }
});

// ── SL hàng quản lý theo Serial → tự sinh thêm dòng (mỗi dòng = 1 serial) ──
window.handleQtyInput = function(qtyInput) {
    const tr = qtyInput.closest('tr');
    const tracking = parseInt(tr.dataset.tracking) || 1;
    const isSerialManaged = (tracking === 3 || tracking === 4); // SERIAL hoặc LOT+SERIAL

    if (isSerialManaged) {
        const qty = parseInt(qtyInput.value) || 0;

        if (qty > 1) {
            const productSel = tr.querySelector('.product-select');
            const productOpt = productSel.options[productSel.selectedIndex];
            const locationSel = tr.querySelector('.location-select');
            const locationVal = locationSel.value;
            const stockMapStr = tr.dataset.stockMap || '';

            qtyInput.value = 1;

            for (let n = 1; n < qty; n++) {
                addRow();
                const newTr = document.getElementById('detailBody').lastElementChild;

                // Copy hàng hóa, ĐVT, tồn, tracking
                const newProductSel = newTr.querySelector('.product-select');
                newProductSel.value = productSel.value;
                newTr.querySelector('.uom-label').textContent = productOpt.dataset.uom || '—';
                newTr.querySelector('.uom-hidden').value = productOpt.dataset.uomId || '';
                const stockEl = newTr.querySelector('.stock-display');
                stockEl.textContent = (parseFloat(productOpt.dataset.stock) || 0).toLocaleString('vi-VN');
                stockEl.dataset.stock = productOpt.dataset.stock || 0;
                newTr.dataset.tracking = tracking;
                newTr.querySelector('.qty-input').value = 1;

                // Copy danh sách vị trí/lot/serial đã fetch (không gọi lại API)
                if (stockMapStr) {
                    newTr.dataset.stockMap = stockMapStr;
                    const locMap = JSON.parse(stockMapStr);
                    const locs = Object.values(locMap);
                    const newLocationSel = newTr.querySelector('.location-select');
                    newLocationSel.innerHTML =
                        '<option value="">— Chọn vị trí lấy hàng —</option>' +
                        locs.map(l =>
                            `<option value="${l.id}">${l.code}${l.name ? ' — ' + l.name : ''} ` +
                            `(Khả dụng: ${l.available_qty.toLocaleString('vi-VN')})</option>`
                        ).join('');
                    newLocationSel.disabled = false;

                    if (locationVal) {
                        newLocationSel.value = locationVal;
                        onLocationChange(newLocationSel);
                    }
                }
            }
        }
    }

    checkStock(qtyInput);
    updateTotals();
}

// ── Kiểm tra SL xuất so với tồn ───────────────────────────────────
window.checkStock = function(qtyInput) {
    if (!qtyInput) return;
    const tr = qtyInput.closest('tr');
    const stock = parseFloat(tr.querySelector('.stock-display')?.dataset.stock) || 0;
    const qty = parseFloat(qtyInput.value) || 0;

    if (qty > 0 && stock > 0 && qty > stock) {
        qtyInput.classList.add('is-invalid');
    } else {
        qtyInput.classList.remove('is-invalid');
    }
    checkAllStock();
    updateTotals();
}

function checkAllStock() {
    const hasWarning = document.querySelector('.qty-input.is-invalid') !== null;
    document.getElementById('stockWarning').classList.toggle('d-none', !hasWarning);
}

// ── Hiện/ẩn trường hạn trả theo loại xuất ────────────────────────
document.getElementById('issueType').addEventListener('change', function() {
    document.getElementById('returnDateGroup').style.display = this.value == '3' ? '' : 'none';
});

document.addEventListener('DOMContentLoaded', () => {
    toggleEmptyState();
    updateTotals();
    @if(!$isEdit) addRow();
    @endif
});
</script>
@endpush
