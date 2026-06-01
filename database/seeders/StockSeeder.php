<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StockSeeder extends Seeder
{
    public function run(): void
    {
        // Lấy id động
        $sp001 = DB::table('products')->where('code', 'SP001')->value('id');
        $sp002 = DB::table('products')->where('code', 'SP002')->value('id');
        $sp003 = DB::table('products')->where('code', 'SP003')->value('id');
        $sp004 = DB::table('products')->where('code', 'SP004')->value('id');
        $sp005 = DB::table('products')->where('code', 'SP005')->value('id');
        $sp006 = DB::table('products')->where('code', 'SP006')->value('id');
        $sp007 = DB::table('products')->where('code', 'SP007')->value('id');

        $locPalA = DB::table('locations')->where('code', 'WH-PAL-A')->value('id');
        $locPalB = DB::table('locations')->where('code', 'WH-PAL-B')->value('id');
        $locShA1 = DB::table('locations')->where('code', 'WH-SHELF-A1')->value('id');
        $locShA2 = DB::table('locations')->where('code', 'WH-SHELF-A2')->value('id');
        $locQua  = DB::table('locations')->where('code', 'VIRTUAL-QUA')->value('id');

        $sup1 = DB::table('suppliers')->where('code', 'NCC001')->value('id');
        $sup2 = DB::table('suppliers')->where('code', 'NCC002')->value('id');

        $lot1 = DB::table('lots')->where('lot_number', 'LOT-VB-2024001')->value('id');
        $lot3 = DB::table('lots')->where('lot_number', 'LOT-DAU-2025001')->value('id');
        $lot4 = DB::table('lots')->where('lot_number', 'LOT-DAU-2025002')->value('id');

        $ser1 = DB::table('serials')->where('serial_number', 'SN-PLC-00001')->value('id');
        $ser2 = DB::table('serials')->where('serial_number', 'SN-PLC-00002')->value('id');

        $now = now();

        // Mỗi dòng stock = 1 (product × location × lot × serial)
        // available_qty là computed column — không insert, SQL Server tự tính
        $stocks = [
            // SP001 - Máy bơm (None tracking)
            [
                'product_id'       => $sp001,
                'location_id'      => $locPalA,
                'lot_id'           => null,
                'serial_id'        => null,
                'quantity'         => 8.000,
                'reserved_qty'     => 0.000,
                'supplier_id'      => $sup1,
                'manufacture_date' => null,
                'received_date'    => '2025-01-10',
                'expiry_date'      => null,
                'status'           => 1,
                'updated_at'       => $now,
            ],
            // SP002 - Động cơ (None tracking)
            [
                'product_id'       => $sp002,
                'location_id'      => $locPalB,
                'lot_id'           => null,
                'serial_id'        => null,
                'quantity'         => 3.000,
                'reserved_qty'     => 1.000,  // có 1 cái đang được reserve
                'supplier_id'      => $sup2,
                'manufacture_date' => null,
                'received_date'    => '2025-03-05',
                'expiry_date'      => null,
                'status'           => 1,
                'updated_at'       => $now,
            ],
            // SP003 - Vòng bi, Lot 1 (còn hạn)
            [
                'product_id'       => $sp003,
                'location_id'      => $locShA1,
                'lot_id'           => $lot1,
                'serial_id'        => null,
                'quantity'         => 60.000,
                'reserved_qty'     => 0.000,
                'supplier_id'      => $sup1,
                'manufacture_date' => '2024-01-15',
                'received_date'    => '2024-02-01',
                'expiry_date'      => '2027-01-15',
                'status'           => 1,
                'updated_at'       => $now,
            ],
            // SP004 - Board PLC, Serial 1
            [
                'product_id'       => $sp004,
                'location_id'      => $locShA2,
                'lot_id'           => null,
                'serial_id'        => $ser1,
                'quantity'         => 1.000,
                'reserved_qty'     => 0.000,
                'supplier_id'      => $sup2,
                'manufacture_date' => '2024-06-01',
                'received_date'    => '2024-07-15',
                'expiry_date'      => null,
                'status'           => 1,
                'updated_at'       => $now,
            ],
            // SP004 - Board PLC, Serial 2
            [
                'product_id'       => $sp004,
                'location_id'      => $locShA2,
                'lot_id'           => null,
                'serial_id'        => $ser2,
                'quantity'         => 1.000,
                'reserved_qty'     => 0.000,
                'supplier_id'      => $sup2,
                'manufacture_date' => '2024-06-01',
                'received_date'    => '2024-07-15',
                'expiry_date'      => null,
                'status'           => 1,
                'updated_at'       => $now,
            ],
            // SP005 - Cáp điện (None tracking)
            [
                'product_id'       => $sp005,
                'location_id'      => $locPalA,
                'lot_id'           => null,
                'serial_id'        => null,
                'quantity'         => 15.000,
                'reserved_qty'     => 0.000,
                'supplier_id'      => $sup1,
                'manufacture_date' => null,
                'received_date'    => '2025-02-20',
                'expiry_date'      => null,
                'status'           => 1,
                'updated_at'       => $now,
            ],
            // SP006 - Ống thép (None tracking)
            [
                'product_id'       => $sp006,
                'location_id'      => $locPalB,
                'lot_id'           => null,
                'serial_id'        => null,
                'quantity'         => 80.000,
                'reserved_qty'     => 0.000,
                'supplier_id'      => $sup1,
                'manufacture_date' => null,
                'received_date'    => '2025-01-25',
                'expiry_date'      => null,
                'status'           => 1,
                'updated_at'       => $now,
            ],
            // SP007 - Dầu bôi trơn, Lot 3 (tốt, tại kệ)
            [
                'product_id'       => $sp007,
                'location_id'      => $locShA1,
                'lot_id'           => $lot3,
                'serial_id'        => null,
                'quantity'         => 80.000,
                'reserved_qty'     => 0.000,
                'supplier_id'      => $sup2,
                'manufacture_date' => '2025-01-01',
                'received_date'    => '2025-02-10',
                'expiry_date'      => '2026-12-31',
                'status'           => 1,
                'updated_at'       => $now,
            ],
            // SP007 - Dầu bôi trơn, Lot 4 (Quarantine — tại vị trí ảo QUARANTINE)
            [
                'product_id'       => $sp007,
                'location_id'      => $locQua,
                'lot_id'           => $lot4,
                'serial_id'        => null,
                'quantity'         => 20.000,
                'reserved_qty'     => 0.000,
                'supplier_id'      => $sup2,
                'manufacture_date' => '2025-03-01',
                'received_date'    => '2025-04-05',
                'expiry_date'      => '2027-03-01',
                'status'           => 2, // Quarantine
                'updated_at'       => $now,
            ],
        ];

        foreach ($stocks as $row) {
            // Kiểm tra trùng (product × location × lot × serial)
            $exists = DB::table('stock')
                ->where('product_id',  $row['product_id'])
                ->where('location_id', $row['location_id'])
                ->where('lot_id',      $row['lot_id'])
                ->where('serial_id',   $row['serial_id'])
                ->exists();

            if (! $exists) {
                DB::table('stock')->insert($row);
            }
        }
    }
}
