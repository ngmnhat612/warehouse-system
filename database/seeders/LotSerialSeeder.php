<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LotSerialSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $sup1 = DB::table('suppliers')->where('code', 'NCC001')->value('id');
        $sup2 = DB::table('suppliers')->where('code', 'NCC002')->value('id');

        $products = DB::table('products')->get();

        // ── LOTS: 1 lot "đang dùng" cho mỗi sản phẩm tracking_type = 2 ────────
        foreach ($products->where('tracking_type', 2) as $p) {
            $lotNumber = "LOT-{$p->code}-001";

            $exists = DB::table('lots')
                ->where('product_id', $p->id)
                ->where('lot_number', $lotNumber)
                ->exists();

            if (! $exists) {
                DB::table('lots')->insert([
                    'product_id'       => $p->id,
                    'lot_number'       => $lotNumber,
                    'supplier_id'      => $sup1,
                    'manufacture_date' => $now->copy()->subMonths(2)->toDateString(),
                    'received_date'    => $now->copy()->subMonths(1)->toDateString(),
                    'expiry_date'      => $now->copy()->addMonths(12)->toDateString(),
                    'status'           => 1, // Active
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ]);
            }
        }

        // ── SERIALS: 2 serial "đang dùng" cho mỗi sản phẩm tracking_type = 3 ──
        foreach ($products->where('tracking_type', 3) as $p) {
            for ($n = 1; $n <= 2; $n++) {
                $serialNumber = sprintf('SN-%s-%03d', $p->code, $n);

                $exists = DB::table('serials')
                    ->where('product_id', $p->id)
                    ->where('serial_number', $serialNumber)
                    ->exists();

                if (! $exists) {
                    DB::table('serials')->insert([
                        'product_id'       => $p->id,
                        'serial_number'    => $serialNumber,
                        'lot_id'           => null,
                        'supplier_id'      => $sup2,
                        'manufacture_date' => $now->copy()->subMonths(2)->toDateString(),
                        'received_date'    => $now->copy()->subMonths(1)->toDateString(),
                        'expiry_date'      => null,
                        'status'           => 1, // InStock
                        'created_at'       => $now,
                        'updated_at'       => $now,
                    ]);
                }
            }
        }

        // ── LOT + SERIAL (tracking_type = 4): 1 lot, mỗi serial gắn vào lot đó ──
        foreach ($products->where('tracking_type', 4) as $p) {
            $lotNumber = "LOT-{$p->code}-001";

            $lotId = DB::table('lots')
                ->where('product_id', $p->id)
                ->where('lot_number', $lotNumber)
                ->value('id');

            if (! $lotId) {
                $lotId = DB::table('lots')->insertGetId([
                    'product_id'       => $p->id,
                    'lot_number'       => $lotNumber,
                    'supplier_id'      => $sup1,
                    'manufacture_date' => $now->copy()->subMonths(2)->toDateString(),
                    'received_date'    => $now->copy()->subMonths(1)->toDateString(),
                    'expiry_date'      => $now->copy()->addMonths(12)->toDateString(),
                    'status'           => 1, // Active
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ]);
            }

            for ($n = 1; $n <= 2; $n++) {
                $serialNumber = sprintf('SN-%s-%03d', $p->code, $n);

                $exists = DB::table('serials')
                    ->where('product_id', $p->id)
                    ->where('serial_number', $serialNumber)
                    ->exists();

                if (! $exists) {
                    DB::table('serials')->insert([
                        'product_id'       => $p->id,
                        'serial_number'    => $serialNumber,
                        'lot_id'           => $lotId,
                        'supplier_id'      => $sup1,
                        'manufacture_date' => $now->copy()->subMonths(2)->toDateString(),
                        'received_date'    => $now->copy()->subMonths(1)->toDateString(),
                        'expiry_date'      => $now->copy()->addMonths(12)->toDateString(),
                        'status'           => 1, // InStock
                        'created_at'       => $now,
                        'updated_at'       => $now,
                    ]);
                }
            }
        }

        // ── LOT SẮP HẾT HẠN: thêm 1 lot "-NEAREXP" cho mỗi sản phẩm Lot/Lot+Serial
        //    có cấu hình alert_before_expiry — expiry còn ít hơn alert vài ngày ──
        $nearExpProducts = $products
            ->whereIn('tracking_type', [2, 4])
            ->whereNotNull('alert_before_expiry');

        foreach ($nearExpProducts as $p) {
            $lotNumber = "LOT-{$p->code}-NEAREXP";

            $exists = DB::table('lots')
                ->where('product_id', $p->id)
                ->where('lot_number', $lotNumber)
                ->exists();

            if ($exists) continue;

            // Hết hạn trong vòng (alert_before_expiry - 5) ngày → rơi vào ngưỡng cảnh báo
            $daysLeft = max(1, (int) $p->alert_before_expiry - 5);

            DB::table('lots')->insert([
                'product_id'       => $p->id,
                'lot_number'       => $lotNumber,
                'supplier_id'      => $sup2,
                'manufacture_date' => $now->copy()->subMonths(6)->toDateString(),
                'received_date'    => $now->copy()->subMonths(5)->toDateString(),
                'expiry_date'      => $now->copy()->addDays($daysLeft)->toDateString(),
                'status'           => 1, // Active
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);
        }
    }
}
