<?php

namespace App\Exports;

use App\Models\InventoryCheck;
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

class InventoryCheckExport implements WithMultipleSheets
{
    public function __construct(protected InventoryCheck $check) {}

    public function sheets(): array
    {
        return [
            new InventoryCheckSummarySheet($this->check),
            new InventoryCheckDiffSheet($this->check),
        ];
    }
}

// ── Sheet 1: Toàn bộ dòng kiểm kê ───────────────────────────────────────────
class InventoryCheckSummarySheet implements FromCollection, WithHeadings, WithMapping,
    WithStyles, WithTitle, ShouldAutoSize, WithColumnWidths
{
    protected int $rowNum = 0;

    public function __construct(protected InventoryCheck $check) {}

    public function title(): string { return 'Biên bản kiểm kê'; }

    public function collection()
    {
        return $this->check->lines()
            ->with(['product.uom', 'location', 'lot', 'countedBy'])
            ->orderBy('location_id')
            ->orderBy('product_id')
            ->get();
    }

    public function headings(): array
    {
        $typeLabels = [1 => 'Toàn kho', 2 => 'Theo khu vực', 3 => 'Theo mặt hàng'];
        $statusLabels = [1 => 'Nháp', 2 => 'Đang kiểm kê', 3 => 'Hoàn thành', 4 => 'Đã hủy'];

        return [
            ['BIÊN BẢN KIỂM KÊ KHO'],
            ['Mã phiếu: ' . $this->check->code . '    |    Loại: ' . ($typeLabels[$this->check->check_type] ?? '?') . '    |    Ngày kiểm: ' . ($this->check->check_date?->format('d/m/Y') ?? '—') . '    |    Trạng thái: ' . ($statusLabels[$this->check->status] ?? '?')],
            ['Người phụ trách: ' . ($this->check->assignedTo?->name ?? '—') . '    |    Ngày xuất: ' . now()->format('H:i d/m/Y')],
            [],
            ['STT', 'Mã hàng', 'Tên hàng hóa', 'ĐVT', 'Vị trí', 'Số lô',
             'Tồn hệ thống', 'Thực tế', 'Chênh lệch', 'Người kiểm', 'Thời gian kiểm', 'Trạng thái'],
        ];
    }

    public function map($line): array
    {
        $diff = $line->diff_qty;

        return [
            ++$this->rowNum,
            $line->product->code ?? '—',
            $line->product->name ?? '—',
            $line->uom->name     ?? '—',
            $line->location->code ?? '—',
            $line->lot->lot_number ?? '—',
            (float) $line->system_qty,
            $line->actual_qty !== null ? (float) $line->actual_qty : '',
            $line->actual_qty !== null ? (float) $diff : '',
            $line->countedBy?->name ?? '—',
            $line->counted_at?->format('H:i d/m/Y') ?? '—',
            $line->actual_qty === null ? 'Chưa kiểm'
                : ($diff == 0 ? 'Khớp' : ($diff > 0 ? 'Thừa' : 'Thiếu')),
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,  'B' => 14, 'C' => 35, 'D' => 8,
            'E' => 12, 'F' => 16, 'G' => 14, 'H' => 12,
            'I' => 12, 'J' => 18, 'K' => 18, 'L' => 12,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        // Tiêu đề
        $sheet->mergeCells('A1:L1');
        $sheet->mergeCells('A2:L2');
        $sheet->mergeCells('A3:L3');

        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        foreach (['A2', 'A3'] as $cell) {
            $sheet->getStyle($cell)->applyFromArray([
                'font'      => ['size' => 10, 'color' => ['rgb' => '555555']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
        }

        // Header row (row 5)
        $sheet->getStyle('A5:L5')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A8A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical'   => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN,
                                           'color'       => ['rgb' => 'FFFFFF']]],
        ]);
        $sheet->getRowDimension(5)->setRowHeight(22);

        // Data rows
        $lastRow = $this->rowNum + 5;

        // Số liệu căn phải
        foreach (['G', 'H', 'I'] as $col) {
            $sheet->getStyle("{$col}6:{$col}{$lastRow}")->applyFromArray([
                'alignment'    => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                'numberFormat' => ['formatCode' => '#,##0.###'],
            ]);
        }

        // Tô màu dòng chênh lệch
        for ($r = 6; $r <= $lastRow; $r++) {
            $diffVal = $sheet->getCell("I{$r}")->getValue();
            if (is_numeric($diffVal) && $diffVal > 0) {
                $sheet->getStyle("A{$r}:L{$r}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('DCFCE7');
            } elseif (is_numeric($diffVal) && $diffVal < 0) {
                $sheet->getStyle("A{$r}:L{$r}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('FEE2E2');
            }
        }

        // Tô màu cột trạng thái (L)
        for ($r = 6; $r <= $lastRow; $r++) {
            $status = $sheet->getCell("L{$r}")->getValue();
            $color = match($status) {
                'Thừa'      => ['font' => ['color' => ['rgb' => '16A34A']], 'font2' => ['bold' => true]],
                'Thiếu'     => ['font' => ['color' => ['rgb' => 'DC2626']], 'font2' => ['bold' => true]],
                'Chưa kiểm' => ['font' => ['color' => ['rgb' => '94A3B8']]],
                default     => [],
            };
            if (!empty($color)) {
                $sheet->getStyle("L{$r}")->applyFromArray($color);
            }
        }

        // Border toàn bảng
        if ($this->rowNum > 0) {
            $sheet->getStyle("A5:L{$lastRow}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN,
                                               'color'       => ['rgb' => 'E2E8F0']]],
            ]);

            // Footer tổng
            $footerRow = $lastRow + 2;
            $sheet->setCellValue("F{$footerRow}", 'Tổng cộng:');
            $sheet->setCellValue("G{$footerRow}", "=SUM(G6:G{$lastRow})");
            $sheet->setCellValue("H{$footerRow}", "=SUM(H6:H{$lastRow})");
            $sheet->setCellValue("I{$footerRow}", "=SUM(I6:I{$lastRow})");
            $sheet->getStyle("A{$footerRow}:L{$footerRow}")->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EFF6FF']],
            ]);
            foreach (['G', 'H', 'I'] as $col) {
                $sheet->getStyle("{$col}{$footerRow}")->applyFromArray([
                    'alignment'    => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                    'numberFormat' => ['formatCode' => '#,##0.###'],
                ]);
            }

            // Chữ ký
            $signRow = $footerRow + 3;
            $sheet->setCellValue("C{$signRow}", 'Người kiểm kê');
            $sheet->setCellValue("H{$signRow}", 'Thủ kho');
            $sheet->setCellValue("K{$signRow}", 'Quản lý kho');
            foreach (['C', 'H', 'K'] as $col) {
                $sheet->getStyle("{$col}{$signRow}")->applyFromArray([
                    'font'      => ['bold' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
            }
            $noteRow = $signRow + 1;
            foreach (['C', 'H', 'K'] as $col) {
                $sheet->setCellValue("{$col}{$noteRow}", '(Ký, ghi rõ họ tên)');
                $sheet->getStyle("{$col}{$noteRow}")->applyFromArray([
                    'font'      => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '94A3B8']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
            }
        }

        $sheet->freezePane('A6');

        return [];
    }
}

// ── Sheet 2: Chỉ các dòng chênh lệch ─────────────────────────────────────────
class InventoryCheckDiffSheet implements FromCollection, WithHeadings, WithMapping,
    WithStyles, WithTitle, ShouldAutoSize, WithColumnWidths
{
    protected int $rowNum = 0;

    public function __construct(protected InventoryCheck $check) {}

    public function title(): string { return 'Chênh lệch'; }

    public function collection()
    {
        return $this->check->lines()
            ->with(['product.uom', 'location', 'lot'])
            ->whereNotNull('actual_qty')
            ->whereRaw('actual_qty <> system_qty')
            ->orderByRaw('ABS(actual_qty - system_qty) DESC')
            ->get();
    }

    public function headings(): array
    {
        return [
            ['DANH SÁCH HÀNG HÓA CHÊNH LỆCH — ' . $this->check->code],
            ['Chỉ liệt kê các mặt hàng có actual_qty ≠ system_qty    |    Xuất lúc: ' . now()->format('H:i d/m/Y')],
            [],
            ['STT', 'Mã hàng', 'Tên hàng hóa', 'ĐVT', 'Vị trí', 'Số lô',
             'Tồn HT', 'Thực tế', 'Chênh lệch', 'Loại chênh'],
        ];
    }

    public function map($line): array
    {
        $diff = (float) $line->actual_qty - (float) $line->system_qty;

        return [
            ++$this->rowNum,
            $line->product->code ?? '—',
            $line->product->name ?? '—',
            $line->uom->name     ?? '—',
            $line->location->code ?? '—',
            $line->lot->lot_number ?? '—',
            (float) $line->system_qty,
            (float) $line->actual_qty,
            $diff,
            $diff > 0 ? 'Thừa' : 'Thiếu',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,  'B' => 14, 'C' => 35, 'D' => 8,
            'E' => 12, 'F' => 16, 'G' => 14, 'H' => 14,
            'I' => 14, 'J' => 10,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->mergeCells('A1:J1');
        $sheet->mergeCells('A2:J2');

        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 13],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('A2')->applyFromArray([
            'font'      => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '555555']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getStyle('A4:J4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DC2626']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical'   => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(4)->setRowHeight(22);

        $lastRow = $this->rowNum + 4;

        foreach (['G', 'H', 'I'] as $col) {
            $sheet->getStyle("{$col}5:{$col}{$lastRow}")->applyFromArray([
                'alignment'    => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                'numberFormat' => ['formatCode' => '#,##0.###'],
            ]);
        }

        // Tô màu xanh/đỏ theo loại chênh lệch
        for ($r = 5; $r <= $lastRow; $r++) {
            $type = $sheet->getCell("J{$r}")->getValue();
            $rgb  = $type === 'Thừa' ? 'DCFCE7' : 'FEE2E2';
            $sheet->getStyle("A{$r}:J{$r}")->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB($rgb);

            $fontColor = $type === 'Thừa' ? '16A34A' : 'DC2626';
            $sheet->getStyle("I{$r}:J{$r}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => $fontColor]],
            ]);
        }

        if ($this->rowNum > 0) {
            $sheet->getStyle("A4:J{$lastRow}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN,
                                               'color'       => ['rgb' => 'E2E8F0']]],
            ]);

            $footerRow = $lastRow + 2;
            $sheet->setCellValue("F{$footerRow}", 'Tổng chênh lệch:');
            $sheet->setCellValue("I{$footerRow}", "=SUM(I5:I{$lastRow})");
            $sheet->getStyle("A{$footerRow}:J{$footerRow}")->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF2F2']],
            ]);
            $sheet->getStyle("I{$footerRow}")->applyFromArray([
                'alignment'    => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                'numberFormat' => ['formatCode' => '#,##0.###'],
            ]);
        }

        $sheet->freezePane('A5');

        return [];
    }
}