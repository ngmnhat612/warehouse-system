@extends('layouts.app')

@section('title', 'Báo cáo tổng hợp — Warehouse System')

@section('breadcrumb')
  <li class="breadcrumb-item">Báo cáo</li>
  <li class="breadcrumb-item active">Báo cáo tổng hợp</li>
@endsection

@section('content')

  {{-- HEADER --}}
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-0 fw-semibold">Báo cáo tổng hợp</h4>
      <small class="text-body-secondary">Thống kê nhập / xuất / tồn kho theo kỳ</small>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-success" onclick="exportExcel()">
        <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-spreadsheet') }}"></use></svg>
        Xuất Excel
      </button>
      <button class="btn btn-outline-danger" onclick="exportPdf()">
        <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-file') }}"></use></svg>
        Xuất PDF
      </button>
    </div>
  </div>

  {{-- BỘ LỌC --}}
  <div class="card mb-4">
    <div class="card-body">
      <form method="GET" action="{{ route('reports.index') }}" id="filterForm">
        <div class="row g-3 align-items-end">

          <div class="col-sm-6 col-lg-3">
            <label class="form-label fw-medium">Từ ngày</label>
            <input type="date" class="form-control" name="date_from"
                   value="{{ request('date_from', now()->startOfMonth()->toDateString()) }}">
          </div>

          <div class="col-sm-6 col-lg-3">
            <label class="form-label fw-medium">Đến ngày</label>
            <input type="date" class="form-control" name="date_to"
                   value="{{ request('date_to', now()->toDateString()) }}">
          </div>

          <div class="col-sm-6 col-lg-3">
            <label class="form-label fw-medium">Nhóm hàng</label>
            <select class="form-select" name="category_id">
              <option value="">Tất cả nhóm</option>
              @foreach ($categories ?? [] as $cat)
                <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                  {{ $cat->name }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="col-sm-6 col-lg-3">
            <label class="form-label fw-medium">Hàng hóa</label>
            <select class="form-select" name="product_id">
              <option value="">Tất cả hàng hóa</option>
              @foreach ($products ?? [] as $prod)
                <option value="{{ $prod->id }}" {{ request('product_id') == $prod->id ? 'selected' : '' }}>
                  {{ $prod->name }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-magnifying-glass') }}"></use></svg>
              Xem báo cáo
            </button>
            <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary">Xóa lọc</a>

            {{-- Nút tắt nhanh kỳ --}}
            <div class="ms-auto d-flex gap-2">
              <a href="{{ route('reports.index', ['date_from' => now()->toDateString(), 'date_to' => now()->toDateString()]) }}"
                 class="btn btn-sm btn-outline-secondary {{ (request('date_from') == now()->toDateString() && request('date_to') == now()->toDateString()) ? 'active' : '' }}">
                Hôm nay
              </a>
              <a href="{{ route('reports.index', ['date_from' => now()->startOfWeek()->toDateString(), 'date_to' => now()->toDateString()]) }}"
                 class="btn btn-sm btn-outline-secondary">
                Tuần này
              </a>
              <a href="{{ route('reports.index', ['date_from' => now()->startOfMonth()->toDateString(), 'date_to' => now()->toDateString()]) }}"
                 class="btn btn-sm btn-outline-secondary">
                Tháng này
              </a>
            </div>
          </div>

        </div>
      </form>
    </div>
  </div>

  {{-- CARDS TÓM TẮT KỲ --}}
  <div class="row g-3 mb-4">

    <div class="col-sm-6 col-xl-3">
      <div class="card border-start border-start-4 border-start-success h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="flex-shrink-0">
            <svg class="icon icon-2xl text-success">
              <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-thick-bottom') }}"></use>
            </svg>
          </div>
          <div>
            <div class="fs-4 fw-bold text-success">{{ number_format($totalReceiptQty ?? 0) }}</div>
            <div class="text-body-secondary small">Tổng nhập kỳ</div>
            <div class="small text-body-secondary">{{ number_format($totalReceiptVouchers ?? 0) }} phiếu</div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card border-start border-start-4 border-start-warning h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="flex-shrink-0">
            <svg class="icon icon-2xl text-warning">
              <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-thick-top') }}"></use>
            </svg>
          </div>
          <div>
            <div class="fs-4 fw-bold text-warning">{{ number_format($totalIssueQty ?? 0) }}</div>
            <div class="text-body-secondary small">Tổng xuất kỳ</div>
            <div class="small text-body-secondary">{{ number_format($totalIssueVouchers ?? 0) }} phiếu</div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card border-start border-start-4 border-start-primary h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="flex-shrink-0">
            <svg class="icon icon-2xl text-primary">
              <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-storage') }}"></use>
            </svg>
          </div>
          <div>
            <div class="fs-4 fw-bold text-primary">{{ number_format($closingStock ?? 0) }}</div>
            <div class="text-body-secondary small">Tồn cuối kỳ</div>
            <div class="small {{ ($closingStock ?? 0) >= ($openingStock ?? 0) ? 'text-success' : 'text-danger' }}">
              @if (($closingStock ?? 0) >= ($openingStock ?? 0))
                <svg class="icon icon-sm"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-top') }}"></use></svg>
                Tăng {{ number_format(($closingStock ?? 0) - ($openingStock ?? 0)) }}
              @else
                <svg class="icon icon-sm"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-bottom') }}"></use></svg>
                Giảm {{ number_format(($openingStock ?? 0) - ($closingStock ?? 0)) }}
              @endif
              so đầu kỳ
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card border-start border-start-4 border-start-danger h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="flex-shrink-0">
            <svg class="icon icon-2xl text-danger">
              <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use>
            </svg>
          </div>
          <div>
            <div class="fs-4 fw-bold text-danger">{{ number_format($lowStockCount ?? 0) }}</div>
            <div class="text-body-secondary small">Hàng dưới ngưỡng</div>
            <div class="small text-body-secondary">{{ number_format($expiringSoonCount ?? 0) }} sắp hết hạn</div>
          </div>
        </div>
      </div>
    </div>

  </div>
  {{-- /.cards --}}

  {{-- BIỂU ĐỒ NHẬP / XUẤT THEO NGÀY --}}
  <div class="row g-3 mb-4">
    <div class="col-lg-8">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span class="fw-semibold">Biểu đồ nhập / xuất theo ngày</span>
          <div class="d-flex gap-1">
            <button class="btn btn-sm btn-outline-secondary active" id="btnBar" onclick="switchChart('bar')">Cột</button>
            <button class="btn btn-sm btn-outline-secondary" id="btnLine" onclick="switchChart('line')">Đường</button>
          </div>
        </div>
        <div class="card-body">
          <div style="height: 280px;">
            <canvas id="dailyChart"></canvas>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card h-100">
        <div class="card-header">
          <span class="fw-semibold">Tỷ lệ xuất theo mục đích</span>
        </div>
        <div class="card-body d-flex align-items-center justify-content-center">
          <div style="height: 240px; width: 100%;">
            <canvas id="purposeChart"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- BẢNG TỔNG HỢP NHẬP XUẤT TỒN --}}
  <div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span class="fw-semibold">Bảng tổng hợp Nhập / Xuất / Tồn theo mặt hàng</span>
      <small class="text-body-secondary">
        Kỳ: {{ request('date_from', now()->startOfMonth()->format('d/m/Y')) }}
        — {{ request('date_to', now()->format('d/m/Y')) }}
      </small>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="summaryTable">
          <thead class="table-light">
            <tr>
              <th class="text-center" style="width:55px">#</th>
              <th style="min-width:100px">Mã hàng</th>
              <th style="min-width:200px">Tên hàng hóa</th>
              <th style="width:110px">Nhóm</th>
              <th style="width:70px">ĐVT</th>
              <th class="text-end" style="width:110px">Tồn đầu kỳ</th>
              <th class="text-end" style="width:100px">
                <span class="text-success">Nhập kỳ</span>
              </th>
              <th class="text-end" style="width:100px">
                <span class="text-warning">Xuất kỳ</span>
              </th>
              <th class="text-end" style="width:110px">
                <span class="text-primary fw-semibold">Tồn cuối kỳ</span>
              </th>
              <th class="text-center" style="width:90px">Trạng thái</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($reportRows ?? [] as $index => $row)
              <tr>
                <td class="text-center text-body-secondary">{{ $index + 1 }}</td>
                <td><code class="text-primary fw-medium">{{ $row->product_code }}</code></td>
                <td>
                  <div class="fw-medium">{{ $row->product_name }}</div>
                </td>
                <td class="small text-body-secondary">{{ $row->category_name ?? '—' }}</td>
                <td class="small">{{ $row->uom_name ?? '—' }}</td>
                <td class="text-end">{{ number_format($row->opening_qty, 0) }}</td>
                <td class="text-end text-success fw-medium">
                  @if ($row->receipt_qty > 0)
                    +{{ number_format($row->receipt_qty, 0) }}
                  @else
                    <span class="text-body-secondary">—</span>
                  @endif
                </td>
                <td class="text-end text-warning fw-medium">
                  @if ($row->issue_qty > 0)
                    -{{ number_format($row->issue_qty, 0) }}
                  @else
                    <span class="text-body-secondary">—</span>
                  @endif
                </td>
                <td class="text-end fw-bold text-primary">{{ number_format($row->closing_qty, 0) }}</td>
                <td class="text-center">
                  @if (($row->closing_qty ?? 0) <= ($row->min_stock ?? 0) && ($row->min_stock ?? 0) > 0)
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size:11px">Dưới ngưỡng</span>
                  @elseif (($row->closing_qty ?? 0) == 0)
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" style="font-size:11px">Hết hàng</span>
                  @else
                    <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:11px">Bình thường</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="10" class="text-center text-body-secondary py-5">
                  <svg class="icon icon-3xl d-block mx-auto mb-2 opacity-25">
                    <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-chart-pie') }}"></use>
                  </svg>
                  Không có dữ liệu trong kỳ đã chọn
                </td>
              </tr>
            @endforelse
          </tbody>
          @if (count($reportRows ?? []) > 0)
            <tfoot class="table-light fw-semibold">
              <tr>
                <td colspan="5" class="text-end">Tổng cộng:</td>
                <td class="text-end">{{ number_format(collect($reportRows ?? [])->sum('opening_qty'), 0) }}</td>
                <td class="text-end text-success">+{{ number_format(collect($reportRows ?? [])->sum('receipt_qty'), 0) }}</td>
                <td class="text-end text-warning">-{{ number_format(collect($reportRows ?? [])->sum('issue_qty'), 0) }}</td>
                <td class="text-end text-primary">{{ number_format(collect($reportRows ?? [])->sum('closing_qty'), 0) }}</td>
                <td></td>
              </tr>
            </tfoot>
          @endif
        </table>
      </div>
    </div>

    @if (isset($reportRows) && method_exists($reportRows, 'hasPages') && $reportRows->hasPages())
      <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-body-secondary">
          Hiển thị {{ $reportRows->firstItem() }}–{{ $reportRows->lastItem() }}
          trong tổng số {{ $reportRows->total() }} mặt hàng
        </small>
        {{ $reportRows->appends(request()->query())->links('pagination::bootstrap-5') }}
      </div>
    @endif
  </div>

  {{-- BẢNG CẢNH BÁO --}}
  <div class="row g-3">

    {{-- Hàng dưới ngưỡng tối thiểu --}}
    <div class="col-lg-6">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span class="fw-semibold text-danger">
            <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use></svg>
            Hàng dưới ngưỡng tối thiểu
          </span>
          <span class="badge bg-danger">{{ count($lowStockItems ?? []) }}</span>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive" style="max-height:280px; overflow-y:auto;">
            <table class="table table-sm table-hover mb-0">
              <thead class="table-light sticky-top">
                <tr>
                  <th>Mặt hàng</th>
                  <th style="width:70px" class="text-center">Vị trí</th>
                  <th class="text-end" style="width:70px">Tồn KD</th>
                  <th class="text-end" style="width:55px">Min</th>
                  <th class="text-end" style="width:65px">Thiếu</th>
                  <th class="text-center" style="width:75px">Mức độ</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($lowStockItems ?? [] as $item)
                  @php
                    $pct      = $item->min_stock > 0 ? ($item->current_qty / $item->min_stock * 100) : 0;
                    $severity = $item->current_qty <= 0 ? 'danger'
                              : ($pct <= 50 ? 'warning' : 'info');
                    $label    = $item->current_qty <= 0 ? 'Hết hàng'
                              : ($pct <= 50 ? 'Nguy hiểm' : 'Chú ý');
                  @endphp
                  <tr>
                    <td>
                      <div class="fw-medium small">{{ $item->name }}</div>
                      <div class="text-body-secondary" style="font-size:11px">{{ $item->code }}</div>
                    </td>
                    <td class="text-center">
                      <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" style="font-size:10px">
                        {{ $item->location_code ?? '—' }}
                      </span>
                    </td>
                    <td class="text-end fw-semibold text-danger small">{{ number_format($item->current_qty, 0) }}</td>
                    <td class="text-end text-body-secondary small">{{ number_format($item->min_stock, 0) }}</td>
                    <td class="text-end">
                      <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size:10px">
                        -{{ number_format($item->shortage_qty, 0) }}
                      </span>
                    </td>
                    <td class="text-center">
                      <span class="badge bg-{{ $severity }}-subtle text-{{ $severity }} border border-{{ $severity }}-subtle" style="font-size:10px">
                        {{ $label }}
                      </span>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="6" class="text-center text-body-secondary py-4">
                      <svg class="icon text-success">
                        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use>
                      </svg>
                      Không có hàng nào dưới ngưỡng
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
        <div class="card-footer py-2 text-end">
          <a href="{{ route('reports.alerts.below_min') }}" class="btn btn-sm btn-outline-danger">
            Xem đầy đủ →
          </a>
        </div>
      </div>
    </div>

    {{-- Hàng sắp hết hạn --}}
    <div class="col-lg-6">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span class="fw-semibold text-warning">
            <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-clock') }}"></use></svg>
            Hàng sắp hết hạn (trong 30 ngày)
          </span>
          <span class="badge bg-warning text-dark">{{ count($expiringSoonItems ?? []) }}</span>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive" style="max-height:280px; overflow-y:auto;">
            <table class="table table-sm table-hover mb-0">
              <thead class="table-light sticky-top">
                <tr>
                  <th>Mặt hàng / Lot</th>
                  <th style="width:70px" class="text-center">Vị trí</th>
                  <th class="text-end" style="width:80px">Số lượng</th>
                  <th class="text-center" style="width:90px">Hết hạn</th>
                  <th class="text-center" style="width:95px">Còn lại</th>
                  <th class="text-center" style="width:80px">Mức độ</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($expiringSoonItems ?? [] as $item)
                  @php
                    $daysLeft = (int) now()->diffInDays(\Carbon\Carbon::parse($item->expiry_date), false);

                    if ($daysLeft < 0) {
                        $severity  = 'dark';
                        $label     = 'Đã hết hạn';
                        $rowClass  = 'table-secondary opacity-75';
                        $remaining = 'Quá ' . abs($daysLeft) . ' ngày';
                        $remColor  = 'text-secondary';
                    } elseif ($daysLeft <= 7) {
                        $severity  = 'danger';
                        $label     = 'Khẩn cấp';
                        $rowClass  = 'table-danger bg-opacity-25';
                        $remaining = $daysLeft . ' ngày';
                        $remColor  = 'text-danger fw-bold';
                    } elseif ($daysLeft <= 14) {
                        $severity  = 'warning';
                        $label     = 'Gấp';
                        $rowClass  = '';
                        $remaining = $daysLeft . ' ngày';
                        $remColor  = 'text-warning fw-bold';
                    } else {
                        $severity  = 'info';
                        $label     = 'Chú ý';
                        $rowClass  = '';
                        $remaining = $daysLeft . ' ngày';
                        $remColor  = 'text-info';
                    }
                  @endphp
                  <tr class="{{ $rowClass }}">
                    <td>
                      <div class="fw-medium small">{{ $item->product_name }}</div>
                      @if (!empty($item->lot_number))
                        <div style="font-size:11px">
                          <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                            {{ $item->lot_number }}
                          </span>
                        </div>
                      @endif
                    </td>
                    <td class="text-center">
                      @if (!empty($item->location_code))
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" style="font-size:11px">
                          {{ $item->location_code }}
                        </span>
                      @else
                        <span class="text-body-secondary small">—</span>
                      @endif
                    </td>
                    <td class="text-end fw-semibold">{{ number_format($item->quantity, 0) }}</td>
                    <td class="text-center small">
                      {{ \Carbon\Carbon::parse($item->expiry_date)->format('d/m/Y') }}
                    </td>
                    <td class="text-center small {{ $remColor }}">{{ $remaining }}</td>
                    <td class="text-center">
                      <span class="badge bg-{{ $severity }}-subtle text-{{ $severity }} border border-{{ $severity }}-subtle" style="font-size:11px">
                        {{ $label }}
                      </span>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="6" class="text-center text-body-secondary py-4">
                      <svg class="icon text-success">
                        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use>
                      </svg>
                      Không có hàng sắp hết hạn
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>

        {{-- Link xem đầy đủ --}}
        <div class="card-footer py-2 text-end">
          <a href="{{ route('reports.alerts.near_expiry') }}" class="btn btn-sm btn-outline-warning">
            Xem đầy đủ →
          </a>
        </div>

      </div>
    </div>

  </div>
  {{-- /.row cảnh báo --}}

