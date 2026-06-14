@extends('layouts.app')

@section('title', 'Tách / Ghép hàng hóa — Warehouse System')

@section('breadcrumb')
  <li class="breadcrumb-item">Nghiệp vụ kho</li>
  <li class="breadcrumb-item active">Tách / Ghép hàng hóa</li>
@endsection

@section('content')

  {{-- HEADER --}}
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-0 fw-semibold">Tách / Ghép hàng hóa</h4>
      <small class="text-body-secondary">Quản lý phiếu tách kiện, ghép lô và chuyển đổi đơn vị hàng hóa</small>
    </div>
    <a href="{{ route('transformations.create') }}" class="btn btn-primary">
      <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use></svg>
      Tạo phiếu mới
    </a>
  </div>

  {{-- CARDS THỐNG KÊ --}}
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
      <div class="card border-start border-start-4 border-start-primary">
        <div class="card-body d-flex align-items-center gap-3">
          <svg class="icon icon-2xl text-primary"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-layers') }}"></use></svg>
          <div>
            <div class="fs-5 fw-semibold">{{ $totalCount ?? 0 }}</div>
            <div class="text-body-secondary small">Tổng phiếu</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card border-start border-start-4 border-start-warning">
        <div class="card-body d-flex align-items-center gap-3">
          <svg class="icon icon-2xl text-warning"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-clock') }}"></use></svg>
          <div>
            <div class="fs-5 fw-semibold">{{ $pendingCount ?? 0 }}</div>
            <div class="text-body-secondary small">Chờ duyệt</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card border-start border-start-4 border-start-success">
        <div class="card-body d-flex align-items-center gap-3">
          <svg class="icon icon-2xl text-success"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use></svg>
          <div>
            <div class="fs-5 fw-semibold">{{ $completedCount ?? 0 }}</div>
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
      <span class="fw-semibold">Danh sách phiếu tách / ghép</span>
      <form method="GET" action="{{ route('transformations.index') }}" class="d-flex gap-2 flex-wrap">
        <div class="input-group" style="width:220px">
          <span class="input-group-text">
            <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-search') }}"></use></svg>
          </span>
          <input type="text" class="form-control" name="search"
                 value="{{ request('search') }}" placeholder="Mã phiếu...">
        </div>
        <select class="form-select" name="type" style="width:145px">
          <option value="">Tất cả loại</option>
          <option value="1" {{ request('type') == '1' ? 'selected' : '' }}>Tách hàng</option>
          <option value="2" {{ request('type') == '2' ? 'selected' : '' }}>Ghép hàng</option>
        </select>
        <select class="form-select" name="status" style="width:150px">
          <option value="">Tất cả trạng thái</option>
          <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Nháp</option>
          <option value="2" {{ request('status') == '2' ? 'selected' : '' }}>Chờ duyệt</option>
          <option value="3" {{ request('status') == '3' ? 'selected' : '' }}>Đã duyệt</option>
          <option value="4" {{ request('status') == '4' ? 'selected' : '' }}>Hoàn thành</option>
          <option value="5" {{ request('status') == '5' ? 'selected' : '' }}>Đã hủy</option>
        </select>
        <input type="date" class="form-control" name="date_from"
               value="{{ request('date_from') }}" style="width:140px">
        <input type="date" class="form-control" name="date_to"
               value="{{ request('date_to') }}" style="width:140px">
        <button class="btn btn-outline-secondary" type="submit">
          <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-filter') }}"></use></svg>
        </button>
        @if(request()->hasAny(['search','type','status','date_from','date_to']))
          <a href="{{ route('transformations.index') }}" class="btn btn-outline-danger" title="Xóa bộ lọc">
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
              <th>Ngày thực hiện</th>
              <th>BOM</th>
              <th>Người tạo</th>
              <th>Trạng thái</th>
              <th>Ghi chú</th>
              <th class="text-end" style="width:120px">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($transformations as $tf)
            @php
              $typeMap = [
                1 => ['Tách hàng', 'warning'],
                2 => ['Ghép hàng', 'info'],
              ];
              [$typeText, $typeColor] = $typeMap[$tf->type] ?? ['?', 'secondary'];

              $statusMap = [
                1 => ['Nháp',       'bg-secondary-subtle text-secondary-emphasis'],
                2 => ['Chờ duyệt',  'bg-warning-subtle text-warning-emphasis'],
                3 => ['Đã duyệt',   'bg-info-subtle text-info-emphasis'],
                4 => ['Hoàn thành', 'bg-success-subtle text-success-emphasis'],
                5 => ['Đã hủy',     'bg-danger-subtle text-danger-emphasis'],
              ];
              [$statusText, $statusClass] = $statusMap[$tf->status] ?? ['?', 'bg-secondary-subtle text-secondary-emphasis'];
            @endphp
            <tr>
              <td>
                <a href="{{ route('transformations.show', $tf) }}" class="fw-semibold text-decoration-none">
                  {{ $tf->code }}
                </a>
              </td>
              <td>
                <span class="badge bg-{{ $typeColor }}-subtle text-{{ $typeColor }}-emphasis border border-{{ $typeColor }}-subtle rounded-pill">
                  @if($tf->type === 1)
                    <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-fork') }}"></use></svg>
                  @else
                    <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-layers') }}"></use></svg>
                  @endif
                  {{ $typeText }}
                </span>
              </td>
              <td>{{ $tf->transformation_date?->format('d/m/Y') ?? '—' }}</td>
              <td class="text-body-secondary small">
                @if($tf->bom)
                  <span title="{{ $tf->bom->name }}">{{ $tf->bom->code }}</span>
                @else
                  <span class="text-body-secondary">—</span>
                @endif
              </td>
              <td class="text-body-secondary small">{{ $tf->createdBy?->name ?? '—' }}</td>
              <td>
                <span class="badge {{ $statusClass }} rounded-pill">{{ $statusText }}</span>
              </td>
              <td class="text-body-secondary small" style="max-width:200px">
                <span class="text-truncate d-inline-block" style="max-width:180px">{{ $tf->note ?? '—' }}</span>
              </td>
              <td class="text-end">
                <a href="{{ route('transformations.show', $tf) }}"
                   class="btn btn-sm btn-outline-primary" title="Xem chi tiết">
                  <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-magnifying-glass') }}"></use></svg>
                </a>
                @if($tf->status === 1)
                  <a href="{{ route('transformations.edit', $tf) }}"
                     class="btn btn-sm btn-outline-secondary" title="Chỉnh sửa">
                    <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-pencil') }}"></use></svg>
                  </a>
                @endif
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="8" class="text-center text-body-secondary py-5">
                <svg class="icon icon-3xl d-block mx-auto mb-2 opacity-25">
                  <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-layers') }}"></use>
                </svg>
                Chưa có phiếu tách / ghép nào.
              </td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if($transformations->hasPages())
      <div class="card-footer">
        {{ $transformations->links() }}
      </div>
    @endif
  </div>

@endsection