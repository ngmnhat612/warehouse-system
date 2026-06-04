<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReorderRuleSeeder extends Seeder
{
    public function run(): void
    {
        // Lấy product id động theo code
        $products = DB::table('products')
            ->whereIn('code', [
                'SP001','SP002','SP003','SP004','SP005',
                'SP006','SP007','SP008','SP009','SP010',
                'SP014','SP015','SP016','SP017','SP026',
                'SP027','SP029','SP030','SP031',
            ])
            ->pluck('id', 'code');

        // Lấy location id động theo code
        $locations = DB::table('locations')
            ->whereIn('code', ['WH-PAL-A','WH-PAL-B','WH-SHELF-A1','WH-SHELF-A2','WH-PAL-C','WH-SHELF-B1'])
            ->pluck('id', 'code');

        $locPalA  = $locations['WH-PAL-A']     ?? null;
        $locPalB  = $locations['WH-PAL-B']     ?? null;
        $locPalC  = $locations['WH-PAL-C']     ?? null;
        $locShA1  = $locations['WH-SHELF-A1']  ?? null;
        $locShA2  = $locations['WH-SHELF-A2']  ?? null;
        $locShB1  = $locations['WH-SHELF-B1']  ?? null;

        if (! $locPalA || ! $locShA1) {
            $this->command->warn('ReorderRuleSeeder: Không tìm thấy locations. Chạy LocationSeeder trước.');
            return;
        }

        $now = now();

        // min_qty / max_qty lấy từ products.min_stock / max_stock
        // Chọn location chính nơi hàng đó thường trữ (theo StockSeeder)
        $rules = [
            // ── Có stock thực tế (SP001–SP007) ───────────────────────────────
            // SP001 - Máy bơm — tồn 8, min 2 → bình thường (để test "không hiển thị")
            [
                'code'      => 'SP001',
                'location'  => $locPalA,
                'min_qty'   => 2.000,
                'max_qty'   => 20.000,
            ],
            // SP002 - Động cơ — tồn 3, reserved 1 → avail 2, min 1 → bình thường
            [
                'code'      => 'SP002',
                'location'  => $locPalB,
                'min_qty'   => 1.000,
                'max_qty'   => 10.000,
            ],
            // SP003 - Vòng bi — tồn 60, min 10 → bình thường
            [
                'code'      => 'SP003',
                'location'  => $locShA1,
                'min_qty'   => 10.000,
                'max_qty'   => 200.000,
            ],
            // SP004 - Board PLC — tồn 2 (2 serial), min 1 → bình thường
            [
                'code'      => 'SP004',
                'location'  => $locShA2,
                'min_qty'   => 1.000,
                'max_qty'   => 5.000,
            ],
            // SP005 - Cáp điện — tồn 15, min 5 → bình thường
            [
                'code'      => 'SP005',
                'location'  => $locPalA,
                'min_qty'   => 5.000,
                'max_qty'   => 50.000,
            ],
            // SP006 - Ống thép — tồn 80, min 10 → bình thường
            [
                'code'      => 'SP006',
                'location'  => $locPalB,
                'min_qty'   => 10.000,
                'max_qty'   => 200.000,
            ],
            // SP007 - Dầu bôi trơn — tồn 80 (tại ShA1, status=1), min 20 → bình thường
            [
                'code'      => 'SP007',
                'location'  => $locShA1,
                'min_qty'   => 20.000,
                'max_qty'   => 200.000,
            ],

            // ── Không có stock (tồn = 0) → sẽ hiển thị "Hết hàng" ───────────
            [
                'code'      => 'SP008',
                'location'  => $locPalA,
                'min_qty'   => 3.000,
                'max_qty'   => 15.000,
            ],
            [
                'code'      => 'SP009',
                'location'  => $locShA2,
                'min_qty'   => 2.000,
                'max_qty'   => 10.000,
            ],
            [
                'code'      => 'SP010',
                'location'  => $locShA2,
                'min_qty'   => 4.000,
                'max_qty'   => 20.000,
            ],
            [
                'code'      => 'SP014',
                'location'  => $locShA1,
                'min_qty'   => 20.000,
                'max_qty'   => 150.000,
            ],
            [
                'code'      => 'SP015',
                'location'  => $locShA1,
                'min_qty'   => 15.000,
                'max_qty'   => 100.000,
            ],

            // ── Đặt min_qty cao hơn tồn thực → hiển thị "Nguy hiểm" / "Chú ý" ─
            // SP016 - Relay — không có stock, min 50
            [
                'code'      => 'SP016',
                'location'  => $locShB1 ?? $locShA1,
                'min_qty'   => 50.000,
                'max_qty'   => 500.000,
            ],
            // SP017 - Khởi động từ — không có stock, min 10
            [
                'code'      => 'SP017',
                'location'  => $locShB1 ?? $locShA1,
                'min_qty'   => 10.000,
                'max_qty'   => 80.000,
            ],
            // SP026 - Mỡ bôi trơn — không có stock, min 10
            [
                'code'      => 'SP026',
                'location'  => $locPalC ?? $locPalB,
                'min_qty'   => 10.000,
                'max_qty'   => 100.000,
            ],
            // SP027 - Dầu thủy lực — không có stock, min 200
            [
                'code'      => 'SP027',
                'location'  => $locPalC ?? $locPalB,
                'min_qty'   => 200.000,
                'max_qty'   => 1000.000,
            ],
            // SP029 - Dây rút — không có stock, min 20
            [
                'code'      => 'SP029',
                'location'  => $locShA2,
                'min_qty'   => 20.000,
                'max_qty'   => 200.000,
            ],
            // SP030 - Ống khí nén — không có stock, min 3
            [
                'code'      => 'SP030',
                'location'  => $locPalA,
                'min_qty'   => 3.000,
                'max_qty'   => 25.000,
            ],
            // SP031 - Keo Loctite — không có stock, min 10
            [
                'code'      => 'SP031',
                'location'  => $locShA2,
                'min_qty'   => 10.000,
                'max_qty'   => 100.000,
            ],
        ];

        foreach ($rules as $rule) {
            $productId = $products[$rule['code']] ?? null;
            if (! $productId || ! $rule['location']) {
                $this->command->warn("ReorderRuleSeeder: Bỏ qua {$rule['code']} — không tìm thấy product hoặc location.");
                continue;
            }

            DB::table('reorder_rules')->updateOrInsert(
                [
                    'product_id'  => $productId,
                    'location_id' => $rule['location'],
                ],
                [
                    'product_id'   => $productId,
                    'location_id'  => $rule['location'],
                    'min_qty'      => $rule['min_qty'],
                    'max_qty'      => $rule['max_qty'],
                    'alert_email'  => null,
                    'note'         => null,
                    'status'       => 1,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ]
            );
        }
    }
}