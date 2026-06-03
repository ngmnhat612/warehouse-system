<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; }

    .page-header { text-align: center; margin-bottom: 18px; padding-bottom: 10px; border-bottom: 2px solid #f59e0b; }
    .page-header h1 { font-size: 15px; font-weight: bold; color: #78350f; margin-bottom: 4px; }
    .page-header p  { font-size: 9px; color: #64748b; }

    .kpi-row { display: flex; gap: 10px; margin-bottom: 18px; }
    .kpi-box { flex: 1; border: 1px solid #e2e8f0; border-radius: 5px; padding: 8px 10px; }
    .kpi-box .val { font-size: 18px; font-weight: bold; }
    .kpi-box .lbl { font-size: 9px; color: #64748b; margin-top: 2px; }
    .kpi-box.warn   .val { color: #d97706; }
    .kpi-box.danger .val { color: #dc2626; }
    .kpi-box.dark   .val { color: #1e293b; }
    .kpi-box.blue   .val { color: #2563eb; }

    table { width: 100%; border-collapse: collapse; }
    thead th {
      background: #f59e0b; color: #fff; font-size: 9px; font-weight: bold;
      padding: 5px 6px; text-align: left; border: 1px solid #d97706;
    }
    thead th.r { text-align: right; }
    thead th.c { text-align: center; }
    tbody tr:nth-child(even) { background: #fffbeb; }
    tbody td { padding: 4px 6px; border: 1px solid #fde68a; font-size: 9px; vertical-align: middle; }
    tbody td.r { text-align: right; }
    tbody td.c { text-align: center; }
    tbody tr.expired { background: #f1f5f9; color: #64748b; }
    tbody tr.urgent  { background: #fef2f2; }
    tfoot td { background: #fef3c7; font-weight: bold; padding: 5px 6px; border: 1px solid #fcd34d; font-size: 9px; }
    tfoot td.r { text-align: right; }

    .badge { display: inline-block; padding: 1px 5px; border-radius: 3px; font-size: 8px; font-weight: bold; }
    .b-danger { background: #fee2e2; color: #b91c1c; }
    .b-warn   { background: #fef3c7; color: #92400e; }
    .b-info   { background: #e0f2fe; color: #0369a1; }
    .b-dark   { background: #f1f5f9; color: #1e293b; }

    .text-danger { color: #dc2626; }
    .text-warn   { color: #d97706; }
    .text-info   { color: #0369a1; }
    .text-muted  { color: #94a3b8; }
    .bold        { font-weight: bold; }

    .footer { margin-top: 16px; padding-top: 6px; border-top: 1px solid #e2e8f0;
              font-size: 8px; color: #94a3b8; text-align: center; }
  </style>
</head>
<body>

  <div class="page-header">
    <h1>📅 CẢNH BÁO HÀNG CẬN DATE / SẮP HẾT HẠN (TRONG {{ $days }} NGÀY)</h1>
    <p>Xuất lúc: {{ now()->format('H:i:s d/m/Y') }}</p>
  </div>

  <div class="kpi-row">
    <div class="kpi-box warn">
      <div class="val">{{ number_format($summary['total']) }}</div>
      <div class="lbl">Lô sắp hết hạn ({{ $days }} ngày)</div>
    </div>
    <div class="kpi-box danger">
      <div class="val">{{ number_format($summary['within_7']) }}</div>
      <div class="lbl">Hết hạn trong 7 ngày</div>
    </div>
    <div class="kpi-box dark">
      <div class="val">{{ number_format($summary['expired']) }}</div>
      <div class="lbl">Đã hết hạn (còn tồn)</div>
    </div>
    <div class="kpi-box blue">
      <div class="val">{{ number_format($summary['total_qty'], 0) }}</div>
      <div class="lbl">Tổng số lượng cận date</div>
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
        <th style="width:85px" class="c">Số Lot</th>
        <th style="width:55px" class="c">Vị trí</th>
        <th style="width:65px" class="r">SL Tồn</th>
        <th style="width:75px" class="c">Ngày HH</th>
        <th style="width:80px" class="c">Còn lại</th>
        <th style="width:65px" class="c">Mức độ</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($items as $i => $row)
        @php
          $daysLeft = (int) $row->days_left;
          if ($daysLeft < 0) {
              $rowClass  = 'expired';
              $badgeCls  = 'b-dark';
              $badgeTxt  = 'Đã hết hạn';
              $remaining = 'Quá ' . abs($daysLeft) . ' ngày';
              $remColor  = 'text-muted';
          } elseif ($daysLeft <= 7) {
              $rowClass  = 'urgent';
              $badgeCls  = 'b-danger';
              $badgeTxt  = 'Khẩn cấp';
              $remaining = $daysLeft . ' ngày';
              $remColor  = 'text-danger bold';
          } elseif ($daysLeft <= 14) {
              $rowClass  = '';
              $badgeCls  = 'b-warn';
              $badgeTxt  = 'Gấp';
              $remaining = $daysLeft . ' ngày';
              $remColor  = 'text-warn bold';
          } else {
              $rowClass  = '';
              $badgeCls  = 'b-info';
              $badgeTxt  = 'Chú ý';
              $remaining = $daysLeft . ' ngày';
              $remColor  = 'text-info';
          }
        @endphp
        <tr class="{{ $rowClass }}">
          <td class="c text-muted">{{ $i + 1 }}</td>
          <td>{{ $row->product_code }}</td>
          <td>{{ $row->product_name }}</td>
          <td class="text-muted">{{ $row->category_name ?? '—' }}</td>
          <td class="c">{{ $row->uom_name }}</td>
          <td class="c">{{ $row->lot_number ?? '—' }}</td>
          <td class="c">{{ $row->location_code }}</td>
          <td class="r bold">{{ number_format($row->quantity, 0) }}</td>
          <td class="c">{{ \Carbon\Carbon::parse($row->expiry_date)->format('d/m/Y') }}</td>
          <td class="c {{ $remColor }}">{{ $remaining }}</td>
          <td class="c"><span class="badge {{ $badgeCls }}">{{ $badgeTxt }}</span></td>
        </tr>
      @empty
        <tr><td colspan="11" class="c text-muted" style="padding:12px">Không có dữ liệu</td></tr>
      @endforelse
    </tbody>
    @if (count($items) > 0)
    <tfoot>
      <tr>
        <td colspan="7" style="text-align:right">Tổng số lượng:</td>
        <td class="r">{{ number_format($items->sum('quantity'), 0) }}</td>
        <td colspan="3"></td>
      </tr>
    </tfoot>
    @endif
  </table>

  <div class="footer">
    Warehouse System — Báo cáo cảnh báo rủi ro | Tạo tự động lúc {{ now()->format('H:i:s d/m/Y') }}
  </div>

</body>
</html>