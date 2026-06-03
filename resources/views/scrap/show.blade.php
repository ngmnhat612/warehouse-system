@extends('layouts.app')
@section('title', 'Phiếu hủy hàng '.$scrap->code.' — Warehouse System')

@section('breadcrumb')
<li class="breadcrumb-item">Nghiệp vụ kho</li>
<li class="breadcrumb-item"><a href="{{ route('scraps.index') }}">Hủy hàng</a></li>
<li class="breadcrumb-item active">{{ $scrap->code }}</li>
@endsection

@section('content')

@php
$sMap = [1=>['Nháp','secondary','cil-pencil'],2=>['Chờ duyệt','warning','cil-clock'],3=>['Đã
duyệt','info','cil-check'],4=>['Hoàn thành','success','cil-check-circle'],5=>['Đã hủy','danger','cil-x-circle']];
[$sText,$sColor,$sIcon] = $sMap[$scrap->status] ?? ['?','secondary','cil-info'];
$s = (int) $scrap->status;
@endphp

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h4 class="mb-1 fw-semibold d-flex align-items-center gap-2">
            {{ $scrap->code }}
            <span
                class="badge bg-{{ $sColor }}-subtle text-{{ $sColor }}-emphasis border border-{{ $sColor }}-subtle rounded-pill fs-6">
                <svg class="icon me-1">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#'.$sIcon) }}"></use>
                </svg>
                {{ $sText }}
            </span>
        </h4>
        <small class="text-body-secondary">Tạo lúc {{ $scrap->created_at?->format('d/m/Y H:i') }} @if($scrap->createdBy)
            bởi {{ $scrap->createdBy->name }} @endif</small>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @if($s === 1)
        <a href="{{ route('scraps.edit', $scrap) }}" class="btn btn-outline-secondary">
            <svg class="icon me-1">
                <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-pencil') }}"></use>
            </svg> Chỉnh sửa
        </a>
        <form method="POST" action="{{ route('scraps.submit', $scrap) }}">
            @csrf
            <button type="submit" class="btn btn-warning">
                <svg class="icon me-1">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-send') }}"></use>
                </svg> Gửi duyệt
            </button>
        </form>
        <form method="POST" action="{{ route('scraps.destroy', $scrap) }}"
            onsubmit="return confirm('Xóa phiếu {{ $scrap->code }}?')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-outline-danger">
                <svg class="icon me-1">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use>
                </svg> Xóa
            </button>
        </form>
        @endif
        @if($s === 2)
        <button type="button" class="btn btn-danger" data-coreui-toggle="modal" data-coreui-target="#approveModal">
            <svg class="icon me-1">
                <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check') }}"></use>
            </svg> Duyệt & Hủy hàng
        </button>
        @endif
        @if(!in_array($s, [4, 5]))
        <form method="POST" action="{{ route('scraps.cancel', $scrap) }}"
            onsubmit="return confirm('Hủy phiếu {{ $scrap->code }}?')">
            @csrf
            <button type="submit" class="btn btn-outline-danger">
                <svg class="icon me-1">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-x-circle') }}"></use>
                </svg> Hủy phiếu
            </button>
        </form>
        @endif
        <a href="{{ route('scraps.index') }}" class="btn btn-outline-secondary">
            <svg class="icon me-1">
                <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-left') }}"></use>
            </svg> Quay lại
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible mb-4">{{ session('success') }}<button type="button" class="btn-close"
        data-coreui-dismiss="alert"></button></div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible mb-4">{{ session('error') }}<button type="button" class="btn-close"
        data-coreui-dismiss="alert"></button></div>
@endif

<div class="row g-4">
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
                    <dd class="col-sm-7 fw-semibold">{{ $scrap->code }}</dd>
                    <dt class="col-sm-5 text-body-secondary">Ngày hủy</dt>
                    <dd class="col-sm-7">{{ $scrap->scrap_date?->format('d/m/Y') }}</dd>
                    <dt class="col-sm-5 text-body-secondary">Trạng thái</dt>
                    <dd class="col-sm-7">
                        <span
                            class="badge bg-{{ $sColor }}-subtle text-{{ $sColor }}-emphasis border border-{{ $sColor }}-subtle rounded-pill">{{ $sText }}</span>
                    </dd>
                    <dt class="col-sm-5 text-body-secondary">Người tạo</dt>
                    <dd class="col-sm-7">{{ $scrap->createdBy?->name ?? '—' }}</dd>
                    @if($scrap->approvedBy)
                    <dt class="col-sm-5 text-body-secondary">Người duyệt</dt>
                    <dd class="col-sm-7">{{ $scrap->approvedBy->name }}</dd>
                    @endif
                    @if($scrap->note)
                    <dt class="col-sm-5 text-body-secondary">Ghi chú</dt>
                    <dd class="col-sm-7">{{ $scrap->note }}</dd>
                    @endif
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header fw-semibold">
                <svg class="icon me-1 text-primary">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-list') }}"></use>
                </svg>
                Danh sách hàng hóa hủy ({{ $scrap->details->count() }} dòng)
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Hàng hóa</th>
                                <th>Vị trí kho</th>
                                <th class="text-end">Số lượng</th>
                                <th>ĐVT</th>
                                <th>Lô</th>
                                <th>Lý do hủy</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($scrap->details as $i => $d)
                            <tr>
                                <td class="text-body-secondary">{{ $i + 1 }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $d->product?->name }}</div>
                                    <small class="text-body-secondary">{{ $d->product?->code }}</small>
                                </td>
                                <td><span
                                        class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle rounded-pill">{{ $d->location?->code ?? '—' }}</span>
                                </td>
                                <td class="text-end fw-semibold text-danger">{{ number_format($d->quantity, 3) }}</td>
                                <td>{{ $d->uom?->name ?? '—' }}</td>
                                <td>{{ $d->lot?->lot_number ?? '—' }}</td>
                                <td class="text-body-secondary">{{ $d->reason ?? '—' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-body-secondary py-3">Không có hàng hóa.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@if($s === 2)
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold">
                    <svg class="icon me-1 text-danger">
                        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use>
                    </svg>
                    Xác nhận duyệt phiếu hủy hàng
                </h5>
                <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc muốn duyệt phiếu <strong>{{ $scrap->code }}</strong>?</p>
                <div class="alert alert-danger d-flex gap-2 align-items-center mb-0">
                    <svg class="icon flex-shrink-0">
                        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use>
                    </svg>
                    <span>Thao tác này sẽ <strong>trừ vĩnh viễn</strong> tồn kho các mặt hàng trong phiếu và chuyển sang
                        kho ảo [SCRAP]. Không thể hoàn tác.</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-coreui-dismiss="modal">Hủy bỏ</button>
                <form method="POST" action="{{ route('scraps.approve', $scrap) }}">
                    @csrf
                    <button type="submit" class="btn btn-danger">
                        <svg class="icon me-1">
                            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check') }}"></use>
                        </svg>
                        Xác nhận hủy hàng
                    </button>
                    <a href="{{ route('scraps.print', $scrap) }}" target="_blank"
                        class="btn btn-outline-secondary btn-sm">
                        <svg class="icon me-1">
                            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-print') }}"></use>
                        </svg>
                        In phiếu
                    </a>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

@endsection