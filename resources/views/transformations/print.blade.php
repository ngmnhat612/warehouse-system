<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Phiếu tách/ghép hàng hóa — {{ $transformation->code }}</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Segoe UI', Arial, sans-serif;
      font-size: 12px;
      color: #1a1a1a;
      background: #fff;
      padding: 24px;
    }

    /* ── HEADER ─────────────────────────────────────────── */
    .header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 18px;
      border-bottom: 2px solid #333;
      padding-bottom: 12px;
    }
    .company-name {
      font-size: 16px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 1px;
    }
    .company-sub { font-size: 11px; color: #666; margin-top: 3px; }

    .doc-title { text-align: right; }
    .doc-title h2 {
      font-size: 18px;
      font-weight: 700;
      text-transform: uppercase;
    }
    .doc-title .code { font-size: 13px; color: #555; margin-top: 2px; }

    .badge-type {
      display: inline-block;
      margin-top: 4px;
      padding: 2px 10px;
      border-radius: 99px;
      font-size: 11px;
      font-weight: 600;
    }
    .badge-split  { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
    .badge-merge  { background: #e0f2fe; color: #0c4a6e; border: 1px solid #bae6fd; }
    .badge-done   { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }

    /* ── META ────────────────────────────────────────────── */
    .meta {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
      margin-bottom: 18px;
    }
    .meta-block {
      border: 1px solid #ddd;
      border-radius: 4px;
      padding: 10px 14px;
    }
    .meta-block h4 {
      font-size: 10px;
      text-transform: uppercase;
      color: #777;
      letter-spacing: 0.5px;
      margin-bottom: 7px;
      border-bottom: 1px solid #eee;
      padding-bottom: 4px;
    }
    .meta-row { display: flex; gap: 8px; margin-bottom: 4px; }
    .meta-label { color: #555; min-width: 120px; flex-shrink: 0; }
    .meta-value { font-weight: 600; }

    /* ── SECTION TITLE ───────────────────────────────────── */
    .section-title {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 12px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 6px;
      margin-top: 14px;
      padding-bottom: 4px;
      border-bottom: 1px solid #ddd;
    }
    .section-title .dot {
      width: 10px; height: 10px;
      border-radius: 50%;
      flex-shrink: 0;
    }
    .dot-consume { background: #ef4444; }
    .dot-produce { background: #22c55e; }

    /* ── TABLE ───────────────────────────────────────────── */
    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 6px;
      font-size: 11.5px;
    }
    thead tr { background: #f3f4f6; }
    th, td {
      border: 1px solid #d1d5db;
      padding: 5px 7px;
      text-align: left;
      vertical-align: middle;
    }
    th {
      font-size: 10.5px;
      text-transform: uppercase;
      letter-spacing: 0.3px;
      color: #374151;
      white-space: nowrap;
    }
    td.num  { text-align: right; }
    td.ctr  { text-align: center; color: #6b7280; font-size: 11px; }
    tfoot td { background: #f9fafb; font-weight: 600; }

    .mono {
      font-family: 'Consolas', 'Courier New', monospace;
      font-size: 11px;
    }
    .text-muted { color: #9ca3af; }

    /* ── ARROW DIVIDER ───────────────────────────────────── */
    .arrow-divider {
      text-align: center;
      padding: 8px 0;
      font-size: 13px;
      color: #6b7280;
      letter-spacing: 1px;
    }
    .arrow-divider span {
      display: inline-block;
      background: #f3f4f6;
      border: 1px solid #d1d5db;
      border-radius: 4px;
      padding: 3px 16px;
      font-weight: 600;
    }

    /* ── SIGNATURES ──────────────────────────────────────── */
    .signatures {
      display: grid;
      grid-template-columns: 1fr 1fr 1fr;
      gap: 24px;
      margin-top: 32px;
    }
    .sig-block { text-align: center; }
    .sig-block .sig-title {
      font-weight: 600;
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: 0.4px;
      margin-bottom: 2px;
    }
    .sig-block .sig-note {
      font-size: 10px;
      color: #9ca3af;
      margin-bottom: 44px;
    }
    .sig-block .sig-line {
      border-top: 1px solid #999;
      padding-top: 4px;
      font-size: 11px;
      color: #555;
    }

    /* ── FOOTER ──────────────────────────────────────────── */
    .footer-note {
      font-size: 10px;
      color: #bbb;
      text-align: center;
      border-top: 1px solid #eee;
      padding-top: 8px;
      margin-top: 20px;
    }

    /* ── NO-PRINT TOOLBAR ────────────────────────────────── */
    .no-print {
      position: fixed;
      top: 16px;
      right: 16px;
      display: flex;
      gap: 8px;
      z-index: 999;
    }
    .btn-print, .btn-back {
      padding: 7px 16px;
      border-radius: 6px;
      font-size: 12px;
      font-weight: 600;
      cursor: pointer;
      border: none;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    .btn-print { background: #2563eb; color: #fff; }
    .btn-back  { background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; }

    @media print {
      body { padding: 10px; }
      .no-print { display: none !important; }
      @page { margin: 12mm 10mm; }
    }
  </style>
</head>
<body>

@php
  $fmt = fn($n) => rtrim(rtrim(number_format((float)$n, 3, '.', ','), '0'), '.');

  $typeText  = $transformation->type === 1 ? 'Tách hàng' : 'Ghép hàng';
  $typeClass = $transformation->type === 1 ? 'badge-split' : 'badge-merge';
  $arrowLabel = $transformation->type === 1 ? '▼  Tách thành  ▼' : '▼  Ghép thành  ▼';

  $hasLotConsume    = $transformation->consumeDetails->contains(fn($d) => in_array((int)($d->product?->tracking_type ?? 1), [2,4]));
  $hasSerialConsume = $transformation->consumeDetails->contains(fn($d) => in_array((int)($d->product?->tracking_type ?? 1), [3,4]));
  $hasLotProduce    = $transformation->produceDetails->contains(fn($d) => in_array((int)($d->product?->tracking_type ?? 1), [2,4]));
  $hasSerialProduce = $transformation->produceDetails->contains(fn($d) => in_array((int)($d->product?->tracking_type ?? 1), [3,4]));
@endphp

{{-- TOOLBAR (ẩn khi in) --}}
<div class="no-print">
  <a href="{{ route('transformations.show', $transformation) }}" class="btn-back">← Quay lại</a>
  <button onclick="window.print()" class="btn-print">🖨 In phiếu</button>
</div>

{{-- HEADER --}}
<div class="header">
  <div>
    <div class="company-name">Ment Automation</div>
    <div class="company-sub">Hệ thống quản lý kho</div>
  </div>
  <div class="doc-title">
    <h2>Phiếu tách / ghép hàng hóa</h2>
    <div class="code">{{ $transformation->code }}</div>
    <div>
      <span class="badge-type {{ $typeClass }}">{{ $typeText }}</span>
      <span class="badge-type badge-done">Hoàn thành</span>
    </div>
  </div>
</div>

{{-- META --}}
<div class="meta">
  <div class="meta-block">
    <h4>Thông tin phiếu</h4>
    <div class="meta-row">
      <span class="meta-label">Mã phiếu:</span>
      <span class="meta-value">{{ $transformation->code }}</span>
    </div>
    <div class="meta-row">
      <span class="meta-label">Loại thao tác:</span>
      <span class="meta-value">{{ $typeText }}</span>
    </div>
    <div class="meta-row">
      <span class="meta-label">Công thức BOM:</span>
      <span class="meta-value">
        {{ $transformation->bom ? $transformation->bom->code . ' — ' . $transformation->bom->name : '—' }}
      </span>
    </div>
    <div class="meta-row">
      <span class="meta-label">Hệ số thực hiện:</span>
      <span class="meta-value">× {{ $transformation->multiplier ?? 1 }}</span>
    </div>
    <div class="meta-row">
      <span class="meta-label">Ngày thực hiện:</span>
      <span class="meta-value">
        {{ $transformation->transformation_date?->format('d/m/Y') ?? '—' }}
      </span>
    </div>
    @if($transformation->note)
    <div class="meta-row">
      <span class="meta-label">Ghi chú:</span>
      <span class="meta-value">{{ $transformation->note }}</span>
    </div>
    @endif
  </div>

  <div class="meta-block">
    <h4>Người thực hiện</h4>
    <div class="meta-row">
      <span class="meta-label">Người tạo phiếu:</span>
      <span class="meta-value">{{ $transformation->createdBy?->name ?? '—' }}</span>
    </div>
    @if($transformation->confirmedBy)
    <div class="meta-row">
      <span class="meta-label">Người duyệt:</span>
      <span class="meta-value">{{ $transformation->confirmedBy->name }}</span>
    </div>
    @endif
    <div class="meta-row">
      <span class="meta-label">Ngày in:</span>
      <span class="meta-value">{{ now()->format('d/m/Y H:i') }}</span>
    </div>
    <div class="meta-row" style="margin-top:8px;">
      <span class="meta-label">SL dòng đầu vào:</span>
      <span class="meta-value">{{ $transformation->consumeDetails->count() }} dòng</span>
    </div>
    <div class="meta-row">
      <span class="meta-label">SL dòng đầu ra:</span>
      <span class="meta-value">{{ $transformation->produceDetails->count() }} dòng</span>
    </div>
  </div>
</div>

{{-- BẢNG ĐẦU VÀO --}}
<div class="section-title">
  <span class="dot dot-consume"></span>
  Hàng hóa đầu vào
  <span style="font-size:10px; color:#9ca3af; font-weight:400; text-transform:none;">
    ({{ $transformation->type === 2 ? 'Nhiều nguồn' : 'Nguồn gốc' }})
  </span>
</div>

<table>
  <thead>
    <tr>
      <th style="width:28px">#</th>
      <th style="width:90px">Mã hàng</th>
      <th>Tên hàng hóa</th>
      <th style="width:55px">ĐVT</th>
      <th style="width:85px" class="num">SL BOM</th>
      <th style="width:85px" class="num">SL thực tế</th>
      <th style="width:95px">Vị trí kho</th>
      @if($hasLotConsume)
        <th style="width:110px">Số Lot</th>
      @endif
      @if($hasSerialConsume)
        <th style="width:110px">Số Serial</th>
      @endif
    </tr>
  </thead>
  <tbody>
    @forelse($transformation->consumeDetails as $i => $d)
    <tr>
      <td class="ctr">{{ $i + 1 }}</td>
      <td style="font-weight:600;" class="mono">{{ $d->product?->code ?? '—' }}</td>
      <td>{{ $d->product?->name ?? '—' }}</td>
      <td>{{ $d->uom?->name ?? '—' }}</td>
      <td class="num text-muted">{{ $fmt($d->bom_qty ?? $d->quantity) }}</td>
      <td class="num" style="font-weight:700;">{{ $fmt($d->quantity) }}</td>
      <td class="mono" style="font-size:11px;">{{ $d->location?->code ?? '—' }}</td>
      @if($hasLotConsume)
        <td class="mono">
          @if($d->lot) {{ $d->lot->lot_number }}
          @elseif(in_array((int)($d->product?->tracking_type ?? 1), [2,4]))
            <span style="color:#ef4444;font-size:10px;">—</span>
          @else <span class="text-muted">—</span>
          @endif
        </td>
      @endif
      @if($hasSerialConsume)
        <td class="mono">
          @if($d->serial) {{ $d->serial->serial_number }}
          @elseif(in_array((int)($d->product?->tracking_type ?? 1), [3,4]))
            <span style="color:#ef4444;font-size:10px;">—</span>
          @else <span class="text-muted">—</span>
          @endif
        </td>
      @endif
    </tr>
    @empty
    <tr>
      <td colspan="10" class="ctr" style="padding:12px;">Không có dòng đầu vào.</td>
    </tr>
    @endforelse
  </tbody>
  @if($transformation->consumeDetails->count())
  <tfoot>
    <tr>
      <td colspan="{{ 4 }}" style="text-align:right; font-size:10.5px; color:#6b7280;">Tổng cộng:</td>
      <td class="num text-muted">{{ $fmt($transformation->consumeDetails->sum('bom_qty')) }}</td>
      <td class="num">{{ $fmt($transformation->consumeDetails->sum('quantity')) }}</td>
      <td colspan="{{ 1 + ($hasLotConsume ? 1 : 0) + ($hasSerialConsume ? 1 : 0) }}"></td>
    </tr>
  </tfoot>
  @endif
</table>

{{-- ARROW --}}
<div class="arrow-divider">
  <span>{{ $arrowLabel }}</span>
</div>

{{-- BẢNG ĐẦU RA --}}
<div class="section-title">
  <span class="dot dot-produce"></span>
  Hàng hóa đầu ra
  <span style="font-size:10px; color:#9ca3af; font-weight:400; text-transform:none;">
    ({{ $transformation->type === 1 ? 'Nhiều sản phẩm' : 'Sản phẩm ghép' }})
  </span>
</div>

<table>
  <thead>
    <tr>
      <th style="width:28px">#</th>
      <th style="width:90px">Mã hàng</th>
      <th>Tên hàng hóa</th>
      <th style="width:55px">ĐVT</th>
      <th style="width:85px" class="num">SL BOM</th>
      <th style="width:85px" class="num">SL thực tế</th>
      <th style="width:95px">Vị trí đích</th>
      @if($hasLotProduce)
        <th style="width:110px">Lot mới</th>
      @endif
      @if($hasSerialProduce)
        <th style="width:110px">Serial mới</th>
      @endif
      <th style="width:85px">Hạn dùng</th>
    </tr>
  </thead>
  <tbody>
    @forelse($transformation->produceDetails as $i => $d)
    @php
      $tracking = (int)($d->product?->tracking_type ?? 1);
      $lotDisplay    = $d->lot?->lot_number ?? $d->lot_number ?? null;
      $serialDisplay = $d->serial?->serial_number ?? $d->serial_number ?? null;
    @endphp
    <tr>
      <td class="ctr">{{ $i + 1 }}</td>
      <td style="font-weight:600;" class="mono">{{ $d->product?->code ?? '—' }}</td>
      <td>{{ $d->product?->name ?? '—' }}</td>
      <td>{{ $d->uom?->name ?? '—' }}</td>
      <td class="num text-muted">{{ $fmt($d->bom_qty ?? $d->quantity) }}</td>
      <td class="num" style="font-weight:700; color:#16a34a;">{{ $fmt($d->quantity) }}</td>
      <td class="mono" style="font-size:11px;">{{ $d->location?->code ?? '—' }}</td>
      @if($hasLotProduce)
        <td class="mono">
          @if($lotDisplay) {{ $lotDisplay }}
          @elseif(in_array($tracking, [2,4]))
            <span style="color:#ef4444;font-size:10px;">—</span>
          @else <span class="text-muted">—</span>
          @endif
        </td>
      @endif
      @if($hasSerialProduce)
        <td class="mono">
          @if($serialDisplay) {{ $serialDisplay }}
          @elseif(in_array($tracking, [3,4]))
            <span style="color:#ef4444;font-size:10px;">—</span>
          @else <span class="text-muted">—</span>
          @endif
        </td>
      @endif
      <td style="font-size:11px;">
        {{ $d->expiry_date ? \Carbon\Carbon::parse($d->expiry_date)->format('d/m/Y') : '—' }}
      </td>
    </tr>
    @empty
    <tr>
      <td colspan="11" class="ctr" style="padding:12px;">Không có dòng đầu ra.</td>
    </tr>
    @endforelse
  </tbody>
  @if($transformation->produceDetails->count())
  <tfoot>
    <tr>
      <td colspan="4" style="text-align:right; font-size:10.5px; color:#6b7280;">Tổng cộng:</td>
      <td class="num text-muted">{{ $fmt($transformation->produceDetails->sum('bom_qty')) }}</td>
      <td class="num" style="color:#16a34a;">{{ $fmt($transformation->produceDetails->sum('quantity')) }}</td>
      <td colspan="{{ 1 + ($hasLotProduce ? 1 : 0) + ($hasSerialProduce ? 1 : 0) + 1 }}"></td>
    </tr>
  </tfoot>
  @endif
</table>

{{-- KÝ TÊN --}}
<div class="signatures">
  <div class="sig-block">
    <div class="sig-title">Người lập phiếu</div>
    <div class="sig-note">(Ký, ghi rõ họ tên)</div>
    <div class="sig-line">{{ $transformation->createdBy?->name ?? '' }}</div>
  </div>
  <div class="sig-block">
    <div class="sig-title">Người duyệt</div>
    <div class="sig-note">(Ký, ghi rõ họ tên)</div>
    <div class="sig-line">{{ $transformation->confirmedBy?->name ?? '' }}</div>
  </div>
  <div class="sig-block">
    <div class="sig-title">Thủ kho</div>
    <div class="sig-note">(Ký, ghi rõ họ tên)</div>
    <div class="sig-line">&nbsp;</div>
  </div>
</div>

<div class="footer-note">
  In lúc {{ now()->format('d/m/Y H:i:s') }} — Ment Automation Warehouse System
</div>

<script>
  window.addEventListener('load', function () {
    window.print();
  });
</script>

</body>
</html>