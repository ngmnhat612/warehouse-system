@extends('layouts.app')

@section('title', 'Putaway Rules — Warehouse System')

@section('breadcrumb')
  <li class="breadcrumb-item">Danh mục</li>
  <li class="breadcrumb-item active">Quy tắc gán vị trí (Putaway Rules)</li>
@endsection

@section('content')

  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-0 fw-semibold">Quy tắc gán vị trí (Putaway Rules)</h4>
      <small class="text-body-secondary">Tự động gợi ý vị trí lưu trữ khi nhập kho</small>
    </div>
    <button class="btn btn-primary" onclick="openCreate()">
      <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use></svg>
      Thêm Rule
    </button>
  </div>

  {{-- THỐNG KÊ --}}
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
      <div class="card border-start border-start-4 border-start-primary">
        <div class="card-body d-flex align-items-center gap-3">
          <svg class="icon icon-2xl text-primary"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-sitemap') }}"></use></svg>
          <div>
            <div class="fs-5 fw-semibold">{{ $totalCount }}</div>
            <div class="text-body-secondary small">Tổng số rules</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card border-start border-start-4 border-start-success">
        <div class="card-body d-flex align-items-center gap-3">
          <svg class="icon icon-2xl text-success"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use></svg>
          <div>
            <div class="fs-5 fw-semibold">{{ $activeCount }}</div>
            <div class="text-body-secondary small">Đang hoạt động</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- BẢNG --}}
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <span class="fw-semibold">Danh sách Putaway Rules</span>
      <form method="GET" action="{{ route('master.putaway_rule.index') }}" class="d-flex gap-2 flex-wrap">
        <div class="input-group" style="width:240px">
          <span class="input-group-text">
            <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-search') }}"></use></svg>
          </span>
          <input type="text" class="form-control" name="search"
                 value="{{ request('search') }}" placeholder="Tên hàng, nhóm hàng...">
        </div>
        <select class="form-select" name="apply_on" style="width:160px">
          <option value="">Tất cả loại</option>
          <option value="product"  {{ request('apply_on') == 'product'  ? 'selected' : '' }}>Theo hàng hóa</option>
          <option value="category" {{ request('apply_on') == 'category' ? 'selected' : '' }}>Theo nhóm</option>
        </select>
        <select class="form-select" name="status" style="width:130px">
          <option value="">Tất cả</option>
          <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Hoạt động</option>
          <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Ngừng</option>
        </select>
        <button type="submit" class="btn btn-outline-primary">Lọc</button>
        @if(request('search') || request('apply_on') || (request('status') !== null && request('status') !== ''))
          <a href="{{ route('master.putaway_rule.index') }}" class="btn btn-outline-secondary">Xóa lọc</a>
        @endif
      </form>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th style="width:50px" class="text-center">#</th>
              <th>Áp dụng cho</th>
              <th>Vị trí đích</th>
              <th class="text-center" style="width:100px">Ưu tiên</th>
              <th style="width:200px">Ghi chú</th>
              <th class="text-center" style="width:120px">Trạng thái</th>
              <th class="text-center" style="width:100px">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($rules as $index => $rule)
              <tr>
                <td class="text-center text-body-secondary">
                  {{ ($rules->currentPage() - 1) * $rules->perPage() + $index + 1 }}
                </td>
                <td>
                  @if ($rule->product_id)
                    <span class="badge bg-info-subtle text-info border border-info-subtle me-1">
                      <svg class="icon icon-sm"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-tag') }}"></use></svg>
                      Hàng hóa
                    </span>
                    <div class="fw-medium">{{ $rule->product->name ?? '—' }}</div>
                    <div class="text-body-secondary small font-monospace">{{ $rule->product->code ?? '' }}</div>
                  @else
                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle me-1">
                      <svg class="icon icon-sm"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-folder') }}"></use></svg>
                      Nhóm hàng
                    </span>
                    <div class="fw-medium">{{ $rule->category->name ?? '—' }}</div>
                  @endif
                </td>
                <td>
                  <div class="fw-medium">{{ $rule->locationDest->name ?? '—' }}</div>
                  <div class="text-body-secondary small font-monospace">{{ $rule->locationDest->code ?? '' }}</div>
                </td>
                <td class="text-center">
                  <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                    {{ $rule->priority }}
                  </span>
                </td>
                <td class="text-body-secondary small">{{ Str::limit($rule->note, 50) }}</td>
                <td class="text-center">
                  @if ($rule->status == 1)
                    <span class="badge bg-success-subtle text-success border border-success-subtle">Hoạt động</span>
                  @else
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Ngừng</span>
                  @endif
                </td>
                <td class="text-center">
                  <button class="btn btn-sm btn-outline-primary me-1" title="Chỉnh sửa"
                          onclick="openEdit({{ $rule }})">
                    <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-pencil') }}"></use></svg>
                  </button>
                  <button class="btn btn-sm btn-outline-danger" title="Xóa"
                          onclick="confirmDelete({{ $rule->id }}, '{{ addslashes($rule->applies_on_label) }}')">
                    <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use></svg>
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center text-body-secondary py-5">
                  <svg class="icon icon-3xl d-block mx-auto mb-2 opacity-25">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-sitemap') }}"></use>
                  </svg>
                  Chưa có putaway rule nào
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if ($rules->hasPages())
      <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-body-secondary">
          Hiển thị {{ $rules->firstItem() }}–{{ $rules->lastItem() }}
          trong tổng số {{ $rules->total() }} rules
        </small>
        {{ $rules->appends(request()->query())->links('pagination::bootstrap-5') }}
      </div>
    @endif
  </div>

  {{-- ═══════════════════════════════════════════════════════════════════════ --}}
  {{-- MODAL THÊM / SỬA --}}
  {{-- ═══════════════════════════════════════════════════════════════════════ --}}
  <div class="modal fade" id="ruleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title fw-semibold" id="ruleModalLabel">Thêm Putaway Rule</h5>
          <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
        </div>
        <form id="ruleForm" method="POST">
          @csrf
          <span id="methodField"></span>
          <div class="modal-body">

            {{-- Loại áp dụng --}}
            <div class="mb-3">
              <label class="form-label fw-medium">Áp dụng cho <span class="text-danger">*</span></label>
              <div class="d-flex gap-3">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="apply_on" id="applyProduct"
                         value="product" checked onchange="toggleApplyOn('product')">
                  <label class="form-check-label" for="applyProduct">Hàng hóa cụ thể</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="apply_on" id="applyCategory"
                         value="category" onchange="toggleApplyOn('category')">
                  <label class="form-check-label" for="applyCategory">Nhóm hàng hóa</label>
                </div>
              </div>
            </div>

            {{-- Hàng hóa --}}
            <div class="mb-3" id="productField">
              <label class="form-label fw-medium" for="product_id">Hàng hóa <span class="text-danger">*</span></label>
              <select class="form-select @error('product_id') is-invalid @enderror" id="product_id" name="product_id">
                <option value="">— Chọn hàng hóa —</option>
                @foreach ($products as $p)
                  <option value="{{ $p->id }}">[{{ $p->code }}] {{ $p->name }}</option>
                @endforeach
              </select>
              @error('product_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            {{-- Nhóm hàng --}}
            <div class="mb-3 d-none" id="categoryField">
              <label class="form-label fw-medium" for="category_id">Nhóm hàng hóa <span class="text-danger">*</span></label>
              <select class="form-select @error('category_id') is-invalid @enderror" id="category_id" name="category_id">
                <option value="">— Chọn nhóm —</option>
                @foreach ($categories as $c)
                  <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
              </select>
              @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            {{-- Vị trí đích --}}
            <div class="mb-3">
              <label class="form-label fw-medium" for="location_dest_id">Vị trí đích <span class="text-danger">*</span></label>
              <select class="form-select @error('location_dest_id') is-invalid @enderror" id="location_dest_id" name="location_dest_id">
                <option value="">— Chọn vị trí —</option>
                @foreach ($locations as $loc)
                  <option value="{{ $loc->id }}">[{{ $loc->code }}] {{ $loc->name }}</option>
                @endforeach
              </select>
              @error('location_dest_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="row g-3">
              {{-- Ưu tiên --}}
              <div class="col-6">
                <label class="form-label fw-medium" for="priority">Ưu tiên</label>
                <input type="number" class="form-control @error('priority') is-invalid @enderror"
                       id="priority" name="priority" value="10" min="1" max="999">
                <div class="form-text">Số nhỏ = ưu tiên cao hơn</div>
                @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              {{-- Trạng thái --}}
              <div class="col-6">
                <label class="form-label fw-medium" for="status_rule">Trạng thái <span class="text-danger">*</span></label>
                <select class="form-select @error('status') is-invalid @enderror" id="status_rule" name="status">
                  <option value="1">Hoạt động</option>
                  <option value="0">Ngừng</option>
                </select>
                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
            </div>

            {{-- Ghi chú --}}
            <div class="mb-0 mt-3">
              <label class="form-label fw-medium" for="rule_note">Ghi chú</label>
              <textarea class="form-control" id="rule_note" name="note" rows="2" maxlength="500"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-coreui-dismiss="modal">Hủy</button>
            <button type="submit" class="btn btn-primary">
              <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-save') }}"></use></svg>
              Lưu
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  {{-- MODAL XÓA --}}
  <div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
      <div class="modal-content">
        <div class="modal-header border-0 pb-0">
          <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
        </div>
        <div class="modal-body text-center px-4 pb-2">
          <svg class="icon icon-3xl text-danger mb-3">
            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use>
          </svg>
          <h6 class="fw-semibold mb-1">Xác nhận xóa</h6>
          <p class="text-body-secondary small mb-0">
            Bạn có chắc muốn xóa rule áp dụng cho<br>
            <strong id="deleteRuleName" class="text-body"></strong>?
          </p>
        </div>
        <div class="modal-footer border-0 pt-0 justify-content-center gap-2">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-coreui-dismiss="modal">Hủy</button>
          <form id="deleteForm" method="POST" class="d-inline">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
          </form>
        </div>
      </div>
    </div>
  </div>

@endsection

@push('scripts')
<script>
  // ─── Helpers ──────────────────────────────────────────────────────────────
  function toggleApplyOn(type) {
    document.getElementById('productField').classList.toggle('d-none',  type !== 'product');
    document.getElementById('categoryField').classList.toggle('d-none', type !== 'category');
  }

  // ─── Mở modal TẠO MỚI ─────────────────────────────────────────────────────
  function openCreate() {
    const form = document.getElementById('ruleForm');
    document.getElementById('ruleModalLabel').textContent = 'Thêm Putaway Rule';
    form.action = '{{ route("master.putaway_rule.store") }}';
    document.getElementById('methodField').innerHTML = '';

    // Reset fields
    form.reset();
    document.querySelector('input[value="product"]').checked = true;
    toggleApplyOn('product');
    document.getElementById('priority').value = 10;
    document.getElementById('status_rule').value = 1;

    new coreui.Modal(document.getElementById('ruleModal')).show();
  }

  // ─── Mở modal CHỈNH SỬA ───────────────────────────────────────────────────
  function openEdit(rule) {
    const form = document.getElementById('ruleForm');
    document.getElementById('ruleModalLabel').textContent = 'Chỉnh sửa Putaway Rule';
    form.action = `{{ url('master/putaway_rule') }}/${rule.id}`;
    document.getElementById('methodField').innerHTML = '<input type="hidden" name="_method" value="PUT">';

    // Apply on
    const applyOn = rule.product_id ? 'product' : 'category';
    document.querySelector(`input[value="${applyOn}"]`).checked = true;
    toggleApplyOn(applyOn);

    document.getElementById('product_id').value      = rule.product_id  || '';
    document.getElementById('category_id').value     = rule.category_id || '';
    document.getElementById('location_dest_id').value = rule.location_dest_id;
    document.getElementById('priority').value        = rule.priority;
    document.getElementById('status_rule').value     = rule.status;
    document.getElementById('rule_note').value       = rule.note || '';

    new coreui.Modal(document.getElementById('ruleModal')).show();
  }

  // ─── Xóa ──────────────────────────────────────────────────────────────────
  function confirmDelete(id, label) {
    document.getElementById('deleteRuleName').textContent = label;
    document.getElementById('deleteForm').action = `{{ url('master/putaway_rule') }}/${id}`;
    new coreui.Modal(document.getElementById('deleteModal')).show();
  }

  // ─── Validation errors → mở modal lại nếu có lỗi ─────────────────────────
  @if ($errors->any())
    const modal = new coreui.Modal(document.getElementById('ruleModal'));
    modal.show();
    @if(old('apply_on'))
      document.querySelector('input[value="{{ old("apply_on") }}"]').checked = true;
      toggleApplyOn('{{ old("apply_on") }}');
    @endif
  @endif
</script>
@endpush