@extends('layouts.app')

@section('title', 'Chuyển kho — Warehouse System')

@section('breadcrumb')
  <li class="breadcrumb-item">Nghiệp vụ kho</li>
  <li class="breadcrumb-item active">Chuyển kho</li>
@endsection

@section('content')

  {{-- HEADER --}}
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-0 fw-semibold">Chuyển kho nội bộ</h4>
      <small class="text-body-secondary">Quản lý phiếu di chuyển hàng hóa giữa các vị trí trong kho</small>
    </div>
    <a href="{{ route('transfers.create') }}" class="btn btn-primary">
      <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use></svg>
      Tạo phiếu chuyển kho
    </a>
  </div>

  {{-- CARDS THỐNG KÊ --}}
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
      <div class="card border-start border-start-4 border-start-primary">
        <div class="card-body d-flex align-items-center gap-3">
          <svg class="icon icon-2xl text-primary"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-transfer') }}"></use></svg>
          <div>
            <div class="fs-5 fw-semibold">{{ $totalCount ?? 0 }}</div>
            <div class="text-body-secondary small">Tổng phiếu chuyển</div>
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
            <div class="text-body-secondary small">Chờ xác nhận</div>
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
      <span class="fw-semibold">Danh sách phiếu chuyển kho</span>
      <form method="GET" action="{{ route('transfers.index') }}" class="d-flex gap-2 flex-wrap">
        <div class="input-group" style="width:220px">
          <span class="input-group-text">
            <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-search') }}"></use></svg>
          </span>
          <input type="text" class="form-control" name="search"
                 value="{{ request('search') }}" placeholder="Mã phiếu...">
        </div>
        <select class="form-select" name="transfer_type" style="width:160px">
          <option value="">Tất cả loại</option>
          <option value="1" {{ request('transfer_type') == '1' ? 'selected' : '' }}>Sắp xếp kho</option>
          <option value="2" {{ request('transfer_type') == '2' ? 'selected' : '' }}>Từ Quarantine</option>
          <option value="3" {{ request('transfer_type') == '3' ? 'selected' : '' }}>Khác</option>
        </select>
        <select class="form-select" name="status" style="width:140px">
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
        @if(request()->hasAny(['search','transfer_type','status','date_from','date_to']))
          <a href="{{ route('transfers.index') }}" class="btn btn-outline-danger" title="Xóa bộ lọc">
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
              <th>Ngày chuyển</th>
              <th class="text-center">Số dòng</th>
              <th>Người tạo</th>
              <th>Trạng thái</th>
              <th>Ghi chú</th>
              <th class="text-end" style="width:120px">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($transfers as $transfer)
            <tr>
              <td>
                <a href="{{ route('transfers.show', $transfer) }}" class="fw-semibold text-decoration-none">
                  {{ $transfer->code }}
                </a>
              </td>
              <td>
                @php
                  $typeLabels = [1 => ['Sắp xếp kho', 'secondary'], 2 => ['Từ Quarantine', 'warning'], 3 => ['Khác', 'info']];
                  [$typeText, $typeColor] = $typeLabels[$transfer->transfer_type] ?? ['?', 'secondary'];
                @endphp
                <span class="badge bg-{{ $typeColor }}-subtle text-{{ $typeColor }}-emphasis border border-{{ $typeColor }}-subtle rounded-pill">
                  {{ $typeText }}
                </span>
              </td>
              <td>{{ $transfer->transfer_date?->format('d/m/Y') ?? '—' }}</td>
              <td class="text-center">
                <span class="badge bg-primary-subtle text-primary-emphasis">{{ $transfer->details_count }}</span>
              </td>
              <td class="text-body-secondary small">{{ $transfer->createdBy?->name ?? '—' }}</td>
              <td>
                @php
                  $statusMap = [
                    1 => ['Nháp',      'bg-secondary-subtle text-secondary-emphasis'],
                    2 => ['Chờ duyệt', 'bg-warning-subtle text-warning-emphasis'],
                    3 => ['Đã duyệt',  'bg-info-subtle text-info-emphasis'],
                    4 => ['Hoàn thành','bg-success-subtle text-success-emphasis'],
                    5 => ['Đã hủy',    'bg-danger-subtle text-danger-emphasis'],
                  ];
                  [$statusText, $statusClass] = $statusMap[$transfer->status] ?? ['?', 'bg-secondary-subtle text-secondary-emphasis'];
                @endphp
                <span class="badge {{ $statusClass }} rounded-pill">{{ $statusText }}</span>
              </td>
              <td class="text-body-secondary small" style="max-width:200px">
                <span class="text-truncate d-inline-block" style="max-width:180px">{{ $transfer->note ?? '—' }}</span>
              </td>
              <td class="text-end">
                <a href="{{ route('transfers.show', $transfer) }}"
                   class="btn btn-sm btn-outline-primary" title="Xem chi tiết">
                  <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-magnifying-glass') }}"></use></svg>
                </a>
                @if($transfer->status === 1)
                  <a href="{{ route('transfers.edit', $transfer) }}"
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
                  <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-transfer') }}"></use>
                </svg>
                Chưa có phiếu chuyển kho nào.
              </td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if($transfers->hasPages())
    <div class="card-footer">
      {{ $transfers->links() }}
    </div>
    @endif
  </div>

@endsection