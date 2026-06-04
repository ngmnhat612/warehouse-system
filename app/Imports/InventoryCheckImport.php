<?php

namespace App\Imports;

use App\Models\InventoryCheckLine;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

/**
 * InventoryCheckImport
 *
 * Đọc file Excel template kiểm kê và cập nhật actual_qty vào các dòng
 * inventory_check_lines tương ứng.
 *
 * Cột bắt buộc trong file Excel (heading row):
 *   - id          : ID dòng kiểm kê (inventory_check_lines.id) — KHÔNG chỉnh sửa
 *   - actual_qty  : Số lượng thực tế đếm được
 *
 * Cột đọc thêm (chỉ để người dùng tham khảo, không ghi vào DB):
 *   - product_code, product_name, location, lot_number, uom, system_qty
 *
 * Quy tắc:
 *   - Bỏ qua dòng không có id hoặc actual_qty rỗng
 *   - Chỉ cập nhật dòng thuộc đúng inventory_check_id được truyền vào
 *   - actual_qty phải >= 0
 */
class InventoryCheckImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    /** Số dòng cập nhật thành công */
    public int $updatedCount = 0;

    /** Số dòng bỏ qua (actual_qty rỗng hoặc không hợp lệ) */
    public int $skippedCount = 0;

    /** Danh sách lỗi theo dòng [{row, message}] */
    public array $errors = [];

    public function __construct(
        protected int $inventoryCheckId
    ) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNum = $index + 2; // +2 vì dòng 1 là heading

            // ── Lấy id dòng ──
            $lineId = isset($row['id']) ? (int) $row['id'] : null;

            if (!$lineId) {
                // Bỏ qua dòng không có id (dòng tổng, chú thích...)
                $this->skippedCount++;
                continue;
            }

            // ── Kiểm tra actual_qty ──
            $rawQty = $row['actual_qty'] ?? $row['thuc_te'] ?? null;

            // Bỏ qua nếu ô trống (người dùng chưa điền)
            if ($rawQty === null || $rawQty === '') {
                $this->skippedCount++;
                continue;
            }

            $actualQty = (float) $rawQty;

            if ($actualQty < 0) {
                $this->errors[] = [
                    'row'     => $rowNum,
                    'message' => "Dòng {$rowNum}: Số lượng thực tế không được âm (giá trị: {$rawQty})",
                ];
                $this->skippedCount++;
                continue;
            }

            // ── Tìm dòng kiểm kê — chỉ cho phép sửa dòng thuộc phiếu này ──
            $line = InventoryCheckLine::where('id', $lineId)
                ->where('inventory_check_id', $this->inventoryCheckId)
                ->first();

            if (!$line) {
                $this->errors[] = [
                    'row'     => $rowNum,
                    'message' => "Dòng {$rowNum}: Không tìm thấy dòng kiểm kê id={$lineId} trong phiếu này.",
                ];
                $this->skippedCount++;
                continue;
            }

            // ── Cập nhật ──
            $line->update([
                'actual_qty' => $actualQty,
                'counted_by' => Auth::id(),
                'counted_at' => now(),
            ]);

            $this->updatedCount++;
        }
    }
}