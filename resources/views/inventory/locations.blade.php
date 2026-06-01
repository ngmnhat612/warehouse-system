@extends('layouts.app')

@section('title', 'Vị trí kho — Warehouse System')

@section('breadcrumb')
  <li class="breadcrumb-item">Tồn kho</li>
  <li class="breadcrumb-item active">Vị trí kho</li>
@endsection

@section('content')

  {{-- HEADER --}}
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-0 fw-semibold">Vị trí kho</h4>
      <small class="text-body-secondary">Tổng quan tồn kho theo từng vị trí lưu trữ</small>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary">
        <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-storage') }}"></use></svg>
        Xem chi tiết tồn kho
      </a>
      <a href="{{ route('master.location.index') }}" class="btn btn-outline-secondary">
        <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-settings') }}"></use></svg>
        Quản lý vị trí
      </a>
    </div>
  </div>

  {{-- CARDS KPI --}}
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
      <div class="card border-start border-start-4 border-start-primary">
        <div class="card-body d-flex align-items-center gap-3">
          <svg class="icon icon-2xl text-primary"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-map') }}"></use></svg>
          <div>
            <div class="fs-5 fw-semibold">{{ number_format($totalLocations) }}</div>
            <div class="text-body-secondary small">Tổng vị trí</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card border-start border-start-4 border-start-success">
        <div class="card-body d-flex align-items-center gap-3">
          <svg class="icon icon-2xl text-success"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-storage') }}"></use></svg>
          <div>
            <div class="fs-5 fw-semibold">{{ number_format($occupiedCount) }}</div>
            <div class="text-body-secondary small">Đang có hàng</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card border-start border-start-4 border-start-secondary">
        <div class="card-body d-flex align-items-center gap-3">
          <svg class="icon icon-2xl text-body-secondary"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-inbox') }}"></use></svg>
          <div>
            <div class="fs-5 fw-semibold">{{ number_format($emptyCount) }}</div>
            <div class="text-body-secondary small">Đang trống</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card border-start border-start-4 border-start-danger">
        <div class="card-body d-flex align-items-center gap-3">
          <svg class="icon icon-2xl text-danger"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use></svg>
          <div>
            <div class="fs-5 fw-semibold">{{ number_format($overCapacity) }}</div>
            <div class="text-body-secondary small">Vượt giới hạn tồn</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- BỘ LỌC --}}
  <div class="card mb-3">
    <div class="card-body py-2">
      <form method="GET" action="{{ route('inventory.locations') }}">
        <div class="row g-2 align-items-end">

          <div class="col-sm-6 col-lg-4">
            <label class="form-label mb-1 small fw-medium">Tìm kiếm</label>
            <div class="input-group">
              <span class="input-group-text">
                <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-search') }}"></use></svg>
              </span>
              <input type="text" class="form-control" name="search"
                     value="{{ request('search') }}" placeholder="Mã / tên vị trí...">
            </div>
          </div>

          <div class="col-sm-6 col-lg-3">
            <label class="form-label mb-1 small fw-medium">Loại vị trí</label>
            <select class="form-select" name="type">
              <option value="1" {{ request('type', '1') == '1' ? 'selected' : '' }}>Internal — Vị trí thực</option>
              <option value="2" {{ request('type') == '2' ? 'selected' : '' }}>Virtual / Supplier</option>
              <option value="3" {{ request('type') == '3' ? 'selected' : '' }}>Virtual / Customer</option>
              <option value="4" {{ request('type') == '4' ? 'selected' : '' }}>Virtual / Scrap</option>
              <option value="5" {{ request('type') == '5' ? 'selected' : '' }}>Virtual / Quarantine</option>
            </select>
          </div>

          <div class="col-sm-6 col-lg-2">
            <label class="form-label mb-1 small fw-medium">Trạng thái</label>
            <select class="form-select" name="status">
              <option value="1" {{ request('status', '1') == '1' ? 'selected' : '' }}>Đang hoạt động</option>
              <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Ngừng hoạt động</option>
              <option value=""  {{ request('status') === '' && request()->has('status') ? 'selected' : '' }}>Tất cả</option>
            </select>
          </div>

          <div class="col-sm-6 col-lg-2 d-flex gap-1">
            <button type="submit" class="btn btn-primary flex-fill">
              <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-filter') }}"></use></svg>
              Lọc
            </button>
            @if(request()->hasAny(['search', 'type', 'status']))
              <a href="{{ route('inventory.locations') }}" class="btn btn-outline-danger" title="Xóa lọc">
                <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-x') }}"></use></svg>
              </a>
            @endif
          </div>

        </div>
      </form>
    </div>
  </div>

  {{-- BẢNG VỊ TRÍ KHO --}}
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span class="fw-semibold">Danh sách vị trí kho</span>
      <small class="text-body-secondary">{{ number_format(count($locationRows)) }} vị trí</small>
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
              <th style="width:120px">Mã vị trí</th>
              <th>Tên vị trí</th>
              <th class="text-center" style="width:90px">Loại</th>
              <th class="text-end" style="width:80px">SKU</th>
              <th class="text-end" style="width:110px">Tổng tồn</th>
              <th class="text-end" style="width:100px">Đang giữ</th>
              <th class="text-end" style="width:110px">Khả dụng</th>
              <th class="text-center" style="width:120px">Sử dụng</th>
              <th class="text-end" style="width:80px">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($locationRows as $loc)
            @php
              $typeColors = [1=>'primary',2=>'info',3=>'success',4=>'danger',5=>'warning'];
              $typeLabels = [1=>'Internal',2=>'Supplier',3=>'Customer',4=>'Scrap',5=>'Quarantine'];
              $color = $typeColors[$loc->type] ?? 'secondary';
              $label = $typeLabels[$loc->type] ?? '?';

              $totalQty  = (float)$loc->total_qty;
              $capacity  = (float)$loc->capacity_limit;
              $available = (float)$loc->available_qty;
              $reserved  = (float)$loc->reserved_qty;
              $skuCount  = (int)$loc->sku_count;

              // Thanh sử dụng
              $usagePct  = ($capacity > 0) ? min(100, round($totalQty / $capacity * 100)) : null;
              $usageColor = 'success';
              if ($usagePct !== null) {
                  if ($usagePct >= 100) $usageColor = 'danger';
                  elseif ($usagePct >= 80) $usageColor = 'warning';
              }

              $isEmpty = $skuCount === 0;
            @endphp
            <tr class="{{ $isEmpty ? 'opacity-60' : '' }}">
              <td>
                <code class="fw-medium text-{{ $color }}">{{ $loc->code }}</code>
              </td>
              <td>
                <div class="fw-medium">{{ $loc->name }}</div>
                @if($capacity > 0)
                  <div class="text-body-secondary" style="font-size:11px">
                    Giới hạn: {{ number_format($capacity, 0) }}
                  </div>
                @endif
              </td>
              <td class="text-center">
                <span class="badge bg-{{ $color }}-subtle text-{{ $color }} border border-{{ $color }}-subtle" style="font-size:11px">
                  {{ $label }}
                </span>
              </td>
              <td class="text-end">
                @if($skuCount > 0)
                  <span class="fw-semibold text-primary">{{ $skuCount }}</span>
                @else
                  <span class="text-body-secondary">—</span>
                @endif
              </td>
              <td class="text-end fw-semibold {{ $isEmpty ? 'text-body-secondary' : '' }}">
                {{ $isEmpty ? '—' : number_format($totalQty, 0) }}
              </td>
              <td class="text-end">
                @if($reserved > 0)
                  <span class="text-warning fw-medium">{{ number_format($reserved, 0) }}</span>
                @else
                  <span class="text-body-secondary">—</span>
                @endif
              </td>
              <td class="text-end {{ $available <= 0 && !$isEmpty ? 'text-danger fw-bold' : 'text-primary fw-semibold' }}">
                {{ $isEmpty ? '—' : number_format($available, 0) }}
              </td>
              <td class="text-center">
                @if($usagePct !== null)
                  <div class="d-flex align-items-center gap-2">
                    <div class="progress flex-grow-1" style="height:6px">
                      <div class="progress-bar bg-{{ $usageColor }}"
                           style="width:{{ $usagePct }}%"></div>
                    </div>
                    <span class="small text-{{ $usageColor }} fw-medium" style="min-width:36px">
                      {{ $usagePct }}%
                    </span>
                  </div>
                @elseif($isEmpty)
                  <span class="badge bg-secondary-subtle text-secondary border" style="font-size:11px">Trống</span>
                @else
                  <span class="text-body-secondary small">Không giới hạn</span>
                @endif
              </td>
              <td class="text-end">
                @if($skuCount > 0)
                  <a href="{{ route('inventory.index', ['location_id' => $loc->id]) }}"
                     class="btn btn-sm btn-outline-primary"
                     title="Xem chi tiết tồn kho tại vị trí này">
                    <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-magnifying-glass') }}"></use></svg>
                  </a>
                @else
                  <button class="btn btn-sm btn-outline-secondary" disabled title="Không có hàng">
                    <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-magnifying-glass') }}"></use></svg>
                  </button>
                @endif
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="9" class="text-center text-body-secondary py-5">
                <svg class="icon icon-3xl d-block mx-auto mb-2 opacity-25">
                  <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-map') }}"></use>
                </svg>
                Không có vị trí nào.
              </td>
            </tr>
            @endforelse
          </tbody>
          @if(count($locationRows) > 0)
          <tfoot class="table-light fw-semibold">
            <tr>
              <td colspan="3" class="text-end">Tổng:</td>
              <td class="text-end text-primary">
                {{ number_format(collect($locationRows)->sum('sku_count')) }} SKU
              </td>
              <td class="text-end">{{ number_format(collect($locationRows)->sum('total_qty'), 0) }}</td>
              <td class="text-end text-warning">{{ number_format(collect($locationRows)->sum('reserved_qty'), 0) }}</td>
              <td class="text-end text-primary">{{ number_format(collect($locationRows)->sum('available_qty'), 0) }}</td>
              <td colspan="2"></td>
            </tr>
          </tfoot>
          @endif
        </table>
      </div>
    </div>
  </div>

@endsection