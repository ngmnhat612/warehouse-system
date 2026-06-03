@extends('layouts.app')

@section('title', 'Cảnh báo hàng cận date — Warehouse System')

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">Báo cáo</a></li>
  <li class="breadcrumb-item active">Hàng cận date</li>
@endsection

@section('content')

  {{-- HEADER --}}
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-0 fw-semibold" style="color:#d97706;">
        <svg class="icon me-2"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-calendar') }}"></use></svg>
        Hàng cận date / sắp hết hạn
      </h4>
      <small class="text-body-secondary">Lô hàng sắp hết hạn trong {{ $days }} ngày tới, còn tồn kho</small>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('reports.alerts.near_expiry.excel', request()->query()) }}" class="btn btn-outline-success btn-sm">
        <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-spreadsheet') }}"></use></svg>
        Xuất Excel
      </a>
      <a href="{{ route('reports.alerts.near_expiry.pdf', request()->query()) }}" class="btn btn-outline-danger btn-sm">
        <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-file') }}"></use></svg>
        Xuất PDF
      </a>
    </div>
  </div>

  {{-- FILTER --}}
  <div class="card mb-4">
    <div class="card-body">
      <form method="GET" action="{{ route('reports.alerts.near_expiry') }}">
        <div class="row g-3 align-items-end">

          <div class="col-sm-6 col-lg-3">
            <label class="form-label fw-medium">Ngưỡng cảnh báo (ngày)</label>
            <div class="input-group">
              <input type="number" class="form-control" name="days" min="1" max="365"
                     value="{{ $days }}">
              <span class="input-group-text">ngày</span>
            </div>
          </div>

          <div class="col-sm-6 col-lg-3">
            <label class="form-label fw-medium">Chọn nhanh</label>
            <div class="d-flex gap-1 flex-wrap">
              @foreach ([7, 14, 30, 60, 90] as $d)
                <a href="{{ route('reports.alerts.near_expiry', array_merge(request()->except('days'), ['days' => $d])) }}"
                   class="btn btn-sm {{ $days == $d ? 'btn-warning' : 'btn-outline-secondary' }}">
                  {{ $d }}n
                </a>
              @endforeach
            </div>
          </div>

          <div class="col-sm-6 col-lg-2">
            <label class="form-label fw-medium">Nhóm hàng</label>
            <select class="form-select" name="category_id">
              <option value="">Tất cả nhóm</option>
              @foreach ($categories as $cat)
                <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                  {{ $cat->name }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="col-sm-6 col-lg-2">
            <label class="form-label fw-medium">Vị trí kho</label>
            <select class="form-select" name="location_id">
              <option value="">Tất cả vị trí</option>
              @foreach ($locations as $loc)
                <option value="{{ $loc->id }}" {{ request('location_id') == $loc->id ? 'selected' : '' }}>
                  [{{ $loc->code }}] {{ $loc->name }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="col-sm-12 col-lg-2 d-flex gap-2">
            <button type="submit" class="btn btn-warning text-white">
              <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-magnifying-glass') }}"></use></svg>
              Xem
            </button>
            <a href="{{ route('reports.alerts.near_expiry') }}" class="btn btn-outline-secondary">Reset</a>
          </div>

        </div>
      </form>
    </div>
  </div>

  {{-- KPI CARDS --}}
  <div class="row g-3 mb-4">
    <div class="col-sm-3">
      <div class="card border-start border-start-4 border-start-warning h-100">
        <div class="card-body">
          <div class="fs-3 fw-bold text-warning">{{ number_format($summary['total']) }}</div>
          <div class="text-body-secondary small">Lô hàng sắp hết hạn ({{ $days }} ngày)</div>
        </div>
      </div>
    </div>
    <div class="col-sm-3">
      <div class="card border-start border-start-4 border-start-danger h-100">
        <div class="card-body">
          <div class="fs-3 fw-bold text-danger">{{ number_format($summary['within_7']) }}</div>
          <div class="text-body-secondary small">Hết hạn trong 7 ngày tới</div>
        </div>
      </div>
    </div>
    <div class="col-sm-3">
      <div class="card border-start border-start-4 border-start-dark h-100">
        <div class="card-body">
          <div class="fs-3 fw-bold">{{ number_format($summary['expired']) }}</div>
          <div class="text-body-secondary small">Đã hết hạn (còn tồn kho)</div>
        </div>
      </div>
    </div>
    <div class="col-sm-3">
      <div class="card border-start border-start-4 border-start-primary h-100">
        <div class="card-body">
          <div class="fs-3 fw-bold text-primary">{{ number_format($summary['total_qty'], 0) }}</div>
          <div class="text-body-secondary small">Tổng số lượng hàng cận date</div>
        </div>
      </div>
    </div>
  </div>

  {{-- TABLE --}}
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span class="fw-semibold">Danh sách lô hàng cận date (trong {{ $days }} ngày tới)</span>
      <span class="badge bg-warning text-dark">{{ $summary['total'] }} lô hàng</span>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="text-center" style="width:50px">#</th>
              <th style="min-width:100px">Mã hàng</th>
              <th style="min-width:200px">Tên hàng hóa</th>
              <th style="width:120px">Nhóm</th>
              <th style="width:60px">ĐVT</th>
              <th style="width:120px">Số Lot</th>
              <th style="width:100px">Vị trí</th>
              <th class="text-end" style="width:100px">Số lượng</th>
              <th class="text-center" style="width:110px">Hết hạn</th>
              <th class="text-center" style="width:110px">Còn lại</th>
              <th class="text-center" style="width:100px">Mức độ</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($items as $i => $row)
              @php
                $daysLeft = (int) $row->days_left;
                if ($daysLeft < 0) {
                    $severity = 'dark';
                    $label    = 'Đã hết hạn';
                } elseif ($daysLeft <= 7) {
                    $severity = 'danger';
                    $label    = 'Khẩn cấp';
                } elseif ($daysLeft <= 14) {
                    $severity = 'warning';
                    $label    = 'Gấp';
                } else {
                    $severity = 'info';
                    $label    = 'Chú ý';
                }
              @endphp
              <tr class="{{ $daysLeft < 0 ? 'table-dark opacity-75' : ($daysLeft <= 7 ? 'table-danger bg-opacity-25' : '') }}">
                <td class="text-center text-body-secondary">{{ $i + 1 }}</td>
                <td><code class="text-primary fw-medium">{{ $row->product_code }}</code></td>
                <td class="fw-medium">{{ $row->product_name }}</td>
                <td class="small text-body-secondary">{{ $row->category_name ?? '—' }}</td>
                <td class="small">{{ $row->uom_name }}</td>
                <td>
                  @if ($row->lot_number)
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" style="font-size:11px">
                      {{ $row->lot_number }}
                    </span>
                  @else
                    <span class="text-body-secondary small">—</span>
                  @endif
                </td>
                <td>
                  <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" style="font-size:11px">
                    {{ $row->location_code }}
                  </span>
                </td>
                <td class="text-end fw-semibold">{{ number_format($row->quantity, 0) }}</td>
                <td class="text-center fw-medium">
                  {{ \Carbon\Carbon::parse($row->expiry_date)->format('d/m/Y') }}
                </td>
                <td class="text-center">
                  @if ($daysLeft < 0)
                    <span class="fw-bold text-danger">Đã hết hạn {{ abs($daysLeft) }}n</span>
                  @else
                    <span class="fw-bold text-{{ $severity }}">{{ $daysLeft }} ngày</span>
                  @endif
                </td>
                <td class="text-center">
                  <span class="badge bg-{{ $severity }}-subtle text-{{ $severity }} border border-{{ $severity }}-subtle" style="font-size:11px">
                    {{ $label }}
                  </span>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="11" class="text-center text-body-secondary py-5">
                  <svg class="icon icon-3xl d-block mx-auto mb-2 text-success opacity-50">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use>
                  </svg>
                  Không có lô hàng nào sắp hết hạn trong {{ $days }} ngày tới
                </td>
              </tr>
            @endforelse
          </tbody>
          @if ($items->count() > 0)
            <tfoot class="table-light fw-semibold">
              <tr>
                <td colspan="7" class="text-end">Tổng số lượng:</td>
                <td class="text-end">{{ number_format($items->sum('quantity'), 0) }}</td>
                <td colspan="3"></td>
              </tr>
            </tfoot>
          @endif
        </table>
      </div>
    </div>
  </div>

@endsection