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

<form method="POST" action="{{ $action }}" id="bomForm" novalidate>
  @csrf
  @method($method)

  {{-- ===== HEADER BAR ===== --}}
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-0 fw-semibold">{{ $isEdit ? 'Chỉnh sửa BOM' : 'Thêm BOM mới' }}</h4>
      <small class="text-body-secondary">
        Định nghĩa nguyên liệu đầu vào
        <span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-1">Consume</span>
        và sản phẩm đầu ra
        <span class="badge bg-success-subtle text-success border border-success-subtle ms-1">Produce</span>
      </small>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('master.bom.index') }}" class="btn btn-outline-secondary">Hủy</a>
      <button type="submit" class="btn btn-primary" id="submitBtn">
        <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-save') }}"></use></svg>
        Lưu BOM
      </button>
    </div>
  </div>

  {{-- ===== VALIDATION ERRORS ===== --}}
  @if ($errors->any())
    <div class="alert alert-danger mb-4" id="serverErrors">
      <div class="d-flex align-items-start gap-2">
        <svg class="icon text-danger mt-1 flex-shrink-0"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use></svg>
        <div>
          <strong class="d-block mb-1">Vui lòng kiểm tra lại thông tin:</strong>
          <ul class="mb-0 ps-3">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      </div>
    </div>
  @endif

  {{-- ===== CLIENT-SIDE CIRCULAR ERROR (JS) ===== --}}
  <div class="alert alert-danger mb-4 d-none" id="cycleAlert">
    <div class="d-flex align-items-start gap-2">
      <svg class="icon text-danger mt-1 flex-shrink-0"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-ban') }}"></use></svg>
      <div>
        <strong>Cấu hình vòng lặp bị ngăn chặn</strong>
        <p class="mb-0 mt-1 small" id="cycleMessage"></p>
      </div>
    </div>
  </div>

  <div class="row g-4">

    {{-- ===== CỘT TRÁI: HEADER ===== --}}
    <div class="col-lg-4">
      <div class="card h-100">
        <div class="card-header fw-semibold">Thông tin BOM</div>
        <div class="card-body">

          <div class="mb-3">
            <label class="form-label fw-medium">Mã BOM <span class="text-danger">*</span></label>
            <input type="text" class="form-control font-monospace text-uppercase @error('code') is-invalid @enderror"
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
                🔀 Tách hàng (Disassemble)
              </option>
              <option value="2" {{ old('type', $bom->type ?? '') == 2 ? 'selected' : '' }}>
                🔗 Ghép hàng (Assemble)
              </option>
            </select>
            @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="mb-3">
            <label class="form-label fw-medium">Ghi chú</label>
            <textarea class="form-control" name="note" rows="3"
                      placeholder="Mô tả thêm về công thức...">{{ old('note', $bom->note ?? '') }}</textarea>
          </div>

          <div class="mb-0">
            <label class="form-label fw-medium">Trạng thái</label>
            <div class="d-flex gap-3">
              <div class="form-check">
                <input class="form-check-input" type="radio" name="status" id="statusActive"
                       value="1" {{ old('status', $bom->status ?? 1) == 1 ? 'checked' : '' }}>
                <label class="form-check-label text-success fw-medium" for="statusActive">
                  <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use></svg>
                  Hoạt động
                </label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="status" id="statusInactive"
                       value="0" {{ old('status', $bom->status ?? 1) == 0 ? 'checked' : '' }}>
                <label class="form-check-label text-secondary" for="statusInactive">
                  <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-x-circle') }}"></use></svg>
                  Ngừng
                </label>
              </div>
            </div>
          </div>

        </div>

        {{-- ===== SUMMARY CARD ===== --}}
        <div class="card-footer bg-transparent">
          <div class="d-flex justify-content-around text-center">
            <div>
              <div class="fw-semibold text-danger fs-5" id="countConsume">0</div>
              <div class="text-body-secondary small">Consume</div>
            </div>
            <div class="vr"></div>
            <div>
              <div class="fw-semibold text-success fs-5" id="countProduce">0</div>
              <div class="text-body-secondary small">Produce</div>
            </div>
            <div class="vr"></div>
            <div>
              <div class="fw-semibold fs-5" id="countTotal">0</div>
              <div class="text-body-secondary small">Tổng dòng</div>
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
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="addRow(1)"
                    title="Thêm nguyên liệu đầu vào (bị tiêu thụ)">
              <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-bottom') }}"></use></svg>
              + Consume
            </button>
            <button type="button" class="btn btn-sm btn-outline-success" onclick="addRow(2)"
                    title="Thêm sản phẩm đầu ra (được tạo ra)">
              <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-top') }}"></use></svg>
              + Produce
            </button>
          </div>
        </div>

        <div class="card-body p-0">

          {{-- Legend --}}
          <div class="px-3 pt-3 pb-2 d-flex gap-3 align-items-center flex-wrap">
            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
              <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-bottom') }}"></use></svg>
              Consume — Nguyên liệu đầu vào (bị tiêu thụ)
            </span>
            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
              <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-top') }}"></use></svg>
              Produce — Sản phẩm đầu ra (được tạo ra)
            </span>
          </div>

          @error('details')
            <div class="alert alert-danger mx-3 mb-2 py-2 small d-flex gap-2 align-items-center">
              <svg class="icon text-danger flex-shrink-0"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use></svg>
              {{ $message }}
            </div>
          @enderror

          <div class="table-responsive">
            <table class="table align-middle mb-0" id="detailTable">
              <thead class="table-light">
                <tr>
                  <th style="width:30px" class="text-center text-body-secondary">#</th>
                  <th style="width:110px" class="text-center">Loại dòng</th>
                  <th>Hàng hóa</th>
                  <th style="width:110px">Số lượng</th>
                  <th style="width:130px">Đơn vị</th>
                  <th style="width:150px">Ghi chú</th>
                  <th style="width:50px"></th>
                </tr>
              </thead>
              <tbody id="detailBody">
                {{-- Populated by JS --}}
              </tbody>
            </table>
          </div>

          <div id="emptyDetail" class="text-center text-body-secondary py-5 small">
            <svg class="icon icon-3xl d-block mx-auto mb-2 opacity-25">
              <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-layers') }}"></use>
            </svg>
            Chưa có dòng nào.<br>
            Nhấn <strong class="text-danger">+ Consume</strong> hoặc <strong class="text-success">+ Produce</strong> để thêm.
          </div>

        </div>
      </div>
    </div>

  </div>{{-- end row --}}
