@extends('layouts.app')

@section('title', 'Dashboard — Warehouse System')

@section('breadcrumb')
  <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')

  {{-- ===== ROW 1: CARDS THỐNG KÊ ===== --}}
  <div class="row g-4 mb-4">

    <div class="col-sm-6 col-xl-3">
      <div class="card text-white bg-primary">
        <div class="card-body pb-0 d-flex justify-content-between align-items-start">
          <div>
            <div class="fs-4 fw-semibold">{{ number_format($totalProducts ?? 0) }}</div>
            <div>Mặt hàng</div>
          </div>
          <svg class="icon icon-2xl text-white opacity-50">
            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-tags') }}"></use>
          </svg>
        </div>
        <div class="mt-3 px-3 pb-2">
          <small class="opacity-75">Tổng mặt hàng đang quản lý</small>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card text-white bg-info">
        <div class="card-body pb-0 d-flex justify-content-between align-items-start">
          <div>
            <div class="fs-4 fw-semibold">{{ number_format($todayReceipts ?? 0) }}</div>
            <div>Phiếu nhập hôm nay</div>
          </div>
          <svg class="icon icon-2xl text-white opacity-50">
            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-thick-bottom') }}"></use>
          </svg>
        </div>
        <div class="mt-3 px-3 pb-2">
          <small class="opacity-75">Số phiếu nhập kho trong ngày</small>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card text-white bg-warning">
        <div class="card-body pb-0 d-flex justify-content-between align-items-start">
          <div>
            <div class="fs-4 fw-semibold">{{ number_format($todayDeliveries ?? 0) }}</div>
            <div>Phiếu xuất hôm nay</div>
          </div>
          <svg class="icon icon-2xl text-white opacity-50">
            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-thick-top') }}"></use>
          </svg>
        </div>
        <div class="mt-3 px-3 pb-2">
          <small class="opacity-75">Số phiếu xuất kho trong ngày</small>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card text-white bg-danger">
        <div class="card-body pb-0 d-flex justify-content-between align-items-start">
          <div>
            <div class="fs-4 fw-semibold">{{ number_format($lowStockCount ?? 0) }}</div>
            <div>Hàng sắp hết</div>
          </div>
          <svg class="icon icon-2xl text-white opacity-50">
            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use>
          </svg>
        </div>
        <div class="mt-3 px-3 pb-2">
          <small class="opacity-75">Tồn kho dưới mức tối thiểu</small>
        </div>
      </div>
    </div>

  </div>
  {{-- /.row cards --}}

  {{-- ===== ROW 2: CHART NHẬP XUẤT 30 NGÀY ===== --}}
  <div class="row g-4 mb-4">
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
              <h5 class="card-title mb-0">Biểu đồ nhập xuất kho</h5>
              <small class="text-body-secondary">30 ngày gần nhất</small>
            </div>
          </div>
          <div style="height: 280px;">
            <canvas id="mainChart"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>
  {{-- /.row chart --}}

  {{-- ===== ROW 3: BẢNG PHIẾU GẦN NHẤT + HÀNG SẮP HẾT ===== --}}
  <div class="row g-4">

    {{-- Phiếu nhập/xuất gần nhất --}}
    <div class="col-lg-7">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span class="fw-semibold">Phiếu gần nhất</span>
          <a href="#" class="btn btn-sm btn-outline-primary">Xem tất cả</a>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th>Mã phiếu</th>
                  <th>Loại</th>
                  <th>Kho</th>
                  <th>Ngày</th>
                  <th class="text-center">Trạng thái</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($recentVouchers ?? [] as $voucher)
                  <tr>
                    <td><a href="#">{{ $voucher->code }}</a></td>
                    <td>{{ $voucher->type }}</td>
                    <td>{{ $voucher->warehouse }}</td>
                    <td>{{ $voucher->date }}</td>
                    <td class="text-center">
                      <span class="badge bg-success">Hoàn thành</span>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="5" class="text-center text-body-secondary py-4">
                      Chưa có phiếu nào
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    {{-- Hàng sắp hết tồn --}}
    <div class="col-lg-5">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span class="fw-semibold">Hàng sắp hết tồn</span>
          <a href="#" class="btn btn-sm btn-outline-danger">Xem tất cả</a>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th>Mặt hàng</th>
                  <th class="text-end">Tồn kho</th>
                  <th class="text-end">Tối thiểu</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($lowStockItems ?? [] as $item)
                  <tr>
                    <td>{{ $item->name }}</td>
                    <td class="text-end text-danger fw-semibold">{{ $item->stock }}</td>
                    <td class="text-end">{{ $item->min_stock }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="3" class="text-center text-body-secondary py-4">
                      Không có hàng nào sắp hết
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div>
  {{-- /.row tables --}}

@endsection

@push('scripts')
<script>
  // Biểu đồ nhập xuất 30 ngày — dữ liệu sẽ được inject từ Controller
  const labels = @json($chartLabels ?? []);
  const receipts = @json($chartReceipts ?? []);
  const deliveries = @json($chartDeliveries ?? []);

  const ctx = document.getElementById('mainChart');
  if (ctx) {
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [
          {
            label: 'Nhập kho',
            data: receipts,
            borderColor: '#0d6efd',
            backgroundColor: 'rgba(13,110,253,0.1)',
            fill: true,
            tension: 0.4,
          },
          {
            label: 'Xuất kho',
            data: deliveries,
            borderColor: '#ffc107',
            backgroundColor: 'rgba(255,193,7,0.1)',
            fill: true,
            tension: 0.4,
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'top' } },
        scales: {
          y: { beginAtZero: true, ticks: { precision: 0 } }
        }
      }
    });
  }
</script>
@endpush
