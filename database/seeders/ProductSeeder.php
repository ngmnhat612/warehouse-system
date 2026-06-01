<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // UOM ids (từ migration): Cái=1, Cuộn=2, Kg=3, Hộp=4, Bộ=5, Mét=6, Lít=7, Tấm=8
        // Category ids (từ migration + CategorySeeder):
        //   MAY=1, LK=2, NVL=3
        //   MAY-BOM=4, MAY-DONG=5, LK-VONG=6, LK-BOARD=7, NVL-CAP=8, NVL-ONG=9, NVL-DAU=10
        // Dùng firstOrCreate qua tên category để không hardcode id

        $catBom   = DB::table('categories')->where('code', 'MAY-BOM')->value('id');
        $catDong  = DB::table('categories')->where('code', 'MAY-DONG')->value('id');
        $catVong  = DB::table('categories')->where('code', 'LK-VONG')->value('id');
        $catBoard = DB::table('categories')->where('code', 'LK-BOARD')->value('id');
        $catCap   = DB::table('categories')->where('code', 'NVL-CAP')->value('id');
        $catOng   = DB::table('categories')->where('code', 'NVL-ONG')->value('id');
        $catDau   = DB::table('categories')->where('code', 'NVL-DAU')->value('id');

        $now = now();

        $products = [
            [
                'code'                => 'SP001',
                'name'                => 'Máy bơm nước LVP-50',
                'category_id'         => $catBom,
                'uom_id'              => 1,   // Cái
                'uom_purchase_id'     => null,
                'barcode'             => 'BC-SP001',
                'min_stock'           => 2.000,
                'max_stock'           => 20.000,
                'alert_before_expiry' => null,
                'tracking_type'       => 1,   // None
                'stock_rotation'      => 1,   // FIFO
                'status'              => 1,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'code'                => 'SP002',
                'name'                => 'Động cơ điện 3 pha 5.5kW',
                'category_id'         => $catDong,
                'uom_id'              => 1,   // Cái
                'uom_purchase_id'     => null,
                'barcode'             => 'BC-SP002',
                'min_stock'           => 1.000,
                'max_stock'           => 10.000,
                'alert_before_expiry' => null,
                'tracking_type'       => 1,   // None
                'stock_rotation'      => 1,   // FIFO
                'status'              => 1,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'code'                => 'SP003',
                'name'                => 'Vòng bi 6205-2RS',
                'category_id'         => $catVong,
                'uom_id'              => 1,   // Cái
                'uom_purchase_id'     => 4,   // Hộp
                'barcode'             => 'BC-SP003',
                'min_stock'           => 10.000,
                'max_stock'           => 200.000,
                'alert_before_expiry' => 180,
                'tracking_type'       => 2,   // Lot
                'stock_rotation'      => 2,   // FEFO
                'status'              => 1,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'code'                => 'SP004',
                'name'                => 'Board điều khiển PLC Siemens S7-1200',
                'category_id'         => $catBoard,
                'uom_id'              => 1,   // Cái
                'uom_purchase_id'     => null,
                'barcode'             => 'BC-SP004',
                'min_stock'           => 1.000,
                'max_stock'           => 5.000,
                'alert_before_expiry' => null,
                'tracking_type'       => 3,   // Serial
                'stock_rotation'      => 1,   // FIFO
                'status'              => 1,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'code'                => 'SP005',
                'name'                => 'Cáp điện CVV 2x1.5mm²',
                'category_id'         => $catCap,
                'uom_id'              => 2,   // Cuộn
                'uom_purchase_id'     => null,
                'barcode'             => 'BC-SP005',
                'min_stock'           => 5.000,
                'max_stock'           => 50.000,
                'alert_before_expiry' => null,
                'tracking_type'       => 1,   // None
                'stock_rotation'      => 1,   // FIFO
                'status'              => 1,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'code'                => 'SP006',
                'name'                => 'Ống thép đen DN42 (6m/cây)',
                'category_id'         => $catOng,
                'uom_id'              => 1,   // Cái (cây)
                'uom_purchase_id'     => null,
                'barcode'             => 'BC-SP006',
                'min_stock'           => 10.000,
                'max_stock'           => 200.000,
                'alert_before_expiry' => null,
                'tracking_type'       => 1,   // None
                'stock_rotation'      => 1,   // FIFO
                'status'              => 1,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
            [
                'code'                => 'SP007',
                'name'                => 'Dầu bôi trơn ISO VG 68 (20L)',
                'category_id'         => $catDau,
                'uom_id'              => 7,   // Lít
                'uom_purchase_id'     => null,
                'barcode'             => 'BC-SP007',
                'min_stock'           => 20.000,
                'max_stock'           => 200.000,
                'alert_before_expiry' => 90,
                'tracking_type'       => 2,   // Lot
                'stock_rotation'      => 2,   // FEFO
                'status'              => 1,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
        ];

        foreach ($products as $row) {
            DB::table('products')->updateOrInsert(
                ['code' => $row['code']],
                $row
            );
        }
    }
}
