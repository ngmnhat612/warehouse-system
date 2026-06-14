@extends('layouts.app')

@section('title', 'Phiếu hủy hàng ' . $scrap->code . ' — Warehouse System')

@section('breadcrumb')
<li class="breadcrumb-item">Nghiệp vụ kho</li>
<li class="breadcrumb-item"><a href="{{ route('scraps.index') }}">Hủy hàng</a></li>
<li class="breadcrumb-item active">{{ $scrap->code }}</li>
@endsection

@section('content')

@php
// 1=Nháp, 2=Chờ duyệt, 3=Đã duyệt, 4=Hoàn thành, 5=Đã hủy
$statusMap = [
    1 => ['Nháp',       'secondary', 'cil-pencil'],
    2 => ['Chờ duyệt',  'warning',   'cil-clock'],
    3 => ['Đã duyệt',   'info',      'cil-check'],
    4 => ['Hoàn thành', 'success',   'cil-check-circle'],
    5 => ['Đã hủy',     'danger',    'cil-x-circle'],
];
$s = (int) $scrap->status;
[$sText, $sColor, $sIcon] = $statusMap[$s] ?? ['?', 'secondary', 'cil-info'];
@endphp

{{-- HEADER --}}
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="mb-1 fw-semibold d-flex align-items-center gap-2">
            {{ $scrap->code }}
            <span class="badge bg-{{ $sColor }}-subtle text-{{ $sColor }}-emphasis border border-{{ $sColor }}-subtle rounded-pill fs-6">
                <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#' . $sIcon) }}"></use></svg>
                {{ $sText }}
            </span>
        </h4>
        <small class="text-body-secondary">
            Tạo lúc {{ $scrap->created_at?->format('d/m/Y H:i') }}
            @if($scrap->createdBy) bởi <strong>{{ $scrap->createdBy->name }}</strong>@endif
        </small>
    </div>

    <div class="d-flex gap-2 flex-wrap">

        {{-- NHÁP (1): Chỉnh sửa + Gửi duyệt + Xóa --}}
        @if($s === 1)
        <a href="{{ route('scraps.edit', $scrap) }}" class="btn btn-outline-secondary btn-sm">
            <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-pencil') }}"></use></svg>
            Chỉnh sửa
        </a>
        <form method="POST" action="{{ route('scraps.submit', $scrap) }}">
            @csrf
            <button type="submit" class="btn btn-warning btn-sm">
                <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-send') }}"></use></svg>
                Gửi duyệt
            </button>
        </form>
        <form method="POST" action="{{ route('scraps.destroy', $scrap) }}"
            onsubmit="return confirm('Xóa vĩnh viễn phiếu {{ $scrap->code }}?')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-sm">
                <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use></svg>
                Xóa
            </button>
        </form>
        @endif

        {{-- CHỜ DUYỆT (2): Duyệt phiếu --}}
        @if($s === 2)
        <form method="POST" action="{{ route('scraps.approve', $scrap) }}">
            @csrf
            <button type="submit" class="btn btn-primary btn-sm">
                <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check') }}"></use></svg>
                Duyệt phiếu
            </button>
        </form>
        @endif

        {{-- ĐÃ DUYỆT (3): Xác nhận hủy hàng (trừ kho) --}}
        @if($s === 3)
        <button type="button" class="btn btn-danger btn-sm"
            data-coreui-toggle="modal" data-coreui-target="#confirmModal">
            <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use></svg>
            Xác nhận hủy hàng
        </button>
        @endif

        {{-- HOÀN THÀNH (4): In phiếu --}}
        @if($s === 4)
        <a href="{{ route('scraps.print', $scrap) }}" target="_blank" class="btn btn-outline-primary btn-sm">
            <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-print') }}"></use></svg>
            Xuất PDF
        </a>
        @endif

        {{-- HỦY PHIẾU: còn trong luồng (1, 2, 3) --}}
        @if(!in_array($s, [4, 5]))
        <form method="POST" action="{{ route('scraps.cancel', $scrap) }}"
            onsubmit="return confirm('Hủy phiếu {{ $scrap->code }}?\nThao tác này không thể khôi phục.')">
            @csrf
            <button type="submit" class="btn btn-outline-danger btn-sm">
                <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-x-circle') }}"></use></svg>
                Hủy phiếu
            </button>
        </form>
        @endif

        <a href="{{ route('scraps.index') }}" class="btn btn-outline-secondary btn-sm">
            <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-left') }}"></use></svg>
            Quay lại
        </a>
    </div>
