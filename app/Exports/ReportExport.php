<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ReportExport implements WithMultipleSheets
{
    public function __construct(
        protected array $filters
    ) {}

    public function sheets(): array
    {
        return [
            new ReportNXTSheet($this->filters),
            new ReportStockSheet($this->filters),
        ];
    }
}

// ── Sheet 1: Nhập - Xuất - Tồn theo kỳ ──────────────────────────────────────
class ReportNXTSheet implements FromCollection, WithHeadings, WithMapping,
    WithStyles, WithTitle, ShouldAutoSize, WithColumnWidths
{
    protected int $rowNum = 0;

    public function __construct(protected array $filters) {}

    public function title(): string { return 'Nhập - Xuất - Tồn'; }

    public function collection()
    {
        $f        = $this->filters;
        $dateFrom = $f['date_from'] ?? now()->startOfMonth()->toDateString();
        $dateTo   = $f['date_to']   ?? now()->toDateString();

        return DB::table('products as p')
            ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
            ->leftJoin('uoms as u', 'p.uom_id', '=', 'u.id')
            ->leftJoinSub(
                DB::table('stock')->selectRaw('product_id, SUM(quantity) as current_qty')
                    ->groupBy('product_id'),
                'st', 'st.product_id', '=', 'p.id'
            )
            ->leftJoinSub(
                DB::table('stock_ledger')
                    ->selectRaw("product_id,
                        SUM(CASE WHEN direction = 1 THEN quantity ELSE 0 END) as receipt_qty,
                        SUM(CASE WHEN direction = 2 THEN quantity ELSE 0 END) as issue_qty")
                    ->whereBetween('transaction_date', [$dateFrom, $dateTo])
                    ->groupBy('product_id'),
                'sl', 'sl.product_id', '=', 'p.id'
            )
            ->select(
                'p.code as product_code',
                'p.name as product_name',
                'c.name as category_name',
                'u.name as uom_name',
                DB::raw('COALESCE(sl.receipt_qty, 0) as receipt_qty'),
                DB::raw('COALESCE(sl.issue_qty,   0) as issue_qty'),
                DB::raw('COALESCE(st.current_qty, 0) as closing_qty'),
                DB::raw('COALESCE(st.current_qty, 0) - COALESCE(sl.receipt_qty, 0) + COALESCE(sl.issue_qty, 0) as opening_qty')
            )
            ->where('p.status', 1)
            ->when($f['category_id'] ?? null, fn($q) => $q->where('p.category_id', $f['category_id']))
            ->when($f['product_id']  ?? null, fn($q) => $q->where('p.id',           $f['product_id']))
            ->orderBy('p.code')
            ->get();
    }

    public function headings(): array
    {
        $dateFrom = $this->filters['date_from'] ?? now()->startOfMonth()->toDateString();
        $dateTo   = $this->filters['date_to']   ?? now()->toDateString();

        return [
            ['BÁO CÁO NHẬP - XUẤT - TỒN KHO'],
            ["Kỳ báo cáo: {$dateFrom} — {$dateTo}"],
            [],
            ['STT', 'Mã hàng', 'Tên hàng hóa', 'Nhóm hàng', 'ĐVT',
             'Tồn đầu kỳ', 'Nhập trong kỳ', 'Xuất trong kỳ', 'Tồn cuối kỳ'],
        ];
    }

    public function map($row): array
    {
        return [
            ++$this->rowNum,
            $row->product_code,
            $row->product_name,
            $row->category_name ?? '—',
            $row->uom_name      ?? '—',
            (float) $row->opening_qty,
            (float) $row->receipt_qty,
            (float) $row->issue_qty,
            (float) $row->closing_qty,
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 14,
            'C' => 35,
            'D' => 18,
            'E' => 8,
            'F' => 14,
            'G' => 14,
            'H' => 14,
            'I' => 14,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        // Tiêu đề lớn
        $sheet->mergeCells('A1:I1');
        $sheet->mergeCells('A2:I2');

        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('A2')->applyFromArray([
            'font'      => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '555555']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Header row (row 4)
        $sheet->getStyle('A4:I4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical'   => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN,
                                           'color'       => ['rgb' => 'FFFFFF']]],
        ]);

        // Số liệu columns — căn phải
        $lastRow = $this->rowNum + 4;
        foreach (['F', 'G', 'H', 'I'] as $col) {
            $sheet->getStyle("{$col}5:{$col}{$lastRow}")->applyFromArray([
                'alignment'  => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                'numberFormat' => ['formatCode' => '#,##0'],
            ]);
        }

        // Tô màu cột tồn cuối kỳ (I)
        $sheet->getStyle("I5:I{$lastRow}")->getFont()->setBold(true);

        // Dòng tổng cộng (footer)
        if ($this->rowNum > 0) {
            $footerRow = $lastRow + 1;
            $sheet->setCellValue("E{$footerRow}", 'Tổng cộng:');
            $sheet->setCellValue("F{$footerRow}", "=SUM(F5:F{$lastRow})");
            $sheet->setCellValue("G{$footerRow}", "=SUM(G5:G{$lastRow})");
            $sheet->setCellValue("H{$footerRow}", "=SUM(H5:H{$lastRow})");
            $sheet->setCellValue("I{$footerRow}", "=SUM(I5:I{$lastRow})");

            $sheet->getStyle("A{$footerRow}:I{$footerRow}")->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EFF6FF']],
            ]);
            foreach (['F', 'G', 'H', 'I'] as $col) {
                $sheet->getStyle("{$col}{$footerRow}")->applyFromArray([
                    'alignment'    => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                    'numberFormat' => ['formatCode' => '#,##0'],
                ]);
            }
        }

        // Freeze header
        $sheet->freezePane('A5');

        return [];
    }
}

