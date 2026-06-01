@extends('layouts.app')

@section('title', 'Kiểm kê kho — Warehouse System')

@section('breadcrumb')
  <li class="breadcrumb-item">Nghiệp vụ kho</li>
  <li class="breadcrumb-item active">Kiểm kê</li>
@endsection

@section('content')

  {{-- HEADER --}}
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-0 fw-semibold">Kiểm kê kho</h4>
      <small class="text-body-secondary">Quản lý phiếu kiểm kê và điều chỉnh tồn kho</small>
    </div>
    <a href="{{ route('stocktakes.create') }}" class="btn btn-primary">
      <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use></svg>
      Tạo phiếu kiểm kê
    </a>
  </div>

  {{-- CARDS THỐNG KÊ --}}
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
      <div class="card border-start border-start-4 border-start-primary">
        <div class="card-body d-flex align-items-center gap-3">
          <svg class="icon icon-2xl text-primary"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-clipboard') }}"></use></svg>
          <div>
            <div class="fs-5 fw-semibold">{{ $totalCount ?? 0 }}</div>
            <div class="text-body-secondary small">Tổng phiếu kiểm kê</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card border-start border-start-4 border-start-warning">
        <div class="card-body d-flex align-items-center gap-3">
          <svg class="icon icon-2xl text-warning"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-lock-locked') }}"></use></svg>
          <div>
            <div class="fs-5 fw-semibold">{{ $inProgressCount ?? 0 }}</div>
            <div class="text-body-secondary small">Đang kiểm kê</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card border-start border-start-4 border-start-success">
        <div class="card-body d-flex align-items-center gap-3">
          <svg class="icon icon-2xl text-success"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use></svg>
          <div>
            <div class="fs-5 fw-semibold">{{ $doneCount ?? 0 }}</div>
            <div class="text-body-secondary small">Hoàn thành</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card border-start border-start-4 border-start-danger">
        <div class="card-body d-flex align-items-center gap-3">
          <svg class="icon icon-2xl text-danger"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-x-circle') }}"></use></svg>
          <div>
            <div class="fs-5 fw-semibold">{{ $cancelledCount ?? 0 }}</div>
            <div class="text-body-secondary small">Đã hủy</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- BẢNG DANH SÁCH --}}
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <span class="fw-semibold">Danh sách phiếu kiểm kê</span>
      <form method="GET" action="{{ route('stocktakes.index') }}" class="d-flex gap-2 flex-wrap">
        <div class="input-group" style="width:210px">
          <span class="input-group-text">
            <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-search') }}"></use></svg>
          </span>
          <input type="text" class="form-control" name="search"
                 value="{{ request('search') }}" placeholder="Mã phiếu...">
        </div>
        <select class="form-select" name="check_type" style="width:155px">
          <option value="">Tất cả loại</option>
          <option value="1" {{ request('check_type') == '1' ? 'selected' : '' }}>Toàn kho</option>
          <option value="2" {{ request('check_type') == '2' ? 'selected' : '' }}>Theo khu vực</option>
          <option value="3" {{ request('check_type') == '3' ? 'selected' : '' }}>Theo mặt hàng</option>
        </select>
        <select class="form-select" name="status" style="width:145px">
          <option value="">Tất cả trạng thái</option>
          <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Nháp</option>
          <option value="2" {{ request('status') == '2' ? 'selected' : '' }}>Đang kiểm kê</option>
          <option value="3" {{ request('status') == '3' ? 'selected' : '' }}>Hoàn thành</option>
          <option value="4" {{ request('status') == '4' ? 'selected' : '' }}>Đã hủy</option>
        </select>
        <input type="date" class="form-control" name="date_from"
               value="{{ request('date_from') }}" style="width:140px">
        <input type="date" class="form-control" name="date_to"
               value="{{ request('date_to') }}" style="width:140px">
        <button class="btn btn-outline-secondary" type="submit">
          <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-filter') }}"></use></svg>
        </button>
        @if(request()->hasAny(['search','check_type','status','date_from','date_to']))
          <a href="{{ route('stocktakes.index') }}" class="btn btn-outline-danger" title="Xóa bộ lọc">
            <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-x') }}"></use></svg>
          </a>
        @endif
      </form>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible mx-3 mt-3 mb-0" role="alert">
        <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check') }}"></use></svg>
        {{ session('success') }}
        <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
      </div>
    @endif
    @if(session('error'))
      <div class="alert alert-danger alert-dismissible mx-3 mt-3 mb-0" role="alert">
        <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use></svg>
        {{ session('error') }}
        <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
      </div>
    @endif

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Mã phiếu</th>
              <th>Loại</th>
              <th>Ngày kiểm</th>
              <th class="text-center">Dòng</th>
              <th>Người phụ trách</th>
              <th>Trạng thái</th>
              <th>Đóng băng</th>
              <th class="text-end" style="width:80px">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($checks as $check)
            @php
              $statusMap = [
                1 => ['Nháp',          'secondary'],
                2 => ['Đang kiểm kê',  'warning'],
                3 => ['Hoàn thành',    'success'],
                4 => ['Đã hủy',        'danger'],
              ];
              [$statusText, $statusColor] = $statusMap[$check->status] ?? ['?', 'secondary'];
              $typeLabels = [1 => ['Toàn kho','primary'], 2 => ['Theo khu vực','info'], 3 => ['Theo mặt hàng','secondary']];
              [$typeText, $typeColor] = $typeLabels[$check->check_type] ?? ['?', 'secondary'];
              $isFrozen = $check->status == 2 && $check->freeze && $check->freeze->isActive();
            @endphp
            <tr>
              <td>
                <a href="{{ route('stocktakes.show', $check) }}" class="fw-semibold text-decoration-none">
                  {{ $check->code }}
                </a>
              </td>
              <td>
                <span class="badge bg-{{ $typeColor }}-subtle text-{{ $typeColor }}-emphasis border border-{{ $typeColor }}-subtle rounded-pill">
                  {{ $typeText }}
                </span>
              </td>
              <td>{{ $check->check_date?->format('d/m/Y') ?? '—' }}</td>
              <td class="text-center">
                <span class="badge bg-primary-subtle text-primary-emphasis">{{ $check->lines_count }}</span>
              </td>
              <td class="text-body-secondary small">{{ $check->assignedTo?->name ?? '—' }}</td>
              <td>
                <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}-emphasis border border-{{ $statusColor }}-subtle rounded-pill">
                  {{ $statusText }}
                </span>
              </td>
              <td>
                @if($isFrozen)
                  <span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle rounded-pill">
                    <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-lock-locked') }}"></use></svg>
                    Đang đóng băng
                  </span>
                @else
                  <span class="text-body-secondary small">—</span>
                @endif
              </td>
              <td class="text-end">
                <a href="{{ route('stocktakes.show', $check) }}"
                   class="btn btn-sm btn-outline-primary" title="Xem chi tiết">
                  <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-magnifying-glass') }}"></use></svg>
                </a>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="8" class="text-center text-body-secondary py-5">
                <svg class="icon icon-3xl d-block mx-auto mb-2 opacity-25">
                  <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-clipboard') }}"></use>
                </svg>
                Chưa có phiếu kiểm kê nào.
              </td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if($checks->hasPages())
    <div class="card-footer">
      {{ $checks->links() }}
    </div>
    @endif
  </div>

@endsection