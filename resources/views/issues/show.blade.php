@extends('layouts.app')

@section('title', 'Phiếu xuất ' . $issue->code . ' — Warehouse System')

@section('breadcrumb')
<li class="breadcrumb-item">Nghiệp vụ kho</li>
<li class="breadcrumb-item"><a href="{{ route('issues.index') }}">Xuất kho</a></li>
<li class="breadcrumb-item active">{{ $issue->code }}</li>
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
$issueStatus = (int) $issue->status;
[$statusText, $statusColor, $statusIcon] = $statusMap[$issueStatus] ?? ['?', 'secondary', 'cil-info'];
$typeLabels = [1 => 'Sản xuất', 2 => 'Bảo trì', 3 => 'Mượn', 4 => 'Khác'];
@endphp

{{-- HEADER --}}
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h4 class="mb-1 fw-semibold d-flex align-items-center gap-2">
            {{ $issue->code }}
            <span
                class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}-emphasis border border-{{ $statusColor }}-subtle rounded-pill fs-6">
                <svg class="icon me-1">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#' . $statusIcon) }}"></use>
                </svg>
                {{ $statusText }}
            </span>
        </h4>
        <small class="text-body-secondary">
            Tạo lúc {{ $issue->created_at?->format('d/m/Y H:i') }}
            @if($issue->creator) bởi {{ $issue->creator->name }} @endif
        </small>
    </div>
    <div class="d-flex gap-2 flex-wrap">

        {{-- DRAFT: Sửa / Gửi duyệt / Xóa --}}
        @if($issueStatus === 1)
        <a href="{{ route('issues.edit', $issue) }}" class="btn btn-outline-secondary">
            <svg class="icon me-1">
                <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-pencil') }}"></use>
            </svg>
            Chỉnh sửa
        </a>
        <form method="POST" action="{{ route('issues.submit', $issue) }}">
            @csrf
            <button type="submit" class="btn btn-warning">
                <svg class="icon me-1">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-send') }}"></use>
                </svg>
                Gửi duyệt
            </button>
        </form>
        <form method="POST" action="{{ route('issues.destroy', $issue) }}"
            onsubmit="return confirm('Xóa vĩnh viễn phiếu {{ $issue->code }}?')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-outline-danger">
                <svg class="icon me-1">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use>
                </svg>
                Xóa
            </button>
        </form>
        @endif

        {{-- PENDING: Duyệt & giữ hàng --}}
        @if($issueStatus === 2)
        <button type="button" class="btn btn-primary" data-coreui-toggle="modal" data-coreui-target="#approveModal">
            <svg class="icon me-1">
                <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check') }}"></use>
            </svg>
            Duyệt & Giữ hàng
        </button>
        @endif

        {{-- APPROVED: Xuất kho thực tế --}}
        @if($issueStatus === 3)
        <button type="button" class="btn btn-success" data-coreui-toggle="modal" data-coreui-target="#confirmModal">
            <svg class="icon me-1">
                <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use>
            </svg>
            Xuất kho thực tế
        </button>
        @endif

        {{-- HỦY: hiện khi chưa Hoàn thành / Đã hủy --}}
        @if(!in_array($issueStatus, [4, 5]))
        @php $cancelExtra = ($issueStatus === 3) ? '\nHàng đang giữ sẽ được giải phóng.' : ''; @endphp
        <form method="POST" action="{{ route('issues.cancel', $issue) }}"
            onsubmit="return confirm('Hủy phiếu {{ $issue->code }}?{{ $cancelExtra }}')">
            @csrf
            <button type="submit" class="btn btn-outline-danger">
                <svg class="icon me-1">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-x-circle') }}"></use>
                </svg>
                Hủy phiếu
            </button>
        </form>
        @endif

        <a href="{{ route('issues.index') }}" class="btn btn-outline-secondary">
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
    {{ session('success') }}
    <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible mb-4">
    {{ session('error') }}
    <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
</div>
@endif

{{-- CẢNH BÁO KHI ĐÃ APPROVED --}}
@if($issueStatus === 3)
<div class="alert alert-info mb-4 d-flex align-items-center gap-2">
    <svg class="icon icon-xl flex-shrink-0">
        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-lock-locked') }}"></use>
    </svg>
    <div>
        <strong>Hàng đang được giữ chỗ.</strong>
        Tồn kho khả dụng đã bị khóa cho phiếu này. Bấm <strong>"Xuất kho thực tế"</strong> để trừ kho hoặc
        <strong>"Hủy phiếu"</strong> để giải phóng.
    </div>
</div>
@endif