</div>

{{-- ALERTS --}}
@if(session('success'))
<div class="alert alert-success alert-dismissible mb-3">
    <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check') }}"></use></svg>
    {{ session('success') }}
    <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible mb-3">
    <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use></svg>
    {{ session('error') }}
    <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
</div>
@endif

{{-- TIMELINE --}}
<div class="card mb-3">
    <div class="card-body py-3">
        @if($s === 5)
        <div class="d-flex align-items-center gap-2 text-danger">
            <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-x-circle') }}"></use></svg>
            <span class="fw-semibold">Phiếu đã bị hủy</span>
        </div>
        @else
        @php $steps = [1 => 'Nháp', 2 => 'Chờ duyệt', 3 => 'Đã duyệt', 4 => 'Hoàn thành']; @endphp
        <div class="d-flex justify-content-between align-items-center">
            @foreach($steps as $step => $label)
            @php
            $done    = $s >= $step;
            $current = $s === $step;
            $lineOk  = $s > $step;
            @endphp
            <div class="d-flex flex-column align-items-center flex-fill">
                <div class="rounded-circle d-flex align-items-center justify-content-center mb-1 border border-2
                    {{ $done ? 'bg-success border-success text-white' : 'bg-body border-secondary text-secondary' }}"
                    style="width:32px;height:32px;font-size:13px;font-weight:600">
                    {{ $step }}
                </div>
                <small class="{{ $current ? 'fw-semibold text-success' : ($done ? 'text-success' : 'text-secondary') }}">
                    {{ $label }}
                </small>
            </div>
            @if($step < 4)
            <div class="flex-fill border-top border-2 mt-2 mb-auto {{ $lineOk ? 'border-success' : 'border-secondary opacity-25' }}"
                style="max-width:60px"></div>
            @endif
            @endforeach
        </div>
        @endif
    </div>
</div>

{{-- THÔNG TIN PHIẾU --}}
<div class="card mb-3">
    <div class="card-header fw-semibold py-2">
        <svg class="icon me-1 text-primary">
            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-description') }}"></use>
        </svg>
        Thông tin phiếu
    </div>
    <div class="card-body py-3">
        <div class="row g-3 small">
            <div class="col-md-2">
                <div class="text-body-secondary mb-1">Mã phiếu</div>
                <div class="fw-semibold">{{ $scrap->code }}</div>
            </div>
            <div class="col-md-2">
                <div class="text-body-secondary mb-1">Ngày hủy</div>
                <div>{{ $scrap->scrap_date ? \Carbon\Carbon::parse($scrap->scrap_date)->format('d/m/Y') : '—' }}</div>
            </div>
            <div class="col-md-2">
                <div class="text-body-secondary mb-1">Trạng thái</div>
                <span class="badge bg-{{ $sColor }}-subtle text-{{ $sColor }}-emphasis border border-{{ $sColor }}-subtle rounded-pill">
                    {{ $sText }}
                </span>
            </div>
            <div class="col-md-2">
                <div class="text-body-secondary mb-1">Người tạo</div>
                <div>{{ $scrap->createdBy?->name ?? '—' }}</div>
            </div>
            @if($scrap->approvedBy)
            <div class="col-md-2">
                <div class="text-body-secondary mb-1">Người duyệt</div>
                <div>{{ $scrap->approvedBy->name }}</div>
            </div>
            @endif
            @if($scrap->confirmed_at ?? $scrap->approved_at)
            <div class="col-md-2">
                <div class="text-body-secondary mb-1">Ngày duyệt</div>
                <div>{{ \Carbon\Carbon::parse($scrap->confirmed_at ?? $scrap->approved_at)->format('d/m/Y H:i') }}</div>
            </div>
            @endif
            @if($scrap->note)
            <div class="col-12">
                <div class="text-body-secondary mb-1">Ghi chú</div>
                <div>{{ $scrap->note }}</div>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- CHI TIẾT HÀNG HÓA --}}