@endsection

@push('scripts')
<script>
  // ── Dữ liệu từ Controller ──────────────────────────────────────────
  const chartLabels   = @json($chartLabels ?? []);
  const chartReceipts = @json($chartReceipts ?? []);
  const chartIssues   = @json($chartIssues ?? []);
  const purposeLabels = @json($purposeLabels ?? []);
  const purposeData   = @json($purposeData ?? []);

  // ── Biểu đồ nhập / xuất theo ngày ─────────────────────────────────
  const dailyCtx = document.getElementById('dailyChart');
  let dailyChart;

  function buildDailyChart(type) {
    if (dailyChart) dailyChart.destroy();
    dailyChart = new Chart(dailyCtx, {
      type: type,
      data: {
        labels: chartLabels,
        datasets: [
          {
            label: 'Nhập kho',
            data: chartReceipts,
            backgroundColor: type === 'bar' ? 'rgba(32,201,151,0.7)' : 'rgba(32,201,151,0.1)',
            borderColor: '#20c997',
            borderWidth: 2,
            borderRadius: type === 'bar' ? 4 : 0,
            fill: type === 'line',
            tension: 0.4,
          },
          {
            label: 'Xuất kho',
            data: chartIssues,
            backgroundColor: type === 'bar' ? 'rgba(255,193,7,0.7)' : 'rgba(255,193,7,0.1)',
            borderColor: '#ffc107',
            borderWidth: 2,
            borderRadius: type === 'bar' ? 4 : 0,
            fill: type === 'line',
            tension: 0.4,
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'top' },
          tooltip: {
            callbacks: {
              label: ctx => ' ' + ctx.dataset.label + ': ' + ctx.parsed.y.toLocaleString('vi-VN')
            }
          }
        },
        scales: {
          y: { beginAtZero: true, ticks: { precision: 0 } }
        }
      }
    });
  }

  function switchChart(type) {
    buildDailyChart(type);
    document.getElementById('btnBar').classList.toggle('active', type === 'bar');
    document.getElementById('btnLine').classList.toggle('active', type === 'line');
  }

  buildDailyChart('bar');

  // ── Biểu đồ tỷ lệ xuất ────────────────────────────────────────────
  const purposeCtx = document.getElementById('purposeChart');
  if (purposeCtx) {
    new Chart(purposeCtx, {
      type: 'doughnut',
      data: {
        labels: purposeLabels,
        datasets: [{
          data: purposeData,
          backgroundColor: ['#321fdb','#0d6efd','#3399ff','#6f42c1'],
          borderWidth: 1,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'bottom', labels: { padding: 16, font: { size: 12 } } }
        },
        cutout: '65%',
      }
    });
  }

  // ── Xuất Excel / PDF (placeholder — route thực tế từ ReportController) ─
  function exportExcel() {
    const params = new URLSearchParams(document.getElementById('filterForm').querySelectorAll('[name]')
      .let ? [] : [...document.getElementById('filterForm').querySelectorAll('[name]')]
        .map(el => [el.name, el.value])
        .filter(([, v]) => v));
    window.location.href = '{{ route('reports.export.excel') }}?' + new URLSearchParams(
      Object.fromEntries(
        [...document.getElementById('filterForm').querySelectorAll('[name]')]
          .map(el => [el.name, el.value])
          .filter(([, v]) => v)
      )
    ).toString();
  }

  function exportPdf() {
    window.location.href = '{{ route('reports.export.pdf') }}?' + new URLSearchParams(
      Object.fromEntries(
        [...document.getElementById('filterForm').querySelectorAll('[name]')]
          .map(el => [el.name, el.value])
          .filter(([, v]) => v)
      )
    ).toString();
  }
</script>
@endpush