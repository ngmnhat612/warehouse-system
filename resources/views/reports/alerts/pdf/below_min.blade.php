<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; }

    .page-header { text-align: center; margin-bottom: 18px; padding-bottom: 10px; border-bottom: 2px solid #dc2626; }
    .page-header h1 { font-size: 15px; font-weight: bold; color: #991b1b; margin-bottom: 4px; }
    .page-header p  { font-size: 9px; color: #64748b; }

    .kpi-row { display: flex; gap: 10px; margin-bottom: 18px; }
    .kpi-box { flex: 1; border: 1px solid #e2e8f0; border-radius: 5px; padding: 8px 10px; }
    .kpi-box .val { font-size: 18px; font-weight: bold; }
    .kpi-box .lbl { font-size: 9px; color: #64748b; margin-top: 2px; }
    .kpi-box.danger .val { color: #dc2626; }
    .kpi-box.dark   .val { color: #1e293b; }
    .kpi-box.warn   .val { color: #d97706; }

    table { width: 100%; border-collapse: collapse; }
    thead th {
      background: #dc2626; color: #fff; font-size: 9px; font-weight: bold;
      padding: 5px 6px; text-align: left; border: 1px solid #b91c1c;
    }
    thead th.r { text-align: right; }
    thead th.c { text-align: center; }
    tbody tr:nth-child(even) { background: #fef2f2; }
    tbody td { padding: 4px 6px; border: 1px solid #fecaca; font-size: 9px; vertical-align: middle; }
    tbody td.r { text-align: right; }
    tbody td.c { text-align: center; }
    tfoot td { background: #fee2e2; font-weight: bold; padding: 5px 6px; border: 1px solid #fca5a5; font-size: 9px; }
    tfoot td.r { text-align: right; }

    .badge { display: inline-block; padding: 1px 5px; border-radius: 3px; font-size: 8px; font-weight: bold; }
    .b-danger { background: #fee2e2; color: #b91c1c; }
    .b-warn   { background: #fef3c7; color: #92400e; }
    .b-info   { background: #e0f2fe; color: #0369a1; }
    .b-sec    { background: #f1f5f9; color: #475569; }

    .text-danger { color: #dc2626; }
    .text-muted  { color: #94a3b8; }
    .bold        { font-weight: bold; }

    .footer { margin-top: 16px; padding-top: 6px; border-top: 1px solid #e2e8f0;
              font-size: 8px; color: #94a3b8; text-align: center; }
  </style>
</head>
<body>

  <div class="page-header">
    <h1>⚠ CẢNH BÁO HÀNG DƯỚI ĐỊNH MỨC TỐI THIỂU</h1>
    <p>Xuất lúc: {{ now()->format('H:i:s d/m/Y') }}</p>
  </div>

  <div class="kpi-row">
    <div class="kpi-box danger">
      <div class="val">{{ number_format($summary['total']) }}</div>
      <div class="lbl">Mặt hàng dưới ngưỡng</div>
    </div>
    <div class="kpi-box dark">
      <div class="val">{{ number_format($summary['zero_stock']) }}</div>
      <div class="lbl">Hết sạch tồn kho</div>
    </div>
    <div class="kpi-box warn">
      <div class="val">{{ number_format($summary['total_shortage'], 0) }}</div>
      <div class="lbl">Tổng số lượng thiếu</div>
    </div>
  </div>

  <table>
    <thead>
      <tr>
        <th style="width:25px" class="c">#</th>
        <th style="width:65px">Mã hàng</th>
        <th>Tên hàng hóa</th>
        <th style="width:75px">Nhóm</th>
        <th style="width:30px" class="c">ĐVT</th>
        <th style="width:60px" class="c">Vị trí</th>
        <th style="width:65px" class="r">Tồn KD</th>
        <th style="width:55px" class="r">Min</th>
        <th style="width:55px" class="r">Max</th>
        <th style="width:65px" class="r">Còn thiếu</th>
        <th style="width:70px" class="r">Cần đặt</th>
        <th style="width:65px" class="c">Mức độ</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($items as $i => $row)
        @php
          $pct      = $row->min_qty > 0 ? ($row->current_qty / $row->min_qty * 100) : 0;
          $badgeCls = $row->current_qty <= 0 ? 'b-danger' : ($pct <= 50 ? 'b-warn' : 'b-info');
          $badgeTxt = $row->current_qty <= 0 ? 'Hết hàng' : ($pct <= 50 ? 'Nguy hiểm' : 'Chú ý');
        @endphp
        <tr>
          <td class="c text-muted">{{ $i + 1 }}</td>
          <td>{{ $row->product_code }}</td>
          <td>{{ $row->product_name }}</td>
          <td class="text-muted">{{ $row->category_name ?? '—' }}</td>
          <td class="c">{{ $row->uom_name }}</td>
          <td class="c">{{ $row->location_code }}</td>
          <td class="r bold text-danger">{{ number_format($row->current_qty, 0) }}</td>
          <td class="r text-muted">{{ number_format($row->min_qty, 0) }}</td>
          <td class="r text-muted">{{ $row->max_qty ? number_format($row->max_qty, 0) : '—' }}</td>
          <td class="r text-danger bold">-{{ number_format($row->shortage_qty, 0) }}</td>
          <td class="r">{{ $row->order_qty > 0 ? number_format($row->order_qty, 0) : '—' }}</td>
          <td class="c"><span class="badge {{ $badgeCls }}">{{ $badgeTxt }}</span></td>
        </tr>
      @empty
        <tr><td colspan="12" class="c text-muted" style="padding:12px">Không có dữ liệu</td></tr>
      @endforelse
    </tbody>
    @if (count($items) > 0)
    <tfoot>
      <tr>
        <td colspan="9" style="text-align:right">Tổng thiếu:</td>
        <td class="r text-danger">-{{ number_format($items->sum('shortage_qty'), 0) }}</td>
        <td colspan="2"></td>
      </tr>
    </tfoot>
    @endif
  </table>

  <div class="footer">
    Warehouse System — Báo cáo cảnh báo rủi ro | Tạo tự động lúc {{ now()->format('H:i:s d/m/Y') }}
  </div>

</body>
</html>