// ── Sheet 2: Tồn kho đa chiều (product × location × lot) ────────────────────
class ReportStockSheet implements FromCollection, WithHeadings, WithMapping,
    WithStyles, WithTitle, ShouldAutoSize, WithColumnWidths
{
    protected int $rowNum = 0;

    public function __construct(protected array $filters) {}

    public function title(): string { return 'Tồn kho chi tiết'; }

    public function collection()
    {
        $f = $this->filters;

        return DB::table('stock as s')
            ->join('products as p',   's.product_id',  '=', 'p.id')
            ->join('locations as l',  's.location_id', '=', 'l.id')
            ->join('uoms as u',       'p.uom_id',      '=', 'u.id')
            ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
            ->leftJoin('lots as lt',  's.lot_id',      '=', 'lt.id')
            ->leftJoin('serials as sr','s.serial_id',  '=', 'sr.id')
            ->select(
                'p.code as product_code',
                'p.name as product_name',
                'c.name as category_name',
                'u.name as uom_name',
                'l.code as location_code',
                'l.name as location_name',
                'lt.lot_number',
                'lt.expiry_date',
                'sr.serial_number',
                's.quantity',
                's.reserved_qty',
                DB::raw('s.quantity - s.reserved_qty as available_qty'),
                's.expiry_date as stock_expiry',
                's.status',
            )
            ->where('p.status', 1)
            ->where('s.quantity', '>', 0)
            ->when($f['category_id'] ?? null, fn($q) => $q->where('p.category_id', $f['category_id']))
            ->when($f['product_id']  ?? null, fn($q) => $q->where('p.id',           $f['product_id']))
            ->orderBy('p.code')
            ->orderBy('l.code')
            ->get();
    }

    public function headings(): array
    {
        return [
            ['TỒN KHO CHI TIẾT ĐA CHIỀU'],
            ['Ngày xuất: ' . now()->format('d/m/Y H:i')],
            [],
            ['STT', 'Mã hàng', 'Tên hàng hóa', 'Nhóm hàng', 'ĐVT',
             'Vị trí', 'Tên vị trí', 'Số Lot', 'Hết hạn', 'Số Serial',
             'Tồn thực tế', 'Đã đặt giữ', 'Khả dụng', 'Trạng thái'],
        ];
    }

    public function map($row): array
    {
        $statusMap = [1 => 'Bình thường', 2 => 'Cách ly', 3 => 'Hết hạn'];
        $expiry    = $row->expiry_date ?? $row->lot_expiry ?? null;

        return [
            ++$this->rowNum,
            $row->product_code,
            $row->product_name,
            $row->category_name ?? '—',
            $row->uom_name,
            $row->location_code,
            $row->location_name,
            $row->lot_number    ?? '—',
            $row->expiry_date   ? \Carbon\Carbon::parse($row->expiry_date)->format('d/m/Y') : '—',
            $row->serial_number ?? '—',
            (float) $row->quantity,
            (float) $row->reserved_qty,
            (float) $row->available_qty,
            $statusMap[$row->status] ?? '—',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,  'B' => 14, 'C' => 35, 'D' => 18, 'E' => 8,
            'F' => 10, 'G' => 20, 'H' => 16, 'I' => 12, 'J' => 16,
            'K' => 12, 'L' => 12, 'M' => 12, 'N' => 12,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->mergeCells('A1:N1');
        $sheet->mergeCells('A2:N2');

        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('A2')->applyFromArray([
            'font'      => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '555555']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getStyle('A4:N4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F766E']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical'   => Alignment::VERTICAL_CENTER],
        ]);

        $lastRow = $this->rowNum + 4;
        foreach (['K', 'L', 'M'] as $col) {
            $sheet->getStyle("{$col}5:{$col}{$lastRow}")->applyFromArray([
                'alignment'    => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                'numberFormat' => ['formatCode' => '#,##0'],
            ]);
        }

        $sheet->freezePane('A5');

        return [];
    }
}
