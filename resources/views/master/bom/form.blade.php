@extends('layouts.app')

@section('title', isset($bom) ? 'Sửa BOM — Warehouse System' : 'Thêm BOM — Warehouse System')

@section('breadcrumb')
  <li class="breadcrumb-item">Danh mục</li>
  <li class="breadcrumb-item"><a href="{{ route('master.bom.index') }}">Công thức BOM</a></li>
  <li class="breadcrumb-item active">{{ isset($bom) ? 'Chỉnh sửa' : 'Thêm mới' }}</li>
@endsection

@section('content')

@php
  $isEdit  = isset($bom);
  $action  = $isEdit ? route('master.bom.update', $bom) : route('master.bom.store');
  $method  = $isEdit ? 'PUT' : 'POST';
@endphp

<form method="POST" action="{{ $action }}" id="bomForm">
  @csrf
  @method($method)

  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-0 fw-semibold">{{ $isEdit ? 'Chỉnh sửa BOM' : 'Thêm BOM mới' }}</h4>
      <small class="text-body-secondary">Định nghĩa nguyên liệu đầu vào (Consume) và sản phẩm đầu ra (Produce)</small>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('master.bom.index') }}" class="btn btn-outline-secondary">Hủy</a>
      <button type="submit" class="btn btn-primary">
        <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-save') }}"></use></svg>
        Lưu BOM
      </button>
    </div>
  </div>

  @if ($errors->any())
    <div class="alert alert-danger mb-4">
      <ul class="mb-0 ps-3">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="row g-4">

    {{-- ===== CỘT TRÁI: HEADER ===== --}}
    <div class="col-lg-4">
      <div class="card h-100">
        <div class="card-header fw-semibold">Thông tin BOM</div>
        <div class="card-body">

          <div class="mb-3">
            <label class="form-label fw-medium">Mã BOM <span class="text-danger">*</span></label>
            <input type="text" class="form-control font-monospace @error('code') is-invalid @enderror"
                   name="code" value="{{ old('code', $bom->code ?? '') }}"
                   placeholder="VD: BOM-001" maxlength="50" required>
            @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="mb-3">
            <label class="form-label fw-medium">Tên công thức <span class="text-danger">*</span></label>
            <input type="text" class="form-control @error('name') is-invalid @enderror"
                   name="name" value="{{ old('name', $bom->name ?? '') }}"
                   placeholder="VD: Tách pallet A thành hộp lẻ" maxlength="200" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="mb-3">
            <label class="form-label fw-medium">Loại <span class="text-danger">*</span></label>
            <select class="form-select @error('type') is-invalid @enderror" name="type" required>
              <option value="">— Chọn loại —</option>
              <option value="1" {{ old('type', $bom->type ?? '') == 1 ? 'selected' : '' }}>
                Tách hàng (Disassemble)
              </option>
              <option value="2" {{ old('type', $bom->type ?? '') == 2 ? 'selected' : '' }}>
                Ghép hàng (Assemble)
              </option>
            </select>
            @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="mb-3">
            <label class="form-label fw-medium">Ghi chú</label>
            <textarea class="form-control" name="note" rows="3"
                      placeholder="Mô tả thêm về công thức...">{{ old('note', $bom->note ?? '') }}</textarea>
          </div>

          <div class="mb-3">
            <label class="form-label fw-medium">Trạng thái</label>
            <div class="d-flex gap-3">
              <div class="form-check">
                <input class="form-check-input" type="radio" name="status" id="statusActive"
                       value="1" {{ old('status', $bom->status ?? 1) == 1 ? 'checked' : '' }}>
                <label class="form-check-label text-success" for="statusActive">Hoạt động</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="status" id="statusInactive"
                       value="0" {{ old('status', $bom->status ?? 1) == 0 ? 'checked' : '' }}>
                <label class="form-check-label text-secondary" for="statusInactive">Ngừng</label>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>

    {{-- ===== CỘT PHẢI: DETAIL ROWS ===== --}}
    <div class="col-lg-8">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span class="fw-semibold">Chi tiết công thức</span>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="addRow(1)">
              <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-minus') }}"></use></svg>
              + Consume
            </button>
            <button type="button" class="btn btn-sm btn-outline-success" onclick="addRow(2)">
              <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use></svg>
              + Produce
            </button>
          </div>
        </div>

        <div class="card-body p-0">

          {{-- Legend --}}
          <div class="px-3 pt-3 pb-2 d-flex gap-3 small">
            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
              Consume — Nguyên liệu đầu vào (bị tiêu thụ)
            </span>
            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
              Produce — Sản phẩm đầu ra (được tạo ra)
            </span>
          </div>

          @error('details')
            <div class="alert alert-danger mx-3 py-2 small">{{ $message }}</div>
          @enderror

          <div class="table-responsive">
            <table class="table align-middle mb-0" id="detailTable">
              <thead class="table-light">
                <tr>
                  <th style="width:110px" class="text-center">Loại dòng</th>
                  <th>Hàng hóa</th>
                  <th style="width:100px">Số lượng</th>
                  <th style="width:120px">Đơn vị</th>
                  <th style="width:160px">Ghi chú</th>
                  <th style="width:50px"></th>
                </tr>
              </thead>
              <tbody id="detailBody">
                {{-- Populated by JS or server-side (edit mode) --}}
              </tbody>
            </table>
          </div>

          <div id="emptyDetail" class="text-center text-body-secondary py-4 small">
            Chưa có dòng nào. Nhấn <strong>+ Consume</strong> hoặc <strong>+ Produce</strong> để thêm.
          </div>

        </div>
      </div>
    </div>

  </div>{{-- end row --}}
