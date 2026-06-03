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

class AlertNearExpiryExport implements FromCollection, WithHeadings, WithMapping,
    WithStyles, WithTitle, ShouldAutoSize, WithColumnWidths
{
    protected int $rowNum = 0;

    public function __construct(
        protected Collection $items,
        protected int $days = 30
    ) {}

    public function title(): string { return 'Hàng cận date'; }

    public function collection(): Collection { return $this->items; }

    public function headings(): array
    {
        return [
            ["CẢNH BÁO HÀNG CẬN DATE / SẮP HẾT HẠN (TRONG {$this->days} NGÀY)"],
            ['Xuất lúc: ' . now()->format('H:i:s d/m/Y')],
            [],
            ['STT', 'Mã hàng', 'Tên hàng hóa', 'Nhóm hàng', 'ĐVT',
             'Số Lot', 'Vị trí', 'Số lượng', 'Ngày hết hạn', 'Còn lại (ngày)', 'Mức độ'],
        ];
    }

    public function map($row): array
    {
        $daysLeft = (int) $row->days_left;
        $level    = $daysLeft < 0    ? 'Đã hết hạn'
                  : ($daysLeft <= 7  ? 'Khẩn cấp'
                  : ($daysLeft <= 14 ? 'Gấp' : 'Chú ý'));

        return [
            ++$this->rowNum,
            $row->product_code,
            $row->product_name,
            $row->category_name ?? '—',
            $row->uom_name,
            $row->lot_number ?? '—',
            $row->location_code,
            (float) $row->quantity,
            \Carbon\Carbon::parse($row->expiry_date)->format('d/m/Y'),
            $daysLeft,
            $level,
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,  'B' => 14, 'C' => 35, 'D' => 18, 'E' => 8,
            'F' => 14, 'G' => 12, 'H' => 12, 'I' => 14, 'J' => 14,
            'K' => 14,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->mergeCells('A1:K1');
        $sheet->mergeCells('A2:K2');

        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('A2')->applyFromArray([
            'font'      => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '555555']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('A4:K4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F59E0B']],
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
            foreach (['H', 'J'] as $col) {
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