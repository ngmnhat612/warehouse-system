<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StockTransactionSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // ── 1. LẤY THỰC THỂ CÓ SẴN ──────────────────────────────────────────
        $products  = DB::table('products')->get();
        $locations = DB::table('locations')->get();

        $supplierId = DB::table('suppliers')->value('id') ?? 1;
        $userId     = DB::table('users')->value('id') ?? 1;

        $locWhShelfA1 = $locations->where('code', 'WH-SHELF-A1')->first()?->id ?? 1;
        $locWhPalA    = $locations->where('code', 'WH-PAL-A')->first()?->id   ?? 6;

        // 3 sản phẩm đại diện 3 nhóm tracking
        $prodNone   = $products->where('code', 'SP003')->first();
        $prodLot    = $products->where('code', 'SP007')->first();
        $prodSerial = $products->where('code', 'SP004')->first();

        if ($products->isEmpty() || !$prodNone || !$prodLot || !$prodSerial) {
            $this->command->warn('StockTransactionSeeder: thiếu sản phẩm SP003/SP004/SP007, bỏ qua.');
            return;
        }

        // ── 2. LOT & SERIAL MẪU ──────────────────────────────────────────────
        $lotExists = DB::table('lots')
            ->where('product_id', $prodLot->id)
            ->where('lot_number', 'LOT-2026-MENT01')
            ->exists();

        $lotId = $lotExists
            ? DB::table('lots')->where('lot_number', 'LOT-2026-MENT01')->value('id')
            : DB::table('lots')->insertGetId([
                'product_id'   => $prodLot->id,
                'lot_number'   => 'LOT-2026-MENT01',
                'supplier_id'  => $supplierId,
                'received_date'=> $now->copy()->subDays(2)->toDateString(),
                'expiry_date'  => $now->copy()->addDays(90)->toDateString(),
                'status'       => 1,   // Active
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);

        $serialExists = DB::table('serials')
            ->where('product_id', $prodSerial->id)
            ->where('serial_number', 'SN-SIEMENS-001')
            ->exists();

        $serialId1 = $serialExists
            ? DB::table('serials')->where('serial_number', 'SN-SIEMENS-001')->value('id')
            : DB::table('serials')->insertGetId([
                'product_id'    => $prodSerial->id,
                'serial_number' => 'SN-SIEMENS-001',
                'lot_id'        => null,
                'supplier_id'   => $supplierId,
                'status'        => 1,  // InStock
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);

        // ── 3. STOCK ─────────────────────────────────────────────────────────
        // available_qty là PERSISTED COMPUTED COLUMN — không insert
        $stockNoneId = $this->upsertStock([
            'product_id'   => $prodNone->id,
            'location_id'  => $locWhShelfA1,
            'lot_id'       => null,
            'serial_id'    => null,
            'quantity'     => 500.000,
            'reserved_qty' => 0.000,
            'supplier_id'  => $supplierId,
            'status'       => 1,
            'updated_at'   => $now,
        ]);

        $stockLotId = $this->upsertStock([
            'product_id'   => $prodLot->id,
            'location_id'  => $locWhPalA,
            'lot_id'       => $lotId,
            'serial_id'    => null,
            'quantity'     => 100.000,
            'reserved_qty' => 20.000,
            'supplier_id'  => $supplierId,
            'status'       => 1,
            'updated_at'   => $now,
        ]);

        $stockSerialId = $this->upsertStock([
            'product_id'   => $prodSerial->id,
            'location_id'  => $locWhShelfA1,
            'lot_id'       => null,
            'serial_id'    => $serialId1,
            'quantity'     => 1.000,
            'reserved_qty' => 0.000,
            'supplier_id'  => $supplierId,
            'status'       => 1,
            'updated_at'   => $now,
        ]);

        // ── 4. NGHIỆP VỤ MẪU ─────────────────────────────────────────────────

        // Phiếu nhập đã hoàn thành
        // receipt_type: 1=NCC, 2=Trả hàng SX, 3=Khác  |  status: 1=Draft … 4=Completed, 5=Cancelled
        $receiptCode = 'RC-2026-0001';
        $receiptCompId = DB::table('stock_receipts')
            ->where('code', $receiptCode)->value('id');

        if (!$receiptCompId) {
            $receiptCompId = DB::table('stock_receipts')->insertGetId([
                'code'         => $receiptCode,
                'receipt_type' => 1,          // NCC
                'supplier_id'  => $supplierId,
                'reference_no' => 'PO-2026-0001',
                'created_by'   => $userId,
                'status'       => 4,          // Completed
                'receipt_date' => $now->copy()->subDays(2)->toDateString(),
                'created_at'   => $now->copy()->subDays(2),
                'updated_at'   => $now->copy()->subDays(2),
            ]);
        }

        // stock_receipt_details — theo migration:
        // location_id, expected_qty, actual_qty, uom_id
        $uomId = DB::table('uoms')->value('id') ?? 1;
        $detailExists = DB::table('stock_receipt_details')
            ->where('stock_receipt_id', $receiptCompId)
            ->where('product_id', $prodNone->id)
            ->exists();

        if (!$detailExists) {
            DB::table('stock_receipt_details')->insert([
                'stock_receipt_id' => $receiptCompId,
                'product_id'       => $prodNone->id,
                'location_id'      => $locWhShelfA1,
                'lot_id'           => null,
                'serial_id'        => null,
                'uom_id'           => $uomId,
                'expected_qty'     => 200.000,
                'actual_qty'       => 200.000,
                'rejected_qty'     => 0.000,
                'qc_status'        => 1,       // Pass
            ]);
        }

        // Stock ledger — nhập kho RC-2026-0001
        DB::table('stock_ledger')->insert([
            'product_id'       => $prodNone->id,
            'stock_id'         => $stockNoneId,
            'lot_id'           => null,
            'serial_id'        => null,
            'location_id'      => $locWhShelfA1,
            'transaction_type' => 'RECEIPT',
            'reference_id'     => $receiptCompId,
            'reference_type'   => 'stock_receipt',
            'reference_code'   => $receiptCode,
            'direction'        => 1,
            'quantity'         => 200.000,
            'balance_after'    => 500.000,
            'created_by'       => $userId,
            'note'             => 'Nhập kho hàng mua mẫu',
            'transaction_date' => $now->copy()->subDays(2),
        ]);

        // Phiếu nhập chờ duyệt
        if (!DB::table('stock_receipts')->where('code', 'RC-2026-0002')->exists()) {
            DB::table('stock_receipts')->insert([
                'code'         => 'RC-2026-0002',
                'receipt_type' => 1,   // NCC
                'supplier_id'  => $supplierId,
                'created_by'   => $userId,
                'status'       => 2,   // Pending
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        }

        // Phiếu xuất đã duyệt
        // issue_type: 1=Sản xuất, 2=Bảo trì, 3=Mượn, 4=Khác  |  status: 1=Draft … 4=Completed
        // requester_id → users.id (theo migration)
        if (!DB::table('stock_issues')->where('code', 'IS-2026-0001')->exists()) {
            $issueAppId = DB::table('stock_issues')->insertGetId([
                'code'         => 'IS-2026-0001',
                'issue_type'   => 1,          // Sản xuất
                'requester_id' => $userId,    // users.id — theo migration
                'created_by'   => $userId,
                'status'       => 3,          // Approved
                'issue_date'   => $now->toDateString(),
                'reference_no' => 'WO-2026-001',
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);

            // stock_issue_details — theo migration: location_id, quantity, uom_id
            DB::table('stock_issue_details')->insert([
                'stock_issue_id' => $issueAppId,
                'product_id'     => $prodLot->id,
                'lot_id'         => $lotId,
                'serial_id'      => null,
                'location_id'    => $locWhPalA,
                'uom_id'         => $uomId,
                'quantity'       => 20.000,
            ]);
        }

        // ── 5. DỮ LIỆU MÔ PHỎNG BIỂU ĐỒ DASHBOARD (30 ngày) ────────────────
        $sampleProducts = $products
            ->whereNotIn('id', [$prodNone->id, $prodLot->id, $prodSerial->id])
            ->take(15);

        $mockStocks = [];

        foreach ($sampleProducts as $p) {
            $randomQty     = rand(10, 150);
            $chosenLocation = rand(0, 1) ? $locWhShelfA1 : $locWhPalA;

            $sId = $this->upsertStock([
                'product_id'   => $p->id,
                'location_id'  => $chosenLocation,
                'lot_id'       => null,
                'serial_id'    => null,
                'quantity'     => $randomQty,
                'reserved_qty' => 0.000,
                'supplier_id'  => $supplierId,
                'received_date'=> $now->copy()->subDays(30)->toDateString(),
                'status'       => 1,
                'updated_at'   => $now->copy()->subDays(30),
            ]);

            $mockStocks[$p->id] = [
                'stock_id'    => $sId,
                'location_id' => $chosenLocation,
                'current_qty' => $randomQty,
            ];
        }

        if ($sampleProducts->isNotEmpty()) {
            for ($i = 30; $i >= 0; $i--) {
                $targetDate = $now->copy()
                    ->subDays($i)
                    ->setHour(rand(8, 17))
                    ->setMinute(rand(0, 59));

                $p = $sampleProducts->random();
                $stockInfo = $mockStocks[$p->id] ?? null;
                if (!$stockInfo) continue;

                $isReceipt = (bool) rand(0, 1);
                $qty       = rand(5, 30);

                $stockInfo['current_qty'] = $isReceipt
                    ? $stockInfo['current_qty'] + $qty
                    : max(0, $stockInfo['current_qty'] - $qty);

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
                    'reference_code'   => ($isReceipt ? 'RC-MOCK-' : 'IS-MOCK-')
                                         . $targetDate->format('md') . rand(10, 99),
                    'direction'        => $isReceipt ? 1 : 2,
                    'quantity'         => $qty,
                    'balance_after'    => $stockInfo['current_qty'],
                    'created_by'       => $userId,
                    'note'             => 'Dữ liệu mô phỏng biến động lịch sử kho',
                    'transaction_date' => $targetDate,
                ]);
            }
        }
        
        // ── 6. PHIẾU SCRAP MẪU ───────────────────────────────────────────────
        $scraps = [
            [
                'code'       => 'SC-2026-0001',
                'status'     => 2,   // Completed
                'scrap_date' => $now->copy()->subDays(10)->toDateString(),
                'note'       => 'Hủy hàng hỏng do vận chuyển',
                'created_by' => $userId,
                'approved_by'=> $userId,
                'created_at' => $now->copy()->subDays(10),
                'updated_at' => $now->copy()->subDays(10),
                'product_id' => $prodNone->id,
                'quantity'   => 15.000,
                'reason'     => 'Hàng bị vỡ khi vận chuyển',
            ],
            [
                'code'       => 'SC-2026-0002',
                'status'     => 2,   // Completed
                'scrap_date' => $now->copy()->subDays(5)->toDateString(),
                'note'       => 'Hủy hàng hết hạn sử dụng',
                'created_by' => $userId,
                'approved_by'=> $userId,
                'created_at' => $now->copy()->subDays(5),
                'updated_at' => $now->copy()->subDays(5),
                'product_id' => $prodLot->id,
                'quantity'   => 30.000,
                'reason'     => 'Hàng quá hạn sử dụng',
            ],
            [
                'code'       => 'SC-2026-0003',
                'status'     => 1,   // Draft
                'scrap_date' => $now->toDateString(),
                'note'       => 'Hủy hàng lỗi sản xuất',
                'created_by' => $userId,
                'approved_by'=> null,
                'created_at' => $now,
                'updated_at' => $now,
                'product_id' => $prodSerial->id,
                'quantity'   => 5.000,
                'reason'     => 'Hàng lỗi kỹ thuật',
            ],
        ];

        foreach ($scraps as $scrap) {
            if (DB::table('scraps')->where('code', $scrap['code'])->exists()) {
                continue;
            }

            $scrapId = DB::table('scraps')->insertGetId([
                'code'        => $scrap['code'],
                'status'      => $scrap['status'],
                'scrap_date'  => $scrap['scrap_date'],
                'note'        => $scrap['note'],
                'created_by'  => $scrap['created_by'],
                'approved_by' => $scrap['approved_by'],
                'created_at'  => $scrap['created_at'],
                'updated_at'  => $scrap['updated_at'],
            ]);

            DB::table('scrap_details')->insert([
                'scrap_id'   => $scrapId,
                'product_id' => $scrap['product_id'],
                'lot_id'     => null,
                'serial_id'  => null,
                'location_id'=> $locWhShelfA1,
                'uom_id'     => $uomId,
                'quantity'   => $scrap['quantity'],
                'reason'     => $scrap['reason'],
            ]);

            // Chỉ ghi ledger cho phiếu đã hoàn thành
            if ($scrap['status'] === 2) {
                $stockId = DB::table('stock')
                    ->where('product_id', $scrap['product_id'])
                    ->where('location_id', $locWhShelfA1)
                    ->whereNull('lot_id')
                    ->whereNull('serial_id')
                    ->value('id');

                if (!$stockId) {
                    $stockId = DB::table('stock')->insertGetId([
                        'product_id'   => $scrap['product_id'],
                        'location_id'  => $locWhShelfA1,
                        'lot_id'       => null,
                        'serial_id'    => null,
                        'quantity'     => 0,
                        'reserved_qty' => 0,
                        'status'       => 1,
                        'updated_at'   => $scrap['created_at'],
                    ]);
                }

                DB::table('stock_ledger')->insert([
                    'product_id'       => $scrap['product_id'],
                    'stock_id'         => $stockId,
                    'lot_id'           => null,
                    'serial_id'        => null,
                    'location_id'      => $locWhShelfA1,
                    'transaction_type' => 'SCRAP',
                    'reference_id'     => $scrapId,
                    'reference_type'   => 'scrap',
                    'reference_code'   => $scrap['code'],
                    'direction'        => 2,
                    'quantity'         => $scrap['quantity'],
                    'balance_after'    => 0,
                    'created_by'       => $userId,
                    'note'             => $scrap['note'],
                    'transaction_date' => $scrap['created_at'],
                ]);
            }
        }
    }

    // ── HELPER: insert hoặc lấy id nếu đã tồn tại ────────────────────────────
    private function upsertStock(array $data): int
    {
        $existing = DB::table('stock')
            ->where('product_id',  $data['product_id'])
            ->where('location_id', $data['location_id'])
            ->where('lot_id',      $data['lot_id'])
            ->where('serial_id',   $data['serial_id'])
            ->value('id');

        if ($existing) {
            return $existing;
        }

        return DB::table('stock')->insertGetId($data);
    }
}
