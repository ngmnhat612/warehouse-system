@extends('layouts.app')

@section('title', 'Chi tiết kiểm kê ' . $stocktake->code)

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('stocktakes.index') }}">Kiểm kê kho</a></li>
  <li class="breadcrumb-item active">{{ $stocktake->code }}</li>
@endsection

@section('content')

@php
  $statusMap = [
    1 => ['Nháp',         'secondary'],
    2 => ['Đang kiểm kê', 'warning'],
    3 => ['Hoàn thành',   'success'],
    4 => ['Đã hủy',       'danger'],
  ];
  [$statusText, $statusColor] = $statusMap[$stocktake->status] ?? ['?', 'secondary'];

  $typeLabels = [
    1 => 'Toàn kho',
    2 => 'Theo khu vực',
    3 => 'Theo mặt hàng',
  ];

  $isFrozen    = $stocktake->freeze && $stocktake->freeze->isActive();
  $canEdit     = $stocktake->status === \App\Models\InventoryCheck::STATUS_IN_PROGRESS;
  $isDraft     = $stocktake->status === \App\Models\InventoryCheck::STATUS_DRAFT;
  $isDone      = $stocktake->status === \App\Models\InventoryCheck::STATUS_DONE;
  $isCancelled = $stocktake->status === \App\Models\InventoryCheck::STATUS_CANCELLED;
@endphp

{{-- HEADER --}}
<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
  <div>
    <div class="d-flex align-items-center gap-2 mb-1">
      <h4 class="mb-0 fw-semibold">{{ $stocktake->code }}</h4>
      <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}-emphasis border border-{{ $statusColor }}-subtle rounded-pill">
        {{ $statusText }}
      </span>
      @if($isFrozen)
        <span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle rounded-pill">
          <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-lock-locked') }}"></use></svg>
          Đóng băng
        </span>
      @endif
    </div>
    <small class="text-body-secondary">
      {{ $typeLabels[$stocktake->check_type] ?? '?' }}
      &nbsp;·&nbsp; Ngày kiểm: {{ $stocktake->check_date?->format('d/m/Y') ?? '—' }}
      &nbsp;·&nbsp; Phụ trách: {{ $stocktake->assignedTo?->name ?? '—' }}
    </small>
  </div>

  <div class="d-flex gap-2 flex-wrap">
    {{-- Kích hoạt --}}
    @if($isDraft)
      <form method="POST" action="{{ route('stocktakes.activate', $stocktake) }}"
            onsubmit="return confirm('Kích hoạt kiểm kê sẽ snapshot tồn kho và đóng băng khu vực. Tiếp tục?')">
        @csrf
        <button class="btn btn-primary">
          <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-play') }}"></use></svg>
          Kích hoạt kiểm kê
        </button>
      </form>
    @endif

    {{-- Lưu hàng loạt --}}
    @if($canEdit)
      <button type="button" class="btn btn-success" onclick="submitAllLines()">
        <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-save') }}"></use></svg>
        Lưu tất cả
      </button>
    @endif

    {{-- Hoàn thành --}}
    @if($canEdit)
      <form method="POST" action="{{ route('stocktakes.complete', $stocktake) }}"
            onsubmit="return confirm('Đánh dấu hoàn thành? Đảm bảo đã nhập đủ số lượng thực tế.')">
        @csrf
        <button class="btn btn-primary">
          <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use></svg>
          Hoàn thành kiểm kê
        </button>
      </form>
    @endif

    {{-- Tạo phiếu điều chỉnh --}}
    @if($isDone || $canEdit)
      <form method="POST" action="{{ route('stocktakes.adjustment.create', $stocktake) }}">
        @csrf
        <button class="btn btn-warning">
          <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-pencil') }}"></use></svg>
          Tạo phiếu điều chỉnh
        </button>
      </form>
    @endif

    {{-- Gỡ đóng băng --}}
    @if($isFrozen && ($canEdit || $isDone))
      <form method="POST" action="{{ route('stocktakes.unfreeze', $stocktake) }}"
            onsubmit="return confirm('Gỡ đóng băng kho? Các giao dịch có thể được thực hiện trở lại.')">
        @csrf
        <button class="btn btn-outline-warning">
          <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-lock-unlocked') }}"></use></svg>
          Gỡ đóng băng
        </button>
      </form>
    @endif

    {{-- Hủy --}}
    @if(!$isDone && !$isCancelled)
      <form method="POST" action="{{ route('stocktakes.cancel', $stocktake) }}"
            onsubmit="return confirm('Hủy phiếu kiểm kê này? Thao tác không thể hoàn tác.')">
        @csrf @method('DELETE')
        <button class="btn btn-outline-danger">
          <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-x-circle') }}"></use></svg>
          Hủy phiếu
        </button>
      </form>
    @endif

    @if(!$isDraft && !$isCancelled)
    <div class="dropdown">
        <button class="btn btn-outline-secondary dropdown-toggle" data-coreui-toggle="dropdown">
        <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-cloud-download') }}"></use></svg>
        Xuất
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
        <li>
            <a class="dropdown-item" href="{{ route('stocktakes.export.excel', $stocktake) }}">
            <svg class="icon me-2 text-success"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-spreadsheet') }}"></use></svg>
            Xuất Excel (.xlsx)
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="{{ route('stocktakes.export.pdf', $stocktake) }}">
            <svg class="icon me-2 text-danger"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-file') }}"></use></svg>
            Xuất PDF (Biên bản)
            </a>
        </li>
        </ul>
    </div>
    @endif
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
@if(session('info'))
  <div class="alert alert-info alert-dismissible mb-4" role="alert">
    {{ session('info') }}
    <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
  </div>
