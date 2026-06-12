@extends('layouts.app')

@section('title', 'Chi tiết phiếu ' . $transformation->code . ' — Warehouse System')

@section('breadcrumb')
  <li class="breadcrumb-item">Nghiệp vụ kho</li>
  <li class="breadcrumb-item"><a href="{{ route('transformations.index') }}">Tách / Ghép hàng hóa</a></li>
  <li class="breadcrumb-item active">{{ $transformation->code }}</li>
@endsection

@section('content')

@php
  $fmt = fn($n) => rtrim(rtrim(number_format((float)$n, 3, '.', ','), '0'), '.');

  $statusMap = [
    1 => ['Nháp',       'secondary', 'cil-pencil'],
    2 => ['Chờ duyệt',  'warning',   'cil-clock'],
    3 => ['Đã duyệt',   'info',      'cil-check'],
    4 => ['Hoàn thành', 'success',   'cil-check-circle'],
    5 => ['Đã hủy',     'danger',    'cil-x-circle'],
  ];
  [$statusText, $statusColor, $statusIcon] = $statusMap[$transformation->status] ?? ['?','secondary','cil-info'];

  $typeMap = [
    1 => ['Tách hàng', 'warning', 'cil-fork'],
    2 => ['Ghép hàng', 'info',    'cil-layers'],
  ];
  [$typeText, $typeColor, $typeIcon] = $typeMap[$transformation->type] ?? ['?','secondary','cil-layers'];

  $hasLotConsume    = $transformation->consumeDetails->contains(fn($d) => in_array((int)($d->product?->tracking_type ?? 1), [2,4]));
  $hasSerialConsume = $transformation->consumeDetails->contains(fn($d) => in_array((int)($d->product?->tracking_type ?? 1), [3,4]));
  $hasLotProduce    = $transformation->produceDetails->contains(fn($d) => in_array((int)($d->product?->tracking_type ?? 1), [2,4]));
  $hasSerialProduce = $transformation->produceDetails->contains(fn($d) => in_array((int)($d->product?->tracking_type ?? 1), [3,4]));
@endphp

{{-- HEADER --}}
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <div>
    <h4 class="mb-1 fw-semibold d-flex align-items-center gap-2">
      {{ $transformation->code }}
      <span class="badge bg-{{ $typeColor }}-subtle text-{{ $typeColor }}-emphasis border border-{{ $typeColor }}-subtle rounded-pill fs-6">
        <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#' . $typeIcon) }}"></use></svg>
        {{ $typeText }}
      </span>
      <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}-emphasis border border-{{ $statusColor }}-subtle rounded-pill fs-6">
        <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#' . $statusIcon) }}"></use></svg>
        {{ $statusText }}
      </span>
    </h4>
    <small class="text-body-secondary">
      Tạo lúc {{ $transformation->created_at?->format('d/m/Y H:i') }}
      @if($transformation->createdBy) bởi <strong>{{ $transformation->createdBy->name }}</strong> @endif
    </small>
  </div>

  <div class="d-flex gap-2 flex-wrap">

    {{-- NHÁP: Chỉnh sửa + Gửi duyệt + Xóa --}}
    @if((int)$transformation->status === 1)
      <a href="{{ route('transformations.edit', $transformation) }}" class="btn btn-outline-secondary btn-sm">
        <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-pencil') }}"></use></svg>Chỉnh sửa
      </a>
      <form method="POST" action="{{ route('transformations.submit', $transformation) }}">
        @csrf
        <button type="submit" class="btn btn-warning btn-sm">
          <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-send') }}"></use></svg>Gửi duyệt
        </button>
      </form>
      <form method="POST" action="{{ route('transformations.destroy', $transformation) }}"
            onsubmit="return confirm('Xóa vĩnh viễn phiếu {{ $transformation->code }}?')">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-outline-danger btn-sm">
          <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-trash') }}"></use></svg>Xóa
        </button>
      </form>
    @endif

    {{-- CHỜ DUYỆT: Duyệt phiếu --}}
    @if((int)$transformation->status === 2)
      <form method="POST" action="{{ route('transformations.approve', $transformation) }}">
        @csrf
        <button type="submit" class="btn btn-primary btn-sm">
          <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check') }}"></use></svg>Duyệt phiếu
        </button>
      </form>
    @endif

    {{-- ĐÃ DUYỆT: Xác nhận thực hiện --}}
    @if((int)$transformation->status === 3)
      <button type="button" class="btn btn-success btn-sm"
              data-coreui-toggle="modal" data-coreui-target="#confirmModal">
        <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use></svg>Xác nhận thực hiện
      </button>
    @endif

    {{-- HỦY (trừ Hoàn thành / Đã hủy) --}}
    @if(!in_array((int)$transformation->status, [4, 5]))
      <form method="POST" action="{{ route('transformations.cancel', $transformation) }}"
            onsubmit="return confirm('Hủy phiếu {{ $transformation->code }}?\nThao tác này không thể khôi phục.')">
        @csrf
        <button type="submit" class="btn btn-outline-danger btn-sm">
          <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-x-circle') }}"></use></svg>Hủy phiếu
        </button>
      </form>
    @endif

    {{-- In phiếu khi Hoàn thành --}}
    @if((int)$transformation->status === 4)
      <a href="{{ route('transformations.print', $transformation) }}" target="_blank" class="btn btn-outline-primary btn-sm">
        <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-print') }}"></use></svg>Xuất PDF
      </a>
    @endif

    <a href="{{ route('transformations.index') }}" class="btn btn-outline-secondary btn-sm">
      <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-left') }}"></use></svg>Quay lại
    </a>
  </div>
