<?php

namespace App\Exports;

use Illuminate\Support\Collection;
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

class AlertBelowMinExport implements FromCollection, WithHeadings, WithMapping,
    WithStyles, WithTitle, ShouldAutoSize, WithColumnWidths
{
    protected int $rowNum = 0;

    public function __construct(protected Collection $items) {}

    public function title(): string { return 'Dưới định mức'; }

    public function collection(): Collection { return $this->items; }

    public function headings(): array
    {
        return [
            ['CẢNH BÁO HÀNG DƯỚI ĐỊNH MỨC TỐI THIỂU'],
            ['Xuất lúc: ' . now()->format('H:i:s d/m/Y')],
            [],
            ['STT', 'Mã hàng', 'Tên hàng hóa', 'Nhóm hàng', 'ĐVT',
             'Vị trí', 'Tồn khả dụng', 'Min', 'Max', 'Còn thiếu', 'Cần đặt thêm', 'Mức độ'],
        ];
    }

    public function map($row): array
    {
        $pct   = $row->min_qty > 0 ? ($row->current_qty / $row->min_qty * 100) : 0;
        $level = $row->current_qty <= 0 ? 'Hết hàng' : ($pct <= 50 ? 'Nguy hiểm' : 'Chú ý');

        return [
            ++$this->rowNum,
            $row->product_code,
            $row->product_name,
            $row->category_name ?? '—',
            $row->uom_name,
            $row->location_code,
            (float) $row->current_qty,
            (float) $row->min_qty,
            $row->max_qty ? (float) $row->max_qty : '—',
            (float) $row->shortage_qty,
            ($row->order_qty !== null && $row->order_qty > 0) ? (float) $row->order_qty : '—',
            $level,
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,  'B' => 14, 'C' => 35, 'D' => 18, 'E' => 8,
            'F' => 12, 'G' => 14, 'H' => 12, 'I' => 12, 'J' => 12,
            'K' => 14, 'L' => 14,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->mergeCells('A1:L1');
        $sheet->mergeCells('A2:L2');

        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('A2')->applyFromArray([
            'font'      => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '555555']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('A4:L4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DC2626']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'borders' => ['allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color'       => ['rgb' => 'FFFFFF'],
            ]],
        ]);

        if ($this->rowNum > 0) {
            $lastRow = $this->rowNum + 4;
            foreach (['G', 'H', 'I', 'J', 'K'] as $col) {
                $sheet->getStyle("{$col}5:{$col}{$lastRow}")->applyFromArray([
                    'alignment'    => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                    'numberFormat' => ['formatCode' => '#,##0'],
                ]);
            }
        }

        $sheet->freezePane('A5');

        return [];
    }
}