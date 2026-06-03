<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; }

    .page-header { text-align: center; margin-bottom: 16px; padding-bottom: 10px; border-bottom: 2px solid #1e3a8a; }
    .page-header h1 { font-size: 15px; font-weight: bold; color: #1e3a8a; margin-bottom: 4px; }
    .page-header p  { font-size: 9px; color: #64748b; }

    .meta-grid { display: flex; gap: 0; margin-bottom: 16px; border: 1px solid #e2e8f0; border-radius: 4px; overflow: hidden; }
    .meta-item { flex: 1; padding: 8px 12px; border-right: 1px solid #e2e8f0; }
    .meta-item:last-child { border-right: none; }
    .meta-item .lbl { font-size: 8px; color: #94a3b8; margin-bottom: 2px; }
    .meta-item .val { font-size: 10px; font-weight: bold; }

    .kpi-row { display: flex; gap: 8px; margin-bottom: 16px; }
    .kpi-box { flex: 1; border: 1px solid #e2e8f0; border-radius: 4px; padding: 8px 10px; text-align: center; }
    .kpi-box .val { font-size: 16px; font-weight: bold; }
    .kpi-box .lbl { font-size: 8px; color: #64748b; margin-top: 2px; }
    .kpi-box.total  .val { color: #2563eb; }
    .kpi-box.ok     .val { color: #16a34a; }
    .kpi-box.diff   .val { color: #dc2626; }
    .kpi-box.uncnt  .val { color: #d97706; }

    .section-title { font-size: 11px; font-weight: bold; color: #1e3a8a;
                     margin: 14px 0 6px; border-left: 3px solid #2563eb; padding-left: 7px; }
    .section-title.red { color: #dc2626; border-left-color: #dc2626; }

    table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    thead th {
      background: #1e3a8a; color: #fff; font-size: 8px; font-weight: bold;
      padding: 4px 5px; text-align: left; border: 1px solid #1d4ed8;
    }
    thead th.r { text-align: right; }
    thead th.c { text-align: center; }
    tbody tr:nth-child(even) { background: #f8fafc; }
    tbody tr.row-plus  { background: #dcfce7; }
    tbody tr.row-minus { background: #fee2e2; }
    tbody td { padding: 3px 5px; border: 1px solid #e2e8f0; font-size: 8.5px; vertical-align: top; }
    tbody td.r { text-align: right; }
    tbody td.c { text-align: center; }
    tfoot td { background: #eff6ff; font-weight: bold; padding: 4px 5px;
               border: 1px solid #bfdbfe; font-size: 8.5px; }
    tfoot td.r { text-align: right; }

    .badge { display: inline-block; padding: 1px 4px; border-radius: 3px; font-size: 7.5px; font-weight: bold; }
    .badge-ok      { background: #dcfce7; color: #15803d; }
    .badge-plus    { background: #dcfce7; color: #15803d; }
    .badge-minus   { background: #fee2e2; color: #b91c1c; }
    .badge-uncnt   { background: #f1f5f9; color: #475569; }

    .text-plus  { color: #16a34a; font-weight: bold; }
    .text-minus { color: #dc2626; font-weight: bold; }
    .text-muted { color: #94a3b8; }

    .sign-row { display: flex; gap: 0; margin-top: 24px; }
    .sign-box { flex: 1; text-align: center; padding: 8px; }
    .sign-box .title { font-weight: bold; font-size: 9px; margin-bottom: 36px; }
    .sign-box .sub   { font-size: 8px; color: #94a3b8; font-style: italic; border-top: 1px solid #e2e8f0; padding-top: 4px; }

    .footer { margin-top: 16px; padding-top: 6px; border-top: 1px solid #e2e8f0;
              font-size: 8px; color: #94a3b8; text-align: center; }

    .page-break { page-break-before: always; }
  </style>
</head>
<body>

  {{-- HEADER --}}
  <div class="page-header">
    <h1>BIÊN BẢN KIỂM KÊ KHO</h1>
    <p>
      Phiếu: <strong>{{ $check->code }}</strong>
      &nbsp;|&nbsp;
      Loại: {{ [1 => 'Toàn kho', 2 => 'Theo khu vực', 3 => 'Theo mặt hàng'][$check->check_type] ?? '?' }}
      &nbsp;|&nbsp;
      Xuất lúc: {{ now()->format('H:i d/m/Y') }}
    </p>
  </div>

  {{-- META --}}
  <div class="meta-grid">
    <div class="meta-item">
      <div class="lbl">Ngày kiểm kê</div>
      <div class="val">{{ $check->check_date?->format('d/m/Y') ?? '—' }}</div>
    </div>
    <div class="meta-item">
      <div class="lbl">Người phụ trách</div>
      <div class="val">{{ $check->assignedTo?->name ?? '—' }}</div>
    </div>
    <div class="meta-item">
      <div class="lbl">Người tạo</div>
      <div class="val">{{ $check->createdBy?->name ?? '—' }}</div>
    </div>
    <div class="meta-item">
      <div class="lbl">Hoàn thành lúc</div>
      <div class="val">{{ $check->completed_at?->format('H:i d/m/Y') ?? '—' }}</div>
    </div>
    <div class="meta-item">
      <div class="lbl">Ghi chú</div>
      <div class="val" style="font-weight:normal">{{ $check->note ?? '—' }}</div>
    </div>
  </div>

  {{-- KPI --}}
  <div class="kpi-row">
    <div class="kpi-box total">
      <div class="val">{{ $totalLines }}</div>
      <div class="lbl">Tổng dòng</div>
    </div>
    <div class="kpi-box ok">
      <div class="val">{{ $matchLines }}</div>
      <div class="lbl">Khớp</div>
    </div>
    <div class="kpi-box diff">
      <div class="val">{{ $diffLines }}</div>
      <div class="lbl">Chênh lệch</div>
    </div>
    <div class="kpi-box uncnt">
      <div class="val">{{ $uncountedLines }}</div>
      <div class="lbl">Chưa kiểm</div>
    </div>
  </div>

  {{-- BẢNG TOÀN BỘ --}}
  <div class="section-title">Bảng kiểm kê chi tiết</div>
  <table>
    <thead>
      <tr>
        <th style="width:22px" class="c">#</th>
        <th style="width:60px">Mã hàng</th>
        <th>Tên hàng hóa</th>
        <th style="width:30px" class="c">ĐVT</th>
        <th style="width:55px">Vị trí</th>
        <th style="width:55px">Số lô</th>
        <th class="r" style="width:55px">Tồn HT</th>
        <th class="r" style="width:55px">Thực tế</th>
        <th class="r" style="width:55px">Chênh lệch</th>
        <th class="c" style="width:55px">TT</th>
      </tr>
    </thead>
    <tbody>
      @php $i = 0; @endphp
      @foreach($lines as $line)
      @php
        $diff    = $line->diff_qty;
        $isCounted = $line->actual_qty !== null;
        $rowClass  = !$isCounted ? '' : ($diff > 0 ? 'row-plus' : ($diff < 0 ? 'row-minus' : ''));
        $i++;
      @endphp
      <tr class="{{ $rowClass }}">
        <td class="c text-muted">{{ $i }}</td>
        <td>{{ $line->product->code ?? '—' }}</td>
        <td>{{ $line->product->name ?? '—' }}</td>
        <td class="c text-muted">{{ $line->uom->name ?? '—' }}</td>
        <td class="text-muted">{{ $line->location->code ?? '—' }}</td>
        <td class="text-muted">{{ $line->lot->lot_number ?? '—' }}</td>
        <td class="r">{{ number_format($line->system_qty, 0) }}</td>
        <td class="r" style="font-weight:bold">
          {{ $isCounted ? number_format($line->actual_qty, 0) : '—' }}
        </td>
        <td class="r">
          @if($isCounted)
            @if($diff > 0)
              <span class="text-plus">+{{ number_format($diff, 0) }}</span>
            @elseif($diff < 0)
              <span class="text-minus">{{ number_format($diff, 0) }}</span>
            @else
              <span class="text-muted">0</span>
            @endif
          @else
            <span class="text-muted">—</span>
          @endif
        </td>
        <td class="c">
          @if(!$isCounted)
            <span class="badge badge-uncnt">Chưa kiểm</span>
          @elseif($diff == 0)
            <span class="badge badge-ok">Khớp</span>
          @elseif($diff > 0)
            <span class="badge badge-plus">Thừa</span>
          @else
            <span class="badge badge-minus">Thiếu</span>
          @endif
        </td>
      </tr>
      @endforeach
    </tbody>
    <tfoot>
      <tr>
        <td colspan="6" style="text-align:right">Tổng cộng:</td>
        <td class="r">{{ number_format($lines->sum('system_qty'), 0) }}</td>
        <td class="r">{{ number_format($lines->whereNotNull('actual_qty')->sum('actual_qty'), 0) }}</td>
        <td class="r">
          @php
            $netDiff = $lines->whereNotNull('actual_qty')
              ->sum(fn($l) => (float)$l->actual_qty - (float)$l->system_qty);
          @endphp
          @if($netDiff > 0)
            <span class="text-plus">+{{ number_format($netDiff, 0) }}</span>
          @elseif($netDiff < 0)
            <span class="text-minus">{{ number_format($netDiff, 0) }}</span>
          @else
            0
          @endif
        </td>
        <td></td>
      </tr>
    </tfoot>
  </table>

  {{-- BẢNG CHÊNH LỆCH --}}
  @if($diffLinesList->count() > 0)
  <div class="section-title red">Tổng hợp chênh lệch ({{ $diffLinesList->count() }} dòng)</div>
  <table>
    <thead>
      <tr>
        <th class="c" style="width:22px">#</th>
        <th style="width:60px">Mã hàng</th>
        <th>Tên hàng hóa</th>
        <th class="c" style="width:30px">ĐVT</th>
        <th style="width:55px">Vị trí</th>
        <th style="width:55px">Số lô</th>
        <th class="r" style="width:55px">Tồn HT</th>
        <th class="r" style="width:55px">Thực tế</th>
        <th class="r" style="width:55px">Chênh lệch</th>
        <th class="c" style="width:45px">Loại</th>
      </tr>
    </thead>
    <tbody>
      @foreach($diffLinesList as $idx => $line)
      @php $diff = (float)$line->actual_qty - (float)$line->system_qty; @endphp
      <tr class="{{ $diff > 0 ? 'row-plus' : 'row-minus' }}">
        <td class="c text-muted">{{ $idx + 1 }}</td>
        <td>{{ $line->product->code ?? '—' }}</td>
        <td>{{ $line->product->name ?? '—' }}</td>
        <td class="c text-muted">{{ $line->uom->name ?? '—' }}</td>
        <td class="text-muted">{{ $line->location->code ?? '—' }}</td>
        <td class="text-muted">{{ $line->lot->lot_number ?? '—' }}</td>
        <td class="r">{{ number_format($line->system_qty, 0) }}</td>
        <td class="r" style="font-weight:bold">{{ number_format($line->actual_qty, 0) }}</td>
        <td class="r {{ $diff > 0 ? 'text-plus' : 'text-minus' }}">
          {{ $diff > 0 ? '+' : '' }}{{ number_format($diff, 0) }}
        </td>
        <td class="c">
          <span class="badge {{ $diff > 0 ? 'badge-plus' : 'badge-minus' }}">
            {{ $diff > 0 ? 'Thừa' : 'Thiếu' }}
          </span>
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>
  @endif

  {{-- CHỮ KÝ --}}
  <div class="sign-row">
    <div class="sign-box">
      <div class="title">Người kiểm kê</div>
      <div class="sub">(Ký, ghi rõ họ tên)</div>
    </div>
    <div class="sign-box">
      <div class="title">Thủ kho</div>
      <div class="sub">(Ký, ghi rõ họ tên)</div>
    </div>
    <div class="sign-box">
      <div class="title">Kế toán kho</div>
      <div class="sub">(Ký, ghi rõ họ tên)</div>
    </div>
    <div class="sign-box">
      <div class="title">Quản lý kho</div>
      <div class="sub">(Ký, ghi rõ họ tên)</div>
    </div>
  </div>

  <div class="footer">
    Warehouse System — Biên bản kiểm kê {{ $check->code }} — In lúc {{ now()->format('H:i:s d/m/Y') }}
  </div>

</body>
</html>