@extends('layouts.app')

@section('title', $stocktake->code . ' — Kiểm kê kho')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('stocktakes.index') }}">Kiểm kê</a></li>
<li class="breadcrumb-item active">{{ $stocktake->code }}</li>
@endsection

@section('content')

@php
$statusMap = [1=>['Nháp','secondary'], 2=>['Đang kiểm kê','warning'], 3=>['Hoàn thành','success'], 4=>['Đã
hủy','danger']];
$typeLabels = [1=>'Toàn kho', 2=>'Theo khu vực', 3=>'Theo mặt hàng'];
[$statusText, $statusColor] = $statusMap[$stocktake->status] ?? ['?','secondary'];
$isDraft = $stocktake->status === 1;
$isInProgress = $stocktake->status === 2;
$isDone = $stocktake->status === 3;
$isCancelled = $stocktake->status === 4;
$isFrozen = $stocktake->freeze?->isActive();
$progress = $totalLines > 0 ? round($countedLines / $totalLines * 100) : 0;
@endphp

{{-- HEADER --}}
<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
    <div>
        <h4 class="mb-1 fw-semibold">{{ $stocktake->code }}</h4>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span
                class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}-emphasis border border-{{ $statusColor }}-subtle rounded-pill">
                {{ $statusText }}
            </span>
            <span class="text-body-secondary small">{{ $typeLabels[$stocktake->check_type] ?? '?' }}</span>
            @if($isFrozen)
            <span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle rounded-pill">
                <svg class="icon me-1">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-lock-locked') }}"></use>
                </svg>
                Kho đang đóng băng
            </span>
            @endif
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('stocktakes.index') }}" class="btn btn-outline-secondary btn-sm">
            <svg class="icon me-1">
                <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-left') }}"></use>
            </svg>
            Quay lại
        </a>

        @if($isDraft)
        <form method="POST" action="{{ route('stocktakes.activate', $stocktake) }}">
            @csrf
            <button type="submit" class="btn btn-warning btn-sm"
                onclick="return confirm('Xác nhận kích hoạt kiểm kê? Tồn kho sẽ được snapshot và kho sẽ đóng băng.')">
                <svg class="icon me-1">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-media-play') }}"></use>
                </svg>
                Kích hoạt kiểm kê
            </button>
        </form>
        @endif

        @if($isInProgress)
        <button type="submit" form="formUpdateLines" class="btn btn-primary btn-sm">
            <svg class="icon me-1">
                <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-save') }}"></use>
            </svg>
            Lưu tất cả
        </button>
        <form method="POST" action="{{ route('stocktakes.complete', $stocktake) }}">
            @csrf
            <button type="submit" class="btn btn-success btn-sm"
                onclick="return confirm('Đánh dấu hoàn thành? Tất cả dòng phải đã được nhập.')">
                <svg class="icon me-1">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check') }}"></use>
                </svg>
                Hoàn thành kiểm kê
            </button>
        </form>
        @endif

        @if($isDone && $stocktake->adjustments->isEmpty())
        <form method="POST" action="{{ route('stocktakes.adjustment.create', $stocktake) }}">
            @csrf
            <button type="submit" class="btn btn-info btn-sm text-white"
                onclick="return confirm('Tạo phiếu điều chỉnh cho các dòng chênh lệch?')">
                <svg class="icon me-1">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-transfer') }}"></use>
                </svg>
                Tạo phiếu điều chỉnh
            </button>
        </form>
        @endif

        @if($isFrozen)
        <form method="POST" action="{{ route('stocktakes.unfreeze', $stocktake) }}">
            @csrf
            <button type="submit" class="btn btn-outline-danger btn-sm"
                onclick="return confirm('Gỡ đóng băng kho? Các giao dịch sẽ được phép thực hiện trở lại.')">
                <svg class="icon me-1">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-lock-unlocked') }}"></use>
                </svg>
                Gỡ đóng băng
            </button>
        </form>
        @endif

        @if(!$isDone && !$isCancelled)
        <form method="POST" action="{{ route('stocktakes.cancel', $stocktake) }}">
            @csrf
            <button type="submit" class="btn btn-outline-danger btn-sm"
                onclick="return confirm('Hủy phiếu kiểm kê này?')">
                <svg class="icon me-1">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-x') }}"></use>
                </svg>
                Hủy phiếu
            </button>
        </form>
        @endif
    </div>
