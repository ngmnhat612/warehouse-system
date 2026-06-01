@extends('layouts.app')

@section('title', 'Tồn kho hiện tại — Warehouse System')

@section('breadcrumb')
  <li class="breadcrumb-item">Tồn kho</li>
  <li class="breadcrumb-item active">Tồn kho hiện tại</li>
@endsection

@section('content')

  {{-- HEADER --}}
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-0 fw-semibold">Tồn kho hiện tại</h4>
      <small class="text-body-secondary">Xem tồn kho theo sản phẩm, vị trí, lô và serial</small>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('inventory.ledger') }}" class="btn btn-outline-secondary">
        <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-list') }}"></use></svg>
        Lịch sử giao dịch
      </a>
    </div>
  </div>

  {{-- CARDS KPI --}}
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
      <div class="card border-start border-start-4 border-start-primary">
        <div class="card-body d-flex align-items-center gap-3">
          <svg class="icon icon-2xl text-primary"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-tags') }}"></use></svg>
          <div>
            <div class="fs-5 fw-semibold">{{ number_format($totalSkuCount ?? 0) }}</div>
            <div class="text-body-secondary small">Mặt hàng đang có tồn</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card border-start border-start-4 border-start-success">
        <div class="card-body d-flex align-items-center gap-3">
          <svg class="icon icon-2xl text-success"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-storage') }}"></use></svg>
          <div>
            <div class="fs-5 fw-semibold">{{ number_format($totalQty ?? 0) }}</div>
            <div class="text-body-secondary small">Tổng tồn kho</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card border-start border-start-4 border-start-warning">
        <div class="card-body d-flex align-items-center gap-3">
          <svg class="icon icon-2xl text-warning"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-lock-locked') }}"></use></svg>
          <div>
            <div class="fs-5 fw-semibold">{{ number_format($reservedQty ?? 0) }}</div>
            <div class="text-body-secondary small">Đang giữ chỗ</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card border-start border-start-4 border-start-danger">
        <div class="card-body d-flex align-items-center gap-3">
          <svg class="icon icon-2xl text-danger"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use></svg>
          <div>
            <div class="fs-5 fw-semibold">{{ number_format($quarantineCount ?? 0) }}</div>
            <div class="text-body-secondary small">Trong Quarantine</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- BỘ LỌC --}}
  <div class="card mb-3">
    <div class="card-body py-2">
      <form method="GET" action="{{ route('inventory.index') }}" id="filterForm">
        <div class="row g-2 align-items-end">

          <div class="col-sm-6 col-lg-3">
            <label class="form-label mb-1 small fw-medium">Tìm kiếm</label>
            <div class="input-group">
              <span class="input-group-text">
                <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-search') }}"></use></svg>
              </span>
              <input type="text" class="form-control" name="search"
                     value="{{ request('search') }}" placeholder="Mã / tên hàng...">
            </div>
          </div>

          <div class="col-sm-6 col-lg-2">
            <label class="form-label mb-1 small fw-medium">Nhóm hàng</label>
            <select class="form-select" name="category_id">
              <option value="">Tất cả nhóm</option>
              @foreach ($categories ?? [] as $cat)
                <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                  {{ $cat->name }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="col-sm-6 col-lg-2">
            <label class="form-label mb-1 small fw-medium">Hàng hóa</label>
            <select class="form-select" name="product_id">
              <option value="">Tất cả hàng hóa</option>
              @foreach ($products ?? [] as $prod)
                <option value="{{ $prod->id }}" {{ request('product_id') == $prod->id ? 'selected' : '' }}>
                  {{ $prod->name }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="col-sm-6 col-lg-2">
            <label class="form-label mb-1 small fw-medium">Vị trí kho</label>
            <select class="form-select" name="location_id">
              <option value="">Tất cả vị trí</option>
              @foreach ($locations ?? [] as $loc)
                <option value="{{ $loc->id }}" {{ request('location_id') == $loc->id ? 'selected' : '' }}>
                  {{ $loc->code }} — {{ $loc->name }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="col-sm-6 col-lg-2">
            <label class="form-label mb-1 small fw-medium">Trạng thái</label>
            <select class="form-select" name="status">
              <option value="">Tất cả</option>
              <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Bình thường</option>
              <option value="2" {{ request('status') == '2' ? 'selected' : '' }}>Quarantine</option>
              <option value="3" {{ request('status') == '3' ? 'selected' : '' }}>Hết hạn</option>
            </select>
          </div>

          <div class="col-sm-6 col-lg-1 d-flex gap-1">
            <button type="submit" class="btn btn-primary flex-fill">
              <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-filter') }}"></use></svg>
            </button>
            @if(request()->hasAny(['search','category_id','location_id','product_id','status']))
              <a href="{{ route('inventory.index') }}" class="btn btn-outline-danger" title="Xóa lọc">
                <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-x') }}"></use></svg>
              </a>
            @endif
          </div>

        </div>
      </form>
    </div>
  </div>

  {{-- BẢNG TỒN KHO --}}
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <span class="fw-semibold">Danh sách tồn kho</span>
      <div class="d-flex align-items-center gap-3">
        <div class="form-check form-switch mb-0">
          <input class="form-check-input" type="checkbox" id="showZeroToggle"
            {{ request('show_zero') ? 'checked' : '' }}
            onchange="toggleZero(this)">
          <label class="form-check-label small" for="showZeroToggle">Hiện hàng hết tồn</label>
        </div>
        @if(isset($stocks) && method_exists($stocks, 'total'))
          <small class="text-body-secondary">{{ number_format($stocks->total()) }} dòng</small>
        @endif
      </div>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible mx-3 mt-3 mb-0" role="alert">
        <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check') }}"></use></svg>
        {{ session('success') }}
        <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
      </div>
    @endif

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th style="width:100px">Mã hàng</th>
              <th style="min-width:200px">Tên hàng hóa</th>
              <th>Nhóm</th>
              <th>Vị trí</th>
              <th>Lô / Serial</th>
              <th style="width:60px">ĐVT</th>
              <th class="text-end" style="width:90px">Tổng tồn</th>
              <th class="text-end" style="width:90px">Đang giữ</th>
              <th class="text-end" style="width:100px">Khả dụng</th>
              <th class="text-center" style="width:80px">HSD</th>
              <th class="text-center" style="width:90px">TT</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($stocks ?? [] as $row)
            @php
              $statusMap  = [1 => ['Bình thường','success'], 2 => ['Quarantine','warning'], 3 => ['Hết hạn','danger']];
              [$statusText, $statusColor] = $statusMap[$row->status] ?? ['?','secondary'];

              $available = (float)$row->available_qty;
              $qty       = (float)$row->quantity;
              $minStock  = (float)($row->min_stock ?? 0);

              // Cảnh báo tồn kho
              $stockAlert = '';
              if ($qty == 0)         $stockAlert = 'zero';
              elseif ($minStock > 0 && $qty <= $minStock) $stockAlert = 'low';

              // HSD
              $expiryDays  = null;
              $expiryClass = '';
              if ($row->expiry_date) {
                  $expiryDays  = now()->diffInDays(\Carbon\Carbon::parse($row->expiry_date), false);
                  $expiryClass = $expiryDays < 0 ? 'danger' : ($expiryDays <= 7 ? 'danger' : ($expiryDays <= 30 ? 'warning' : 'success'));
              }
            @endphp
            <tr class="{{ $stockAlert === 'zero' ? 'table-secondary opacity-75' : ($stockAlert === 'low' ? 'table-warning' : '') }}">
              <td>
                <code class="text-primary fw-medium">{{ $row->product_code }}</code>
              </td>
              <td>
                <div class="fw-medium">{{ $row->product_name }}</div>
                @if($stockAlert === 'low')
                  <div style="font-size:11px" class="text-danger">
                    <svg class="icon icon-sm"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use></svg>
                    Dưới ngưỡng tối thiểu (min: {{ number_format($minStock, 0) }})
                  </div>
                @endif
              </td>
              <td class="text-body-secondary small">{{ $row->category_name ?? '—' }}</td>
              <td>
                <span class="small">
                  <code class="text-secondary">{{ $row->location_code }}</code>
                  <span class="text-body-secondary">{{ $row->location_name }}</span>
                </span>
              </td>
              <td class="small">
                @if($row->lot_id && $row->serial_number)
                  <div class="text-body-secondary">Lot: {{ $row->lot_number }}</div>
                  <div><code>S/N: {{ $row->serial_number }}</code></div>
                @elseif($row->lot_id)
                  <code>{{ $row->lot_number }}</code>
                @elseif($row->serial_id)
                  <code>S/N: {{ $row->serial_number }}</code>
                @else
                  <span class="text-body-secondary">—</span>
                @endif
              </td>
              <td class="small text-body-secondary">{{ $row->uom_name }}</td>
              <td class="text-end fw-semibold {{ $qty == 0 ? 'text-body-secondary' : '' }}">
                {{ number_format($qty, 0) }}
              </td>
              <td class="text-end">
                @if((float)$row->reserved_qty > 0)
                  <span class="text-warning fw-medium">{{ number_format($row->reserved_qty, 0) }}</span>
                @else
                  <span class="text-body-secondary">—</span>
                @endif
              </td>
              <td class="text-end fw-bold {{ $available <= 0 ? 'text-danger' : 'text-primary' }}">
                {{ number_format($available, 0) }}
              </td>
              <td class="text-center">
                @if($row->expiry_date)
                  <span class="badge bg-{{ $expiryClass }}-subtle text-{{ $expiryClass }} border border-{{ $expiryClass }}-subtle" style="font-size:11px">
                    {{ \Carbon\Carbon::parse($row->expiry_date)->format('d/m/Y') }}
                  </span>
                @else
                  <span class="text-body-secondary small">—</span>
                @endif
              </td>
              <td class="text-center">
                <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}-emphasis border border-{{ $statusColor }}-subtle rounded-pill" style="font-size:11px">
                  {{ $statusText }}
                </span>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="11" class="text-center text-body-secondary py-5">
                <svg class="icon icon-3xl d-block mx-auto mb-2 opacity-25">
                  <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-storage') }}"></use>
                </svg>
                Không tìm thấy dữ liệu tồn kho.
              </td>
            </tr>
            @endforelse
          </tbody>
          @if(isset($stocks) && $stocks->count() > 0)
          <tfoot class="table-light fw-semibold">
            <tr>
              <td colspan="6" class="text-end">Tổng trang này:</td>
              <td class="text-end">{{ number_format(collect($stocks->items())->sum('quantity'), 0) }}</td>
              <td class="text-end text-warning">{{ number_format(collect($stocks->items())->sum('reserved_qty'), 0) }}</td>
              <td class="text-end text-primary">{{ number_format(collect($stocks->items())->sum('available_qty'), 0) }}</td>
              <td colspan="2"></td>
            </tr>
          </tfoot>
          @endif
        </table>
      </div>
    </div>

    @if(isset($stocks) && method_exists($stocks, 'hasPages') && $stocks->hasPages())
    <div class="card-footer d-flex justify-content-between align-items-center">
      <small class="text-body-secondary">
        Hiển thị {{ $stocks->firstItem() }}–{{ $stocks->lastItem() }}
        trong tổng số {{ $stocks->total() }} dòng
      </small>
      {{ $stocks->appends(request()->query())->links('pagination::bootstrap-5') }}
    </div>
    @endif
  </div>

@endsection

@push('scripts')
<script>
  function toggleZero(cb) {
    const url = new URL(window.location.href);
    if (cb.checked) {
      url.searchParams.set('show_zero', '1');
    } else {
      url.searchParams.delete('show_zero');
    }
    window.location.href = url.toString();
  }
</script>
@endpush