@endif

{{-- KPI CARDS --}}
<div class="row g-3 mb-4">
  <div class="col-sm-6 col-lg-3">
    <div class="card border-start border-start-4 border-start-primary h-100">
      <div class="card-body d-flex align-items-center gap-3">
        <svg class="icon icon-2xl text-primary"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-list') }}"></use></svg>
        <div>
          <div class="fs-5 fw-semibold">{{ $totalLines }}</div>
          <div class="text-body-secondary small">Tổng dòng</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="card border-start border-start-4 border-start-success h-100">
      <div class="card-body d-flex align-items-center gap-3">
        <svg class="icon icon-2xl text-success"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check') }}"></use></svg>
        <div>
          <div class="fs-5 fw-semibold">{{ $countedLines }}</div>
          <div class="text-body-secondary small">Đã kiểm</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="card border-start border-start-4 border-start-warning h-100">
      <div class="card-body d-flex align-items-center gap-3">
        <svg class="icon icon-2xl text-warning"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-clock') }}"></use></svg>
        <div>
          <div class="fs-5 fw-semibold">{{ $totalLines - $countedLines }}</div>
          <div class="text-body-secondary small">Chưa kiểm</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="card border-start border-start-4 border-start-danger h-100">
      <div class="card-body d-flex align-items-center gap-3">
        <svg class="icon icon-2xl text-danger"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use></svg>
        <div>
          <div class="fs-5 fw-semibold">{{ $diffLines }}</div>
          <div class="text-body-secondary small">Dòng chênh lệch</div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- TIẾN ĐỘ --}}
@if($totalLines > 0)
@php $pct = round($countedLines / $totalLines * 100); @endphp
<div class="card mb-4">
  <div class="card-body py-3">
    <div class="d-flex justify-content-between mb-1">
      <small class="fw-semibold">Tiến độ kiểm kê</small>
      <small class="text-body-secondary">{{ $countedLines }} / {{ $totalLines }} dòng ({{ $pct }}%)</small>
    </div>
    <div class="progress" style="height:8px">
      <div class="progress-bar bg-success" style="width:{{ $pct }}%"></div>
    </div>
  </div>
</div>
@endif