</div>

{{-- ALERTS --}}
@if(session('success'))
  <div class="alert alert-success alert-dismissible mb-3">
    <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check') }}"></use></svg>
    {{ session('success') }}
    <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
  </div>
@endif
@if(session('error'))
  <div class="alert alert-danger alert-dismissible mb-3">
    <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-warning') }}"></use></svg>
    {{ session('error') }}
    <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
  </div>
@endif

{{-- TIMELINE --}}
<div class="card mb-3">
  <div class="card-body py-3">
    <div class="d-flex justify-content-between align-items-center">
      @php $steps = [1 => 'Nháp', 2 => 'Chờ duyệt', 3 => 'Đã duyệt', 4 => 'Hoàn thành']; @endphp
      @foreach($steps as $step => $label)
        @php
          $done    = $transformation->status >= $step && $transformation->status !== 5;
          $current = $transformation->status === $step;
          $color   = $done ? 'success' : 'secondary';
          $lineClass = $transformation->status > $step ? 'border-success' : 'border-secondary';
        @endphp
        <div class="d-flex flex-column align-items-center flex-fill">
          <div class="rounded-circle d-flex align-items-center justify-content-center mb-1 border border-2
               bg-{{ $color }}{{ $current ? '' : '-subtle' }}
               text-{{ $color }}{{ $current ? ' text-white' : '' }}
               border-{{ $color }}"
               style="width:32px;height:32px;font-size:13px">
            {{ $step }}
          </div>
          <small class="text-{{ $color }} {{ $current ? 'fw-semibold' : '' }}">{{ $label }}</small>
        </div>
        @if($step < 4)
          <div class="flex-fill border-top border-2 mt-2 mb-auto {{ $lineClass }}" style="max-width:60px"></div>
        @endif
      @endforeach
    </div>
    {{-- Thêm dòng mô tả nếu đã hủy --}}
    @if($transformation->status === 5)
      <div class="text-center mt-2">
        <span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle rounded-pill">
          <svg class="icon icon-sm me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-x-circle') }}"></use></svg>
          Phiếu đã bị hủy
        </span>
      </div>
    @endif
  </div>
</div>

