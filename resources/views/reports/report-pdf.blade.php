<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; }

    .page-header { text-align: center; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid #2563eb; }
    .page-header h1 { font-size: 16px; font-weight: bold; color: #1e3a8a; margin-bottom: 4px; }
    .page-header p  { font-size: 10px; color: #64748b; }

    .kpi-row { display: flex; gap: 12px; margin-bottom: 20px; }
    .kpi-box { flex: 1; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 12px; }
    .kpi-box .val  { font-size: 18px; font-weight: bold; }
    .kpi-box .lbl  { font-size: 9px; color: #64748b; margin-top: 2px; }
    .kpi-box.in  .val { color: #16a34a; }
    .kpi-box.out .val { color: #d97706; }
    .kpi-box.bal .val { color: #2563eb; }
    .kpi-box.wrn .val { color: #dc2626; }

    .section-title { font-size: 12px; font-weight: bold; color: #1e3a8a;
                     margin: 18px 0 8px; border-left: 3px solid #2563eb; padding-left: 8px; }

    table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    thead th {
      background: #2563eb; color: #fff; font-size: 9px; font-weight: bold;
      padding: 5px 6px; text-align: left; border: 1px solid #1d4ed8;
    }
    thead th.r { text-align: right; }
    tbody tr:nth-child(even) { background: #f8fafc; }
    tbody td { padding: 4px 6px; border: 1px solid #e2e8f0; font-size: 9px; vertical-align: top; }
    tbody td.r { text-align: right; }
    tbody td.c { text-align: center; }
    tfoot td { background: #eff6ff; font-weight: bold; padding: 5px 6px;
               border: 1px solid #bfdbfe; font-size: 9px; }
    tfoot td.r { text-align: right; }

    .badge { display: inline-block; padding: 1px 5px; border-radius: 3px; font-size: 8px; }
    .badge-success  { background: #dcfce7; color: #15803d; }
    .badge-danger   { background: #fee2e2; color: #b91c1c; }
    .badge-warning  { background: #fef3c7; color: #92400e; }
    .badge-secondary { background: #f1f5f9; color: #475569; }

    .text-success { color: #16a34a; }
    .text-warning { color: #d97706; }
    .text-danger  { color: #dc2626; }
    .text-muted   { color: #94a3b8; }

    .footer { margin-top: 24px; padding-top: 8px; border-top: 1px solid #e2e8f0;
              font-size: 9px; color: #94a3b8; text-align: center; }

    .alert-section { display: flex; gap: 12px; }
    .alert-section > div { flex: 1; }
  </style>
</head>
<body>

  {{-- HEADER --}}
  <div class="page-header">
    <h1>BÁO CÁO NHẬP - XUẤT - TỒN KHO</h1>
    <p>Kỳ báo cáo: {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}
       &nbsp;|&nbsp; Xuất lúc: {{ now()->format('H:i d/m/Y') }}</p>
  </div>

  {{-- KPI --}}
  <div class="kpi-row">
    <div class="kpi-box in">
      <div class="val">{{ number_format($totalReceiptQty ?? 0) }}</div>
      <div class="lbl">Tổng nhập kỳ &nbsp;({{ number_format($totalReceiptVouchers ?? 0) }} phiếu)</div>
    </div>
    <div class="kpi-box out">
      <div class="val">{{ number_format($totalIssueQty ?? 0) }}</div>
      <div class="lbl">Tổng xuất kỳ &nbsp;({{ number_format($totalIssueVouchers ?? 0) }} phiếu)</div>
    </div>
    <div class="kpi-box bal">
      <div class="val">{{ number_format($closingStock ?? 0) }}</div>
      <div class="lbl">Tồn cuối kỳ</div>
    </div>
    <div class="kpi-box wrn">
      <div class="val">{{ number_format($lowStockCount ?? 0) }}</div>
      <div class="lbl">Hàng dưới ngưỡng &nbsp;/ {{ number_format($expiringSoonCount ?? 0) }} sắp hết hạn</div>
    </div>
  </div>

  {{-- BẢNG NXT --}}
  <div class="section-title">Bảng tổng hợp Nhập / Xuất / Tồn theo mặt hàng</div>
  <table>
    <thead>
      <tr>
        <th style="width:28px">#</th>
        <th style="width:70px">Mã hàng</th>
        <th>Tên hàng hóa</th>
        <th style="width:80px">Nhóm</th>
        <th style="width:35px">ĐVT</th>
        <th class="r" style="width:65px">Tồn đầu kỳ</th>
        <th class="r" style="width:65px">Nhập kỳ</th>
        <th class="r" style="width:65px">Xuất kỳ</th>
        <th class="r" style="width:65px">Tồn cuối kỳ</th>
        <th class="r" style="width:60px">Trạng thái</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($reportRows ?? [] as $i => $row)
        <tr>
          <td class="c">{{ $i + 1 }}</td>
          <td>{{ $row->product_code }}</td>
          <td>{{ $row->product_name }}</td>
          <td class="text-muted">{{ $row->category_name ?? '—' }}</td>
          <td class="c">{{ $row->uom_name ?? '—' }}</td>
          <td class="r">{{ number_format($row->opening_qty, 0) }}</td>
          <td class="r text-success">{{ $row->receipt_qty > 0 ? '+'.number_format($row->receipt_qty, 0) : '—' }}</td>
          <td class="r text-warning">{{ $row->issue_qty > 0 ? '-'.number_format($row->issue_qty, 0) : '—' }}</td>
          <td class="r" style="font-weight:bold">{{ number_format($row->closing_qty, 0) }}</td>
          <td class="c">
            @if(($row->closing_qty ?? 0) == 0)
              <span class="badge badge-secondary">Hết hàng</span>
            @elseif(($row->closing_qty ?? 0) <= ($row->min_stock ?? 0) && ($row->min_stock ?? 0) > 0)
              <span class="badge badge-danger">Dưới ngưỡng</span>
            @else
              <span class="badge badge-success">Bình thường</span>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="10" style="text-align:center; padding:12px; color:#94a3b8">Không có dữ liệu</td></tr>
      @endforelse
    </tbody>
    @if(count($reportRows ?? []) > 0)
    <tfoot>
      <tr>
        <td colspan="5" style="text-align:right">Tổng cộng:</td>
        <td class="r">{{ number_format(collect($reportRows)->sum('opening_qty'), 0) }}</td>
        <td class="r text-success">+{{ number_format(collect($reportRows)->sum('receipt_qty'), 0) }}</td>
        <td class="r text-warning">-{{ number_format(collect($reportRows)->sum('issue_qty'), 0) }}</td>
        <td class="r">{{ number_format(collect($reportRows)->sum('closing_qty'), 0) }}</td>
        <td></td>
      </tr>
    </tfoot>
    @endif
  </table>

  {{-- CẢNH BÁO --}}
  <div class="alert-section">

    <div>
      <div class="section-title" style="color:#dc2626; border-left-color:#dc2626">
        Hàng dưới ngưỡng tối thiểu ({{ count($lowStockItems ?? []) }})
      </div>
      <table>
        <thead>
          <tr>
            <th>Mặt hàng</th>
            <th class="r">Tồn</th>
            <th class="r">Min</th>
            <th class="r">Thiếu</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($lowStockItems ?? [] as $item)
            <tr>
              <td>{{ $item->name }}<br><span class="text-muted">{{ $item->code }}</span></td>
              <td class="r text-danger">{{ number_format($item->current_qty, 0) }}</td>
              <td class="r text-muted">{{ number_format($item->min_stock, 0) }}</td>
              <td class="r text-danger">-{{ number_format($item->min_stock - $item->current_qty, 0) }}</td>
            </tr>
          @empty
            <tr><td colspan="4" class="c text-muted">Không có</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div>
      <div class="section-title" style="color:#d97706; border-left-color:#d97706">
        Hàng sắp hết hạn trong 30 ngày ({{ count($expiringSoonItems ?? []) }})
      </div>
      <table>
        <thead>
          <tr>
            <th>Mặt hàng / Lot</th>
            <th class="r">SL</th>
            <th class="c">Hết hạn</th>
            <th class="c">Còn</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($expiringSoonItems ?? [] as $item)
            @php $daysLeft = now()->diffInDays(\Carbon\Carbon::parse($item->expiry_date), false); @endphp
            <tr>
              <td>{{ $item->product_name }}<br><span class="text-muted">{{ $item->lot_number }}</span></td>
              <td class="r">{{ number_format($item->quantity, 0) }}</td>
              <td class="c">{{ \Carbon\Carbon::parse($item->expiry_date)->format('d/m/Y') }}</td>
              <td class="c">
                <span class="badge {{ $daysLeft <= 7 ? 'badge-danger' : ($daysLeft <= 14 ? 'badge-warning' : 'badge-secondary') }}">
                  {{ $daysLeft }}n
                </span>
              </td>
            </tr>
          @empty
            <tr><td colspan="4" class="c text-muted">Không có</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

  </div>

  <div class="footer">
    Warehouse System — Báo cáo được tạo tự động lúc {{ now()->format('H:i:s d/m/Y') }}
  </div>

</body>
</html>
