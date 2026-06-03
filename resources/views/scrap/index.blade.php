@extends('layouts.app')
@section('title', 'Hủy hàng — Warehouse System')

@section('breadcrumb')
<li class="breadcrumb-item">Nghiệp vụ kho</li>
<li class="breadcrumb-item active">Hủy hàng</li>
@endsection

@section('content')

<div class="row g-3 mb-4">
    @foreach([
    ['label'=>'Tổng phiếu','value'=>$totalCount,'color'=>'primary','icon'=>'cil-trash'],
    ['label'=>'Chờ duyệt','value'=>$pendingCount,'color'=>'warning','icon'=>'cil-clock'],
    ['label'=>'Hoàn thành','value'=>$completedCount,'color'=>'success','icon'=>'cil-check-circle'],
    ['label'=>'Đã hủy','value'=>$cancelledCount,'color'=>'danger','icon'=>'cil-x-circle'],
    ] as $card)
    <div class="col-6 col-md-3">
        <div class="card border-0 bg-{{ $card['color'] }}-subtle">
            <div class="card-body d-flex align-items-center gap-3">
                <svg class="icon icon-xl text-{{ $card['color'] }}">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#'.$card['icon']) }}"></use>
                </svg>
                <div>
                    <div class="fs-5 fw-bold">{{ $card['value'] }}</div>
                    <div class="small text-body-secondary">{{ $card['label'] }}</div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Tìm mã phiếu..."
                    value="{{ request('search') }}">
            </div>
            <div class="col-6 col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">— Trạng thái —</option>
                    <option value="1" @selected(request('status')==1)>Nháp</option>
                    <option value="2" @selected(request('status')==2)>Chờ duyệt</option>
                    <option value="4" @selected(request('status')==4)>Hoàn thành</option>
                    <option value="5" @selected(request('status')==5)>Đã hủy</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <input type="date" name="date_from" class="form-control form-control-sm"
                    value="{{ request('date_from') }}">
            </div>
            <div class="col-6 col-md-2">
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
            </div>
            <div class="col-6 col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-fill">
                    <svg class="icon">
                        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-search') }}"></use>
                    </svg>
                </button>
                <a href="{{ route('scraps.index') }}" class="btn btn-outline-secondary btn-sm flex-fill">
                    <svg class="icon">
                        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-x') }}"></use>
                    </svg>
                </a>
            </div>
        </form>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible mb-3">{{ session('success') }}<button type="button" class="btn-close"
        data-coreui-dismiss="alert"></button></div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible mb-3">{{ session('error') }}<button type="button" class="btn-close"
        data-coreui-dismiss="alert"></button></div>
@endif

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center fw-semibold">
        <span>
            <svg class="icon me-1 text-danger">
                <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use>
            </svg>
            Danh sách phiếu hủy hàng
        </span>
        <a href="{{ route('scraps.create') }}" class="btn btn-primary btn-sm">
            <svg class="icon me-1">
                <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use>
            </svg>
            Tạo phiếu hủy
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Mã phiếu</th>
                        <th>Ngày hủy</th>
                        <th class="text-center">Số dòng</th>
                        <th>Trạng thái</th>
                        <th>Người tạo</th>
                        <th class="text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($scraps as $sc)
                    @php
                    $sMap = [1=>['Nháp','secondary'],2=>['Chờ duyệt','warning'],3=>['Đã duyệt','info'],4=>['Hoàn
                    thành','success'],5=>['Đã hủy','danger']];
                    [$sLabel,$sColor] = $sMap[$sc->status] ?? ['?','secondary'];
                    @endphp
                    <tr>
                        <td><a href="{{ route('scraps.show', $sc) }}"
                                class="fw-semibold text-decoration-none">{{ $sc->code }}</a></td>
                        <td>{{ $sc->scrap_date?->format('d/m/Y') }}</td>
                        <td class="text-center">{{ $sc->details_count }}</td>
                        <td>
                            <span
                                class="badge bg-{{ $sColor }}-subtle text-{{ $sColor }}-emphasis border border-{{ $sColor }}-subtle rounded-pill">{{ $sLabel }}</span>
                        </td>
                        <td>{{ $sc->createdBy?->name ?? '—' }}</td>
                        <td class="text-center">
                            <a href="{{ route('scraps.show', $sc) }}" class="btn btn-outline-secondary btn-sm px-2">
                                <svg class="icon">
                                    <use
                                        xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-magnifying-glass') }}">
                                    </use>
                                </svg>
                            </a>
                            @if($sc->status === 1)
                            <a href="{{ route('scraps.edit', $sc) }}" class="btn btn-outline-warning btn-sm px-2">
                                <svg class="icon">
                                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-pencil') }}">
                                    </use>
                                </svg>
                            </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-body-secondary py-4">Không có phiếu nào.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($scraps->hasPages())
    <div class="card-footer">{{ $scraps->links() }}</div>
    @endif
</div>

@endsection
