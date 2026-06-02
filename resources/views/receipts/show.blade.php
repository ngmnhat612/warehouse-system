@extends('layouts.app')

@section('title', 'Phiếu nhập ' . $receipt->code . ' — Warehouse System')

@section('breadcrumb')
<li class="breadcrumb-item">Nghiệp vụ kho</li>
<li class="breadcrumb-item"><a href="{{ route('receipts.index') }}">Nhập kho</a></li>
<li class="breadcrumb-item active">{{ $receipt->code }}</li>
@endsection

@section('content')

@php
$statusMap = [
1 => ['Nháp', 'secondary', 'cil-pencil'],
2 => ['Chờ duyệt', 'warning', 'cil-clock'],
3 => ['Đã duyệt', 'info', 'cil-check'],
4 => ['Hoàn thành', 'success', 'cil-check-circle'],
5 => ['Đã hủy', 'danger', 'cil-x-circle'],
];
[$statusText, $statusColor, $statusIcon] = $statusMap[$receipt->status] ?? ['?', 'secondary', 'cil-info'];
$typeLabels = [1 => 'Từ nhà cung cấp', 2 => 'Trả hàng SX', 3 => 'Khác'];
@endphp

{{-- HEADER --}}
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h4 class="mb-1 fw-semibold d-flex align-items-center gap-2">
            {{ $receipt->code }}
            <span
                class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}-emphasis border border-{{ $statusColor }}-subtle rounded-pill fs-6">
                <svg class="icon me-1">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#' . $statusIcon) }}"></use>
                </svg>
                {{ $statusText }}
            </span>
        </h4>
        <small class="text-body-secondary">
            Tạo lúc {{ $receipt->created_at?->format('d/m/Y H:i') }}
            @if($receipt->createdBy) bởi {{ $receipt->createdBy->name }} @endif
        </small>
    </div>
    <div class="d-flex gap-2 flex-wrap">

        {{-- DRAFT: Sửa / Gửi duyệt / Xóa --}}
        @if((int) $receipt->status === 1)
        <a href="{{ route('receipts.edit', $receipt) }}" class="btn btn-outline-secondary">
            <svg class="icon me-1">
                <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-pencil') }}"></use>
            </svg>
            Chỉnh sửa
        </a>
        <form method="POST" action="{{ route('receipts.submit', $receipt) }}">
            @csrf
            <button type="submit" class="btn btn-warning">
                <svg class="icon me-1">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-send') }}"></use>
                </svg>
                Gửi duyệt
            </button>
        </form>
        <form method="POST" action="{{ route('receipts.destroy', $receipt) }}"
            onsubmit="return confirm('Xóa vĩnh viễn phiếu {{ $receipt->code }}?')">
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
        @if((int) $receipt->status === 2)
        <form method="POST" action="{{ route('receipts.approve', $receipt) }}">
            @csrf
            <button type="submit" class="btn btn-primary">
                <svg class="icon me-1">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check') }}"></use>
                </svg>
                Duyệt phiếu
            </button>
        </form>
        @endif

        {{-- APPROVED: Hoàn tất nhận hàng --}}
        @if((int) $receipt->status === 3)
        <button type="button" class="btn btn-success" data-coreui-toggle="modal" data-coreui-target="#confirmModal">
            <svg class="icon me-1">
                <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use>
            </svg>
            Xác nhận nhận hàng
        </button>
        @endif

        {{-- Hủy phiếu (bất kỳ trạng thái trừ COMPLETED/CANCELLED) --}}
        @if(!in_array((int) $receipt->status, [4, 5]))
        <form method="POST" action="{{ route('receipts.cancel', $receipt) }}"
            onsubmit="return confirm('Hủy phiếu {{ $receipt->code }}?\nThao tác này không thể khôi phục.')">
            @csrf
            <button type="submit" class="btn btn-outline-danger">
                <svg class="icon me-1">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-x-circle') }}"></use>
                </svg>
                Hủy phiếu
            </button>
        </form>
        @endif

        <a href="{{ route('receipts.index') }}" class="btn btn-outline-secondary">
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
            $done = $receipt->status >= $step && $receipt->status !== 5;
            $current = $receipt->status === $step;
            $color = $done ? 'success' : 'secondary';
            $bgClass = 'bg-' . $color . ($current ? '' : '-subtle');
            $textClass = 'text-' . $color . ($current ? ' text-white' : '');
            $borderClass = 'border-' . $color;
            $fwClass = $current ? 'fw-semibold' : 'fw-normal';
            $lineClass = $receipt->status > $step ? 'border-success' : 'border-secondary';
            @endphp
            <div class="d-flex flex-column align-items-center flex-fill">
                {{-- style chỉ chứa giá trị tĩnh → CSS linter không báo lỗi --}}
                <div class="rounded-circle d-flex align-items-center justify-content-center mb-1 border border-2 {{ $bgClass }} {{ $textClass }} {{ $borderClass }}"
                    style="width:32px;height:32px;font-size:13px">
                    {{ $step }}
                </div>
                <small class="{{ $textClass }} {{ $fwClass }}">{{ $label }}</small>
            </div>
            @if($step < 4) {{-- max-width tĩnh, màu dùng Bootstrap class → không có {{ }} trong style --}} <div
                class="flex-fill border-top border-2 mt-2 mb-auto {{ $lineClass }}" style="max-width:60px">
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
                    <dd class="col-sm-7 fw-semibold">{{ $receipt->code }}</dd>

                    <dt class="col-sm-5 text-body-secondary">Loại nhập</dt>
                    <dd class="col-sm-7">{{ $typeLabels[$receipt->receipt_type] ?? '—' }}</dd>

                    <dt class="col-sm-5 text-body-secondary">Nhà cung cấp</dt>
                    <dd class="col-sm-7">{{ $receipt->supplier?->name ?? '—' }}</dd>

                    <dt class="col-sm-5 text-body-secondary">Số tham chiếu</dt>
                    <dd class="col-sm-7">{{ $receipt->reference_no ?? '—' }}</dd>

                    <dt class="col-sm-5 text-body-secondary">Ngày nhập</dt>
                    <dd class="col-sm-7">
                        {{ $receipt->receipt_date ? \Carbon\Carbon::parse($receipt->receipt_date)->format('d/m/Y') : '—' }}
                    </dd>

                    <dt class="col-sm-5 text-body-secondary">Trạng thái</dt>
                    <dd class="col-sm-7">
                        <span
                            class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}-emphasis border border-{{ $statusColor }}-subtle rounded-pill">
                            {{ $statusText }}
                        </span>
                    </dd>

                    <dt class="col-sm-5 text-body-secondary">Người tạo</dt>
                    <dd class="col-sm-7">{{ $receipt->createdBy?->name ?? '—' }}</dd>

                    @if($receipt->confirmedBy)
                    <dt class="col-sm-5 text-body-secondary">Người duyệt</dt>
                    <dd class="col-sm-7">{{ $receipt->confirmedBy->name }}</dd>
                    @endif

                    @if($receipt->note)
                    <dt class="col-sm-5 text-body-secondary">Ghi chú</dt>
                    <dd class="col-sm-7">{{ $receipt->note }}</dd>
                    @endif
                </dl>
            </div>
        </div>

        @if($receipt->status === 4)
        <div class="card mt-4 border-success">
            <div class="card-body text-success d-flex align-items-center gap-2">
                <svg class="icon icon-xl">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use>
                </svg>
                <div>
                    <div class="fw-semibold small">Tồn kho đã được cập nhật</div>
                    <div class="text-body-secondary small">Hàng hóa đã nhập vào kho thành công.</div>
                </div>
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
            <span class="badge bg-primary-subtle text-primary-emphasis">{{ $receipt->details->count() }} dòng</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:36px">#</th>
                            <th>Hàng hóa</th>
                            <th>ĐVT</th>
                            <th class="text-end">SL dự kiến</th>
                            <th class="text-end">SL thực nhận</th>
                            <th>Vị trí kho</th>
                            <th>Lot / Batch</th>
                            <th>Hạn dùng</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($receipt->details as $i => $detail)
                        <tr>
                            <td class="text-center text-body-secondary small">{{ $i + 1 }}</td>
                            <td>
                                <div class="fw-semibold small">{{ $detail->product?->name ?? '—' }}</div>
                                <div class="text-body-secondary small">{{ $detail->product?->code }}</div>
                            </td>
                            <td class="text-body-secondary small">{{ $detail->uom?->name ?? '—' }}</td>
                            <td class="text-end fw-semibold">{{ number_format($detail->expected_qty, 3) }}</td>
                            <td class="text-end">
                                @if($detail->actual_qty !== null)
                                <span
                                    class="fw-semibold {{ $detail->actual_qty < $detail->expected_qty ? 'text-warning' : 'text-success' }}">
                                    {{ number_format($detail->actual_qty, 3) }}
                                </span>
                                @else
                                <span class="text-body-secondary">—</span>
                                @endif
                            </td>
                            <td>
                                @if($detail->location)
                                <span
                                    class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle">
                                    {{ $detail->location->code }}
                                </span>
                                @else
                                <span class="text-body-secondary small">—</span>
                                @endif
                            </td>
                            <td class="text-body-secondary small">{{ $detail->lot?->lot_number ?? '—' }}</td>
                            <td class="small">
                                @if($detail->expiry_date)
                                @php
                                $expiry = \Carbon\Carbon::parse($detail->expiry_date);
                                $daysLeft = now()->diffInDays($expiry, false);
                                @endphp
                                <span
                                    class="{{ $daysLeft < 30 ? 'text-danger fw-semibold' : ($daysLeft < 90 ? 'text-warning' : '') }}">
                                    {{ $expiry->format('d/m/Y') }}
                                </span>
                                @else
                                <span class="text-body-secondary">—</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-body-secondary py-4">Không có dòng chi tiết.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($receipt->details->count())
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="3" class="text-end fw-semibold small text-body-secondary">Tổng:</td>
                            <td class="text-end fw-bold">
                                {{ number_format($receipt->details->sum('expected_qty'), 3) }}</td>
                            <td class="text-end fw-bold">
                                {{ number_format($receipt->details->sum('actual_qty'), 3) }}</td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>

</div>

{{-- MODAL XÁC NHẬN NHẬN HÀNG (chỉ hiện khi APPROVED) --}}
@if((int) $receipt->status === 3)
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-success">
                    <svg class="icon me-1">
                        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use>
                    </svg>
                    Xác nhận nhận hàng
                </h5>
                <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Xác nhận đã nhận đủ hàng theo phiếu <strong>{{ $receipt->code }}</strong>?</p>
                <div class="alert alert-info small mb-0">
                    <svg class="icon me-1">
                        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-info') }}"></use>
                    </svg>
                    Sau khi xác nhận, tồn kho sẽ được cập nhật và không thể hoàn tác.
                    Nếu số lượng thực nhận khác dự kiến, vui lòng cập nhật
                    <strong>SL thực nhận</strong> trước khi xác nhận.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-coreui-dismiss="modal">Hủy bỏ</button>
                <form method="POST" action="{{ route('receipts.confirm', $receipt) }}">
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