<div class="row g-4">

    {{-- CỘT TRÁI --}}
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
                    <dd class="col-sm-7 fw-semibold">{{ $issue->code }}</dd>

                    <dt class="col-sm-5 text-body-secondary">Loại xuất</dt>
                    <dd class="col-sm-7">{{ $typeLabels[$issue->issue_type] ?? '—' }}</dd>

                    <dt class="col-sm-5 text-body-secondary">Ngày xuất</dt>
                    <dd class="col-sm-7">
                        {{ $issue->issue_date ? \Carbon\Carbon::parse($issue->issue_date)->format('d/m/Y') : '—' }}
                    </dd>

                    @if($issue->expected_return_date)
                    <dt class="col-sm-5 text-body-secondary">Hạn trả</dt>
                    <dd class="col-sm-7 text-warning fw-semibold">
                        {{ \Carbon\Carbon::parse($issue->expected_return_date)->format('d/m/Y') }}
                    </dd>
                    @endif

                    <dt class="col-sm-5 text-body-secondary">Người y/c</dt>
                    <dd class="col-sm-7">{{ $issue->requester?->name ?? '—' }}</dd>

                    <dt class="col-sm-5 text-body-secondary">Số tham chiếu</dt>
                    <dd class="col-sm-7">{{ $issue->reference_no ?? '—' }}</dd>

                    <dt class="col-sm-5 text-body-secondary">Trạng thái</dt>
                    <dd class="col-sm-7">
                        <span
                            class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}-emphasis border border-{{ $statusColor }}-subtle rounded-pill">
                            {{ $statusText }}
                        </span>
                    </dd>

                    <dt class="col-sm-5 text-body-secondary">Người tạo</dt>
                    <dd class="col-sm-7">{{ $issue->creator?->name ?? '—' }}</dd>

                    @if($issue->confirmer)
                    <dt class="col-sm-5 text-body-secondary">Người duyệt</dt>
                    <dd class="col-sm-7">{{ $issue->confirmer->name }}</dd>
                    @endif

                    @if($issue->note)
                    <dt class="col-sm-5 text-body-secondary">Ghi chú</dt>
                    <dd class="col-sm-7">{{ $issue->note }}</dd>
                    @endif
                </dl>
            </div>
        </div>
    </div>

    {{-- CỘT PHẢI: Chi tiết + Gợi ý Lot/Serial --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
                <span>
                    <svg class="icon me-1 text-primary">
                        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-list') }}"></use>
                    </svg>
                    Chi tiết hàng hóa
                </span>
                <span class="badge bg-primary-subtle text-primary-emphasis">{{ $issue->details->count() }} dòng</span>
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
                                <th>Vị trí kho</th>
                                <th>Lot / Batch</th>
                                <th>Ghi chú</th>
                                @if(in_array($issueStatus, [1, 2, 3]))
                                <th style="width:90px">Gợi ý</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($issue->details as $i => $detail)
                            <tr>
                                <td class="text-center text-body-secondary small">{{ $i + 1 }}</td>
                                <td>
                                    <div class="fw-semibold small">{{ $detail->product?->name ?? '—' }}</div>
                                    <div class="text-body-secondary small">{{ $detail->product?->code }}</div>
                                </td>
                                <td class="text-body-secondary small">{{ $detail->uom?->name ?? '—' }}</td>
                                <td class="text-end fw-semibold">{{ number_format($detail->quantity, 3) }}</td>
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
                                <td class="text-body-secondary small">{{ $detail->note ?? '—' }}</td>
                                @if(in_array($issueStatus, [1, 2, 3]))
                                <td>
                                    @if($suggestions[$detail->id]?->isNotEmpty())
                                    {{-- [SỬA 2] Dùng data-id thay vì {{ }} trong onclick --}}
                                    <button type="button" class="btn btn-sm btn-outline-info"
                                        data-id="{{ $detail->id }}" onclick="showSuggestion(this.dataset.id)"
                                        title="Xem gợi ý Lot/Serial">
                                        <svg class="icon">
                                            <use
                                                xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-lightbulb') }}">
                                            </use>
                                        </svg>
                                    </button>
                                    @else
                                    <span class="text-danger small" title="Không đủ tồn kho">
                                        <svg class="icon">
                                            <use
                                                xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}">
                                            </use>
                                        </svg>
                                    </span>
                                    @endif
                                </td>
                                @endif
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center text-body-secondary py-4">Không có dòng chi tiết.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if($issue->details->count())
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="3" class="text-end fw-semibold small text-body-secondary">Tổng SL:</td>
                                <td class="text-end fw-bold">{{ number_format($issue->details->sum('quantity'), 3) }}
                                </td>
                                <td colspan="{{ in_array($issue->status, [1,2,3]) ? 4 : 3 }}"></td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        {{-- Bảng gợi ý Lot/Serial (ẩn, hiện khi bấm nút) --}}
        @if(in_array($issueStatus, [1, 2, 3]))
        @foreach($issue->details as $detail)
        @if($suggestions[$detail->id]?->isNotEmpty())
        <div id="suggestion-{{ $detail->id }}" class="card mt-3 border-info d-none">
            <div class="card-header bg-info-subtle text-info-emphasis fw-semibold small d-flex justify-content-between">
                <span>
                    <svg class="icon me-1">
                        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-lightbulb') }}"></use>
                    </svg>
                    Gợi ý {{ $detail->product?->stock_rotation === 2 ? 'FEFO' : 'FIFO' }} —
                    {{ $detail->product?->name }}
                </span>
                {{-- [SỬA 3] Dùng data-id thay vì {{ }} trong onclick --}}
                <button type="button" class="btn-close btn-close-sm" data-id="{{ $detail->id }}"
                    onclick="hideSuggestion(this.dataset.id)"></button>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Vị trí</th>
                            <th>Lot</th>
                            <th class="text-end">SL gợi ý</th>
                            <th>Hạn dùng</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($suggestions[$detail->id] as $s)
                        <tr>
                            <td class="small">
                                <span class="badge bg-secondary-subtle text-secondary-emphasis border">
                                    {{ \App\Models\Location::find($s['location_id'])?->code ?? $s['location_id'] }}
                                </span>
                            </td>
                            <td class="small text-body-secondary">
                                {{ $s['lot_id'] ? (\App\Models\Lot::find($s['lot_id'])?->lot_number ?? '—') : '—' }}
                            </td>
                            <td class="text-end fw-semibold">{{ number_format($s['qty_suggest'], 3) }}</td>
                            <td
                                class="small {{ $s['expiry_date'] && \Carbon\Carbon::parse($s['expiry_date'])->diffInDays(now(), false) > 0 ? 'text-danger' : '' }}">
                                {{ $s['expiry_date'] ? \Carbon\Carbon::parse($s['expiry_date'])->format('d/m/Y') : '—' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
        @endforeach
        @endif
    </div>

</div>

{{-- MODAL DUYỆT PHIẾU (PENDING → APPROVED) --}}
@if($issueStatus === 2)
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-primary">
                    <svg class="icon me-1">
                        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check') }}"></use>
                    </svg>
                    Duyệt phiếu xuất
                </h5>
                <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Duyệt phiếu <strong>{{ $issue->code }}</strong> và giữ chỗ hàng trong kho?</p>
                <div class="alert alert-warning small mb-0">
                    <svg class="icon me-1">
                        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-lock-locked') }}"></use>
                    </svg>
                    Hệ thống sẽ tự động <strong>giữ chỗ</strong> (reserve) số lượng tương ứng theo chiến lược FIFO/FEFO.
                    Nếu không đủ hàng, thao tác này sẽ thất bại.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-coreui-dismiss="modal">Hủy bỏ</button>
                <form method="POST" action="{{ route('issues.approve', $issue) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        <svg class="icon me-1">
                            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check') }}"></use>
                        </svg>
                        Duyệt & Giữ hàng
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

{{-- MODAL HOÀN TẤT XUẤT KHO (APPROVED → COMPLETED) --}}
@if($issueStatus === 3)
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-success">
                    <svg class="icon me-1">
                        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use>
                    </svg>
                    Xác nhận xuất kho thực tế
                </h5>
                <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Xác nhận đã lấy hàng theo phiếu <strong>{{ $issue->code }}</strong>?</p>
                <div class="alert alert-danger small mb-0">
                    <svg class="icon me-1">
                        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use>
                    </svg>
                    Sau khi xác nhận, tồn kho sẽ bị <strong>trừ thực tế</strong> và không thể hoàn tác.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-coreui-dismiss="modal">Hủy bỏ</button>
                <form method="POST" action="{{ route('issues.confirm', $issue) }}">
                    @csrf
                    <button type="submit" class="btn btn-success">
                        <svg class="icon me-1">
                            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}">
                            </use>
                        </svg>
                        Xuất kho & trừ tồn
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
function showSuggestion(id) {
    document.querySelectorAll('[id^="suggestion-"]').forEach(el => el.classList.add('d-none'));
    document.getElementById('suggestion-' + id)?.classList.remove('d-none');
}

function hideSuggestion(id) {
    document.getElementById('suggestion-' + id)?.classList.add('d-none');
}
</script>
@endpush
