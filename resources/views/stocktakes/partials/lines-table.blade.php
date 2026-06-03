{{--
  Params:
    $lines       — Collection<InventoryCheckLine>
    $canEdit     — bool
    $highlightDiff — bool (optional, default false)
--}}
@php $highlightDiff = $highlightDiff ?? false; @endphp

<div class="table-responsive">
  <table class="table table-hover align-middle mb-0">
    <thead class="table-light">
      <tr>
        <th style="width:36px">#</th>
        <th>Mặt hàng</th>
        <th>Vị trí</th>
        <th>Số lô</th>
        <th style="width:40px">ĐVT</th>
        <th class="text-end" style="width:100px">Tồn HT</th>
        <th class="text-end" style="width:120px">Thực tế</th>
        <th class="text-end" style="width:100px">Chênh lệch</th>
        <th style="width:110px">Người kiểm</th>
        <th class="text-center" style="width:80px">Trạng thái</th>
      </tr>
    </thead>
    <tbody>
      @forelse($lines as $i => $line)
      @php
        $diff      = $line->diff_qty;
        $isCounted = $line->actual_qty !== null;
        $hasDiff   = $isCounted && $diff != 0;
        $rowClass  = $highlightDiff && $hasDiff
          ? ($diff > 0 ? 'table-success' : 'table-danger')
          : '';
      @endphp
      <tr class="{{ $rowClass }}">
        <td class="text-body-secondary small">{{ $loop->iteration }}</td>

        {{-- Mặt hàng --}}
        <td>
          <div class="fw-semibold small">{{ $line->product->name ?? '—' }}</div>
          <div class="text-body-secondary" style="font-size:11px">{{ $line->product->code ?? '' }}</div>
        </td>

        {{-- Vị trí --}}
        <td class="small text-body-secondary">{{ $line->location->code ?? '—' }}</td>

        {{-- Lot --}}
        <td class="small text-body-secondary">{{ $line->lot->lot_number ?? '—' }}</td>

        {{-- ĐVT --}}
        <td class="small text-center text-body-secondary">{{ $line->uom->name ?? '—' }}</td>

        {{-- Tồn hệ thống --}}
        <td class="text-end fw-semibold">{{ number_format($line->system_qty, 0) }}</td>

        {{-- Thực tế — editable nếu đang kiểm --}}
        <td class="text-end">
          @if($canEdit)
            <input type="hidden" name="lines[{{ $loop->index }}][id]" value="{{ $line->id }}">
            <input type="number"
                   name="lines[{{ $loop->index }}][actual_qty]"
                   class="form-control form-control-sm text-end actual-qty-input"
                   style="width:90px; margin-left:auto"
                   min="0" step="0.001"
                   value="{{ old("lines.{$loop->index}.actual_qty", $line->actual_qty) }}"
                   placeholder="Nhập..."
                   data-line-id="{{ $line->id }}"
                   data-system="{{ $line->system_qty }}">
          @else
            <span class="{{ $isCounted ? 'fw-semibold' : 'text-body-secondary' }}">
              {{ $isCounted ? number_format($line->actual_qty, 0) : '—' }}
            </span>
          @endif
        </td>

        {{-- Chênh lệch --}}
        <td class="text-end">
          @if($isCounted)
            @if($diff > 0)
              <span class="fw-bold text-success">+{{ number_format($diff, 0) }}</span>
            @elseif($diff < 0)
              <span class="fw-bold text-danger">{{ number_format($diff, 0) }}</span>
            @else
              <span class="text-body-secondary">0</span>
            @endif
          @else
            <span class="text-body-secondary">—</span>
          @endif
        </td>

        {{-- Người kiểm --}}
        <td class="small text-body-secondary">
          {{ $line->countedBy?->name ?? '—' }}
          @if($line->counted_at)
            <div style="font-size:10px">{{ $line->counted_at->format('H:i d/m') }}</div>
          @endif
        </td>

        {{-- Trạng thái dòng --}}
        <td class="text-center">
          @if(!$isCounted)
            <span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle rounded-pill"
                  style="font-size:10px">Chưa kiểm</span>
          @elseif($hasDiff)
            <span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle rounded-pill"
                  style="font-size:10px">Lệch</span>
          @else
            <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle rounded-pill"
                  style="font-size:10px">Khớp</span>
          @endif
        </td>
      </tr>
      @empty
      <tr>
        <td colspan="10" class="text-center text-body-secondary py-5">
          <svg class="icon icon-3xl d-block mx-auto mb-2 opacity-25">
            <use xlink:href="{{ asset('vendor/coreui/icons/sprites/free.svg#cil-inbox') }}"></use>
          </svg>
          Không có dữ liệu.
        </td>
      </tr>
      @endforelse
    </tbody>

    @if($lines->count() > 0 && $highlightDiff)
    <tfoot class="table-light fw-semibold">
      <tr>
        <td colspan="5" class="text-end">Tổng:</td>
        <td class="text-end">{{ number_format($lines->sum('system_qty'), 0) }}</td>
        <td class="text-end">
          @php $totalActual = $lines->whereNotNull('actual_qty')->sum('actual_qty'); @endphp
          {{ $totalActual > 0 ? number_format($totalActual, 0) : '—' }}
        </td>
        <td class="text-end">
          @php
            $totalDiff = $lines->whereNotNull('actual_qty')
              ->sum(fn($l) => (float)$l->actual_qty - (float)$l->system_qty);
          @endphp
          @if($totalDiff > 0)
            <span class="text-success">+{{ number_format($totalDiff, 0) }}</span>
          @elseif($totalDiff < 0)
            <span class="text-danger">{{ number_format($totalDiff, 0) }}</span>
          @else
            <span class="text-body-secondary">0</span>
          @endif
        </td>
        <td colspan="2"></td>
      </tr>
    </tfoot>
    @endif
  </table>
</div>