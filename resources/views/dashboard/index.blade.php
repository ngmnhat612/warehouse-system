@extends('layouts.app')

@section('title', 'Dashboard — Warehouse System')

@section('breadcrumb')
  <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')

  {{-- ===== ROW 1: CARDS KPI ===== --}}
  <div class="row g-4 mb-4">

    <div class="col-sm-6 col-xl-3">
      <div class="card text-white bg-primary">
        <div class="card-body pb-0 d-flex justify-content-between align-items-start">
          <div>
            <div class="fs-4 fw-bold text-value-lg">{{ number_format($totalSkus ?? 0) }}</div>
            <div>Tổng SKU</div>
          </div>
          <svg class="icon icon-2xl text-white opacity-75">
            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-tags') }}"></use>
          </svg>
        </div>
        <div class="mt-3 px-3 pb-2">
          <small class="text-white opacity-75">Mặt hàng đang quản lý</small>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card text-white bg-success">
        <div class="card-body pb-0 d-flex justify-content-between align-items-start">
          <div>
            <div class="fs-4 fw-bold text-value-lg">{{ number_format($totalStock ?? 0) }}</div>
            <div>Tổng tồn kho</div>
          </div>
          <svg class="icon icon-2xl text-white opacity-75">
            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-storage') }}"></use>
          </svg>
        </div>
        <div class="mt-3 px-3 pb-2">
          <small class="text-white opacity-75">Tổng số lượng hiện tại</small>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card text-white bg-info">
        <div class="card-body pb-0 d-flex justify-content-between align-items-start">
          <div>
            <div class="fs-4 fw-bold text-value-lg">{{ number_format($pendingReceipts ?? 0) }}</div>
            <div>Phiếu nhập chờ duyệt</div>
          </div>
          <svg class="icon icon-2xl text-white opacity-75">
            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-thick-bottom') }}"></use>
          </svg>
        </div>
        <div class="mt-3 px-3 pb-2">
          <small>
            <a href="{{ route('receipts.index') }}" class="text-white opacity-75">Xem danh sách</a>
          </small>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card text-white bg-warning">
        <div class="card-body pb-0 d-flex justify-content-between align-items-start">
          <div>
            <div class="fs-4 fw-bold text-value-lg">{{ number_format($pendingIssues ?? 0) }}</div>
            <div>Phiếu xuất chờ duyệt</div>
          </div>
          <svg class="icon icon-2xl text-white opacity-75">
            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-thick-top') }}"></use>
          </svg>
        </div>
        <div class="mt-3 px-3 pb-2">
          <small>
            <a href="{{ route('issues.index') }}" class="text-white opacity-75">Xem danh sách</a>
          </small>
        </div>
      </div>
    </div>

  </div>{{-- /.row KPI --}}


  {{-- ===== ROW 2: CHART ===== --}}
  <div class="row g-4 mb-4">
    <div class="col-12">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <span class="fw-semibold">Biểu đồ nhập / xuất kho</span>
            <small class="text-body-secondary ms-2">30 ngày gần nhất</small>
          </div>
          <a href="{{ route('inventory.ledger') }}" class="btn btn-sm btn-outline-primary">Xem chi tiết</a>
        </div>
        <div class="card-body">
          <div style="height:260px">
            <canvas id="mainChart"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>{{-- /.row chart --}}


  {{-- ===== ROW 3: CẢNH BÁO ===== --}}
  <div class="row g-4 mb-4">

    {{-- Hàng dưới mức tồn tối thiểu --}}
    <div class="col-lg-6">
      <div class="card h-100 border-danger border-opacity-25">
        <div class="card-header d-flex align-items-center gap-2">
          <svg class="icon text-danger"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use></svg>
          <span class="fw-semibold">Hàng dưới mức tối thiểu</span>
          @if(($lowStockItems ?? collect())->count())
            <span class="badge bg-danger">{{ $lowStockItems->count() }}</span>
          @endif
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Mặt hàng</th>
                  <th>Vị trí</th>
                  <th class="text-end">Tồn KD</th>
                  <th class="text-end">Tối thiểu</th>
                  <th class="text-end text-danger">Thiếu</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($lowStockItems ?? [] as $item)
                  <tr>
                    <td>
                      <div class="fw-medium small">{{ $item->product_name }}</div>
                      <div class="text-body-secondary" style="font-size:11px">{{ $item->product_code }}</div>
                    </td>
                    <td><code class="small text-secondary">{{ $item->location_code }}</code></td>
                    <td class="text-end text-danger fw-semibold small">
                      {{ number_format($item->current_stock, 0) }}
                      <span class="text-body-secondary fw-normal">{{ $item->uom_name }}</span>
                    </td>
                    <td class="text-end text-body-secondary small">{{ number_format($item->min_qty, 0) }}</td>
                    <td class="text-end fw-semibold text-danger small">−{{ number_format($item->shortage, 0) }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="5" class="text-center text-body-secondary py-4">
                      <svg class="icon icon-2xl d-block mx-auto mb-1 text-success opacity-50">
                        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use>
                      </svg>
                      Tất cả mặt hàng đều đủ tồn kho
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    {{-- Lô hàng sắp hết hạn --}}
    <div class="col-lg-6">
      <div class="card h-100 border-warning border-opacity-25">
        <div class="card-header d-flex align-items-center gap-2">
          <svg class="icon text-warning"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-clock') }}"></use></svg>
          <span class="fw-semibold">Lô hàng sắp hết hạn</span>
          @if(($expiringLots ?? collect())->count())
            <span class="badge bg-warning text-dark">{{ $expiringLots->count() }}</span>
          @endif
          <small class="text-body-secondary ms-1">(30 ngày tới)</small>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Mặt hàng / Lô</th>
                  <th class="text-end">Tồn lô</th>
                  <th class="text-end">Hết hạn</th>
                  <th class="text-center">Còn lại</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($expiringLots ?? [] as $lot)
                  @php
                    $days    = (int) $lot->days_remaining;
                    $urgency = $days <= 7 ? 'danger' : ($days <= 14 ? 'warning' : 'secondary');
                  @endphp
                  <tr>
                    <td>
                      <div class="fw-medium small">{{ $lot->product_name }}</div>
                      <code class="text-body-secondary" style="font-size:11px">{{ $lot->lot_number }}</code>
                    </td>
                    <td class="text-end small">
                      {{ number_format($lot->current_qty, 0) }}
                      <span class="text-body-secondary">{{ $lot->uom_name }}</span>
                    </td>
                    <td class="text-end small text-body-secondary">
                      {{ \Carbon\Carbon::parse($lot->expiry_date)->format('d/m/Y') }}
                    </td>
                    <td class="text-center">
                      <span class="badge bg-{{ $urgency }}-subtle text-{{ $urgency }}-emphasis border border-{{ $urgency }}-subtle rounded-pill" style="font-size:11px">
                        {{ $days }} ngày
                      </span>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="4" class="text-center text-body-secondary py-4">
                      <svg class="icon icon-2xl d-block mx-auto mb-1 text-success opacity-50">
                        <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use>
                      </svg>
                      Không có lô nào sắp hết hạn
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div>{{-- /.row cảnh báo --}}


  {{-- ===== ROW 4: 10 GIAO DỊCH MỚI NHẤT ===== --}}
  <div class="row g-4">
    <div class="col-12">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span class="fw-semibold">10 giao dịch kho mới nhất</span>
          <a href="{{ route('inventory.ledger') }}" class="btn btn-sm btn-outline-secondary">Xem tất cả</a>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th class="text-nowrap">Thời gian</th>
                  <th>Loại GD</th>
                  <th>Mã phiếu</th>
                  <th>Mặt hàng</th>
                  <th>Vị trí</th>
                  <th class="text-end">Số lượng</th>
                  <th class="text-end">Tồn sau</th>
                  <th>Người thực hiện</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($recentTransactions ?? [] as $row)
                @php
                  $typeMap = [
                    'RECEIPT'   => ['Nhập kho',   'success'],
                    'ISSUE'     => ['Xuất kho',   'warning'],
                    'TRANSFER'  => ['Chuyển kho', 'info'],
                    'SCRAP'     => ['Hủy hàng',   'danger'],
                    'ADJUST'    => ['Điều chỉnh', 'secondary'],
                    'TRANSFORM' => ['Tách/Ghép',  'primary'],
                    'RETURN'    => ['Trả hàng',   'info'],
                  ];
                  [$typeText, $typeColor] = $typeMap[$row->transaction_type] ?? [$row->transaction_type, 'secondary'];
                  $isIn = (int) $row->direction === 1;
                @endphp
                <tr>
                  <td class="text-nowrap small text-body-secondary">
                    {{ \Carbon\Carbon::parse($row->transaction_date)->format('d/m H:i') }}
                  </td>
                  <td>
                    <span class="badge bg-{{ $typeColor }}-subtle text-{{ $typeColor }}-emphasis border border-{{ $typeColor }}-subtle rounded-pill" style="font-size:11px">
                      {{ $typeText }}
                    </span>
                  </td>
                  <td><code class="small text-body-secondary">{{ $row->reference_code ?? '—' }}</code></td>
                  <td>
                    <div class="small fw-medium">{{ $row->product_name }}</div>
                    <div class="text-body-secondary" style="font-size:11px">{{ $row->product_code }}</div>
                  </td>
                  <td class="small"><code class="text-secondary">{{ $row->location_code }}</code></td>
                  <td class="text-end fw-semibold {{ $isIn ? 'text-success' : 'text-warning' }}">
                    {{ $isIn ? '+' : '−' }}{{ number_format((float) $row->quantity, 0) }}
                    <span class="text-body-secondary small fw-normal">{{ $row->uom_name }}</span>
                  </td>
                  <td class="text-end text-body-secondary small font-monospace">
                    {{ number_format((float) $row->balance_after, 0) }}
                  </td>
                  <td class="small text-body-secondary">{{ $row->created_by_name ?? '—' }}</td>
                </tr>
                @empty
                <tr>
                  <td colspan="8" class="text-center text-body-secondary py-5">Chưa có giao dịch nào</td>
                </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>{{-- /.row recent transactions --}}

@endsection

@push('scripts')
<script>
  const labels     = @json($chartLabels     ?? []);
  const receipts   = @json($chartReceipts   ?? []);
  const deliveries = @json($chartDeliveries ?? []);

  const ctx = document.getElementById('mainChart');
  if (ctx && typeof Chart !== 'undefined') {
    new Chart(ctx, {
      type: 'line',
      data: {
        labels,
        datasets: [
          {
            label: 'Nhập kho',
            data: receipts,
            borderColor: '#0d6efd',
            backgroundColor: 'rgba(13,110,253,0.08)',
            fill: true,
            tension: 0.4,
            pointRadius: 3,
            pointHoverRadius: 6,
          },
          {
            label: 'Xuất kho',
            data: deliveries,
            borderColor: '#ffc107',
            backgroundColor: 'rgba(255,193,7,0.08)',
            fill: true,
            tension: 0.4,
            pointRadius: 3,
            pointHoverRadius: 6,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
          legend: { position: 'top' },
          tooltip: {
            callbacks: {
              label: c => ` ${c.dataset.label}: ${new Intl.NumberFormat('vi-VN').format(c.parsed.y)}`
            }
          }
        },
        scales: {
          y: { beginAtZero: true, ticks: { precision: 0 } },
          x: { grid: { display: false } },
        },
      },
    });
  }
</script>
@endpush