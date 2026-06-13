@extends('layouts.app')

@section('title', 'Phiếu chuyển kho ' . $transfer->code . ' — Warehouse System')

@section('breadcrumb')
<li class="breadcrumb-item">Nghiệp vụ kho</li>
<li class="breadcrumb-item"><a href="{{ route('transfers.index') }}">Chuyển kho</a></li>
<li class="breadcrumb-item active">{{ $transfer->code }}</li>
@endsection

@section('content')

@php
$fmt = fn($n) => rtrim(rtrim(number_format((float)$n, 3, '.', ','), '0'), '.');
$statusMap = [
1 => ['Nháp', 'secondary', 'cil-pencil'],
2 => ['Chờ duyệt', 'warning', 'cil-clock'],
3 => ['Đã duyệt', 'info', 'cil-check'],
4 => ['Hoàn thành', 'success', 'cil-check-circle'],
5 => ['Đã hủy', 'danger', 'cil-x-circle'],
];
[$statusText, $statusColor, $statusIcon] = $statusMap[$transfer->status] ?? ['?', 'secondary', 'cil-info'];
$typeLabels = [1 => 'Sắp xếp kho', 2 => 'Từ Quarantine', 3 => 'Khác'];
$transferStatus = (int) $transfer->status;
@endphp

{{-- HEADER --}}
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h4 class="mb-1 fw-semibold d-flex align-items-center gap-2">
            {{ $transfer->code }}
            <span
                class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}-emphasis border border-{{ $statusColor }}-subtle rounded-pill fs-6">
                <svg class="icon me-1">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#' . $statusIcon) }}"></use>
                </svg>
                {{ $statusText }}
            </span>
        </h4>
        <small class="text-body-secondary">
            Tạo lúc {{ $transfer->created_at?->format('d/m/Y H:i') }}
            @if($transfer->createdBy) bởi {{ $transfer->createdBy->name }} @endif
        </small>
    </div>
    <div class="d-flex gap-2 flex-wrap">

        {{-- DRAFT: Sửa / Gửi duyệt / Xóa --}}
        @if($transferStatus === 1)
        <a href="{{ route('transfers.edit', $transfer) }}" class="btn btn-outline-secondary">
            <svg class="icon me-1">
                <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-pencil') }}"></use>
            </svg>
            Chỉnh sửa
        </a>
        <form method="POST" action="{{ route('transfers.submit', $transfer) }}">
            @csrf
            <button type="submit" class="btn btn-warning">
                <svg class="icon me-1">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-send') }}"></use>
                </svg>
                Gửi duyệt
            </button>
        </form>
        <form method="POST" action="{{ route('transfers.destroy', $transfer) }}"
            onsubmit="return confirm('Xóa vĩnh viễn phiếu {{ $transfer->code }}?')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-outline-danger">
                <svg class="icon me-1">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use>
                </svg>
                Xóa
            </button>
        </form>
        @endif

        {{-- PENDING: Duyệt phiếu --}}
        @if($transferStatus === 2 && auth()->user()->can('transfer.approve'))
        <form method="POST" action="{{ route('transfers.approve', $transfer) }}">
            @csrf
            <button type="submit" class="btn btn-primary">
                <svg class="icon me-1">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check') }}"></use>
                </svg>
                Duyệt phiếu
            </button>
        </form>
        @endif

        {{-- APPROVED: Xác nhận chuyển kho --}}
        @if($transferStatus === 3)
        <button type="button" class="btn btn-success" data-coreui-toggle="modal" data-coreui-target="#confirmModal">
            <svg class="icon me-1">
                <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use>
            </svg>
            Xác nhận chuyển kho
        </button>
        @endif

        {{-- In phiếu --}}
        @if(in_array($transferStatus, [3, 4]))
        <a href="{{ route('transfers.print', $transfer) }}" target="_blank" class="btn btn-outline-secondary">
            <svg class="icon me-1">
                <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-print') }}"></use>
            </svg>
            Xuất PDF
        </a>
        @endif

        {{-- Hủy phiếu --}}
        @if(!in_array($transferStatus, [4, 5]))
        <form method="POST" action="{{ route('transfers.cancel', $transfer) }}"
            onsubmit="return confirm('Hủy phiếu {{ $transfer->code }}?\nThao tác này không thể khôi phục.')">
            @csrf
            <button type="submit" class="btn btn-outline-danger">
                <svg class="icon me-1">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-x-circle') }}"></use>
                </svg>
                Hủy phiếu
            </button>
        </form>
        @endif

        <a href="{{ route('transfers.index') }}" class="btn btn-outline-secondary">
            <svg class="icon me-1">
                <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-left') }}"></use>
            </svg>
            Quay lại
        </a>
    </div>
</div>

{{-- ALERTS --}}
@if(session('success'))
<div class="alert alert-success alert-dismissible mb-4">
    <svg class="icon me-1">
        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check') }}"></use>
    </svg>
    {{ session('success') }}
    <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible mb-4">
    <svg class="icon me-1">
        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use>
    </svg>
    {{ session('error') }}
    <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
</div>
@endif

