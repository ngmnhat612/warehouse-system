@extends('layouts.app')

@section('title', 'Phiếu chuyển kho ' . $transfer->code . ' — Warehouse System')

@section('breadcrumb')
  <li class="breadcrumb-item">Nghiệp vụ kho</li>
  <li class="breadcrumb-item"><a href="{{ route('transfers.index') }}">Chuyển kho</a></li>
  <li class="breadcrumb-item active">{{ $transfer->code }}</li>
@endsection

@section('content')

@php
  $statusMap = [
    1 => ['Nháp',       'secondary', 'cil-pencil'],
    2 => ['Chờ duyệt',  'warning',   'cil-clock'],
    3 => ['Đã duyệt',   'info',      'cil-check'],
    4 => ['Hoàn thành', 'success',   'cil-check-circle'],
    5 => ['Đã hủy',     'danger',    'cil-x-circle'],
  ];
  [$statusText, $statusColor, $statusIcon] = $statusMap[$transfer->status] ?? ['?', 'secondary', 'cil-info'];

  $typeLabels = [
    1 => 'Sắp xếp kho',
    2 => 'Từ Quarantine',
    3 => 'Khác',
  ];
@endphp

  {{-- HEADER --}}
  <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
      <h4 class="mb-1 fw-semibold d-flex align-items-center gap-2">
        {{ $transfer->code }}
        <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}-emphasis border border-{{ $statusColor }}-subtle rounded-pill fs-6">
          <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#' . $statusIcon) }}"></use></svg>
          {{ $statusText }}
        </span>
      </h4>
      <small class="text-body-secondary">
        Tạo lúc {{ $transfer->created_at?->format('d/m/Y H:i') }}
        @if($transfer->createdBy) bởi {{ $transfer->createdBy->name }} @endif
      </small>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      @if($transfer->status === 1)
        <a href="{{ route('transfers.edit', $transfer) }}" class="btn btn-outline-secondary">
          <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-pencil') }}"></use></svg>
          Chỉnh sửa
        </a>
      @endif

      @if(in_array($transfer->status, [1, 2]))
        <form method="POST" action="{{ route('transfers.confirm', $transfer) }}"
              onsubmit="return confirm('Xác nhận phiếu chuyển kho {{ $transfer->code }}?\nTồn kho sẽ được cập nhật ngay.')">
          @csrf
          <button type="submit" class="btn btn-success">
            <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use></svg>
            Xác nhận & Chuyển kho
          </button>
        </form>
      @endif

      @if(!in_array($transfer->status, [4, 5]))
        <form method="POST" action="{{ route('transfers.cancel', $transfer) }}"
              onsubmit="return confirm('Hủy phiếu {{ $transfer->code }}?\nThao tác này không thể khôi phục.')">
          @csrf
          <button type="submit" class="btn btn-outline-danger">
            <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-x-circle') }}"></use></svg>
            Hủy phiếu
          </button>
        </form>
      @endif

      @if($transfer->status === 1)
        <form method="POST" action="{{ route('transfers.destroy', $transfer) }}"
              onsubmit="return confirm('Xóa vĩnh viễn phiếu {{ $transfer->code }}?')">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-outline-danger">
            <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use></svg>
            Xóa
          </button>
        </form>
      @endif

      <a href="{{ route('transfers.index') }}" class="btn btn-outline-secondary">
        <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-left') }}"></use></svg>
        Quay lại
      </a>
    </div>
  </div>

  {{-- ALERTS --}}
  @if(session('success'))
    <div class="alert alert-success alert-dismissible mb-4" role="alert">
      <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check') }}"></use></svg>
      {{ session('success') }}
      <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
    </div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger alert-dismissible mb-4" role="alert">
      <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use></svg>
      {{ session('error') }}
      <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
    </div>
  @endif

  <div class="row g-4">

    {{-- CỘT TRÁI: Thông tin phiếu --}}
    <div class="col-lg-4">
      <div class="card">
        <div class="card-header fw-semibold">
          <svg class="icon me-1 text-primary"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-description') }}"></use></svg>
          Thông tin phiếu
        </div>
        <div class="card-body">
          <dl class="row mb-0 small">
            <dt class="col-sm-5 text-body-secondary">Mã phiếu</dt>
            <dd class="col-sm-7 fw-semibold">{{ $transfer->code }}</dd>

            <dt class="col-sm-5 text-body-secondary">Loại chuyển</dt>
            <dd class="col-sm-7">{{ $typeLabels[$transfer->transfer_type] ?? '—' }}</dd>

            <dt class="col-sm-5 text-body-secondary">Ngày chuyển</dt>
            <dd class="col-sm-7">{{ $transfer->transfer_date?->format('d/m/Y') ?? '—' }}</dd>

            <dt class="col-sm-5 text-body-secondary">Trạng thái</dt>
            <dd class="col-sm-7">
              <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}-emphasis border border-{{ $statusColor }}-subtle rounded-pill">
                {{ $statusText }}
              </span>
            </dd>

            <dt class="col-sm-5 text-body-secondary">Người tạo</dt>
            <dd class="col-sm-7">{{ $transfer->createdBy?->name ?? '—' }}</dd>

            @if($transfer->confirmedBy)
            <dt class="col-sm-5 text-body-secondary">Người xác nhận</dt>
            <dd class="col-sm-7">{{ $transfer->confirmedBy->name }}</dd>
            @endif

            <dt class="col-sm-5 text-body-secondary">Ngày tạo</dt>
            <dd class="col-sm-7">{{ $transfer->created_at?->format('d/m/Y H:i') ?? '—' }}</dd>

            @if($transfer->note)
            <dt class="col-sm-5 text-body-secondary">Ghi chú</dt>
            <dd class="col-sm-7">{{ $transfer->note }}</dd>
            @endif
          </dl>
        </div>
      </div>

      @if($transfer->status === 4)
      <div class="card mt-4 border-success">
        <div class="card-body text-success d-flex align-items-center gap-2">
          <svg class="icon icon-xl"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use></svg>
          <div>
            <div class="fw-semibold small">Đã cập nhật tồn kho</div>
            <div class="text-body-secondary small">Tổng kho không thay đổi — chỉ vị trí thay đổi.</div>
          </div>
        </div>
      </div>
      @endif
    </div>

    {{-- CỘT PHẢI: Chi tiết hàng hóa --}}
    <div class="col-lg-8">
      <div class="card">
        <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
          <span>
            <svg class="icon me-1 text-primary"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-list') }}"></use></svg>
            Chi tiết hàng hóa
          </span>
          <span class="badge bg-primary-subtle text-primary-emphasis">
            {{ $transfer->details->count() }} dòng
          </span>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th style="width:36px">#</th>
                  <th>Hàng hóa</th>
                  <th>ĐVT</th>
                  <th class="text-end">Số lượng</th>
                  <th>Vị trí nguồn</th>
                  <th>Vị trí đích</th>
                  <th>Lot / Batch</th>
                  <th>Ghi chú</th>
                </tr>
              </thead>
              <tbody>
                @forelse($transfer->details as $i => $detail)
                <tr>
                  <td class="text-center text-body-secondary small">{{ $i + 1 }}</td>
                  <td>
                    <div class="fw-semibold small">{{ $detail->product?->name ?? '—' }}</div>
                    <div class="text-body-secondary small">{{ $detail->product?->code }}</div>
                  </td>
                  <td class="text-body-secondary small">{{ $detail->uom?->name ?? '—' }}</td>
                  <td class="text-end fw-semibold">
                    {{ number_format($detail->quantity, 3) }}
                  </td>
                  <td>
                    <span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle">
                      {{ $detail->fromLocation?->code ?? '—' }}
                    </span>
                    @if($detail->fromLocation?->name)
                      <div class="text-body-secondary small">{{ $detail->fromLocation->name }}</div>
                    @endif
                  </td>
                  <td>
                    <span class="badge bg-primary-subtle text-primary-emphasis border border-primary-subtle">
                      {{ $detail->toLocation?->code ?? '—' }}
                    </span>
                    @if($detail->toLocation?->name)
                      <div class="text-body-secondary small">{{ $detail->toLocation->name }}</div>
                    @endif
                  </td>
                  <td class="text-body-secondary small">{{ $detail->lot?->lot_number ?? '—' }}</td>
                  <td class="text-body-secondary small">{{ $detail->note ?? '—' }}</td>
                </tr>
                @empty
                <tr>
                  <td colspan="8" class="text-center text-body-secondary py-4">Không có dòng chi tiết.</td>
                </tr>
                @endforelse
              </tbody>
              @if($transfer->details->count())
              <tfoot class="table-light">
                <tr>
                  <td colspan="3" class="text-end fw-semibold small text-body-secondary">Tổng số lượng:</td>
                  <td class="text-end fw-bold">{{ number_format($transfer->details->sum('quantity'), 3) }}</td>
                  <td colspan="4"></td>
                </tr>
              </tfoot>
              @endif
            </table>
          </div>
        </div>
      </div>
    </div>

  </div>

@endsection