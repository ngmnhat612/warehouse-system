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
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-0 fw-semibold">{{ $isEdit ? 'Sửa phiếu chuyển kho' : 'Tạo phiếu chuyển kho mới' }}</h4>
      <small class="text-body-secondary">{{ $isEdit ? $transfer->code : 'Điền thông tin và thêm hàng hóa cần chuyển' }}</small>
    </div>
    <a href="{{ route('transfers.index') }}" class="btn btn-outline-secondary">
      <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-left') }}"></use></svg>
      Quay lại
    </a>
  </div>

  <form method="POST" action="{{ $action }}" id="transferForm">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="row g-4">

      {{-- CỘT TRÁI: Thông tin phiếu --}}
      <div class="col-lg-4">

        <div class="card mb-4">
          <div class="card-header fw-semibold">
            <svg class="icon me-1 text-primary"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-description') }}"></use></svg>
            Thông tin phiếu
          </div>
          <div class="card-body">

            <div class="mb-3">
              <label class="form-label">Mã phiếu</label>
              <input type="text" class="form-control text-uppercase @error('code') is-invalid @enderror"
                     name="code" value="{{ old('code', $transfer->code ?? '') }}"
                     placeholder="VD: CK-2024-001" maxlength="50"
                     {{ $isEdit ? 'readonly' : '' }}>
              @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
              @if(!$isEdit)
                <div class="form-text">Để trống để hệ thống tự sinh mã.</div>
              @endif
            </div>

            <div class="mb-3">
              <label class="form-label">Loại chuyển kho <span class="text-danger">*</span></label>
              <select class="form-select @error('transfer_type') is-invalid @enderror"
                      name="transfer_type" required>
                <option value="1" {{ old('transfer_type', $transfer->transfer_type ?? 1) == 1 ? 'selected' : '' }}>Sắp xếp kho</option>
                <option value="2" {{ old('transfer_type', $transfer->transfer_type ?? 1) == 2 ? 'selected' : '' }}>Từ Quarantine</option>
                <option value="3" {{ old('transfer_type', $transfer->transfer_type ?? 1) == 3 ? 'selected' : '' }}>Khác</option>
              </select>
              @error('transfer_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
              <label class="form-label">Ngày chuyển <span class="text-danger">*</span></label>
              <input type="date" class="form-control @error('transfer_date') is-invalid @enderror"
                     name="transfer_date"
                     value="{{ old('transfer_date', isset($transfer->transfer_date) ? \Carbon\Carbon::parse($transfer->transfer_date)->format('Y-m-d') : date('Y-m-d')) }}"
                     required>
              @error('transfer_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-0">
              <label class="form-label">Ghi chú</label>
              <textarea class="form-control" name="note" rows="3"
                        placeholder="Lý do chuyển kho, ghi chú thêm...">{{ old('note', $transfer->note ?? '') }}</textarea>
            </div>

          </div>
        </div>

        {{-- Nút lưu --}}
        <div class="d-grid gap-2">
          <button type="submit" class="btn btn-primary btn-lg" name="action" value="save">
            <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-save') }}"></use></svg>
            {{ $isEdit ? 'Cập nhật phiếu' : 'Lưu phiếu chuyển kho' }}
          </button>
          @if(!$isEdit)
          <button type="submit" class="btn btn-outline-primary" name="action" value="save_and_new">
            <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use></svg>
            Lưu & tạo phiếu mới
          </button>
          @endif
          <a href="{{ route('transfers.index') }}" class="btn btn-outline-secondary">Hủy</a>
        </div>

      </div>

      {{-- CỘT PHẢI: Chi tiết hàng hóa --}}
      <div class="col-lg-8">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-semibold">
              <svg class="icon me-1 text-primary"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-list') }}"></use></svg>
              Chi tiết hàng hóa cần chuyển
            </span>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addRow()">
              <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use></svg>
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
                    <th style="width:90px">ĐVT</th>
                    <th style="width:110px">Số lượng <span class="text-danger">*</span></th>
                    <th style="width:130px">Vị trí nguồn <span class="text-danger">*</span></th>
                    <th style="width:130px">Vị trí đích <span class="text-danger">*</span></th>
                    <th style="width:110px">Lot / Batch</th>
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
                                name="details[{{ $i }}][product_id]" required
                                onchange="onProductChange(this)">
                          <option value="">— Chọn hàng hóa —</option>
                          @foreach($products as $p)
                            <option value="{{ $p->id }}"
                                    data-uom="{{ $p->uom?->name }}"
                                    data-uom-id="{{ $p->uom_id }}"
                                    {{ $detail->product_id == $p->id ? 'selected' : '' }}>
                              {{ $p->code }} — {{ $p->name }}
                            </option>
                          @endforeach
                        </select>
                      </td>
                      <td>
                        <input type="hidden" name="details[{{ $i }}][uom_id]"
                               class="uom-hidden" value="{{ $detail->uom_id }}">
                        <span class="uom-label text-body-secondary small">{{ $detail->uom?->name ?? '—' }}</span>
                      </td>
                      <td>
                        <input type="number" class="form-control form-control-sm text-end qty-input"
                               name="details[{{ $i }}][quantity]"
                               value="{{ $detail->quantity }}"
                               min="0.001" step="0.001" required
                               oninput="updateTotals()">
                      </td>
                      <td>
                        <select class="form-select form-select-sm from-location-select"
                                name="details[{{ $i }}][from_location_id]" required
                                onchange="validateLocationPair(this)">
                          <option value="">— Chọn —</option>
                          @foreach($locations as $loc)
                            <option value="{{ $loc->id }}" {{ $detail->from_location_id == $loc->id ? 'selected' : '' }}>
                              {{ $loc->code }}
                            </option>
                          @endforeach
                        </select>
                      </td>
                      <td>
                        <select class="form-select form-select-sm to-location-select"
                                name="details[{{ $i }}][to_location_id]" required
                                onchange="validateLocationPair(this)">
                          <option value="">— Chọn —</option>
                          @foreach($locations as $loc)
                            <option value="{{ $loc->id }}" {{ $detail->to_location_id == $loc->id ? 'selected' : '' }}>
                              {{ $loc->code }}
                            </option>
                          @endforeach
                        </select>
                      </td>
                      <td>
                        <select class="form-select form-select-sm lot-select" name="details[{{ $i }}][lot_id]">
                          <option value="">— Không chọn —</option>
                          @if($detail->product)
                            @foreach($detail->product->lots()->active()->get() as $lot)
                              <option value="{{ $lot->id }}" {{ $detail->lot_id == $lot->id ? 'selected' : '' }}>
                                {{ $lot->lot_number }}
                              </option>
                            @endforeach
                          @endif
                        </select>
                      </td>
                      <td>
                        <input type="text" class="form-control form-control-sm"
                               name="details[{{ $i }}][note]"
                               value="{{ $detail->note ?? '' }}"
                               placeholder="Ghi chú..." maxlength="200">
                      </td>
                      <td>
                        <button type="button" class="btn btn-sm btn-outline-danger p-1"
                                onclick="removeRow(this)" title="Xóa dòng">
                          <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use></svg>
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
              <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addRow()">
                <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use></svg>
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
          <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use></svg>
          <strong>Cảnh báo:</strong> Một số dòng có vị trí nguồn và vị trí đích giống nhau.
        </div>
      </div>

    </div>
  </form>

@endsection

@push('scripts')
<script>
const PRODUCTS = @json($products->map(fn($p) => [
  'id'     => $p->id,
  'code'   => $p->code,
  'name'   => $p->name,
  'uom'    => $p->uom?->name ?? '—',
  'uom_id' => $p->uom_id,
]));

const LOCATIONS = @json($locations->map(fn($l) => [
  'id'   => $l->id,
  'code' => $l->code,
  'name' => $l->name ?? '',
]));

// Lots indexed by product_id
const LOTS = @json($lots ?? []);

let rowIndex = {{ $isEdit ? $transfer->details->count() : 0 }};

// ── Template dòng chi tiết ─────────────────────────────────────────
function rowTemplate(i) {
  const productOptions = PRODUCTS.map(p =>
    `<option value="${p.id}" data-uom="${p.uom}" data-uom-id="${p.uom_id}">
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
             oninput="updateTotals()">
    </td>
    <td>
      <select class="form-select form-select-sm from-location-select"
              name="details[${i}][from_location_id]" required
              onchange="validateLocationPair(this)">
        <option value="">— Nguồn —</option>
        ${locationOptions}
      </select>
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
      <select class="form-select form-select-sm lot-select" name="details[${i}][lot_id]">
        <option value="">— Không chọn —</option>
      </select>
    </td>
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

function addRow() {
  document.getElementById('detailBody').insertAdjacentHTML('beforeend', rowTemplate(rowIndex));
  rowIndex++;
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
    total.toLocaleString('vi-VN', { minimumFractionDigits: 0, maximumFractionDigits: 3 });
}

// ── Khi chọn hàng hóa → điền ĐVT và lots ────────────────────────
function onProductChange(sel) {
  const opt       = sel.options[sel.selectedIndex];
  const tr        = sel.closest('tr');
  const productId = parseInt(opt.value);

  tr.querySelector('.uom-label').textContent = opt.dataset.uom || '—';
  tr.querySelector('.uom-hidden').value       = opt.dataset.uomId || '';

  // Điền lots theo sản phẩm
  const lotSel = tr.querySelector('.lot-select');
  if (lotSel) {
    const productLots = (LOTS[productId] || []);
    lotSel.innerHTML = '<option value="">— Không chọn —</option>' +
      productLots.map(l =>
        `<option value="${l.id}">${l.lot_number}${l.expiry_date ? ' (HSD: ' + l.expiry_date + ')' : ''}</option>`
      ).join('');
  }
}

// ── Kiểm tra vị trí nguồn ≠ vị trí đích ─────────────────────────
function validateLocationPair(sel) {
  const tr      = sel.closest('tr');
  const fromSel = tr.querySelector('.from-location-select');
  const toSel   = tr.querySelector('.to-location-select');
  const fromVal = fromSel.value;
  const toVal   = toSel.value;

  if (fromVal && toVal && fromVal === toVal) {
    toSel.classList.add('is-invalid');
    fromSel.classList.add('is-invalid');
  } else {
    toSel.classList.remove('is-invalid');
    fromSel.classList.remove('is-invalid');
  }
  checkAllLocationPairs();
}

function checkAllLocationPairs() {
  const hasWarning = document.querySelector('.from-location-select.is-invalid, .to-location-select.is-invalid') !== null;
  document.getElementById('locationWarning').classList.toggle('d-none', !hasWarning);
}

document.addEventListener('DOMContentLoaded', () => {
  toggleEmptyState();
  updateTotals();
  @if(!$isEdit) addRow(); @endif
});
</script>
@endpush