{{-- TABS --}}
<div class="card">
  <div class="card-header p-0">
    <ul class="nav nav-tabs border-0 px-3 pt-2" id="stocktakeTabs" role="tablist">
      <li class="nav-item">
        <button class="nav-link active" data-coreui-toggle="tab" data-coreui-target="#tab-all">
          Tất cả dòng
          <span class="badge bg-secondary-subtle text-secondary-emphasis ms-1">{{ $totalLines }}</span>
        </button>
      </li>
      <li class="nav-item">
        <button class="nav-link" data-coreui-toggle="tab" data-coreui-target="#tab-diff">
          Chênh lệch
          @if($diffLines > 0)
            <span class="badge bg-danger-subtle text-danger-emphasis ms-1">{{ $diffLines }}</span>
          @else
            <span class="badge bg-secondary-subtle text-secondary-emphasis ms-1">0</span>
          @endif
        </button>
      </li>
      <li class="nav-item">
        <button class="nav-link" data-coreui-toggle="tab" data-coreui-target="#tab-uncounted">
          Chưa kiểm
          @if($totalLines - $countedLines > 0)
            <span class="badge bg-warning-subtle text-warning-emphasis ms-1">{{ $totalLines - $countedLines }}</span>
          @else
            <span class="badge bg-secondary-subtle text-secondary-emphasis ms-1">0</span>
          @endif
        </button>
      </li>
      @if($stocktake->adjustments->count() > 0)
      <li class="nav-item">
        <button class="nav-link" data-coreui-toggle="tab" data-coreui-target="#tab-adjustments">
          Phiếu điều chỉnh
          <span class="badge bg-warning-subtle text-warning-emphasis ms-1">{{ $stocktake->adjustments->count() }}</span>
        </button>
      </li>
      @endif
    </ul>
  </div>

  {{-- Session / validation errors --}}
  @if($errors->any())
    <div class="alert alert-danger mx-3 mt-3 mb-0">
      <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
  @endif

  <form method="POST" action="{{ route('stocktakes.lines.update', $stocktake) }}" id="linesForm">
    @csrf

  <div class="tab-content">

    {{-- ── TAB: TẤT CẢ ── --}}
    <div class="tab-pane fade show active" id="tab-all">
      @include('stocktakes.partials.lines-table', ['lines' => $stocktake->lines, 'canEdit' => $canEdit])
    </div>

    {{-- ── TAB: CHÊNH LỆCH ── --}}
    <div class="tab-pane fade" id="tab-diff">
      @php
        $diffLinesList = $stocktake->lines->filter(fn($l) => $l->actual_qty !== null && $l->diff_qty != 0);
      @endphp
      @if($diffLinesList->isEmpty())
        <div class="text-center text-body-secondary py-5">
          <svg class="icon icon-3xl d-block mx-auto mb-2 opacity-25">
            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use>
          </svg>
          Không có chênh lệch nào.
        </div>
      @else
        @include('stocktakes.partials.lines-table', ['lines' => $diffLinesList, 'canEdit' => $canEdit, 'highlightDiff' => true])
      @endif
    </div>

    {{-- ── TAB: CHƯA KIỂM ── --}}
    <div class="tab-pane fade" id="tab-uncounted">
      @php
        $uncountedList = $stocktake->lines->filter(fn($l) => $l->actual_qty === null);
      @endphp
      @if($uncountedList->isEmpty())
        <div class="text-center text-body-secondary py-5">
          <svg class="icon icon-3xl d-block mx-auto mb-2 opacity-25">
            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use>
          </svg>
          Tất cả đã được kiểm.
        </div>
      @else
        @include('stocktakes.partials.lines-table', ['lines' => $uncountedList, 'canEdit' => $canEdit])
      @endif
    </div>

    {{-- ── TAB: PHIẾU ĐIỀU CHỈNH ── --}}
    @if($stocktake->adjustments->count() > 0)
    <div class="tab-pane fade" id="tab-adjustments">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Mã phiếu</th>
              <th>Ngày điều chỉnh</th>
              <th class="text-center">Trạng thái</th>
              <th>Người tạo</th>
              <th class="text-end">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            @foreach($stocktake->adjustments as $adj)
            @php
              $adjStatusMap = [
                1 => ['Nháp',       'secondary'],
                2 => ['Chờ duyệt',  'warning'],
                3 => ['Đã duyệt',   'info'],
                4 => ['Đã áp dụng', 'success'],
                5 => ['Từ chối',    'danger'],
              ];
              [$adjText, $adjColor] = $adjStatusMap[$adj->status] ?? ['?', 'secondary'];
            @endphp
            <tr>
              <td class="fw-semibold">{{ $adj->code }}</td>
              <td>{{ $adj->adjustment_date ? \Carbon\Carbon::parse($adj->adjustment_date)->format('d/m/Y') : '—' }}</td>
              <td class="text-center">
                <span class="badge bg-{{ $adjColor }}-subtle text-{{ $adjColor }}-emphasis border border-{{ $adjColor }}-subtle rounded-pill">
                  {{ $adjText }}
                </span>
              </td>
              <td class="text-body-secondary small">{{ $adj->createdBy?->name ?? '—' }}</td>
              <td class="text-end">
                <a href="{{ route('stocktakes.adjustment.show', [$stocktake, $adj]) }}"
                   class="btn btn-sm btn-outline-primary">
                  <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-magnifying-glass') }}"></use></svg>
                </a>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
    @endif

  </div>{{-- /tab-content --}}
  </form>
</div>{{-- /card --}}

@endsection

@push('scripts')
<script>
function submitAllLines() {
  if (confirm('Lưu toàn bộ số lượng thực tế đã nhập?')) {
    document.getElementById('linesForm').submit();
  }
}
</script>
@endpush
