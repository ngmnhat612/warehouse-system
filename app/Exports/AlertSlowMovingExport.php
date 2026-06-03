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

class AlertSlowMovingExport implements FromCollection, WithHeadings, WithMapping,
    WithStyles, WithTitle, ShouldAutoSize, WithColumnWidths
{
    protected int $rowNum = 0;

    public function __construct(
        protected Collection $items,
        protected int $days = 90
    ) {}

    public function title(): string { return 'Hàng đọng kho'; }

    public function collection(): Collection { return $this->items; }

    public function headings(): array
    {
        return [
            ["CẢNH BÁO HÀNG ĐỌNG KHO LÂU NGÀY (> {$this->days} NGÀY)"],
            ['Xuất lúc: ' . now()->format('H:i:s d/m/Y')],
            [],
            ['STT', 'Mã hàng', 'Tên hàng hóa', 'Nhóm hàng', 'ĐVT',
             'Vị trí', 'Tồn thực tế', 'Khả dụng', 'Ngày nhập cuối', 'Ngày xuất cuối',
             'Số ngày đọng', 'Mức độ'],
        ];
    }

    public function map($row): array
    {
        $idleDays = (int) $row->idle_days;
        $level    = $idleDays >= 365 ? 'Nghiêm trọng'
                  : ($idleDays >= 180 ? 'Cao'
                  : ($idleDays >= 90  ? 'Trung bình' : 'Chú ý'));

        return [
            ++$this->rowNum,
            $row->product_code,
            $row->product_name,
            $row->category_name ?? '—',
            $row->uom_name,
            $row->location_code,
            (float) $row->total_qty,
            (float) $row->available_qty,
            $row->last_received_date
                ? \Carbon\Carbon::parse($row->last_received_date)->format('d/m/Y') : '—',
            $row->last_issue_date
                ? \Carbon\Carbon::parse($row->last_issue_date)->format('d/m/Y') : 'Chưa xuất',
            $idleDays,
            $level,
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,  'B' => 14, 'C' => 35, 'D' => 18, 'E' => 8,
            'F' => 12, 'G' => 14, 'H' => 14, 'I' => 14, 'J' => 14,
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
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D97706']],
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
            foreach (['G', 'H', 'K'] as $col) {
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