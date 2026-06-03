<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StockTransactionSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // 1. LẤY THÔNG TIN CÁC THỰC THỂ CÓ SẴN TRONG DB
        $products = DB::table('products')->get();
        $locations = DB::table('locations')->get();
        $supplierId = DB::table('suppliers')->value('id') ?? 1;
        $employeeId = DB::table('employees')->value('id') ?? 1; // ID nhân viên mẫu để gán vào requester_id
        $userId = DB::table('users')->value('id') ?? 1;

        $locWhShelfA1 = $locations->where('code', 'WH-SHELF-A1')->first()->id ?? 1;
        $locWhPalA    = $locations->where('code', 'WH-PAL-A')->first()->id ?? 6;

        // Định vị 3 sản phẩm đại diện 3 nhóm tracking
        $prodNone   = $products->where('code', 'SP003')->first();
        $prodLot    = $products->where('code', 'SP007')->first();
        $prodSerial = $products->where('code', 'SP004')->first();

        if ($products->isEmpty() || !$prodNone || !$prodLot || !$prodSerial) {
            return;
        }

        // 2. KHỞI TẠO LOT & SERIAL MẪU KHỚP MIGRATION
        $lotId = DB::table('lots')->insertGetId([
            'product_id' => $prodLot->id,
            'lot_number' => 'LOT-2026-MENT01',
            'supplier_id' => $supplierId,
            'received_date' => $now->copy()->subDays(2),
            'expiry_date' => $now->copy()->addDays(90),
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now
        ]);

        $serialId1 = DB::table('serials')->insertGetId([
            'product_id' => $prodSerial->id,
            'serial_number' => 'SN-SIEMENS-001',
            'lot_id' => null,
            'supplier_id' => $supplierId,
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now
        ]);

        // 3. ĐIỀN SỐ LIỆU CHO BẢNG stock (BỎ available_qty VÌ LÀ COLUMN COMPUTED)
        $stockNoneId = DB::table('stock')->insertGetId([
            'product_id' => $prodNone->id, 'location_id' => $locWhShelfA1, 'lot_id' => null, 'serial_id' => null, 'quantity' => 500.000, 'reserved_qty' => 0.000, 'supplier_id' => $supplierId, 'status' => 1, 'updated_at' => $now
        ]);

        $stockLotId = DB::table('stock')->insertGetId([
            'product_id' => $prodLot->id, 'location_id' => $locWhPalA, 'lot_id' => $lotId, 'serial_id' => null, 'quantity' => 100.000, 'reserved_qty' => 20.000, 'supplier_id' => $supplierId, 'status' => 1, 'updated_at' => $now
        ]);

        $stockSerialId = DB::table('stock')->insertGetId([
            'product_id' => $prodSerial->id, 'location_id' => $locWhShelfA1, 'lot_id' => null, 'serial_id' => $serialId1, 'quantity' => 1.000, 'reserved_qty' => 0.000, 'supplier_id' => $supplierId, 'status' => 1, 'updated_at' => $now
        ]);

        // =====================================================================
        // 4. KHỐI DỮ LIỆU ĐẶC THÙ ĐỂ KIỂM THỬ LUỒNG NGHIỆP VỤ (E2E)
        // =====================================================================

        // Phiếu nhập đã hoàn thành (ĐÃ XÓA BỎ 'employee_id')
        $receiptCompId = DB::table('stock_receipts')->insertGetId([
            'reference_no' => 'RC-2026-0001',
            'supplier_id' => $supplierId,
            'receipt_type' => 'Purchase',
            'status' => 'COMPLETED',
            'created_at' => $now->copy()->subDays(2),
            'updated_at' => $now->copy()->subDays(2)
        ]);
        DB::table('stock_receipt_details')->insert(['stock_receipt_id' => $receiptCompId, 'product_id' => $prodNone->id, 'location_dest_id' => $locWhShelfA1, 'lot_id' => null, 'serial_id' => null, 'requested_qty' => 200.00, 'actual_qty' => 200.00, 'qc_status' => 'Pass']);

        // Ghi nhận vào sổ nhật ký kho stock_ledger
        DB::table('stock_ledger')->insert([
            'product_id'       => $prodNone->id,
            'stock_id'         => $stockNoneId,
            'lot_id'           => null,
            'serial_id'        => null,
            'location_id'      => $locWhShelfA1,
            'transaction_type' => 'RECEIPT',
            'reference_id'     => $receiptCompId,
            'reference_type'   => 'stock_receipt',
            'reference_code'   => 'RC-2026-0001',
            'direction'        => 1,
            'quantity'         => 200.000,
            'balance_after'    => 500.000,
            'created_by'       => $userId,
            'note'             => 'Nhập kho hàng mua mẫu',
            'transaction_date' => $now->copy()->subDays(2)
        ]);

        // Phiếu nhập chờ duyệt (ĐÃ XÓA BỎ 'employee_id')
        DB::table('stock_receipts')->insert([
            'reference_no' => 'RC-2026-0002',
            'supplier_id' => $supplierId,
            'receipt_type' => 'Purchase',
            'status' => 'PENDING',
            'created_at' => $now,
            'updated_at' => $now
        ]);

        // Phiếu xuất đã duyệt giữ hàng (ĐÃ ĐỔI THÀNH 'requester_id' VÀ XÓA 'employee_id')
        $issueAppId = DB::table('stock_issues')->insertGetId([
            'reference_no' => 'IS-2026-0001',
            'issue_type' => 'Production',
            'status' => 'APPROVED',
            'requester_id' => $employeeId, // Chuẩn theo tài liệu đặc tả
            'created_at' => $now,
            'updated_at' => $now
        ]);
        DB::table('stock_issue_details')->insert(['stock_issue_id' => $issueAppId, 'product_id' => $prodLot->id, 'location_src_id' => $locWhPalA, 'lot_id' => $lotId, 'serial_id' => null, 'requested_qty' => 20.00, 'actual_qty' => 0.00]);

        // Phiếu kiểm kê gây đóng băng kho
        // Lưu ý: Nếu chạy lệnh mà bảng 'inventory_checks' cũng báo lỗi 'employee_id', hãy xóa dòng 'employee_id' ở dưới đây.
        $checkId = DB::table('inventory_checks')->insertGetId([
            'reference_no' => 'IC-2026-0001',
            'status' => 'IN_PROGRESS',
            'created_at' => $now,
            'updated_at' => $now
        ]);
        DB::table('inventory_freezes')->insert([
            'inventory_check_id' => $checkId, 'location_id' => $locWhShelfA1, 'frozen_at' => $now, 'unfrozen_at' => null
        ]);

        // =====================================================================
        // 5. KHỐI BỔ SUNG: TRÌNH GIẢ LẬP CHẠY BIỂU ĐỒ DASHBOARD
        // =====================================================================

        $sampleProducts = $products->whereNotIn('id', [$prodNone->id, $prodLot->id, $prodSerial->id])->take(15);
        $mockStocks = [];

        foreach ($sampleProducts as $p) {
            $randomQty = rand(10, 150);
            if ($p->code === 'SP008' || $p->code === 'SP014') { $randomQty = 1.00; }

            $chosenLocation = rand(0, 1) ? $locWhShelfA1 : $locWhPalA;

            $sId = DB::table('stock')->insertGetId([
                'product_id' => $p->id, 'location_id' => $chosenLocation, 'lot_id' => null, 'serial_id' => null, 'quantity' => $randomQty, 'reserved_qty' => 0.000, 'supplier_id' => $supplierId, 'status' => 1, 'updated_at' => $now->copy()->subDays(30)
            ]);

            $mockStocks[$p->id] = ['stock_id' => $sId, 'location_id' => $chosenLocation, 'current_qty' => $randomQty];
        }

        for ($i = 30; $i >= 0; $i--) {
            $targetDate = $now->copy()->subDays($i)->setHour(rand(8, 17))->setMinute(rand(0, 59));
            if ($sampleProducts->isEmpty()) continue;

            $p = $sampleProducts->random();
            $stockInfo = $mockStocks[$p->id] ?? null;
            if (!$stockInfo) continue;

            $isReceipt = rand(0, 1);
            $qty = rand(5, 30);

            if ($isReceipt) {
                $stockInfo['current_qty'] += $qty;
            } else {
                $stockInfo['current_qty'] = max(0, $stockInfo['current_qty'] - $qty);
            }
            $mockStocks[$p->id]['current_qty'] = $stockInfo['current_qty'];

            DB::table('stock_ledger')->insert([
                'product_id'       => $p->id,
                'stock_id'         => $stockInfo['stock_id'],
                'lot_id'           => null,
                'serial_id'        => null,
                'location_id'      => $stockInfo['location_id'],
                'transaction_type' => $isReceipt ? 'RECEIPT' : 'ISSUE',
                'reference_id'     => rand(100, 999),
                'reference_type'   => $isReceipt ? 'stock_receipt' : 'stock_issue',
                'reference_code'   => ($isReceipt ? 'RC-MOCK-' : 'IS-MOCK-') . $targetDate->format('md') . rand(10, 99),
                'direction'        => $isReceipt ? 1 : 2,
                'quantity'         => $qty,
                'balance_after'    => $stockInfo['current_qty'],
                'created_by'       => $userId,
                'note'             => 'Dữ liệu mô phỏng biến động lịch sử kho',
                'transaction_date' => $targetDate,
            ]);
        }
    }
}