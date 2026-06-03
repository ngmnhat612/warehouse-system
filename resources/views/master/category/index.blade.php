@extends('layouts.app')

@section('title', 'Danh mục hàng hóa — Warehouse System')

@section('breadcrumb')
  <li class="breadcrumb-item">Danh mục</li>
  <li class="breadcrumb-item active">Nhóm hàng hóa</li>
@endsection

@section('content')

  {{-- ===== HEADER ===== --}}
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-0 fw-semibold">Nhóm hàng hóa</h4>
      <small class="text-body-secondary">Quản lý danh mục hàng hóa (hỗ trợ phân cấp cha — con)</small>
    </div>
    <button class="btn btn-primary" onclick="openModal()">
      <svg class="icon me-1">
        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use>
      </svg>
      Thêm mới
    </button>
  </div>

  {{-- ===== CARDS THỐNG KÊ ===== --}}
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
      <div class="card border-start border-start-4 border-start-primary">
        <div class="card-body d-flex align-items-center gap-3">
          <svg class="icon icon-2xl text-primary">
            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-layers') }}"></use>
          </svg>
          <div>
            <div class="fs-5 fw-semibold">{{ $totalCount }}</div>
            <div class="text-body-secondary small">Tổng danh mục</div>
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
            <div class="fs-5 fw-semibold">{{ $activeCount }}</div>
            <div class="text-body-secondary small">Đang hoạt động</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card border-start border-start-4 border-start-info">
        <div class="card-body d-flex align-items-center gap-3">
          <svg class="icon icon-2xl text-info">
            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-sitemap') }}"></use>
          </svg>
          <div>
            <div class="fs-5 fw-semibold">{{ $parentOptions->count() }}</div>
            <div class="text-body-secondary small">Danh mục gốc</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- ===== VIEW TOGGLE ===== --}}
  <ul class="nav nav-tabs mb-3" id="categoryViewTabs" data-coreui-toggle="tabs">
    <li class="nav-item">
      <a class="nav-link {{ $viewMode !== 'tree' ? 'active' : '' }}" href="#tabList" data-coreui-toggle="tab">
        <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-list') }}"></use></svg>
        Danh sách
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link {{ $viewMode === 'tree' ? 'active' : '' }}" href="#tabTree" data-coreui-toggle="tab">
        <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-sitemap') }}"></use></svg>
        Sơ đồ cây
      </a>
    </li>
  </ul>

  <div class="tab-content">

    {{-- ===== TAB DANH SÁCH ===== --}}
    <div class="tab-pane fade {{ $viewMode !== 'tree' ? 'show active' : '' }}" id="tabList">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
          <span class="fw-semibold">Danh sách nhóm hàng hóa</span>

          <form method="GET" action="{{ route('master.category.index') }}" class="d-flex gap-2 flex-wrap">
            <input type="hidden" name="view" value="list">
            <div class="input-group" style="width:220px">
              <span class="input-group-text">
                <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-search') }}"></use></svg>
              </span>
              <input type="text" class="form-control" name="search"
                     value="{{ request('search') }}" placeholder="Mã hoặc tên danh mục...">
            </div>

            <select class="form-select" name="parent_id" style="width:160px">
              <option value="">Tất cả cấp</option>
              <option value="root" {{ request('parent_id') == 'root' ? 'selected' : '' }}>Chỉ danh mục gốc</option>
              @foreach ($parentOptions as $p)
                <option value="{{ $p->id }}" {{ request('parent_id') == $p->id ? 'selected' : '' }}>
                  Con của: {{ $p->name }}
                </option>
              @endforeach
            </select>

            <select class="form-select" name="status" style="width:130px">
              <option value="">Tất cả</option>
              <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Hoạt động</option>
              <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Ngừng</option>
            </select>

            <button type="submit" class="btn btn-outline-primary">Lọc</button>
            @if(request('search') || request('parent_id') || (request('status') !== null && request('status') !== ''))
              <a href="{{ route('master.category.index') }}" class="btn btn-outline-secondary">Xóa lọc</a>
            @endif
          </form>
        </div>

        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th style="width:55px" class="text-center">#</th>
                  <th style="width:110px">Mã</th>
                  <th>Tên danh mục</th>
                  <th style="width:180px">Danh mục cha</th>
                  <th>Mô tả</th>
                  <th class="text-center" style="width:130px">Trạng thái</th>
                  <th class="text-center" style="width:120px">Thao tác</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($categories as $index => $cat)
                  <tr>
                    <td class="text-center text-body-secondary">
                      {{ ($categories->currentPage() - 1) * $categories->perPage() + $index + 1 }}
                    </td>
                    <td>
                      <code class="text-primary fw-medium">{{ $cat->code }}</code>
                    </td>
                    <td>
                      @if ($cat->parent)
                        <span class="text-body-secondary me-1" style="font-size:11px">
                          <svg class="icon icon-sm"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-level-down') }}"></use></svg>
                        </span>
                      @endif
                      <span class="fw-medium">{{ $cat->name }}</span>
                      @if ($cat->hasChildren())
                        <span class="badge bg-info-subtle text-info border border-info-subtle ms-1" style="font-size:10px">
                          Có con
                        </span>
                      @endif
                    </td>
                    <td>
                      @if ($cat->parent)
                        <span class="badge bg-body-secondary text-body border">{{ $cat->parent->name }}</span>
                      @else
                        <span class="text-body-secondary small">— Danh mục gốc</span>
                      @endif
                    </td>
                    <td class="text-body-secondary small">
                      {{ Str::limit($cat->description, 60) ?: '—' }}
                    </td>
                    <td class="text-center">
                      @if ($cat->status == 1)
                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                          Hoạt động
                        </span>
                      @else
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                          Ngừng
                        </span>
                      @endif
                    </td>
                    <td class="text-center">
                      <button class="btn btn-sm btn-outline-primary me-1"
                              onclick="openModal(
                                {{ $cat->id }},
                                '{{ addslashes($cat->code) }}',
                                '{{ addslashes($cat->name) }}',
                                {{ $cat->parent_id ?? 'null' }},
                                '{{ addslashes($cat->description ?? '') }}',
                                {{ $cat->status }}
                              )"
                              title="Chỉnh sửa">
                        <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-pencil') }}"></use></svg>
                      </button>
                      <button class="btn btn-sm btn-outline-danger"
                              onclick="confirmDelete({{ $cat->id }}, '{{ addslashes($cat->name) }}')"
                              title="Xóa">
                        <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use></svg>
                      </button>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="7" class="text-center text-body-secondary py-5">
                      <svg class="icon icon-3xl d-block mx-auto mb-2 opacity-25">
                        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-layers') }}"></use>
                      </svg>
                      Chưa có danh mục nào
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

        @if ($paginator && $paginator->hasPages())
          <div class="card-footer d-flex justify-content-between align-items-center">
            <small class="text-body-secondary">
              Hiển thị {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}
              trong tổng số {{ $paginator->total() }} danh mục
            </small>
            {{ $paginator->appends(request()->query())->links('pagination::bootstrap-5') }}
          </div>
        @endif
      </div>
    </div>

    {{-- ===== TAB SƠ ĐỒ CÂY ===== --}}
    <div class="tab-pane fade {{ $viewMode === 'tree' ? 'show active' : '' }}" id="tabTree">
      @include('master.category.partials.tree')
    </div>

  </div>

  {{-- ===== MODAL TẠO / SỬA ===== --}}
  <div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form id="categoryForm" method="POST">
          @csrf
          <input type="hidden" name="_method" id="formMethod" value="POST">
          <input type="hidden" name="return_view" id="returnView" value="list">

          <div class="modal-header">
            <h5 class="modal-title" id="categoryModalLabel">Thêm danh mục</h5>
            <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
          </div>

          <div class="modal-body">

            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label fw-medium">
                  Mã danh mục <span class="text-danger">*</span>
                </label>
                <input type="text" class="form-control text-uppercase" id="catCode" name="code"
                       placeholder="VD: LK, MAY..." required maxlength="50"
                       style="letter-spacing:1px">
                <div class="form-text">Tự động viết hoa</div>
              </div>

              <div class="col-md-8">
                <label class="form-label fw-medium">
                  Tên danh mục <span class="text-danger">*</span>
                </label>
                <input type="text" class="form-control" id="catName" name="name"
                       placeholder="VD: Linh kiện điện tử..." required maxlength="200">
              </div>
            </div>

            <div class="mb-3 mt-3">
              <label class="form-label fw-medium">Danh mục cha</label>
              <select class="form-select" id="catParent" name="parent_id">
                <option value="">— Không có (danh mục gốc) —</option>
                @foreach ($parentOptions as $p)
                  <option value="{{ $p->id }}">{{ $p->name }}</option>
                @endforeach
              </select>
              <div class="form-text">Để trống nếu đây là danh mục cấp cao nhất</div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-medium">Mô tả</label>
              <textarea class="form-control" id="catDesc" name="description"
                        rows="2" maxlength="500"
                        placeholder="Mô tả ngắn về nhóm hàng hóa này..."></textarea>
            </div>

            <div>
              <label class="form-label fw-medium">Trạng thái</label>
              <div class="d-flex gap-3">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="status"
                         id="catStatusActive" value="1" checked>
                  <label class="form-check-label text-success" for="catStatusActive">Hoạt động</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="status"
                         id="catStatusInactive" value="0">
                  <label class="form-check-label text-secondary" for="catStatusInactive">Ngừng hoạt động</label>
                </div>
              </div>
            </div>

          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-coreui-dismiss="modal">Hủy</button>
            <button type="submit" class="btn btn-primary">
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
            Bạn có chắc muốn xóa danh mục<br>
            <strong id="deleteCatName" class="text-body"></strong>?
          </p>
          <p class="text-danger small mt-1">Không thể xóa nếu có danh mục con hoặc hàng hóa.</p>
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
  const routeStore = '{{ route('master.category.store') }}';
  const routeBase  = '{{ url('master/category') }}';

  // Khi mở modal từ tree view, truyền return_view = 'tree' để redirect về đúng tab
  function openModal(id = null, code = '', name = '', parentId = null, desc = '', status = 1) {
    const modal      = new coreui.Modal(document.getElementById('categoryModal'));
    const form       = document.getElementById('categoryForm');
    const title      = document.getElementById('categoryModalLabel');
    const method     = document.getElementById('formMethod');
    const returnView = document.getElementById('returnView');

    // Xác định đang ở tab nào để redirect về đúng chỗ sau khi lưu
    const activeTab = document.querySelector('#categoryViewTabs .nav-link.active');
    returnView.value = (activeTab && activeTab.getAttribute('href') === '#tabTree') ? 'tree' : 'list';

    document.getElementById('catCode').value   = code;
    document.getElementById('catName').value   = name;
    document.getElementById('catDesc').value   = desc;
    document.getElementById('catParent').value = parentId ?? '';
    document.getElementById(status == 1 ? 'catStatusActive' : 'catStatusInactive').checked = true;

    if (id) {
      title.textContent = 'Chỉnh sửa danh mục';
      form.action       = `${routeBase}/${id}`;
      method.value      = 'PUT';
    } else {
      title.textContent = 'Thêm danh mục';
      form.action       = routeStore;
      method.value      = 'POST';
      form.reset();
      document.getElementById('catStatusActive').checked = true;
      returnView.value = (activeTab && activeTab.getAttribute('href') === '#tabTree') ? 'tree' : 'list';
    }

    modal.show();
    setTimeout(() => document.getElementById('catCode').focus(), 300);
  }

  function confirmDelete(id, name) {
    document.getElementById('deleteCatName').textContent = name;
    document.getElementById('deleteForm').action = `${routeBase}/${id}`;
    new coreui.Modal(document.getElementById('deleteModal')).show();
  }

  // Tự động viết hoa mã danh mục
  document.getElementById('catCode').addEventListener('input', function () {
    const pos = this.selectionStart;
    this.value = this.value.toUpperCase();
    this.setSelectionRange(pos, pos);
  });

  // Khôi phục tab đúng khi redirect về với ?view=tree
  @if($viewMode === 'tree')
    document.addEventListener('DOMContentLoaded', function () {
      const treeTab = document.querySelector('[href="#tabTree"]');
      if (treeTab) coreui.Tab.getOrCreateInstance(treeTab).show();
    });
  @endif
</script>
@endpush