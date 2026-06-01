<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LotSerialSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Lấy product_id theo code để không hardcode
        $sp003 = DB::table('products')->where('code', 'SP003')->value('id'); // Vòng bi — Lot
        $sp004 = DB::table('products')->where('code', 'SP004')->value('id'); // Board PLC — Serial
        $sp007 = DB::table('products')->where('code', 'SP007')->value('id'); // Dầu bôi trơn — Lot

        $sup1 = DB::table('suppliers')->where('code', 'NCC001')->value('id');
        $sup2 = DB::table('suppliers')->where('code', 'NCC002')->value('id');

        // ── LOTS ──────────────────────────────────────────────────────────────
        $lots = [
            // Vòng bi 6205 — Lot còn hạn
            [
                'product_id'       => $sp003,
                'lot_number'       => 'LOT-VB-2024001',
                'supplier_id'      => $sup1,
                'manufacture_date' => '2024-01-15',
                'received_date'    => '2024-02-01',
                'expiry_date'      => '2027-01-15',
                'status'           => 1, // Active
                'created_at'       => $now,
                'updated_at'       => $now,
            ],
            // Vòng bi 6205 — Lot sắp hết hạn (trong 180 ngày)
            [
                'product_id'       => $sp003,
                'lot_number'       => 'LOT-VB-2024002',
                'supplier_id'      => $sup1,
                'manufacture_date' => '2024-03-10',
                'received_date'    => '2024-04-01',
                'expiry_date'      => '2024-09-30',   // đã hết hạn — status Expired
                'status'           => 3, // Expired
                'created_at'       => $now,
                'updated_at'       => $now,
            ],
            // Dầu bôi trơn — Lot tốt
            [
                'product_id'       => $sp007,
                'lot_number'       => 'LOT-DAU-2025001',
                'supplier_id'      => $sup2,
                'manufacture_date' => '2025-01-01',
                'received_date'    => '2025-02-10',
                'expiry_date'      => '2026-12-31',
                'status'           => 1, // Active
                'created_at'       => $now,
                'updated_at'       => $now,
            ],
            // Dầu bôi trơn — Lot đang trong Quarantine (QC fail)
            [
                'product_id'       => $sp007,
                'lot_number'       => 'LOT-DAU-2025002',
                'supplier_id'      => $sup2,
                'manufacture_date' => '2025-03-01',
                'received_date'    => '2025-04-05',
                'expiry_date'      => '2027-03-01',
                'status'           => 2, // Quarantine
                'created_at'       => $now,
                'updated_at'       => $now,
            ],
        ];

        foreach ($lots as $row) {
            $exists = DB::table('lots')
                ->where('product_id', $row['product_id'])
                ->where('lot_number', $row['lot_number'])
                ->exists();
            if (! $exists) {
                DB::table('lots')->insert($row);
            }
        }

        // ── SERIALS (Board PLC) ───────────────────────────────────────────────
        $serials = [
            [
                'product_id'       => $sp004,
                'serial_number'    => 'SN-PLC-00001',
                'lot_id'           => null,
                'supplier_id'      => $sup2,
                'manufacture_date' => '2024-06-01',
                'received_date'    => '2024-07-15',
                'expiry_date'      => null,
                'status'           => 1, // InStock
                'created_at'       => $now,
                'updated_at'       => $now,
            ],
            [
                'product_id'       => $sp004,
                'serial_number'    => 'SN-PLC-00002',
                'lot_id'           => null,
                'supplier_id'      => $sup2,
                'manufacture_date' => '2024-06-01',
                'received_date'    => '2024-07-15',
                'expiry_date'      => null,
                'status'           => 1, // InStock
                'created_at'       => $now,
                'updated_at'       => $now,
            ],
            [
                'product_id'       => $sp004,
                'serial_number'    => 'SN-PLC-00003',
                'lot_id'           => null,
                'supplier_id'      => $sup2,
                'manufacture_date' => '2024-08-10',
                'received_date'    => '2024-09-01',
                'expiry_date'      => null,
                'status'           => 4, // Issued (đã xuất kho — làm dữ liệu lịch sử)
                'created_at'       => $now,
                'updated_at'       => $now,
            ],
        ];

        foreach ($serials as $row) {
            $exists = DB::table('serials')
                ->where('product_id', $row['product_id'])
                ->where('serial_number', $row['serial_number'])
                ->exists();
            if (! $exists) {
                DB::table('serials')->insert($row);
            }
        }
    }
}
