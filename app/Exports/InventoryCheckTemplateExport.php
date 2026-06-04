<?php

namespace App\Exports;

use App\Models\InventoryCheck;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Protection;

/**
 * InventoryCheckTemplateExport
 *
 * Xuất file Excel để nhân viên điền số lượng thực tế offline.
 *
 * Cấu trúc sheet:
 *   Cột A  : id              (ẩn, khoá — không cho sửa)
 *   Cột B  : Mã hàng         (khoá)
 *   Cột C  : Tên hàng        (khoá)
 *   Cột D  : ĐVT             (khoá)
 *   Cột E  : Vị trí          (khoá)
 *   Cột F  : Số lô           (khoá)
 *   Cột G  : Tồn hệ thống    (khoá)
 *   Cột H  : Thực tế ← NHẬP VÀO ĐÂY  (mở khoá, nền vàng)
 *   Cột I  : Ghi chú         (mở khoá)
 *
 * Khi upload lại, Import class sẽ đọc cột A (id) + cột H (actual_qty).
 */
class InventoryCheckTemplateExport implements FromCollection, WithHeadings, WithMapping,
    WithStyles, WithTitle, ShouldAutoSize, WithColumnWidths, WithColumnFormatting
{
    protected int $rowNum = 0;
    protected int $totalDataRows = 0;

    public function __construct(protected InventoryCheck $check) {}

    public function title(): string
    {
        return 'Template_' . $this->check->code;
    }

    public function collection()
    {
        $lines = $this->check->lines()
            ->with(['product.uom', 'location', 'lot'])
            ->orderBy('location_id')
            ->orderBy('product_id')
            ->get();

        $this->totalDataRows = $lines->count();

        return $lines;
    }

    public function headings(): array
    {
        $typeLabels = [1 => 'Toàn kho', 2 => 'Theo khu vực', 3 => 'Theo mặt hàng'];

        return [
            // Dòng 1: tiêu đề lớn
            ['PHIẾU KIỂM KÊ KHO — ' . $this->check->code],
            // Dòng 2: thông tin phiếu
            [
                'Loại: ' . ($typeLabels[$this->check->check_type] ?? '?') .
                '    |    Ngày kiểm: ' . ($this->check->check_date?->format('d/m/Y') ?? '—') .
                '    |    Phụ trách: ' . ($this->check->assignedTo?->name ?? '—') .
                '    |    Xuất lúc: ' . now()->format('H:i d/m/Y'),
            ],
            // Dòng 3: hướng dẫn
            ['⚠ HƯỚNG DẪN: Chỉ điền vào cột "Thực tế" (cột H màu vàng). KHÔNG thay đổi các cột khác. Lưu file và upload lại hệ thống.'],
            // Dòng 4: trống
            [],
            // Dòng 5: heading bảng
            ['id', 'Mã hàng', 'Tên hàng hóa', 'ĐVT', 'Vị trí', 'Số lô', 'Tồn hệ thống', 'Thực tế ✏', 'Ghi chú'],
        ];
    }

    public function map($line): array
    {
        $this->rowNum++;

        return [
            $line->id,                              // A — id (ẩn)
            $line->product->code ?? '—',            // B
            $line->product->name ?? '—',            // C
            $line->uom->name     ?? '—',            // D
            $line->location->code ?? '—',           // E
            $line->lot->lot_number ?? '—',          // F
            (float) $line->system_qty,              // G — tồn hệ thống
            $line->actual_qty !== null              // H — thực tế (pre-fill nếu đã có)
                ? (float) $line->actual_qty
                : '',
            '',                                     // I — ghi chú (trống)
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,   // id
            'B' => 14,  // Mã hàng
            'C' => 36,  // Tên hàng
            'D' => 8,   // ĐVT
            'E' => 14,  // Vị trí
            'F' => 18,  // Số lô
            'G' => 16,  // Tồn HT
            'H' => 16,  // Thực tế
            'I' => 28,  // Ghi chú
        ];
    }

    public function columnFormats(): array
    {
        return [
            'G' => '#,##0.00',   // thay FORMAT_NUMBER_COMMA_SEP1
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastDataRow = $this->totalDataRows + 5; // 4 dòng header + 1 heading

        // ── Merge & style tiêu đề ──────────────────────────────────────────
        $sheet->mergeCells('A1:I1');
        $sheet->mergeCells('A2:I2');
        $sheet->mergeCells('A3:I3');

        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('A2')->applyFromArray([
            'font'      => ['size' => 10, 'color' => ['rgb' => '374151']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('A3')->applyFromArray([
            'font'      => ['italic' => true, 'size' => 9, 'color' => ['rgb' => 'B45309']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFBEB']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // ── Header row (dòng 5) ────────────────────────────────────────────
        $sheet->getStyle('A5:I5')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A8A']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
            'borders' => ['allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color'       => ['rgb' => 'FFFFFF'],
            ]],
        ]);
        $sheet->getRowDimension(5)->setRowHeight(26);

        // Đặc biệt: cột H header nền cam nổi bật
        $sheet->getStyle('H5')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D97706']],
        ]);

        // ── Dữ liệu rows ──────────────────────────────────────────────────
        if ($this->totalDataRows > 0) {
            // Cột A (id) — ẩn bằng cách thu nhỏ và tô xám
            $sheet->getColumnDimension('A')->setWidth(6);
            $sheet->getStyle("A6:A{$lastDataRow}")->applyFromArray([
                'font'      => ['color' => ['rgb' => 'D1D5DB'], 'size' => 8],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);

            // Cột B–G: khoá, nền trắng mờ
            $sheet->getStyle("B6:G{$lastDataRow}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']],
                'font' => ['color' => ['rgb' => '374151']],
            ]);

            // Cột G (tồn HT) — căn phải + bold
            $sheet->getStyle("G6:G{$lastDataRow}")->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                'font'      => ['bold' => true],
            ]);

            // Cột H (thực tế) — NỀN VÀNG, mở khoá, căn phải
            $sheet->getStyle("H6:H{$lastDataRow}")->applyFromArray([
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF9C3']],
                'font'      => ['bold' => true, 'color' => ['rgb' => '92400E']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                'borders'   => ['allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => 'FCD34D'],
                ]],
            ]);

            // Cột I (ghi chú) — nền trắng nhẹ
            $sheet->getStyle("I6:I{$lastDataRow}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEFCE8']],
                'font' => ['color' => ['rgb' => '6B7280'], 'italic' => true],
            ]);

            // Borders toàn bảng
            $sheet->getStyle("A5:I{$lastDataRow}")->applyFromArray([
                'borders' => ['allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => 'E2E8F0'],
                ]],
            ]);

            // Tô màu xen kẽ dòng (zebra striping)
            for ($r = 6; $r <= $lastDataRow; $r++) {
                if ($r % 2 === 0) {
                    // Chỉ tô các cột không có màu riêng (B-G, I)
                    foreach (['B', 'C', 'D', 'E', 'F'] as $col) {
                        $sheet->getStyle("{$col}{$r}")->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('F1F5F9');
                    }
                }
            }

            // ── Footer: dòng tổng ────────────────────────────────────────
            $footerRow = $lastDataRow + 1;
            $sheet->setCellValue("F{$footerRow}", 'Tổng cộng:');
            $sheet->setCellValue("G{$footerRow}", "=SUM(G6:G{$lastDataRow})");
            $sheet->setCellValue("H{$footerRow}", "=SUM(H6:H{$lastDataRow})");
            $sheet->getStyle("A{$footerRow}:I{$footerRow}")->applyFromArray([
                'font' => ['bold' => true, 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DBEAFE']],
                'borders' => ['outline' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color'       => ['rgb' => '1E3A8A'],
                ]],
            ]);
            foreach (['G', 'H'] as $col) {
                $sheet->getStyle("{$col}{$footerRow}")->applyFromArray([
                    'alignment'    => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                    'numberFormat' => ['formatCode' => '#,##0.###'],
                ]);
            }
        }

        // ── Freeze panes tại dòng dữ liệu ────────────────────────────────
        $sheet->freezePane('B6');

        // ── Ẩn cột A (id) — thu nhỏ để không gây nhầm lẫn ───────────────
        $sheet->getColumnDimension('A')->setWidth(4);

        // ── Validation: cột H chỉ nhận số >= 0 ───────────────────────────
        if ($this->totalDataRows > 0) {
            $validation = $sheet->getCell('H6')->getDataValidation();
            $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_DECIMAL);
            $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
            $validation->setAllowBlank(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Giá trị không hợp lệ');
            $validation->setError('Số lượng thực tế phải là số >= 0');
            $validation->setOperator(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::OPERATOR_GREATERTHANOREQUAL);
            $validation->setFormula1('0');
            $validation->setSqref("H6:H{$lastDataRow}");
        }

        return [];
    }
}