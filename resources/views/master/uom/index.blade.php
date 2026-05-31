@extends('layouts.app')

@section('title', 'Đơn vị tính — Warehouse System')

@section('breadcrumb')
  <li class="breadcrumb-item">Danh mục</li>
  <li class="breadcrumb-item active">Đơn vị tính</li>
@endsection

@section('content')

  {{-- ===== HEADER ===== --}}
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-0 fw-semibold">Đơn vị tính</h4>
      <small class="text-body-secondary">Quản lý danh sách đơn vị tính hàng hóa</small>
    </div>
    <button class="btn btn-primary" onclick="openModal()">
      <svg class="icon me-1">
        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use>
      </svg>
      Thêm mới
    </button>
  </div>

  {{-- ===== CARD THỐNG KÊ ===== --}}
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
      <div class="card border-start border-start-4 border-start-primary">
        <div class="card-body d-flex align-items-center gap-3">
          <svg class="icon icon-2xl text-primary">
            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-tags') }}"></use>
          </svg>
          <div>
            <div class="fs-5 fw-semibold">{{ $totalCount ?? 0 }}</div>
            <div class="text-body-secondary small">Tổng đơn vị tính</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card border-start border-start-4 border-start-success">
        <div class="card-body d-flex align-items-center gap-3">
          <svg class="icon icon-2xl text-success">
            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use>
          </svg>
          <div>
            <div class="fs-5 fw-semibold">{{ $activeCount ?? 0 }}</div>
            <div class="text-body-secondary small">Đang hoạt động</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- ===== BẢNG DANH SÁCH ===== --}}
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <span class="fw-semibold">Danh sách đơn vị tính</span>

      {{-- Tìm kiếm + lọc --}}
      <div class="d-flex gap-2 flex-wrap">
        <form method="GET" action="{{ route('master.uom.index') }}" class="d-flex gap-2">
          <div class="input-group" style="width:220px">
            <span class="input-group-text">
              <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-search') }}"></use></svg>
            </span>
            <input type="text" class="form-control" name="search"
                   value="{{ request('search') }}" placeholder="Tìm tên đơn vị...">
          </div>
          <select class="form-select" name="status" style="width:140px">
            <option value="">Tất cả</option>
            <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Hoạt động</option>
            <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Ngừng</option>
          </select>
          <button type="submit" class="btn btn-outline-primary">Lọc</button>
          @if(request('search') || request('status') !== null && request('status') !== '')
            <a href="{{ route('master.uom.index') }}" class="btn btn-outline-secondary">Xóa lọc</a>
          @endif
        </form>
      </div>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th style="width:60px" class="text-center">#</th>
              <th>Tên đơn vị tính</th>
              <th class="text-center" style="width:130px">Trạng thái</th>
              <th class="text-center" style="width:120px">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($uoms as $index => $uom)
              <tr>
                <td class="text-center text-body-secondary">
                  {{ ($uoms->currentPage() - 1) * $uoms->perPage() + $index + 1 }}
                </td>
                <td class="fw-medium">{{ $uom->name }}</td>
                <td class="text-center">
                  @if ($uom->status == 1)
                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                      <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use></svg>
                      Hoạt động
                    </span>
                  @else
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                      <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-ban') }}"></use></svg>
                      Ngừng
                    </span>
                  @endif
                </td>
                <td class="text-center">
                  <button class="btn btn-sm btn-outline-primary me-1"
                          onclick="openModal({{ $uom->id }}, '{{ addslashes($uom->name) }}', {{ $uom->status }})"
                          title="Chỉnh sửa">
                    <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-pencil') }}"></use></svg>
                  </button>
                  <button class="btn btn-sm btn-outline-danger"
                          onclick="confirmDelete({{ $uom->id }}, '{{ addslashes($uom->name) }}')"
                          title="Xóa">
                    <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use></svg>
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="text-center text-body-secondary py-5">
                  <svg class="icon icon-3xl d-block mx-auto mb-2 opacity-25">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-tags') }}"></use>
                  </svg>
                  Chưa có đơn vị tính nào
                  @if(request('search'))
                    <div class="small mt-1">Không tìm thấy kết quả cho "<strong>{{ request('search') }}</strong>"</div>
                  @endif
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    {{-- Pagination --}}
    @if ($uoms->hasPages())
      <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-body-secondary">
          Hiển thị {{ $uoms->firstItem() }}–{{ $uoms->lastItem() }}
          trong tổng số {{ $uoms->total() }} đơn vị tính
        </small>
        {{ $uoms->appends(request()->query())->links('pagination::bootstrap-5') }}
      </div>
    @endif
  </div>

  {{-- ===== MODAL TẠO / SỬA ===== --}}
  <div class="modal fade" id="uomModal" tabindex="-1" aria-labelledby="uomModalLabel">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form id="uomForm" method="POST">
          @csrf
          <input type="hidden" name="_method" id="formMethod" value="POST">

          <div class="modal-header">
            <h5 class="modal-title" id="uomModalLabel">Thêm đơn vị tính</h5>
            <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
          </div>

          <div class="modal-body">
            {{-- Tên --}}
            <div class="mb-3">
              <label class="form-label fw-medium">
                Tên đơn vị tính <span class="text-danger">*</span>
              </label>
              <input type="text" class="form-control" id="uomName" name="name"
                     placeholder="VD: Cái, Cuộn, Kg, Hộp..."
                     required maxlength="50">
              <div class="form-text">Tối đa 50 ký tự</div>
            </div>

            {{-- Trạng thái --}}
            <div class="mb-3">
              <label class="form-label fw-medium">Trạng thái</label>
              <div class="d-flex gap-3">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="status"
                         id="statusActive" value="1" checked>
                  <label class="form-check-label" for="statusActive">
                    <span class="text-success">Hoạt động</span>
                  </label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="status"
                         id="statusInactive" value="0">
                  <label class="form-check-label" for="statusInactive">
                    <span class="text-secondary">Ngừng hoạt động</span>
                  </label>
                </div>
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary"
                    data-coreui-dismiss="modal">Hủy</button>
            <button type="submit" class="btn btn-primary" id="submitBtn">
              <svg class="icon me-1">
                <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-save') }}"></use>
              </svg>
              Lưu
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  {{-- ===== MODAL XÁC NHẬN XÓA ===== --}}
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
            Bạn có chắc muốn xóa đơn vị tính<br>
            <strong id="deleteUomName" class="text-body"></strong>?
          </p>
          <p class="text-danger small mt-1">Hành động này không thể hoàn tác.</p>
        </div>
        <div class="modal-footer border-0 pt-0 justify-content-center gap-2">
          <button type="button" class="btn btn-outline-secondary btn-sm"
                  data-coreui-dismiss="modal">Hủy</button>
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
  const routeStore = '{{ route('master.uom.store') }}';
  const routeBase  = '{{ url('master/uom') }}';

  function openModal(id = null, name = '', status = 1) {
    const modal     = new coreui.Modal(document.getElementById('uomModal'));
    const form      = document.getElementById('uomForm');
    const title     = document.getElementById('uomModalLabel');
    const nameInput = document.getElementById('uomName');
    const method    = document.getElementById('formMethod');

    if (id) {
      // Chế độ sửa
      title.textContent   = 'Chỉnh sửa đơn vị tính';
      form.action         = `${routeBase}/${id}`;
      method.value        = 'PUT';
      nameInput.value     = name;
      document.getElementById(status == 1 ? 'statusActive' : 'statusInactive').checked = true;
    } else {
      // Chế độ tạo mới
      title.textContent = 'Thêm đơn vị tính';
      form.action       = routeStore;
      method.value      = 'POST';
      form.reset();
      document.getElementById('statusActive').checked = true;
    }

    modal.show();
    setTimeout(() => nameInput.focus(), 300);
  }

  function confirmDelete(id, name) {
    document.getElementById('deleteUomName').textContent = name;
    document.getElementById('deleteForm').action = `${routeBase}/${id}`;
    new coreui.Modal(document.getElementById('deleteModal')).show();
  }

  // Tự mở lại modal nếu có validation error
  @if ($errors->any())
    document.addEventListener('DOMContentLoaded', () => {
      openModal(
        {{ old('_id', 'null') }},
        '{{ old('name', '') }}',
        {{ old('status', 1) }}
      );
    });
  @endif
</script>
@endpush
