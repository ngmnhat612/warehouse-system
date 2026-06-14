@extends('layouts.app')

@section('title', 'Hủy hàng — Warehouse System')

@section('breadcrumb')
<li class="breadcrumb-item">Nghiệp vụ kho</li>
<li class="breadcrumb-item active">Hủy hàng</li>
@endsection

@section('content')

{{-- HEADER --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-semibold">Hủy hàng</h4>
        <small class="text-body-secondary">Quản lý phiếu hủy hàng hóa ra khỏi kho</small>
    </div>
    <a href="{{ route('scraps.create') }}" class="btn btn-danger">
        <svg class="icon me-1">
            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-plus') }}"></use>
        </svg>
        Tạo phiếu hủy
    </a>
</div>

{{-- CARDS THỐNG KÊ --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card border-start border-start-4 border-start-danger">
            <div class="card-body d-flex align-items-center gap-3">
                <svg class="icon icon-2xl text-danger">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use>
                </svg>
                <div>
                    <div class="fs-5 fw-semibold">{{ $totalCount ?? 0 }}</div>
                    <div class="text-body-secondary small">Tổng phiếu hủy</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-start border-start-4 border-start-warning">
            <div class="card-body d-flex align-items-center gap-3">
                <svg class="icon icon-2xl text-warning">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-clock') }}"></use>
                </svg>
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
                <svg class="icon icon-2xl text-success">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use>
                </svg>
                <div>
                    <div class="fs-5 fw-semibold">{{ $completedCount ?? 0 }}</div>
                    <div class="text-body-secondary small">Hoàn thành</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-start border-start-4 border-start-secondary">
            <div class="card-body d-flex align-items-center gap-3">
                <svg class="icon icon-2xl text-secondary">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-x-circle') }}"></use>
                </svg>
                <div>
                    <div class="fs-5 fw-semibold">{{ $cancelledCount ?? 0 }}</div>
                    <div class="text-body-secondary small">Đã hủy</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ALERTS --}}
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
    <svg class="icon me-2"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use></svg>
    {{ session('success') }}
    <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
    <svg class="icon me-2"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use></svg>
    {{ session('error') }}
    <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
</div>
@endif

{{-- BẢNG DANH SÁCH --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="fw-semibold">Danh sách phiếu hủy hàng</span>
        <form method="GET" action="{{ route('scraps.index') }}" class="d-flex gap-2 flex-wrap">
            <div class="input-group" style="width:230px">
                <span class="input-group-text">
                    <svg class="icon">
                        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-search') }}"></use>
                    </svg>
                </span>
                <input type="text" class="form-control" name="search" value="{{ request('search') }}"
                    placeholder="Mã phiếu, ghi chú...">
            </div>
            <select class="form-select" name="status" style="width:160px">
                <option value="">Tất cả trạng thái</option>
                <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Nháp</option>
                <option value="2" {{ request('status') == '2' ? 'selected' : '' }}>Chờ duyệt</option>
                <option value="3" {{ request('status') == '3' ? 'selected' : '' }}>Đã duyệt</option>
                <option value="4" {{ request('status') == '4' ? 'selected' : '' }}>Hoàn thành</option>
                <option value="5" {{ request('status') == '5' ? 'selected' : '' }}>Đã hủy</option>
            </select>
            <input type="date" class="form-control" name="date_from" value="{{ request('date_from') }}"
                style="width:145px" title="Từ ngày">
            <input type="date" class="form-control" name="date_to" value="{{ request('date_to') }}"
                style="width:145px" title="Đến ngày">
            <button type="submit" class="btn btn-outline-primary">Lọc</button>
            @if(request('search') || request('status') || request('date_from') || request('date_to'))
            <a href="{{ route('scraps.index') }}" class="btn btn-outline-secondary">Xóa lọc</a>
            @endif
        </form>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width:50px">#</th>
                        <th style="width:140px">Mã phiếu</th>
                        <th style="width:110px">Ngày hủy</th>
                        <th class="text-center" style="width:80px">Số dòng</th>
                        <th>Ghi chú</th>
                        <th class="text-center" style="width:120px">Trạng thái</th>
                        <th style="width:120px">Người tạo</th>
                        <th class="text-center" style="width:100px">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($scraps as $index => $sc)
                    @php
                    $statusMap = [
                        1 => ['label' => 'Nháp',       'color' => 'secondary'],
                        2 => ['label' => 'Chờ duyệt',  'color' => 'warning'],
                        3 => ['label' => 'Đã duyệt',   'color' => 'info'],
                        4 => ['label' => 'Hoàn thành', 'color' => 'success'],
                        5 => ['label' => 'Đã hủy',     'color' => 'danger'],
                    ];
                    $st = $statusMap[$sc->status] ?? ['label' => '—', 'color' => 'secondary'];
                    @endphp
                    <tr>
                        <td class="text-center text-body-secondary">
                            {{ ($scraps->currentPage() - 1) * $scraps->perPage() + $index + 1 }}
                        </td>
                        <td>
                            <a href="{{ route('scraps.show', $sc->id) }}"
                                class="fw-medium text-primary text-decoration-none">
                                {{ $sc->code }}
                            </a>
                        </td>
                        <td class="small">
                            {{ $sc->scrap_date ? \Carbon\Carbon::parse($sc->scrap_date)->format('d/m/Y') : '—' }}
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border">{{ $sc->details_count ?? 0 }}</span>
                        </td>
                        <td class="small text-body-secondary text-truncate" style="max-width:200px"
                            title="{{ $sc->note }}">
                            {{ $sc->note ?? '—' }}
                        </td>
                        <td class="text-center">
                            <span
                                class="badge bg-{{ $st['color'] }}-subtle text-{{ $st['color'] }} border border-{{ $st['color'] }}-subtle"
                                style="font-size:11px">
                                {{ $st['label'] }}
                            </span>
                        </td>
                        <td class="small text-body-secondary">{{ $sc->createdBy?->name ?? '—' }}</td>
                        <td class="text-center">
                            <a href="{{ route('scraps.show', $sc->id) }}"
                                class="btn btn-sm btn-outline-secondary me-1" title="Xem chi tiết">
                                <svg class="icon">
                                    <use
                                        xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-magnifying-glass') }}">
                                    </use>
                                </svg>
                            </a>
                            @if($sc->status == 1)
                            <a href="{{ route('scraps.edit', $sc->id) }}"
                                class="btn btn-sm btn-outline-primary me-1" title="Chỉnh sửa">
                                <svg class="icon">
                                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-pencil') }}">
                                    </use>
                                </svg>
                            </a>
                            <button class="btn btn-sm btn-outline-danger"
                                onclick="confirmDelete({{ $sc->id }}, '{{ $sc->code }}')" title="Xóa">
                                <svg class="icon">
                                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}">
                                    </use>
                                </svg>
                            </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-body-secondary py-5">
                            <svg class="icon icon-3xl d-block mx-auto mb-2 opacity-25">
                                <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use>
                            </svg>
                            Chưa có phiếu hủy hàng nào
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($scraps->hasPages())
    <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-body-secondary">
            Hiển thị {{ $scraps->firstItem() }}–{{ $scraps->lastItem() }}
            trong tổng số {{ $scraps->total() }} phiếu
        </small>
        {{ $scraps->appends(request()->query())->links('pagination::bootstrap-5') }}
    </div>
    @endif
</div>

{{-- MODAL XÁC NHẬN XÓA --}}
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title text-danger">
                    <svg class="icon me-1">
                        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use>
                    </svg>
                    Xác nhận xóa
                </h5>
                <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Bạn có chắc muốn xóa phiếu hủy <strong id="deleteCode"></strong>?
                <div class="small text-body-secondary mt-1">Thao tác này không thể hoàn tác.</div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-coreui-dismiss="modal">Hủy</button>
                <form id="deleteForm" method="POST">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger">Xóa phiếu</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function confirmDelete(id, code) {
    document.getElementById('deleteCode').textContent = code;
    document.getElementById('deleteForm').action = `/scraps/${id}`;
    new coreui.Modal(document.getElementById('deleteModal')).show();
}
</script>
@endpush