</form>

{{-- ===== DATA CHO JS ===== --}}
@php
  $productData = $products->map(fn($p) => ['id' => $p->id, 'code' => $p->code, 'name' => $p->name, 'uom_id' => $p->uom_id]);
  $uomData     = $uoms->map(fn($u) => ['id' => $u->id, 'name' => $u->name]);

  $oldDetails = old('details');
  if ($oldDetails) {
      $existingRows = $oldDetails;
  } elseif (isset($bom)) {
      $existingRows = $bom->details->map(fn($d) => [
          'product_id' => $d->product_id,
          'line_type'  => $d->line_type,
          'qty'        => $d->qty,
          'uom_id'     => $d->uom_id,
          'note'       => $d->note,
      ])->toArray();
  } else {
      $existingRows = [];
  }
@endphp

<script>
  const products = @json($productData);
  const uoms     = @json($uomData);

  // Dữ liệu cũ (edit mode hoặc validation fail từ server)
  const existingRows = @json($existingRows);

  let rowIndex = 0;

  // ===== BUILD HTML HELPERS =====

  function buildUomOptions(selectedId) {
    return uoms.map(u =>
      `<option value="${u.id}" ${u.id == selectedId ? 'selected' : ''}>${u.name}</option>`
    ).join('');
  }

  function buildProductOptions(selectedId) {
    const opts = products.map(p =>
      `<option value="${p.id}" data-uom="${p.uom_id}" ${p.id == selectedId ? 'selected' : ''}>${p.code} — ${p.name}</option>`
    ).join('');
    return `<option value="">— Chọn hàng hóa —</option>${opts}`;
  }

  // ===== ROW MANAGEMENT =====

  function addRow(lineType, data = {}) {
    const i         = rowIndex++;
    const isConsume = lineType == 1;
    const rowClass  = isConsume ? 'table-danger' : 'table-success';
    const typeBadge = isConsume
      ? `<span class="badge bg-danger-subtle text-danger border border-danger-subtle">
           <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-bottom') }}"></use></svg>
           Consume
         </span>`
      : `<span class="badge bg-success-subtle text-success border border-success-subtle">
           <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-top') }}"></use></svg>
           Produce
         </span>`;

    const tr = document.createElement('tr');
    tr.className = rowClass;
    tr.dataset.index    = i;
    tr.dataset.lineType = lineType;

    tr.innerHTML = `
      <td class="text-center text-body-secondary small seq-num"></td>
      <td class="text-center">
        ${typeBadge}
        <input type="hidden" name="details[${i}][line_type]" value="${lineType}">
      </td>
      <td>
        <select class="form-select form-select-sm product-select" name="details[${i}][product_id]"
                onchange="onProductChange(this, ${i})" required>
          ${buildProductOptions(data.product_id || '')}
        </select>
      </td>
      <td>
        <input type="number" class="form-control form-control-sm font-monospace text-end"
               name="details[${i}][qty]" value="${data.qty || 1}"
               step="0.001" min="0.001" required style="width:100px">
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
        <button type="button" class="btn btn-sm btn-ghost-danger" onclick="removeRow(this)" title="Xóa dòng">
          <svg class="icon icon-sm"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use></svg>
        </button>
      </td>
    `;

    document.getElementById('detailBody').appendChild(tr);
    updateUI();
  }

  function onProductChange(select, i) {
    const opt   = select.options[select.selectedIndex];
    const uomId = opt?.dataset?.uom;
    if (uomId) {
      document.getElementById(`uomSel_${i}`).value = uomId;
    }
    validateCircularClient();
  }

  function removeRow(btn) {
    btn.closest('tr').remove();
    updateUI();
    validateCircularClient();
  }

  // ===== UI UPDATE =====

  function updateUI() {
    const rows    = document.querySelectorAll('#detailBody tr');
    const isEmpty = rows.length === 0;

    document.getElementById('emptyDetail').style.display = isEmpty ? '' : 'none';

    // Đánh số thứ tự
    rows.forEach((tr, idx) => {
      const seqCell = tr.querySelector('.seq-num');
      if (seqCell) seqCell.textContent = idx + 1;
    });

    // Cập nhật counter
    let consume = 0, produce = 0;
    rows.forEach(tr => {
      if (tr.dataset.lineType == 1) consume++;
      else produce++;
    });
    document.getElementById('countConsume').textContent = consume;
    document.getElementById('countProduce').textContent = produce;
    document.getElementById('countTotal').textContent   = rows.length;
  }

  // ===== CLIENT-SIDE CIRCULAR REFERENCE CHECK =====
  // Chỉ kiểm tra vòng lặp trực tiếp trong cùng 1 BOM (vòng gián tiếp do server validate).

  function validateCircularClient() {
    const rows       = document.querySelectorAll('#detailBody tr');
    const produceIds = new Set();
    const consumeIds = new Set();

    rows.forEach(tr => {
      const sel = tr.querySelector('.product-select');
      if (!sel || !sel.value) return;
      const pid = parseInt(sel.value);
      if (tr.dataset.lineType == 2) produceIds.add(pid);
      else consumeIds.add(pid);
    });

    const overlap = [...produceIds].filter(id => consumeIds.has(id));

    const alertEl = document.getElementById('cycleAlert');
    const msgEl   = document.getElementById('cycleMessage');

    if (overlap.length > 0) {
      const names = overlap.map(id => {
        const p = products.find(p => p.id == id);
        return p ? `${p.code} — ${p.name}` : id;
      }).join(', ');
      msgEl.textContent = `Hàng hóa [${names}] vừa là Consume vừa là Produce trong cùng BOM này. Vui lòng chỉ chọn một vai trò cho mỗi sản phẩm.`;
      alertEl.classList.remove('d-none');
      document.getElementById('submitBtn').disabled = true;
    } else {
      alertEl.classList.add('d-none');
      document.getElementById('submitBtn').disabled = false;
    }
  }

  // ===== FORM SUBMIT GUARD =====

  document.getElementById('bomForm').addEventListener('submit', function (e) {
    const rows    = document.querySelectorAll('#detailBody tr');
    let consume   = 0, produce = 0;

    rows.forEach(tr => {
      if (tr.dataset.lineType == 1) consume++;
      else produce++;
    });

    if (rows.length < 2 || consume === 0 || produce === 0) {
      e.preventDefault();
      alert('BOM phải có ít nhất 1 dòng Consume và 1 dòng Produce.');
      return false;
    }

    // Kiểm tra circular một lần nữa trước submit
    const produceIds = new Set();
    const consumeIds = new Set();
    rows.forEach(tr => {
      const sel = tr.querySelector('.product-select');
      if (!sel || !sel.value) return;
      const pid = parseInt(sel.value);
      if (tr.dataset.lineType == 2) produceIds.add(pid);
      else consumeIds.add(pid);
    });
    const overlap = [...produceIds].filter(id => consumeIds.has(id));
    if (overlap.length > 0) {
      e.preventDefault();
      return false;
    }
  });

  // ===== INIT =====

  document.addEventListener('DOMContentLoaded', () => {
    existingRows.forEach(row => addRow(row.line_type, row));
    updateUI();
  });
</script>

@endsection