</div>

{{-- FLASH MESSAGES --}}
@if(session('success'))
<div class="alert alert-success alert-dismissible mb-3" role="alert">
    <svg class="icon me-1">
        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check') }}"></use>
    </svg>
    {{ session('success') }}
    <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible mb-3" role="alert">
    <svg class="icon me-1">
        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use>
    </svg>
    {{ session('error') }}
    <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
</div>
@endif
@if(session('info'))
<div class="alert alert-info alert-dismissible mb-3" role="alert">
    {{ session('info') }}
    <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
</div>
@endif

<div class="row g-4">

    {{-- CỘT TRÁI: Thông tin phiếu --}}
    <div class="col-lg-4 col-xl-3">

        <div class="card mb-3">
            <div class="card-header fw-semibold">Thông tin phiếu</div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-5 text-body-secondary">Mã phiếu</dt>
                    <dd class="col-7 font-monospace">{{ $stocktake->code }}</dd>

                    <dt class="col-5 text-body-secondary">Loại</dt>
                    <dd class="col-7">{{ $typeLabels[$stocktake->check_type] ?? '?' }}</dd>

                    <dt class="col-5 text-body-secondary">Ngày kiểm</dt>
                    <dd class="col-7">{{ $stocktake->check_date?->format('d/m/Y') ?? '—' }}</dd>

                    <dt class="col-5 text-body-secondary">Phụ trách</dt>
                    <dd class="col-7">{{ $stocktake->assignedTo?->name ?? '—' }}</dd>

                    <dt class="col-5 text-body-secondary">Người tạo</dt>
                    <dd class="col-7">{{ $stocktake->createdBy?->name ?? '—' }}</dd>

                    @if($stocktake->completed_at)
                    <dt class="col-5 text-body-secondary">Hoàn thành</dt>
                    <dd class="col-7">{{ $stocktake->completed_at->format('d/m/Y H:i') }}</dd>
                    @endif

                    @if($stocktake->note)
                    <dt class="col-5 text-body-secondary">Ghi chú</dt>
                    <dd class="col-7">{{ $stocktake->note }}</dd>
                    @endif
                </dl>
            </div>
        </div>

        {{-- Tiến độ kiểm kê --}}
        @if($isInProgress || $isDone)
        <div class="card mb-3">
            <div class="card-header fw-semibold">Tiến độ kiểm kê</div>
            <div class="card-body">
                <div class="d-flex justify-content-between small mb-1">
                    <span>Đã kiểm</span>
                    <span class="fw-medium">{{ $countedLines }} / {{ $totalLines }}</span>
                </div>
                <div class="progress" style="height:10px">
                    <div class="progress-bar bg-{{ $progress == 100 ? 'success' : 'warning' }}" role="progressbar"
                        style="width:{{ $progress }}%">
                    </div>
                </div>
                <div class="d-flex justify-content-between mt-2 small text-body-secondary">
                    <span>{{ $progress }}% hoàn thành</span>
                    @if($diffLines > 0)
                    <span class="text-danger fw-medium">{{ $diffLines }} dòng chênh lệch</span>
                    @else
                    <span class="text-success">Không có chênh lệch</span>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- Thông tin đóng băng --}}
        @if($stocktake->freeze)
        <div class="card border-{{ $isFrozen ? 'danger' : 'secondary' }}">
            <div class="card-header fw-semibold text-{{ $isFrozen ? 'danger' : 'body-secondary' }}">
                <svg class="icon me-1">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-lock-locked') }}"></use>
                </svg>
                Đóng băng kho
            </div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-6 text-body-secondary">Trạng thái</dt>
                    <dd class="col-6">
                        @if($isFrozen)
                        <span class="text-danger fw-medium">Đang đóng băng</span>
                        @else
                        <span class="text-success">Đã gỡ băng</span>
                        @endif
                    </dd>
                    <dt class="col-6 text-body-secondary">Đóng băng lúc</dt>
                    <dd class="col-6">{{ $stocktake->freeze->frozen_at?->format('d/m/Y H:i') }}</dd>
                    @if(!$isFrozen)
                    <dt class="col-6 text-body-secondary">Gỡ băng lúc</dt>
                    <dd class="col-6">{{ $stocktake->freeze->unfrozen_at?->format('d/m/Y H:i') }}</dd>
                    @endif
                </dl>
            </div>
        </div>
        @endif

        {{-- Phiếu điều chỉnh --}}
        @foreach($stocktake->adjustments as $adj)
        <div class="card mt-3">
            <div class="card-header fw-semibold small">Phiếu điều chỉnh</div>
            <div class="card-body">
                <a href="{{ route('stocktakes.adjustment.show', [$stocktake, $adj]) }}"
                    class="fw-semibold text-decoration-none font-monospace">
                    {{ $adj->code }}
                </a>
                @php $adjStatuses = [1=>'Nháp',2=>'Chờ duyệt',3=>'Đã duyệt',4=>'Đã áp dụng',5=>'Từ chối']; @endphp
                <span class="ms-2 badge bg-secondary-subtle text-secondary-emphasis border rounded-pill small">
                    {{ $adjStatuses[$adj->status] ?? '?' }}
                </span>
            </div>
        </div>
        @endforeach

    </div>

    {{-- CỘT PHẢI: Bảng dòng kiểm kê --}}
    <div class="col-lg-8 col-xl-9">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span class="fw-semibold">
                    Danh sách mặt hàng kiểm kê
                    <span
                        class="badge bg-primary-subtle text-primary-emphasis border rounded-pill ms-1">{{ $totalLines }}</span>
                </span>
                @if($isInProgress)
                <div class="d-flex gap-2">
                    <select id="filterStatus" class="form-select form-select-sm" style="width:160px">
                        <option value="">Tất cả</option>
                        <option value="counted">Đã kiểm</option>
                        <option value="uncounted">Chưa kiểm</option>
                        <option value="diff">Có chênh lệch</option>
                    </select>
                </div>
                @endif
            </div>

            @if($isInProgress)
            <form method="POST" action="{{ route('stocktakes.lines.updateAll', $stocktake) }}" id="formUpdateLines">
                @csrf
                @endif

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 table-sm" id="tblLines">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Sản phẩm</th>
                                    <th>Vị trí</th>
                                    <th>Lot</th>
                                    <th class="text-end">Tồn hệ thống</th>
                                    <th class="text-end" style="min-width:130px">
                                        @if($isInProgress) Thực tế (nhập) @else Thực tế @endif
                                    </th>
                                    <th class="text-end">Chênh lệch</th>
                                    <th class="text-center">Trạng thái</th>
                                    @if($isInProgress || $isDone)
                                    <th class="text-end" style="width:90px">Lưu nhanh</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($stocktake->lines as $i => $line)
                                @php
                                $isCounted = $line->actual_qty !== null;
                                $diffQty = $line->diff_qty;
                                $hasDiff = $isCounted && $diffQty != 0;
                                $rowClass = $hasDiff ? 'table-warning' : ($isCounted ? '' : '');
                                $statusData = $isCounted ? ($hasDiff ? 'diff' : 'counted') : 'uncounted';
                                @endphp
                                <tr class="{{ $rowClass }}" data-status="{{ $statusData }}">
                                    <td class="text-body-secondary small">{{ $i + 1 }}</td>
                                    <td>
                                        <div class="fw-medium small">{{ $line->product?->name }}</div>
                                        <div class="text-body-secondary" style="font-size:.75rem">
                                            {{ $line->product?->code }}</div>
                                        @if($line->uom)
                                        <span class="badge bg-light text-body-secondary border"
                                            style="font-size:.7rem">{{ $line->uom->name ?? $line->product?->uom?->name }}</span>
                                        @endif
                                    </td>
                                    <td class="small text-body-secondary">
                                        {{ $line->location?->code }}<br>
                                        <span style="font-size:.72rem">{{ $line->location?->name }}</span>
                                    </td>
                                    <td class="small text-body-secondary">
                                        {{ $line->lot?->lot_number ?? '—' }}
                                        @if($line->lot?->expiry_date)
                                        <br><span style="font-size:.72rem"
                                            class="{{ $line->lot->expiry_date->isPast() ? 'text-danger' : '' }}">
                                            HSD: {{ $line->lot->expiry_date->format('d/m/Y') }}
                                        </span>
                                        @endif
                                    </td>
                                    <td class="text-end font-monospace">{{ number_format($line->system_qty, 0) }}</td>
                                    <td class="text-end">
                                        @if($isInProgress)
                                        <input type="hidden" name="lines[{{ $i }}][id]" value="{{ $line->id }}">
                                        <input type="number" step="0.001" min="0" name="lines[{{ $i }}][actual_qty]"
                                            class="form-control form-control-sm text-end actual-qty-input font-monospace"
                                            value="{{ old("lines.{$i}.actual_qty", $isCounted ? (float)$line->actual_qty : '') }}"
                                            data-line-id="{{ $line->id }}" placeholder="0" style="width:110px">
                                        @else
                                        <span
                                            class="font-monospace">{{ $isCounted ? number_format($line->actual_qty, 0) : '—' }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end font-monospace">
                                        @if($isCounted)
                                        @if($diffQty > 0)
                                        <span class="text-success fw-medium">+{{ number_format($diffQty, 0) }}</span>
                                        @elseif($diffQty < 0) <span class="text-danger fw-medium">
                                            {{ number_format($diffQty, 0) }}</span>
                                            @else
                                            <span class="text-body-secondary">0</span>
                                            @endif
                                            @else
                                            <span class="text-body-secondary">—</span>
                                            @endif
                                    </td>
                                    <td class="text-center">
                                        @if($isCounted)
                                        @if($hasDiff)
                                        <span
                                            class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill"
                                            style="font-size:.72rem">
                                            Chênh lệch
                                        </span>
                                        @else
                                        <span
                                            class="badge bg-success-subtle text-success-emphasis border border-success-subtle rounded-pill"
                                            style="font-size:.72rem">
                                            <svg class="icon" style="width:12px;height:12px">
                                                <use
                                                    xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check') }}">
                                                </use>
                                            </svg>
                                            Đã kiểm
                                        </span>
                                        @endif
                                        @else
                                        <span
                                            class="badge bg-secondary-subtle text-secondary-emphasis border rounded-pill"
                                            style="font-size:.72rem">Chưa kiểm</span>
                                        @endif
                                    </td>
                                    @if($isInProgress || $isDone)
                                    <td class="text-end">
                                        @if($isInProgress)
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-save-line"
                                            data-line-id="{{ $line->id }}"
                                            data-url="{{ route('stocktakes.lines.update', [$stocktake, $line]) }}"
                                            title="Lưu dòng này">
                                            <svg class="icon">
                                                <use
                                                    xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-save') }}">
                                                </use>
                                            </svg>
                                        </button>
                                        @endif
                                    </td>
                                    @endif
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center text-body-secondary py-5">
                                        <svg class="icon icon-3xl d-block mx-auto mb-2 opacity-25">
                                            <use
                                                xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-clipboard') }}">
                                            </use>
                                        </svg>
                                        Chưa có dữ liệu. Kích hoạt phiếu để snapshot tồn kho.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if($isInProgress)
            </form>
            @endif

        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
(function() {

    // ── Lọc dòng theo trạng thái ──────────────────────────────────────────────
    const filterSelect = document.getElementById('filterStatus');
    filterSelect?.addEventListener('change', function() {
        const val = this.value;
        document.querySelectorAll('#tblLines tbody tr[data-status]').forEach(tr => {
            tr.style.display = (!val || tr.dataset.status === val) ? '' : 'none';
        });
    });

    // ── Lưu nhanh từng dòng (AJAX) ────────────────────────────────────────────
    document.querySelectorAll('.btn-save-line').forEach(btn => {
        btn.addEventListener('click', function() {
            const lineId = this.dataset.lineId;
            const url = this.dataset.url;
            const input = document.querySelector(`.actual-qty-input[data-line-id="${lineId}"]`);
            if (!input) return;

            const qty = input.value;
            if (qty === '') {
                alert('Vui lòng nhập số lượng thực tế.');
                return;
            }

            const btnEl = this;
            btnEl.disabled = true;
            btnEl.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

            fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                            .content,
                    },
                    body: JSON.stringify({
                        actual_qty: qty
                    }),
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const row = input.closest('tr');

                        // Cập nhật cột chênh lệch
                        const diffCell = row.querySelector('td:nth-child(7)');
                        const diff = parseFloat(data.diff_qty);
                        if (diffCell) {
                            if (diff > 0) diffCell.innerHTML =
                                `<span class="text-success fw-medium font-monospace">+${diff}</span>`;
                            else if (diff < 0) diffCell.innerHTML =
                                `<span class="text-danger fw-medium font-monospace">${diff}</span>`;
                            else diffCell.innerHTML =
                                `<span class="text-body-secondary font-monospace">0</span>`;
                        }

                        // Cập nhật badge trạng thái
                        const statusCell = row.querySelector('td:nth-child(8)');
                        const newStatus = diff != 0 ? 'diff' : 'counted';
                        row.dataset.status = newStatus;

                        if (statusCell) {
                            if (newStatus === 'diff') {
                                statusCell.innerHTML =
                                    `<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill" style="font-size:.72rem">Chênh lệch</span>`;
                                row.classList.add('table-warning');
                            } else {
                                statusCell.innerHTML =
                                    `<span class="badge bg-success-subtle text-success-emphasis border border-success-subtle rounded-pill" style="font-size:.72rem"><svg class="icon" style="width:12px;height:12px"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check') }}"></use></svg> Đã kiểm</span>`;
                                row.classList.remove('table-warning');
                            }
                        }

                        btnEl.innerHTML =
                            '<svg class="icon text-success"><use xlink:href="{{ asset("vendor/coreui/icons/sprites/free.svg#cil-check") }}"></use></svg>';
                        setTimeout(() => {
                            btnEl.disabled = false;
                            btnEl.innerHTML =
                                '<svg class="icon"><use xlink:href="{{ asset("vendor/coreui/icons/sprites/free.svg#cil-save") }}"></use></svg>';
                        }, 1500);

                    } else {
                        alert(data.error || 'Lỗi khi lưu dòng.');
                        btnEl.disabled = false;
                        btnEl.innerHTML =
                            '<svg class="icon"><use xlink:href="{{ asset("vendor/coreui/icons/sprites/free.svg#cil-save") }}"></use></svg>';
                    }
                })
                .catch(() => {
                    alert('Lỗi kết nối. Vui lòng thử lại.');
                    btnEl.disabled = false;
                    btnEl.innerHTML =
                        '<svg class="icon"><use xlink:href="{{ asset("vendor/coreui/icons/sprites/free.svg#cil-save") }}"></use></svg>';
                });
        });
    });

})();
</script>
@endpush