{{-- TIMELINE TRẠNG THÁI --}}
<div class="card mb-4">
    <div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-center">
            @php
            $steps = [1 => 'Nháp', 2 => 'Chờ duyệt', 3 => 'Đã duyệt', 4 => 'Hoàn thành'];
            @endphp
            @foreach($steps as $step => $label)
            @php
            $done = $transferStatus >= $step && $transferStatus !== 5;
            $current = $transferStatus === $step;
            $color = $done ? 'success' : 'secondary';
            $bgClass = 'bg-' . $color . ($current ? '' : '-subtle');
            $textClass = 'text-' . $color . ($current ? ' text-white' : '');
            $borderClass = 'border-' . $color;
            $fwClass = $current ? 'fw-semibold' : 'fw-normal';
            $lineClass = $transferStatus > $step ? 'border-success' : 'border-secondary';
            @endphp
            <div class="d-flex flex-column align-items-center flex-fill">
                <div class="rounded-circle d-flex align-items-center justify-content-center mb-1 border border-2 {{ $bgClass }} {{ $textClass }} {{ $borderClass }}"
                    style="width:32px;height:32px;font-size:13px">
                    {{ $step }}
                </div>
                <small class="{{ $textClass }} {{ $fwClass }}">{{ $label }}</small>
            </div>
            @if($step < 4) <div class="flex-fill border-top border-2 mt-2 mb-auto {{ $lineClass }}"
                style="max-width:60px">
        </div>
        @endif
        @endforeach
    </div>
</div>
</div>

<div class="row g-4">

    {{-- CỘT TRÁI: Thông tin phiếu --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header fw-semibold">
                <svg class="icon me-1 text-primary">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-description') }}"></use>
                </svg>
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
                        <span
                            class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}-emphasis border border-{{ $statusColor }}-subtle rounded-pill">
                            {{ $statusText }}
                        </span>
                    </dd>

                    <dt class="col-sm-5 text-body-secondary">Người tạo</dt>
                    <dd class="col-sm-7">{{ $transfer->createdBy?->name ?? '—' }}</dd>

                    @if($transfer->approvedBy)
                    <dt class="col-sm-5 text-body-secondary">Người duyệt</dt>
                    <dd class="col-sm-7">{{ $transfer->approvedBy->name }}</dd>
                    @endif

                    @if($transfer->confirmedBy)
                    <dt class="col-sm-5 text-body-secondary">Người xác nhận</dt>
                    <dd class="col-sm-7">{{ $transfer->confirmedBy->name }}</dd>
                    @endif

                    @if($transfer->note)
                    <dt class="col-sm-5 text-body-secondary">Ghi chú</dt>
                    <dd class="col-sm-7">{{ $transfer->note }}</dd>
                    @endif
                </dl>
            </div>
        </div>

        @if($transferStatus === 4)
        <div class="card mt-4 border-success">
            <div class="card-body text-success d-flex align-items-center gap-2">
                <svg class="icon icon-xl">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use>
                </svg>
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
                    <svg class="icon me-1 text-primary">
                        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-list') }}"></use>
                    </svg>
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
                                <td class="text-end fw-semibold">{{ $fmt($detail->quantity) }}</td>
                                <td>
                                    <span
                                        class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle">
                                        {{ $detail->fromLocation?->code ?? '—' }}
                                    </span>
                                    @if($detail->fromLocation?->name)
                                    <div class="text-body-secondary small">{{ $detail->fromLocation->name }}</div>
                                    @endif
                                </td>
                                <td>
                                    <span
                                        class="badge bg-primary-subtle text-primary-emphasis border border-primary-subtle">
                                        {{ $detail->toLocation?->code ?? '—' }}
                                    </span>
                                    @if($detail->toLocation?->name)
                                    <div class="text-body-secondary small">{{ $detail->toLocation->name }}</div>
                                    @endif
                                </td>
                                <td class="text-body-secondary small">
                                    {{ $detail->lot?->lot_number ?? ($detail->serial?->serial_number ?? '—') }}
                                </td>
                                <td class="text-body-secondary small">{{ $detail->note ?? '—' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center text-body-secondary py-4">Không có dòng chi tiết.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if($transfer->details->count())
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="7" class="text-end fw-semibold small text-body-secondary">
                                    Tổng cộng: <span class="fw-bold text-body">{{ $transfer->details->count() }} mặt
                                        hàng</span>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- MODAL DUYỆT PHIẾU --}}
@if($transferStatus === 2)
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-primary">
                    <svg class="icon me-1">
                        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check') }}"></use>
                    </svg>
                    Duyệt phiếu chuyển kho
                </h5>
                <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Duyệt phiếu <strong>{{ $transfer->code }}</strong>?</p>
                <div class="alert alert-info small mb-0">
                    <svg class="icon me-1">
                        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-info') }}"></use>
                    </svg>
                    Sau khi duyệt, thủ kho sẽ tiến hành xác nhận để cập nhật tồn kho thực tế.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-coreui-dismiss="modal">Hủy bỏ</button>
                <form method="POST" action="{{ route('transfers.approve', $transfer) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        <svg class="icon me-1">
                            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check') }}"></use>
                        </svg>
                        Duyệt phiếu
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

{{-- MODAL XÁC NHẬN CHUYỂN KHO --}}
@if($transferStatus === 3)
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-success">
                    <svg class="icon me-1">
                        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use>
                    </svg>
                    Xác nhận chuyển kho
                </h5>
                <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Xác nhận đã chuyển hàng theo phiếu <strong>{{ $transfer->code }}</strong>?</p>
                <div class="alert alert-danger small mb-0">
                    <svg class="icon me-1">
                        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use>
                    </svg>
                    Sau khi xác nhận, tồn kho sẽ được cập nhật và <strong>không thể hoàn tác</strong>.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-coreui-dismiss="modal">Hủy bỏ</button>
                <form method="POST" action="{{ route('transfers.confirm', $transfer) }}">
                    @csrf
                    <button type="submit" class="btn btn-success">
                        <svg class="icon me-1">
                            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}">
                            </use>
                        </svg>
                        Xác nhận & cập nhật tồn kho
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

@endsection