{{-- THÔNG TIN PHIẾU --}}
<div class="card mb-3">
  <div class="card-header fw-semibold py-2">
    <svg class="icon me-1 text-primary"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-description') }}"></use></svg>
    Thông tin phiếu
  </div>
  <div class="card-body py-3">
    <div class="row g-3 small">
      <div class="col-md-2">
        <div class="text-body-secondary mb-1">Mã phiếu</div>
        <div class="fw-semibold">{{ $transformation->code }}</div>
      </div>
      <div class="col-md-2">
        <div class="text-body-secondary mb-1">Loại</div>
        <span class="badge bg-{{ $typeColor }}-subtle text-{{ $typeColor }}-emphasis border border-{{ $typeColor }}-subtle rounded-pill">
          {{ $typeText }}
        </span>
      </div>
      <div class="col-md-2">
        <div class="text-body-secondary mb-1">Công thức BOM</div>
        <div>
          @if($transformation->bom)
            <a href="{{ route('master.bom.edit', $transformation->bom) }}" class="text-decoration-none">
              {{ $transformation->bom->code }}
            </a>
            <div class="text-body-secondary">{{ $transformation->bom->name }}</div>
          @else —
          @endif
        </div>
      </div>
      <div class="col-md-2">
        <div class="text-body-secondary mb-1">Hệ số thực hiện</div>
        <div class="fw-semibold">× {{ $transformation->multiplier ?? 1 }}</div>
      </div>
      <div class="col-md-2">
        <div class="text-body-secondary mb-1">Ngày thực hiện</div>
        <div>{{ $transformation->transformation_date?->format('d/m/Y') ?? '—' }}</div>
      </div>
      <div class="col-md-2">
        <div class="text-body-secondary mb-1">Trạng thái</div>
        <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}-emphasis border border-{{ $statusColor }}-subtle rounded-pill">
          {{ $statusText }}
        </span>
      </div>
      @if($transformation->note)
        <div class="col-12">
          <div class="text-body-secondary mb-1">Ghi chú</div>
          <div>{{ $transformation->note }}</div>
        </div>
      @endif
      <div class="col-md-2">
        <div class="text-body-secondary mb-1">Người tạo</div>
        <div>{{ $transformation->createdBy?->name ?? '—' }}</div>
      </div>
      @if($transformation->confirmedBy)
        <div class="col-md-2">
          <div class="text-body-secondary mb-1">Người duyệt</div>
          <div>{{ $transformation->confirmedBy->name }}</div>
        </div>
      @endif
    </div>
  </div>
</div>

{{-- ĐẦU VÀO --}}
<div class="card mb-3">
  <div class="card-header fw-semibold d-flex justify-content-between align-items-center py-2">
    <span>
      <svg class="icon me-1 text-danger"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-thick-bottom') }}"></use></svg>
      Hàng hóa đầu vào
      <span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle ms-1">
        {{ $transformation->type === 2 ? 'Nhiều nguồn' : 'Nguồn gốc' }}
      </span>
    </span>
    <span class="badge bg-primary-subtle text-primary-emphasis">{{ $transformation->consumeDetails->count() }} dòng</span>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-sm align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width:36px" class="text-center">#</th>
            <th>Hàng hóa</th>
            <th style="width:70px">ĐVT</th>
            <th style="width:100px" class="text-end">SL BOM</th>
            <th style="width:100px" class="text-end">SL thực tế</th>
            <th style="width:110px">Vị trí</th>
            <th style="width:110px">Tracking</th>
            @if($hasLotConsume)
              <th style="width:130px">Số Lot</th>
            @endif
            @if($hasSerialConsume)
              <th style="width:130px">Số Serial</th>
            @endif
          </tr>
        </thead>
        <tbody>
          @forelse($transformation->consumeDetails as $i => $d)
            @php
              $tracking      = (int)($d->product?->tracking_type ?? 1);
              $trackingLabel = [1=>'—', 2=>'Lô', 3=>'Serial', 4=>'Lô+Serial'][$tracking] ?? '—';
              $trackingColor = [1=>'secondary', 2=>'info', 3=>'warning', 4=>'primary'][$tracking] ?? 'secondary';
            @endphp
            <tr>
              <td class="text-center text-body-secondary small">{{ $i + 1 }}</td>
              <td>
                <div class="fw-semibold small">{{ $d->product?->name ?? '—' }}</div>
                <div class="text-body-secondary" style="font-size:11px">{{ $d->product?->code }}</div>
              </td>
              <td class="text-body-secondary small">{{ $d->uom?->name ?? '—' }}</td>
              <td class="text-end text-body-secondary small">{{ $fmt($d->bom_qty ?? $d->quantity) }}</td>
              <td class="text-end fw-semibold small">{{ $fmt($d->quantity) }}</td>
              <td>
                @if($d->location)
                  <span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle">
                    {{ $d->location->code }}
                  </span>
                @else <span class="text-body-secondary small">—</span>
                @endif
              </td>
              <td>
                <span class="badge bg-{{ $trackingColor }}-subtle text-{{ $trackingColor }}-emphasis border border-{{ $trackingColor }}-subtle">
                  {{ $trackingLabel }}
                </span>
              </td>
              @if($hasLotConsume)
                <td class="small">
                  @if($d->lot)
                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle font-monospace">
                      {{ $d->lot->lot_number }}
                    </span>
                  @elseif(in_array($tracking, [2,4]))
                    <span class="text-danger small">Chưa có lot</span>
                  @else
                    <span class="text-body-secondary">—</span>
                  @endif
                </td>
              @endif
              @if($hasSerialConsume)
                <td class="small">
                  @if($d->serial)
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle font-monospace">
                      {{ $d->serial->serial_number }}
                    </span>
                  @elseif(in_array($tracking, [3,4]))
                    <span class="text-danger small">Chưa có serial</span>
                  @else
                    <span class="text-body-secondary">—</span>
                  @endif
                </td>
              @endif
            </tr>
          @empty
            <tr><td colspan="10" class="text-center text-body-secondary py-3">Không có dòng đầu vào.</td></tr>
          @endforelse
        </tbody>
        @if($transformation->consumeDetails->count())
          <tfoot class="table-light">
            <tr>
              <td colspan="3" class="small text-body-secondary">{{ $transformation->consumeDetails->count() }} dòng</td>
              <td class="text-end fw-semibold small">{{ $fmt($transformation->consumeDetails->sum('bom_qty')) }}</td>
              <td class="text-end fw-semibold small text-danger">{{ $fmt($transformation->consumeDetails->sum('quantity')) }}</td>
              <td colspan="{{ 2 + ($hasLotConsume ? 1 : 0) + ($hasSerialConsume ? 1 : 0) }}"></td>
            </tr>
          </tfoot>
        @endif
      </table>
    </div>
  </div>
