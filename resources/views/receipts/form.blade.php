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
      <small class="text-body-secondary">{{ $isEdit ? $receipt->code : 'Điền thông tin và thêm hàng hóa cần nhập' }}</small>
    </div>
    <a href="{{ route('receipts.index') }}" class="btn btn-outline-secondary">
      <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-left') }}"></use></svg>
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
            <svg class="icon me-1 text-primary"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-description') }}"></use></svg>
            Thông tin phiếu
          </div>
          <div class="card-body">

            <div class="mb-3">
              <label class="form-label">Mã phiếu <span class="text-danger">*</span></label>
              <input type="text" class="form-control text-uppercase @error('code') is-invalid @enderror"
                     name="code" value="{{ old('code', $receipt->code ?? '') }}"
                     placeholder="VD: NK-2024-001" required maxlength="50"
                     {{ $isEdit ? 'readonly' : '' }}>
              @error('code')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
              @if(!$isEdit)
                <div class="form-text">Để trống để hệ thống tự sinh mã.</div>
              @endif
            </div>

            <div class="mb-3">
              <label class="form-label">Loại nhập <span class="text-danger">*</span></label>
              <select class="form-select @error('receipt_type') is-invalid @enderror"
                      name="receipt_type" id="receiptType" required>
                <option value="1" {{ old('receipt_type', $receipt->receipt_type ?? 1) == 1 ? 'selected' : '' }}>Từ nhà cung cấp</option>
                <option value="2" {{ old('receipt_type', $receipt->receipt_type ?? 1) == 2 ? 'selected' : '' }}>Trả hàng từ SX / bảo trì</option>
                <option value="3" {{ old('receipt_type', $receipt->receipt_type ?? 1) == 3 ? 'selected' : '' }}>Khác</option>
              </select>
              @error('receipt_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
              @error('supplier_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
              <label class="form-label">Số tham chiếu</label>
              <input type="text" class="form-control @error('reference_no') is-invalid @enderror"
                     name="reference_no" value="{{ old('reference_no', $receipt->reference_no ?? '') }}"
                     placeholder="Số PO / chứng từ liên quan" maxlength="100">
              @error('reference_no') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
              <label class="form-label">Ngày nhập <span class="text-danger">*</span></label>
              <input type="date" class="form-control @error('receipt_date') is-invalid @enderror"
                     name="receipt_date"
                     value="{{ old('receipt_date', isset($receipt->receipt_date) ? \Carbon\Carbon::parse($receipt->receipt_date)->format('Y-m-d') : date('Y-m-d')) }}"
                     required>
              @error('receipt_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
            <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-save') }}"></use></svg>
            {{ $isEdit ? 'Cập nhật phiếu' : 'Lưu phiếu nhập' }}
          </button>
          @if(!$isEdit)
          <button type="submit" class="btn btn-outline-primary" name="action" value="save_and_new">
            <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use></svg>
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
              <svg class="icon me-1 text-primary"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-list') }}"></use></svg>
              Chi tiết hàng hóa
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
                        <input type="number" class="form-control form-control-sm text-end"
                               name="details[{{ $i }}][expected_qty]"
                               value="{{ $detail->expected_qty }}"
                               min="0.001" step="0.001" required>
                      </td>
                      <td>
                        <input type="number" class="form-control form-control-sm text-end"
                               name="details[{{ $i }}][actual_qty]"
                               value="{{ $detail->actual_qty }}"
                               min="0" step="0.001">
                      </td>
                      <td>
                        <select class="form-select form-select-sm" name="details[{{ $i }}][location_id]">
                          <option value="">— Chọn —</option>
                          @foreach($locations as $loc)
                            <option value="{{ $loc->id }}" {{ $detail->location_id == $loc->id ? 'selected' : '' }}>
                              {{ $loc->code }}
                            </option>
                          @endforeach
                        </select>
                      </td>
                      <td>
                        <input type="text" class="form-control form-control-sm"
                               name="details[{{ $i }}][lot_number]"
                               value="{{ $detail->lot?->lot_number ?? '' }}"
                               placeholder="Số lot" maxlength="50">
                      </td>
                      <td>
                        <input type="date" class="form-control form-control-sm"
                               name="details[{{ $i }}][expiry_date]"
                               value="{{ $detail->expiry_date ? \Carbon\Carbon::parse($detail->expiry_date)->format('Y-m-d') : '' }}">
                      </td>
                      <td>
                        <button type="button" class="btn btn-sm btn-outline-danger p-1" onclick="removeRow(this)" title="Xóa dòng">
                          <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use></svg>
                        </button>
                      </td>
                    </tr>
                    @endforeach
                  @else
                    {{-- Dòng trống mặc định khi tạo mới --}}
                  @endif

                </tbody>
              </table>
            </div>

            {{-- Empty state khi chưa có dòng nào --}}
            <div id="emptyDetail" class="text-center text-body-secondary py-5"
                 style="{{ ($isEdit && $receipt->details->count()) ? 'display:none' : '' }}">
              <svg class="icon icon-3xl d-block mx-auto mb-2 opacity-25">
                <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-list') }}"></use>
              </svg>
              Chưa có hàng hóa nào.<br>
              <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addRow()">
                <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use></svg>
                Thêm dòng đầu tiên
              </button>
            </div>
          </div>

          <div class="card-footer text-body-secondary small d-flex justify-content-between">
            <span>Tổng số dòng: <strong id="rowCount">{{ $isEdit ? $receipt->details->count() : 0 }}</strong></span>
            <span>
              <svg class="icon icon-sm me-1"></svg>
              Tổng SL dự kiến: <strong id="totalExpected">0</strong>
            </span>
          </div>
        </div>
      </div>

    </div>{{-- end row --}}
  </form>

@endsection

@push('scripts')
<script>
// ── Dữ liệu từ controller ──────────────────────────────────────────
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

let rowIndex = {{ $isEdit ? $receipt->details->count() : 0 }};

// ── Template một dòng chi tiết ─────────────────────────────────────
function rowTemplate(i) {
  const productOptions = PRODUCTS.map(p =>
    `<option value="${p.id}" data-uom="${p.uom}" data-uom-id="${p.uom_id}">${p.code} — ${p.name}</option>`
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
             oninput="updateTotals()">
    </td>
    <td>
      <input type="number" class="form-control form-control-sm text-end"
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
      <input type="text" class="form-control form-control-sm"
             name="details[${i}][lot_number]"
             placeholder="Số lot" maxlength="50">
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

// ── Thêm dòng mới ──────────────────────────────────────────────────
function addRow() {
  const tbody = document.getElementById('detailBody');
  tbody.insertAdjacentHTML('beforeend', rowTemplate(rowIndex));
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
    total.toLocaleString('vi-VN', { minimumFractionDigits: 0, maximumFractionDigits: 3 });
}

// ── Khi chọn hàng hóa → tự điền ĐVT ──────────────────────────────
function onProductChange(sel) {
  const opt = sel.options[sel.selectedIndex];
  const td = sel.closest('tr');
  td.querySelector('.uom-label').textContent = opt.dataset.uom || '—';
  td.querySelector('.uom-hidden').value = opt.dataset.uomId || '';
}

// ── Hiện/ẩn NCC theo loại nhập ────────────────────────────────────
document.getElementById('receiptType').addEventListener('change', function() {
  document.getElementById('supplierGroup').style.display =
    this.value == '1' ? '' : 'none';
});

// Khởi tạo ban đầu
document.addEventListener('DOMContentLoaded', () => {
  const type = document.getElementById('receiptType').value;
  if (type != '1') document.getElementById('supplierGroup').style.display = 'none';
  toggleEmptyState();
  updateTotals();

  // Nếu tạo mới, thêm 1 dòng mặc định
  @if(!$isEdit)
  addRow();
  @endif
});
</script>
@endpush