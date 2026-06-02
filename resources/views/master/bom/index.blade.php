@extends('layouts.app')

@section('title', 'Công thức BOM — Warehouse System')

@section('breadcrumb')
  <li class="breadcrumb-item">Danh mục</li>
  <li class="breadcrumb-item active">Công thức BOM</li>
@endsection

@section('content')

  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-0 fw-semibold">Công thức BOM</h4>
      <small class="text-body-secondary">Quản lý công thức nguyên liệu đầu vào và sản phẩm đầu ra</small>
    </div>
    <a href="{{ route('master.bom.create') }}" class="btn btn-primary">
      <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use></svg>
      Thêm BOM
    </a>
  </div>

  {{-- THỐNG KÊ --}}
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
      <div class="card border-start border-start-4 border-start-primary">
        <div class="card-body d-flex align-items-center gap-3">
          <svg class="icon icon-2xl text-primary"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-layers') }}"></use></svg>
          <div>
            <div class="fs-5 fw-semibold">{{ $totalCount }}</div>
            <div class="text-body-secondary small">Tổng BOM</div>
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
      <span class="fw-semibold">Danh sách BOM</span>
      <form method="GET" action="{{ route('master.bom.index') }}" class="d-flex gap-2 flex-wrap">
        <div class="input-group" style="width:240px">
          <span class="input-group-text">
            <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-search') }}"></use></svg>
          </span>
          <input type="text" class="form-control" name="search"
                 value="{{ request('search') }}" placeholder="Mã, tên BOM...">
        </div>
        <select class="form-select" name="type" style="width:160px">
          <option value="">Tất cả loại</option>
          <option value="1" {{ request('type') == '1' ? 'selected' : '' }}>Tách hàng</option>
          <option value="2" {{ request('type') == '2' ? 'selected' : '' }}>Ghép hàng</option>
        </select>
        <select class="form-select" name="status" style="width:130px">
          <option value="">Tất cả</option>
          <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Hoạt động</option>
          <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Ngừng</option>
        </select>
        <button type="submit" class="btn btn-outline-primary">Lọc</button>
        @if(request('search') || request('type') || request('status') !== null && request('status') !== '')
          <a href="{{ route('master.bom.index') }}" class="btn btn-outline-secondary">Xóa lọc</a>
        @endif
      </form>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th style="width:60px" class="text-center">#</th>
              <th style="width:120px">Mã BOM</th>
              <th>Tên công thức</th>
              <th class="text-center" style="width:140px">Loại</th>
              <th class="text-center" style="width:100px">Consume</th>
              <th class="text-center" style="width:100px">Produce</th>
              <th class="text-center" style="width:120px">Trạng thái</th>
              <th class="text-center" style="width:120px">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($boms as $index => $bom)
              <tr>
                <td class="text-center text-body-secondary">
                  {{ ($boms->currentPage() - 1) * $boms->perPage() + $index + 1 }}
                </td>
                <td class="font-monospace fw-medium">{{ $bom->code }}</td>
                <td>
                  <div class="fw-medium">{{ $bom->name }}</div>
                  @if ($bom->note)
                    <div class="text-body-secondary small">{{ Str::limit($bom->note, 60) }}</div>
                  @endif
                </td>
                <td class="text-center">
                  @if ($bom->type == 1)
                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                      <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-fork') }}"></use></svg>
                      Tách hàng
                    </span>
                  @else
                    <span class="badge bg-info-subtle text-info border border-info-subtle">
                      <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-merge') }}"></use></svg>
                      Ghép hàng
                    </span>
                  @endif
                </td>
                <td class="text-center">
                  <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                    {{ $bom->consume_lines_count }} dòng
                  </span>
                </td>
                <td class="text-center">
                  <span class="badge bg-success-subtle text-success border border-success-subtle">
                    {{ $bom->produce_lines_count }} dòng
                  </span>
                </td>
                <td class="text-center">
                  @if ($bom->status == 1)
                    <span class="badge bg-success-subtle text-success border border-success-subtle">Hoạt động</span>
                  @else
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Ngừng</span>
                  @endif
                </td>
                <td class="text-center">
                  <a href="{{ route('master.bom.edit', $bom) }}"
                     class="btn btn-sm btn-outline-primary me-1" title="Chỉnh sửa">
                    <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-pencil') }}"></use></svg>
                  </a>
                  <button class="btn btn-sm btn-outline-danger"
                          onclick="confirmDelete({{ $bom->id }}, '{{ addslashes($bom->name) }}')"
                          title="Xóa">
                    <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use></svg>
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center text-body-secondary py-5">
                  <svg class="icon icon-3xl d-block mx-auto mb-2 opacity-25">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-layers') }}"></use>
                  </svg>
                  Chưa có BOM nào
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if ($boms->hasPages())
      <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-body-secondary">
          Hiển thị {{ $boms->firstItem() }}–{{ $boms->lastItem() }}
          trong tổng số {{ $boms->total() }} BOM
        </small>
        {{ $boms->appends(request()->query())->links('pagination::bootstrap-5') }}
      </div>
    @endif
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
            Bạn có chắc muốn xóa BOM<br>
            <strong id="deleteBomName" class="text-body"></strong>?
          </p>
          <p class="text-danger small mt-1">Toàn bộ dòng chi tiết sẽ bị xóa theo.</p>
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
  function confirmDelete(id, name) {
    document.getElementById('deleteBomName').textContent = name;
    document.getElementById('deleteForm').action = `{{ url('master/bom') }}/${id}`;
    new coreui.Modal(document.getElementById('deleteModal')).show();
  }
</script>
@endpush