</div>

{{-- MŨI TÊN --}}
<div class="text-center mb-3">
  <div class="d-inline-flex align-items-center gap-3 px-4 py-2 rounded-3 bg-body-secondary">
    <svg class="icon icon-xl text-body-secondary">
      <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-thick-bottom') }}"></use>
    </svg>
    <span class="fw-semibold text-body-secondary">
      {{ $transformation->type === 1 ? 'Tách thành' : 'Ghép thành' }}
    </span>
    <svg class="icon icon-xl text-body-secondary">
      <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-thick-bottom') }}"></use>
    </svg>
  </div>
</div>

{{-- ĐẦU RA --}}
<div class="card">
  <div class="card-header fw-semibold d-flex justify-content-between align-items-center py-2">
    <span>
      <svg class="icon me-1 text-success"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-arrow-thick-top') }}"></use></svg>
      Hàng hóa đầu ra
      <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle ms-1">
        {{ $transformation->type === 1 ? 'Nhiều sản phẩm' : 'Sản phẩm ghép' }}
      </span>
    </span>
    <span class="badge bg-primary-subtle text-primary-emphasis">{{ $transformation->produceDetails->count() }} dòng</span>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-sm align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width:36px" class="text-center">#</th>
            <th>Hàng hóa</th>
            <th style="width:70px">ĐVT</th>
            <th style="width:100px" class="text-end">SL BOM</th>
            <th style="width:100px" class="text-end">SL thực tế</th>
            <th style="width:110px">Vị trí đích</th>
            <th style="width:110px">Tracking</th>
            @if($hasLotProduce)
              <th style="width:130px">Lot mới</th>
            @endif
            @if($hasSerialProduce)
              <th style="width:130px">Serial mới</th>
            @endif
            <th style="width:100px">Hạn dùng</th>
          </tr>
        </thead>
        <tbody>
          @forelse($transformation->produceDetails as $i => $d)
            @php
              $tracking      = (int)($d->product?->tracking_type ?? 1);
              $trackingLabel = [1=>'—', 2=>'Lô', 3=>'Serial', 4=>'Lô+Serial'][$tracking] ?? '—';
              $trackingColor = [1=>'secondary', 2=>'info', 3=>'warning', 4=>'primary'][$tracking] ?? 'secondary';
            @endphp
            <tr>
              <td class="text-center text-body-secondary small">{{ $i + 1 }}</td>
              <td>
                <div class="fw-semibold small">{{ $d->product?->name ?? '—' }}</div>
                <div class="text-body-secondary" style="font-size:11px">{{ $d->product?->code }}</div>
              </td>
              <td class="text-body-secondary small">{{ $d->uom?->name ?? '—' }}</td>
              <td class="text-end text-body-secondary small">{{ $fmt($d->bom_qty ?? $d->quantity) }}</td>
              <td class="text-end fw-semibold small">{{ $fmt($d->quantity) }}</td>
              <td>
                @if($d->location)
                  <span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle">
                    {{ $d->location->code }}
                  </span>
                @else <span class="text-body-secondary small">—</span>
                @endif
              </td>
              <td>
                <span class="badge bg-{{ $trackingColor }}-subtle text-{{ $trackingColor }}-emphasis border border-{{ $trackingColor }}-subtle">
                  {{ $trackingLabel }}
                </span>
              </td>
              @if($hasLotProduce)
                <td class="small">
                  @if($d->lot)
                    <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle font-monospace">
                      {{ $d->lot->lot_number }}
                    </span>
                  @elseif($d->lot_number)
                    <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle font-monospace">
                      {{ $d->lot_number }}
                    </span>
                  @elseif(in_array($tracking, [2,4]))
                    <span class="text-danger small">Chưa có lot</span>
                  @else
                    <span class="text-body-secondary">—</span>
                  @endif
                </td>
              @endif
              @if($hasSerialProduce)
                <td class="small">
                  @if($d->serial)
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle font-monospace">
                      {{ $d->serial->serial_number }}
                    </span>
                  @elseif($d->serial_number)
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle font-monospace">
                      {{ $d->serial_number }}
                    </span>
                  @elseif(in_array($tracking, [3,4]))
                    <span class="text-danger small">Chưa có serial</span>
                  @else
                    <span class="text-body-secondary">—</span>
                  @endif
                </td>
              @endif
              <td class="small">
                @if($d->expiry_date)
                  @php
                    $expiry   = \Carbon\Carbon::parse($d->expiry_date);
                    $daysLeft = now()->diffInDays($expiry, false);
                  @endphp
                  <span class="{{ $daysLeft < 30 ? 'text-danger fw-semibold' : ($daysLeft < 90 ? 'text-warning' : '') }}">
                    {{ $expiry->format('d/m/Y') }}
                  </span>
                  @if($daysLeft < 30 && $daysLeft >= 0)
                    <div style="font-size:10px" class="text-danger">còn {{ $daysLeft }} ngày</div>
                  @elseif($daysLeft < 0)
                    <div style="font-size:10px" class="text-danger">Đã hết hạn</div>
                  @endif
                @else
                  <span class="text-body-secondary">—</span>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="11" class="text-center text-body-secondary py-3">Không có dòng đầu ra.</td></tr>
          @endforelse
        </tbody>
        @if($transformation->produceDetails->count())
          <tfoot class="table-light">
            <tr>
              <td colspan="3" class="small text-body-secondary">{{ $transformation->produceDetails->count() }} dòng</td>
              <td class="text-end fw-semibold small">{{ $fmt($transformation->produceDetails->sum('bom_qty')) }}</td>
              <td class="text-end fw-semibold small text-success">{{ $fmt($transformation->produceDetails->sum('quantity')) }}</td>
              <td colspan="{{ 2 + ($hasLotProduce ? 1 : 0) + ($hasSerialProduce ? 1 : 0) + 1 }}"></td>
            </tr>
          </tfoot>
        @endif
      </table>
    </div>
  </div>

  @if($transformation->status === 4)
    <div class="card-footer border-success bg-success-subtle text-success d-flex align-items-center gap-2 py-2">
      <svg class="icon"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use></svg>
      <span class="small fw-semibold">Tồn kho đã được cập nhật thành công.</span>
    </div>
  @endif
</div>

{{-- MODAL XÁC NHẬN THỰC HIỆN --}}
@if((int)$transformation->status === 3)
  <div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title text-success">
            <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use></svg>
            Xác nhận thực hiện tách/ghép
          </h5>
          <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p>Xác nhận thực hiện phiếu <strong>{{ $transformation->code }}</strong>?</p>
          <div class="alert alert-info small mb-0">
            <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-info') }}"></use></svg>
            Sau khi xác nhận, tồn kho sẽ được cập nhật (trừ hàng đầu vào, cộng hàng đầu ra)
            và không thể hoàn tác.
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-coreui-dismiss="modal">Hủy bỏ</button>
          <form method="POST" action="{{ route('transformations.confirm', $transformation) }}">
            @csrf
            <button type="submit" class="btn btn-success btn-sm">
              <svg class="icon me-1"><use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-check-circle') }}"></use></svg>
              Xác nhận &amp; cập nhật tồn kho
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
@endif

@endsection