@php
$hasLot    = $scrap->details->contains(fn($d) => in_array((int)($d->product?->tracking_type ?? 1), [2, 4]));
$hasSerial = $scrap->details->contains(fn($d) => in_array((int)($d->product?->tracking_type ?? 1), [3, 4]));
@endphp
<div class="card">
    <div class="card-header fw-semibold d-flex justify-content-between align-items-center py-2">
        <span>
            <svg class="icon me-1 text-danger">
                <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-list') }}"></use>
            </svg>
            Danh sách hàng hóa hủy
        </span>
        <span class="badge bg-danger-subtle text-danger-emphasis">{{ $scrap->details->count() }} dòng</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:36px" class="text-center">#</th>
                        <th>Hàng hóa</th>
                        <th style="width:70px">ĐVT</th>
                        <th style="width:100px" class="text-end">Số lượng</th>
                        <th style="width:110px">Vị trí kho</th>
                        @if($hasLot)
                        <th style="width:120px">Số Lot/Batch</th>
                        @endif
                        @if($hasSerial)
                        <th style="width:120px">Số Serial</th>
                        @endif
                        <th>Lý do hủy</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($scrap->details as $i => $d)
                    <tr>
                        <td class="text-center text-body-secondary small">{{ $i + 1 }}</td>
                        <td>
                            <div class="fw-semibold small">{{ $d->product?->name ?? '—' }}</div>
                            <div class="text-body-secondary" style="font-size:11px">{{ $d->product?->code }}</div>
                        </td>
                        <td class="text-body-secondary small">{{ $d->uom?->name ?? $d->product?->uom?->name ?? '—' }}</td>
                        <td class="text-end fw-semibold small text-danger">
                            {{ number_format($d->quantity, 3) }}
                        </td>
                        <td>
                            @if($d->location)
                            <span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle">
                                {{ $d->location->code }}
                            </span>
                            @else
                            <span class="text-body-secondary small">—</span>
                            @endif
                        </td>
                        @if($hasLot)
                        <td class="small">
                            @if($d->lot)
                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle font-monospace">
                                {{ $d->lot->lot_number }}
                            </span>
                            @else
                            <span class="text-body-secondary">—</span>
                            @endif
                        </td>
                        @endif
                        @if($hasSerial)
                        <td class="small">
                            @if($d->serial)
                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle font-monospace">
                                {{ $d->serial->serial_number }}
                            </span>
                            @else
                            <span class="text-body-secondary">—</span>
                            @endif
                        </td>
                        @endif
                        <td class="small text-body-secondary">{{ $d->reason ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ 5 + ($hasLot ? 1 : 0) + ($hasSerial ? 1 : 0) + 1 }}"
                            class="text-center text-body-secondary py-4">Không có dòng chi tiết.</td>
                    </tr>
                    @endforelse
                </tbody>
                @if($scrap->details->count())
                <tfoot class="table-light">
                    <tr>
                        <td colspan="3" class="small text-body-secondary">{{ $scrap->details->count() }} dòng</td>
                        <td class="text-end fw-semibold small text-danger">
                            {{ number_format($scrap->details->sum('quantity'), 3) }}
                        </td>
                        <td colspan="{{ 1 + ($hasLot ? 1 : 0) + ($hasSerial ? 1 : 0) + 1 }}"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    @if($s === 4)
    <div class="card-footer border-success bg-success-subtle text-success d-flex align-items-center gap-2 py-2">
        <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use></svg>
        <span class="small fw-semibold">Đã hủy hàng thành công — tồn kho đã được trừ.</span>
    </div>
    @endif
</div>

{{-- MODAL XÁC NHẬN HỦY HÀNG (chỉ ở bước Đã duyệt = 3) --}}
@if($s === 3)
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">
                    <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use></svg>
                    Xác nhận hủy hàng
                </h5>
                <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Xác nhận hủy hàng theo phiếu <strong>{{ $scrap->code }}</strong>
                    với <strong>{{ $scrap->details->count() }} dòng</strong>?</p>
                <div class="alert alert-danger small mb-0">
                    <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use></svg>
                    Sau khi xác nhận, tồn kho sẽ bị <strong>trừ vĩnh viễn</strong>.
                    Thao tác này <strong>không thể hoàn tác</strong>.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm"
                    data-coreui-dismiss="modal">Hủy bỏ</button>
                <form method="POST" action="{{ route('scraps.confirm', $scrap) }}">
                    @csrf
                    <button type="submit" class="btn btn-danger btn-sm">
                        <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use></svg>
                        Xác nhận & trừ tồn kho
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

@endsection