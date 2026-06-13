<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BomSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $sp001 = DB::table('products')->where('code', 'SP001')->value('id'); // Máy bơm nước LVP-50
        $sp002 = DB::table('products')->where('code', 'SP002')->value('id'); // Động cơ điện 3 pha
        $sp005 = DB::table('products')->where('code', 'SP005')->value('id'); // Cáp điện CVV

        $uomCai = DB::table('uoms')->where('name', 'Cái')->value('id') ?? 1;
        $uomMet = DB::table('uoms')->where('name', 'Mét')->value('id') ?? $uomCai;

        if (!$sp001 || !$sp002 || !$sp005) {
            $this->command->warn('BomSeeder: thiếu SP001/SP002/SP005, bỏ qua.');
            return;
        }

        $bomCode = 'BOM-001';
        $bomId = DB::table('boms')->where('code', $bomCode)->value('id');

        if (!$bomId) {
            $bomId = DB::table('boms')->insertGetId([
                'code'       => $bomCode,
                'name'       => 'Lắp ráp Máy bơm nước LVP-50',
                'type'       => 2, // Assemble — Ghép
                'note'       => 'Động cơ điện + Cáp điện → Máy bơm nước hoàn chỉnh',
                'status'     => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('bom_details')->insert([
                // Consume — nguyên liệu đầu vào
                [
                    'bom_id'     => $bomId,
                    'product_id' => $sp002,
                    'line_type'  => 1, // Consume
                    'qty'        => 1.000,
                    'uom_id'     => $uomCai,
                    'note'       => 'Động cơ điện 3 pha 5.5kW',
                ],
                [
                    'bom_id'     => $bomId,
                    'product_id' => $sp005,
                    'line_type'  => 1, // Consume
                    'qty'        => 3.000,
                    'uom_id'     => $uomMet,
                    'note'       => 'Cáp điện CVV 2x1.5mm² — đấu nối motor',
                ],
                // Produce — sản phẩm đầu ra
                [
                    'bom_id'     => $bomId,
                    'product_id' => $sp001,
                    'line_type'  => 2, // Produce
                    'qty'        => 1.000,
                    'uom_id'     => $uomCai,
                    'note'       => 'Máy bơm nước LVP-50 hoàn chỉnh',
                ],
            ]);
        }

        // ── BOM-002: Tách (Disassemble) — Máy bơm nước LVP-50 → Động cơ + Cáp điện ──
        $bomCode2 = 'BOM-002';
        $bomId2 = DB::table('boms')->where('code', $bomCode2)->value('id');

        if (!$bomId2) {
            $bomId2 = DB::table('boms')->insertGetId([
                'code'       => $bomCode2,
                'name'       => 'Tách rã Máy bơm nước LVP-50',
                'type'       => 1, // Disassemble — Tách
                'note'       => 'Máy bơm nước → Động cơ điện + Cáp điện (thu hồi linh kiện)',
                'status'     => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('bom_details')->insert([
                // Consume — đầu vào (hàng cần tách)
                [
                    'bom_id'     => $bomId2,
                    'product_id' => $sp001,
                    'line_type'  => 1, // Consume
                    'qty'        => 1.000,
                    'uom_id'     => $uomCai,
                    'note'       => 'Máy bơm nước LVP-50 cần tách rã',
                ],
                // Produce — đầu ra (linh kiện thu hồi)
                [
                    'bom_id'     => $bomId2,
                    'product_id' => $sp002,
                    'line_type'  => 2, // Produce
                    'qty'        => 1.000,
                    'uom_id'     => $uomCai,
                    'note'       => 'Động cơ điện 3 pha 5.5kW thu hồi',
                ],
                [
                    'bom_id'     => $bomId2,
                    'product_id' => $sp005,
                    'line_type'  => 2, // Produce
                    'qty'        => 2.500,
                    'uom_id'     => $uomMet,
                    'note'       => 'Cáp điện CVV 2x1.5mm² thu hồi (hao hụt 0.5m)',
                ],
            ]);
        }
    }
}