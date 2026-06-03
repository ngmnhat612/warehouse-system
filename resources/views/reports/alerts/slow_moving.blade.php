@extends('layouts.app')

@section('title', 'Cảnh báo hàng đọng kho — Warehouse System')

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">Báo cáo</a></li>
  <li class="breadcrumb-item active">Hàng đọng kho lâu ngày</li>
@endsection

@section('content')

  {{-- HEADER --}}
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-0 fw-semibold text-warning">
        <svg class="icon me-2"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-clock') }}"></use></svg>
        Hàng đọng kho lâu ngày
      </h4>
      <small class="text-body-secondary">Mặt hàng có tồn kho nhưng không xuất trong thời gian dài</small>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('reports.alerts.slow_moving.excel', request()->query()) }}" class="btn btn-outline-success btn-sm">
        <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-spreadsheet') }}"></use></svg>
        Xuất Excel
      </a>
      <a href="{{ route('reports.alerts.slow_moving.pdf', request()->query()) }}" class="btn btn-outline-danger btn-sm">
        <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-file') }}"></use></svg>
        Xuất PDF
      </a>
    </div>
  </div>

  {{-- FILTER --}}
  <div class="card mb-4">
    <div class="card-body">
      <form method="GET" action="{{ route('reports.alerts.slow_moving') }}">
        <div class="row g-3 align-items-end">

          <div class="col-sm-6 col-lg-3">
            <label class="form-label fw-medium">Không xuất quá (ngày)</label>
            <div class="input-group">
              <input type="number" class="form-control" name="days" min="1" max="3650"
                     value="{{ $days }}">
              <span class="input-group-text">ngày</span>
            </div>
          </div>

          {{-- Nút tắt nhanh kỳ --}}
          <div class="col-sm-6 col-lg-3">
            <label class="form-label fw-medium">Chọn nhanh</label>
            <div class="d-flex gap-1 flex-wrap">
              @foreach ([30, 60, 90, 180, 365] as $d)
                <a href="{{ route('reports.alerts.slow_moving', array_merge(request()->except('days'), ['days' => $d])) }}"
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
            <a href="{{ route('reports.alerts.slow_moving') }}" class="btn btn-outline-secondary">Reset</a>
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
          <div class="text-body-secondary small">Mặt hàng đọng kho > {{ $days }} ngày</div>
        </div>
      </div>
    </div>
    <div class="col-sm-3">
      <div class="card border-start border-start-4 border-start-primary h-100">
        <div class="card-body">
          <div class="fs-3 fw-bold text-primary">{{ number_format($summary['total_qty'], 0) }}</div>
          <div class="text-body-secondary small">Tổng tồn kho bị đọng</div>
        </div>
      </div>
    </div>
    <div class="col-sm-3">
      <div class="card border-start border-start-4 border-start-secondary h-100">
        <div class="card-body">
          <div class="fs-3 fw-bold">{{ number_format($summary['avg_idle']) }}</div>
          <div class="text-body-secondary small">Số ngày đọng trung bình</div>
        </div>
      </div>
    </div>
    <div class="col-sm-3">
      <div class="card border-start border-start-4 border-start-danger h-100">
        <div class="card-body">
          <div class="fs-3 fw-bold text-danger">{{ number_format($summary['max_idle']) }}</div>
          <div class="text-body-secondary small">Số ngày đọng cao nhất</div>
        </div>
      </div>
    </div>
  </div>

  {{-- TABLE --}}
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span class="fw-semibold">Danh sách hàng đọng kho (không xuất trong {{ $days }} ngày)</span>
      <span class="badge bg-warning text-dark">{{ $summary['total'] }} mặt hàng</span>
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
              <th style="width:100px">Vị trí</th>
              <th class="text-end" style="width:100px">Tồn thực tế</th>
              <th class="text-end" style="width:100px">Khả dụng</th>
              <th class="text-center" style="width:120px">Nhập cuối</th>
              <th class="text-center" style="width:120px">Xuất cuối</th>
              <th class="text-center" style="width:110px">Số ngày đọng</th>
              <th class="text-center" style="width:90px">Mức độ</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($items as $i => $row)
              @php
                $idleDays = (int) $row->idle_days;
                $severity = $idleDays >= 365 ? 'danger'
                          : ($idleDays >= 180 ? 'warning'
                          : ($idleDays >= 90  ? 'info' : 'secondary'));
                $severityLabel = $idleDays >= 365 ? 'Nghiêm trọng'
                               : ($idleDays >= 180 ? 'Cao'
                               : ($idleDays >= 90  ? 'Trung bình' : 'Chú ý'));
              @endphp
              <tr>
                <td class="text-center text-body-secondary">{{ $i + 1 }}</td>
                <td><code class="text-primary fw-medium">{{ $row->product_code }}</code></td>
                <td class="fw-medium">{{ $row->product_name }}</td>
                <td class="small text-body-secondary">{{ $row->category_name ?? '—' }}</td>
                <td class="small">{{ $row->uom_name }}</td>
                <td>
                  <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" style="font-size:11px">
                    {{ $row->location_code }}
                  </span>
                </td>
                <td class="text-end">{{ number_format($row->total_qty, 0) }}</td>
                <td class="text-end fw-semibold text-primary">{{ number_format($row->available_qty, 0) }}</td>
                <td class="text-center small text-body-secondary">
                  {{ $row->last_received_date ? \Carbon\Carbon::parse($row->last_received_date)->format('d/m/Y') : '—' }}
                </td>
                <td class="text-center small">
                  @if ($row->last_issue_date)
                    {{ \Carbon\Carbon::parse($row->last_issue_date)->format('d/m/Y') }}
                  @else
                    <span class="text-danger fw-medium">Chưa xuất</span>
                  @endif
                </td>
                <td class="text-center">
                  <span class="fw-bold text-{{ $severity }}">{{ number_format($idleDays) }} ngày</span>
                </td>
                <td class="text-center">
                  <span class="badge bg-{{ $severity }}-subtle text-{{ $severity }} border border-{{ $severity }}-subtle" style="font-size:11px">
                    {{ $severityLabel }}
                  </span>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="12" class="text-center text-body-secondary py-5">
                  <svg class="icon icon-3xl d-block mx-auto mb-2 text-success opacity-50">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use>
                  </svg>
                  Không có hàng đọng kho trong {{ $days }} ngày gần đây
                </td>
              </tr>
            @endforelse
          </tbody>
          @if ($items->count() > 0)
            <tfoot class="table-light fw-semibold">
              <tr>
                <td colspan="6" class="text-end">Tổng tồn đọng:</td>
                <td class="text-end">{{ number_format($summary['total_qty'], 0) }}</td>
                <td colspan="5"></td>
              </tr>
            </tfoot>
          @endif
        </table>
      </div>
    </div>
  </div>

@endsection