<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phiếu hủy hàng — {{ $scrap->code }}</title>
    <style>
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        font-family: 'Segoe UI', Arial, sans-serif;
        font-size: 12px;
        color: #1a1a1a;
        background: #fff;
        padding: 24px;
    }

    .header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 20px;
        border-bottom: 2px solid #333;
        padding-bottom: 12px;
    }

    .company-name {
        font-size: 16px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .doc-title {
        text-align: right;
    }

    .doc-title h2 {
        font-size: 18px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .doc-title .code {
        font-size: 13px;
        color: #555;
        margin-top: 2px;
    }

    .meta {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin-bottom: 20px;
    }

    .meta-block {
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 10px 14px;
    }

    .meta-block h4 {
        font-size: 11px;
        text-transform: uppercase;
        color: #777;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
        border-bottom: 1px solid #eee;
        padding-bottom: 4px;
    }

    .meta-row {
        display: flex;
        gap: 8px;
        margin-bottom: 4px;
    }

    .meta-label {
        color: #555;
        min-width: 110px;
        flex-shrink: 0;
    }

    .meta-value {
        font-weight: 600;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }

    thead tr {
        background: #f0f0f0;
    }

    th,
    td {
        border: 1px solid #ccc;
        padding: 6px 8px;
        text-align: left;
        vertical-align: middle;
    }

    th {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        color: #444;
    }

    td.num {
        text-align: right;
    }

    td.center {
        text-align: center;
        color: #666;
        font-size: 11px;
    }

    tfoot td {
        background: #f8f8f8;
        font-weight: 600;
    }

    .badge-completed {
        display: inline-block;
        padding: 2px 8px;
        background: #d1fae5;
        color: #065f46;
        border-radius: 99px;
        font-size: 11px;
        font-weight: 600;
    }

    .badge-pending {
        display: inline-block;
        padding: 2px 8px;
        background: #fef3c7;
        color: #92400e;
        border-radius: 99px;
        font-size: 11px;
        font-weight: 600;
    }

    .signatures {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr 1fr;
        gap: 24px;
        margin-top: 32px;
    }

    .sig-block {
        text-align: center;
    }

    .sig-block .sig-title {
        font-weight: 600;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        margin-bottom: 4px;
    }

    .sig-block .sig-note {
        font-size: 10px;
        color: #888;
        margin-bottom: 48px;
    }

    .sig-block .sig-line {
        border-top: 1px solid #999;
        padding-top: 4px;
        font-size: 11px;
        color: #555;
    }

    .footer-note {
        font-size: 10px;
        color: #aaa;
        text-align: center;
        border-top: 1px solid #eee;
        padding-top: 8px;
    }

    @media print {
        body {
            padding: 10px;
        }

        .no-print {
            display: none !important;
        }
    }
    </style>
</head>

<body>

    {{-- NÚT IN --}}
    <div class="no-print" style="margin-bottom:16px; text-align:right">
        <button onclick="window.print()"
            style="background:#334155;color:#fff;border:none;border-radius:4px;padding:6px 14px;font-size:11px;cursor:pointer">
            🖨 In phiếu
        </button>
        <a href="{{ route('scraps.show', $scrap) }}"
            style="margin-left:8px;background:#f1f5f9;color:#334155;border:none;border-radius:4px;padding:6px 14px;font-size:11px;cursor:pointer;text-decoration:none">
            ← Quay lại
        </a>
    </div>

    @php
    $statusMap = [
        1 => ['Nháp',       'badge-pending'],
        2 => ['Chờ duyệt',  'badge-pending'],
        3 => ['Đã duyệt',   'badge-pending'],
        4 => ['Hoàn thành', 'badge-completed'],
        5 => ['Đã hủy',     ''],
    ];
    [$statusLabel, $statusClass] = $statusMap[$scrap->status] ?? ['—', ''];
    @endphp

    <div class="header">
        <div>
            <div class="company-name">Ment Automation</div>
            <div style="font-size:11px; color:#666; margin-top:3px;">Hệ thống quản lý kho</div>
        </div>
        <div class="doc-title">
            <h2>Phiếu hủy hàng</h2>
            <div class="code">{{ $scrap->code }}</div>
            <div style="margin-top:4px;">
                <span class="{{ $statusClass }}">{{ $statusLabel }}</span>
            </div>
        </div>
    </div>

    <div class="meta">
        <div class="meta-block">
            <h4>Thông tin phiếu</h4>
            <div class="meta-row">
                <span class="meta-label">Mã phiếu:</span>
                <span class="meta-value">{{ $scrap->code }}</span>
            </div>
            <div class="meta-row">
                <span class="meta-label">Ngày hủy:</span>
                <span class="meta-value">
                    {{ $scrap->scrap_date ? \Carbon\Carbon::parse($scrap->scrap_date)->format('d/m/Y') : '—' }}
                </span>
            </div>
            <div class="meta-row">
                <span class="meta-label">Tổng dòng hàng:</span>
                <span class="meta-value">{{ $scrap->details->count() }} dòng</span>
            </div>
            @if($scrap->note)
            <div class="meta-row">
                <span class="meta-label">Ghi chú:</span>
                <span class="meta-value">{{ $scrap->note }}</span>
            </div>
            @endif
        </div>
        <div class="meta-block">
            <h4>Người thực hiện</h4>
            <div class="meta-row">
                <span class="meta-label">Người tạo:</span>
                <span class="meta-value">{{ $scrap->createdBy?->name ?? '—' }}</span>
            </div>
            @if($scrap->approvedBy)
            <div class="meta-row">
                <span class="meta-label">Người duyệt:</span>
                <span class="meta-value">{{ $scrap->approvedBy->name }}</span>
            </div>
            @endif
            @if($scrap->approved_at)
            <div class="meta-row">
                <span class="meta-label">Ngày duyệt:</span>
                <span class="meta-value">{{ \Carbon\Carbon::createFromTimestamp($scrap->approved_at)->format('d/m/Y') }}</span>
            </div>
            @endif
            <div class="meta-row">
                <span class="meta-label">Ngày in:</span>
                <span class="meta-value">{{ now()->format('d/m/Y H:i') }}</span>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:32px">#</th>
                <th>Mã hàng</th>
                <th>Tên hàng hóa</th>
                <th>ĐVT</th>
                <th class="num">Số lượng</th>
                <th>Vị trí kho</th>
                <th>Lot / Batch</th>
                <th>Lý do hủy</th>
            </tr>
        </thead>
        <tbody>
            @forelse($scrap->details as $i => $row)
            <tr>
                <td class="center">{{ $i + 1 }}</td>
                <td style="font-weight:600;">{{ $row->product?->code ?? '—' }}</td>
                <td>{{ $row->product?->name ?? '—' }}</td>
                <td>{{ $row->uom?->name ?? $row->product?->uom?->name ?? '—' }}</td>
                <td class="num" style="font-weight:600;">{{ number_format($row->quantity, 3) }}</td>
                <td>{{ $row->location?->code ?? '—' }}</td>
                <td>{{ $row->lot?->lot_number ?? '—' }}</td>
                <td style="color:#555;">{{ $row->reason ?? '—' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="center" style="padding:16px;">Không có dòng chi tiết.</td>
            </tr>
            @endforelse
        </tbody>
        @if($scrap->details->count())
        <tfoot>
            <tr>
                <td colspan="4" style="text-align:right; font-size:11px; color:#555;">
                    Tổng số lượng hủy:
                </td>
                <td class="num">{{ number_format($scrap->details->sum('quantity'), 3) }}</td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="signatures">
        <div class="sig-block">
            <div class="sig-title">Người lập phiếu</div>
            <div class="sig-note">(Ký, ghi rõ họ tên)</div>
            <div class="sig-line">{{ $scrap->createdBy?->name ?? '' }}</div>
        </div>
        <div class="sig-block">
            <div class="sig-title">Thủ kho</div>
            <div class="sig-note">(Ký, ghi rõ họ tên)</div>
            <div class="sig-line">&nbsp;</div>
        </div>
        <div class="sig-block">
            <div class="sig-title">Kiểm tra chất lượng</div>
            <div class="sig-note">(Ký, ghi rõ họ tên)</div>
            <div class="sig-line">&nbsp;</div>
        </div>
        <div class="sig-block">
            <div class="sig-title">Người duyệt</div>
            <div class="sig-note">(Ký, ghi rõ họ tên)</div>
            <div class="sig-line">{{ $scrap->approvedBy?->name ?? '' }}</div>
        </div>
    </div>

    <div class="footer-note" style="margin-top:24px;">
        In lúc {{ now()->format('d/m/Y H:i:s') }} — Ment Automation Warehouse System
    </div>

    <script>
    window.addEventListener('load', function() {
        window.print();
    });
    </script>
</body>

</html>