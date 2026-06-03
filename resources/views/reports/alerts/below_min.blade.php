@extends('layouts.app')

@section('title', 'Cảnh báo dưới định mức — Warehouse System')

@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">Báo cáo</a></li>
  <li class="breadcrumb-item active">Dưới định mức</li>
@endsection

@section('content')

  {{-- HEADER --}}
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-0 fw-semibold text-danger">
        <svg class="icon me-2"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use></svg>
        Hàng dưới định mức tối thiểu
      </h4>
      <small class="text-body-secondary">Các mặt hàng có tồn khả dụng thấp hơn ngưỡng reorder rule</small>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('reports.alerts.below_min.excel', request()->query()) }}" class="btn btn-outline-success btn-sm">
        <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-spreadsheet') }}"></use></svg>
        Xuất Excel
      </a>
      <a href="{{ route('reports.alerts.below_min.pdf', request()->query()) }}" class="btn btn-outline-danger btn-sm">
        <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-file') }}"></use></svg>
        Xuất PDF
      </a>
    </div>
  </div>

  {{-- FILTER --}}
  <div class="card mb-4">
    <div class="card-body">
      <form method="GET" action="{{ route('reports.alerts.below_min') }}">
        <div class="row g-3 align-items-end">
          <div class="col-sm-6 col-lg-4">
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
          <div class="col-sm-6 col-lg-4">
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
          <div class="col-sm-12 col-lg-4 d-flex gap-2">
            <button type="submit" class="btn btn-danger">
              <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-magnifying-glass') }}"></use></svg>
              Xem báo cáo
            </button>
            <a href="{{ route('reports.alerts.below_min') }}" class="btn btn-outline-secondary">Xóa lọc</a>
          </div>
        </div>
      </form>
    </div>
  </div>

  {{-- KPI CARDS --}}
  <div class="row g-3 mb-4">
    <div class="col-sm-4">
      <div class="card border-start border-start-4 border-start-danger h-100">
        <div class="card-body">
          <div class="fs-3 fw-bold text-danger">{{ number_format($summary['total']) }}</div>
          <div class="text-body-secondary small">Tổng mặt hàng dưới ngưỡng</div>
        </div>
      </div>
    </div>
    <div class="col-sm-4">
      <div class="card border-start border-start-4 border-start-dark h-100">
        <div class="card-body">
          <div class="fs-3 fw-bold">{{ number_format($summary['zero_stock']) }}</div>
          <div class="text-body-secondary small">Mặt hàng hết sạch tồn kho</div>
        </div>
      </div>
    </div>
    <div class="col-sm-4">
      <div class="card border-start border-start-4 border-start-warning h-100">
        <div class="card-body">
          <div class="fs-3 fw-bold text-warning">{{ number_format($summary['total_shortage'], 0) }}</div>
          <div class="text-body-secondary small">Tổng số lượng còn thiếu</div>
        </div>
      </div>
    </div>
  </div>

  {{-- TABLE --}}
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span class="fw-semibold">Danh sách hàng dưới định mức</span>
      <span class="badge bg-danger">{{ $summary['total'] }} mặt hàng</span>
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
              <th class="text-end" style="width:110px">Tồn khả dụng</th>
              <th class="text-end" style="width:90px">Min</th>
              <th class="text-end" style="width:90px">Max</th>
              <th class="text-end" style="width:100px">Còn thiếu</th>
              <th class="text-end" style="width:110px">Cần đặt thêm</th>
              <th class="text-center" style="width:100px">Mức độ</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($items as $i => $row)
              @php
                $pct       = $row->min_qty > 0 ? ($row->current_qty / $row->min_qty * 100) : 0;
                $severity  = $row->current_qty <= 0 ? 'danger'
                           : ($pct <= 50 ? 'warning' : 'info');
                $severityLabel = $row->current_qty <= 0 ? 'Hết hàng'
                               : ($pct <= 50 ? 'Nguy hiểm' : 'Chú ý');
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
                <td class="text-end fw-bold text-danger">{{ number_format($row->current_qty, 0) }}</td>
                <td class="text-end text-body-secondary">{{ number_format($row->min_qty, 0) }}</td>
                <td class="text-end text-body-secondary">{{ $row->max_qty ? number_format($row->max_qty, 0) : '—' }}</td>
                <td class="text-end">
                  <span class="text-danger fw-semibold">-{{ number_format($row->shortage_qty, 0) }}</span>
                </td>
                <td class="text-end">
                  @if ($row->order_qty !== null && $row->order_qty > 0)
                    <span class="text-primary fw-semibold">{{ number_format($row->order_qty, 0) }}</span>
                  @else
                    <span class="text-body-secondary">—</span>
                  @endif
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
                  Không có mặt hàng nào dưới định mức
                </td>
              </tr>
            @endforelse
          </tbody>
          @if ($items->count() > 0)
            <tfoot class="table-light fw-semibold">
              <tr>
                <td colspan="9" class="text-end">Tổng thiếu:</td>
                <td class="text-end text-danger">-{{ number_format($items->sum('shortage_qty'), 0) }}</td>
                <td colspan="2"></td>
              </tr>
            </tfoot>
          @endif
        </table>
      </div>
    </div>
  </div>

@endsection