</form>

{{-- Data cho JS --}}
<script>
  const products = @json($products->map(fn($p) => ['id' => $p->id, 'code' => $p->code, 'name' => $p->name, 'uom_id' => $p->uom_id]));
  const uoms     = @json($uoms->map(fn($u) => ['id' => $u->id, 'name' => $u->name]));

  // Dữ liệu cũ (edit mode hoặc validation fail)
  const existingRows = @json(
    old('details',
      isset($bom)
        ? $bom->details->map(fn($d) => [
            'product_id' => $d->product_id,
            'line_type'  => $d->line_type,
            'qty'        => $d->qty,
            'uom_id'     => $d->uom_id,
            'note'       => $d->note,
          ])->toArray()
        : []
    )
  );

  let rowIndex = 0;

  function buildUomOptions(selectedId) {
    return uoms.map(u =>
      `<option value="${u.id}" ${u.id == selectedId ? 'selected' : ''}>${u.name}</option>`
    ).join('');
  }

  function buildProductOptions(selectedId) {
    return `<option value="">— Chọn hàng hóa —</option>` +
      products.map(p =>
        `<option value="${p.id}" data-uom="${p.uom_id}" ${p.id == selectedId ? 'selected' : ''}>${p.code} — ${p.name}</option>`
      ).join('');
  }

  function addRow(lineType, data = {}) {
    const i = rowIndex++;
    const isConsume = lineType == 1;
    const rowClass  = isConsume ? 'table-danger' : 'table-success';
    const typeLabel = isConsume
      ? '<span class="badge bg-danger-subtle text-danger border border-danger-subtle">Consume</span>'
      : '<span class="badge bg-success-subtle text-success border border-success-subtle">Produce</span>';

    const tr = document.createElement('tr');
    tr.className = rowClass;
    tr.dataset.index = i;
    tr.innerHTML = `
      <td class="text-center">
        ${typeLabel}
        <input type="hidden" name="details[${i}][line_type]" value="${lineType}">
      </td>
      <td>
        <select class="form-select form-select-sm product-select" name="details[${i}][product_id]"
                onchange="onProductChange(this, ${i})" required>
          ${buildProductOptions(data.product_id || '')}
        </select>
      </td>
      <td>
        <input type="number" class="form-control form-control-sm font-monospace"
               name="details[${i}][qty]" value="${data.qty || 1}"
               step="0.001" min="0.001" required style="width:90px">
      </td>
      <td>
        <select class="form-select form-select-sm" name="details[${i}][uom_id]" id="uomSel_${i}" required>
          ${buildUomOptions(data.uom_id || '')}
        </select>
      </td>
      <td>
        <input type="text" class="form-control form-control-sm"
               name="details[${i}][note]" value="${data.note || ''}" placeholder="Ghi chú...">
      </td>
      <td class="text-center">
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)" title="Xóa dòng">
          <svg class="icon icon-sm"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use></svg>
        </button>
      </td>
    `;
    document.getElementById('detailBody').appendChild(tr);
    updateEmptyState();
  }

  function onProductChange(select, i) {
    const opt = select.options[select.selectedIndex];
    const uomId = opt?.dataset?.uom;
    if (uomId) {
      document.getElementById(`uomSel_${i}`).value = uomId;
    }
  }

  function removeRow(btn) {
    btn.closest('tr').remove();
    updateEmptyState();
  }

  function updateEmptyState() {
    const empty = document.getElementById('detailBody').children.length === 0;
    document.getElementById('emptyDetail').style.display = empty ? 'block' : 'none';
  }

  // Load existing rows (edit mode / validation fail)
  document.addEventListener('DOMContentLoaded', () => {
    existingRows.forEach(row => addRow(row.line_type, row));
    updateEmptyState();
  });
</script>

@endsection