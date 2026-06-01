@extends('layouts.app')

@section('title', 'Lịch sử giao dịch — Warehouse System')

@section('breadcrumb')
  <li class="breadcrumb-item">Tồn kho</li>
  <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}">Tồn kho hiện tại</a></li>
  <li class="breadcrumb-item active">Lịch sử giao dịch</li>
@endsection

@section('content')

  {{-- HEADER --}}
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-0 fw-semibold">Lịch sử giao dịch</h4>
      <small class="text-body-secondary">Nhật ký kho — mọi biến động tồn kho</small>
    </div>
    <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary">
      <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-storage') }}"></use></svg>
      Xem tồn kho hiện tại
    </a>
  </div>

  {{-- CARDS KPI --}}
  <div class="row g-3 mb-4">
    <div class="col-sm-6">
      <div class="card border-start border-start-4 border-start-success">
        <div class="card-body d-flex align-items-center gap-3">
          <svg class="icon icon-2xl text-success"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-thick-bottom') }}"></use></svg>
          <div>
            <div class="fs-5 fw-semibold text-success">+{{ number_format($totalIn ?? 0) }}</div>
            <div class="text-body-secondary small">Tổng nhập trong kỳ</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6">
      <div class="card border-start border-start-4 border-start-warning">
        <div class="card-body d-flex align-items-center gap-3">
          <svg class="icon icon-2xl text-warning"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-thick-top') }}"></use></svg>
          <div>
            <div class="fs-5 fw-semibold text-warning">-{{ number_format($totalOut ?? 0) }}</div>
            <div class="text-body-secondary small">Tổng xuất trong kỳ</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- BỘ LỌC --}}
  <div class="card mb-3">
    <div class="card-body py-2">
      <form method="GET" action="{{ route('inventory.ledger') }}">
        <div class="row g-2 align-items-end">

          <div class="col-sm-6 col-lg-2">
            <label class="form-label mb-1 small fw-medium">Từ ngày</label>
            <input type="date" class="form-control" name="date_from"
                   value="{{ $dateFrom }}">
          </div>

          <div class="col-sm-6 col-lg-2">
            <label class="form-label mb-1 small fw-medium">Đến ngày</label>
            <input type="date" class="form-control" name="date_to"
                   value="{{ $dateTo }}">
          </div>

          <div class="col-sm-6 col-lg-2">
            <label class="form-label mb-1 small fw-medium">Hàng hóa</label>
            <select class="form-select" name="product_id">
              <option value="">Tất cả</option>
              @foreach ($products ?? [] as $prod)
                <option value="{{ $prod->id }}" {{ request('product_id') == $prod->id ? 'selected' : '' }}>
                  {{ $prod->name }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="col-sm-6 col-lg-2">
            <label class="form-label mb-1 small fw-medium">Vị trí</label>
            <select class="form-select" name="location_id">
              <option value="">Tất cả</option>
              @foreach ($locations ?? [] as $loc)
                <option value="{{ $loc->id }}" {{ request('location_id') == $loc->id ? 'selected' : '' }}>
                  {{ $loc->code }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="col-sm-6 col-lg-2">
            <label class="form-label mb-1 small fw-medium">Loại GD</label>
            <select class="form-select" name="transaction_type">
              <option value="">Tất cả loại</option>
              @foreach ($transactionTypes ?? [] as $key => $label)
                <option value="{{ $key }}" {{ request('transaction_type') == $key ? 'selected' : '' }}>
                  {{ $label }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="col-sm-6 col-lg-1">
            <label class="form-label mb-1 small fw-medium">Chiều</label>
            <select class="form-select" name="direction">
              <option value="">Tất cả</option>
              <option value="1" {{ request('direction') == '1' ? 'selected' : '' }}>Nhập</option>
              <option value="2" {{ request('direction') == '2' ? 'selected' : '' }}>Xuất</option>
            </select>
          </div>

          <div class="col-sm-6 col-lg-2">
            <label class="form-label mb-1 small fw-medium">Tìm kiếm</label>
            <div class="input-group">
              <span class="input-group-text">
                <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-search') }}"></use></svg>
              </span>
              <input type="text" class="form-control" name="search"
                     value="{{ request('search') }}" placeholder="Mã phiếu...">
            </div>
          </div>

          <div class="col-sm-6 col-lg-1 d-flex gap-1">
            <button type="submit" class="btn btn-primary flex-fill">
              <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-filter') }}"></use></svg>
            </button>
            @if(request()->hasAny(['product_id','location_id','transaction_type','direction','search']))
              <a href="{{ route('inventory.ledger') }}" class="btn btn-outline-danger" title="Xóa lọc">
                <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-x') }}"></use></svg>
              </a>
            @endif
          </div>

        </div>

        {{-- Nút tắt nhanh kỳ --}}
        <div class="d-flex gap-2 mt-2">
          <a href="{{ route('inventory.ledger', ['date_from' => now()->toDateString(), 'date_to' => now()->toDateString()]) }}"
             class="btn btn-sm btn-outline-secondary {{ ($dateFrom == now()->toDateString() && $dateTo == now()->toDateString()) ? 'active' : '' }}">
            Hôm nay
          </a>
          <a href="{{ route('inventory.ledger', ['date_from' => now()->startOfWeek()->toDateString(), 'date_to' => now()->toDateString()]) }}"
             class="btn btn-sm btn-outline-secondary">
            Tuần này
          </a>
          <a href="{{ route('inventory.ledger', ['date_from' => now()->startOfMonth()->toDateString(), 'date_to' => now()->toDateString()]) }}"
             class="btn btn-sm btn-outline-secondary">
            Tháng này
          </a>
        </div>

      </form>
    </div>
  </div>

  {{-- BẢNG LỊCH SỬ --}}
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span class="fw-semibold">Nhật ký kho</span>
      @if(isset($ledgers) && method_exists($ledgers, 'total'))
        <small class="text-body-secondary">{{ number_format($ledgers->total()) }} giao dịch</small>
      @endif
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Thời gian</th>
              <th style="width:120px">Loại GD</th>
              <th style="width:130px">Mã phiếu</th>
              <th style="min-width:180px">Hàng hóa</th>
              <th>Vị trí</th>
              <th>Lô / Serial</th>
              <th style="width:55px">ĐVT</th>
              <th class="text-end" style="width:100px">Số lượng</th>
              <th class="text-end" style="width:100px">Tồn sau GD</th>
              <th>Người thực hiện</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($ledgers ?? [] as $row)
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
              $isIn = (int)$row->direction === 1;
            @endphp
            <tr>
              <td class="small text-body-secondary text-nowrap">
                {{ \Carbon\Carbon::parse($row->transaction_date)->format('d/m/Y H:i') }}
              </td>
              <td>
                <span class="badge bg-{{ $typeColor }}-subtle text-{{ $typeColor }}-emphasis border border-{{ $typeColor }}-subtle rounded-pill" style="font-size:11px">
                  {{ $typeText }}
                </span>
              </td>
              <td>
                <code class="small text-body-secondary">{{ $row->reference_code ?? '—' }}</code>
              </td>
              <td>
                <div class="fw-medium small">{{ $row->product_name }}</div>
                <div class="text-body-secondary" style="font-size:11px">{{ $row->product_code }}</div>
              </td>
              <td class="small">
                <code class="text-secondary">{{ $row->location_code }}</code>
              </td>
              <td class="small">
                @if($row->lot_number && $row->serial_number)
                  <div class="text-body-secondary">{{ $row->lot_number }}</div>
                  <code>{{ $row->serial_number }}</code>
                @elseif($row->lot_number)
                  <code>{{ $row->lot_number }}</code>
                @elseif($row->serial_number)
                  <code>{{ $row->serial_number }}</code>
                @else
                  <span class="text-body-secondary">—</span>
                @endif
              </td>
              <td class="small text-body-secondary">{{ $row->uom_name }}</td>
              <td class="text-end fw-semibold {{ $isIn ? 'text-success' : 'text-warning' }}">
                {{ $isIn ? '+' : '-' }}{{ number_format((float)$row->quantity, 0) }}
              </td>
              <td class="text-end text-body-secondary small">
                {{ number_format((float)$row->balance_after, 0) }}
              </td>
              <td class="small text-body-secondary">
                {{ $row->created_by_name ?? '—' }}
              </td>
            </tr>
            @if($row->note)
            <tr class="border-top-0">
              <td colspan="10" class="py-0 ps-3 pb-1">
                <small class="text-body-secondary fst-italic">
                  <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-comment-bubble') }}"></use></svg>
                  {{ $row->note }}
                </small>
              </td>
            </tr>
            @endif
            @empty
            <tr>
              <td colspan="10" class="text-center text-body-secondary py-5">
                <svg class="icon icon-3xl d-block mx-auto mb-2 opacity-25">
                  <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-list') }}"></use>
                </svg>
                Không có giao dịch trong kỳ đã chọn.
              </td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if(isset($ledgers) && method_exists($ledgers, 'hasPages') && $ledgers->hasPages())
    <div class="card-footer d-flex justify-content-between align-items-center">
      <small class="text-body-secondary">
        Hiển thị {{ $ledgers->firstItem() }}–{{ $ledgers->lastItem() }}
        trong tổng số {{ $ledgers->total() }} giao dịch
      </small>
      {{ $ledgers->appends(request()->query())->links('pagination::bootstrap-5') }}
    </div>
    @endif
  </div>

@endsection