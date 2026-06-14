<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PutawayRuleSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Mỗi nhóm hàng → 1 vị trí đích mặc định khi nhập kho
        $map = [
            'MAY-BOM'  => 'WH-PAL-A',     // Máy bơm
            'MAY-DONG' => 'WH-PAL-B',     // Động cơ
            'LK-VONG'  => 'WH-SHELF-A1',  // Vòng bi / linh kiện nhỏ
            'LK-BOARD' => 'WH-SHELF-A2',  // Board điện tử / PLC
            'NVL-CAP'  => 'WH-SHELF-B1',  // Cáp điện
            'NVL-ONG'  => 'WH-PAL-C',     // Ống thép
            'NVL-DAU'  => 'WH-SHELF',     // Dầu nhớt, hóa chất
        ];

        $priority = 10;

        foreach ($map as $categoryCode => $locationCode) {
            $categoryId = DB::table('categories')->where('code', $categoryCode)->value('id');
            $locationId = DB::table('locations')->where('code', $locationCode)->value('id');

            if (!$categoryId || !$locationId) {
                $this->command->warn("PutawayRuleSeeder: thiếu category {$categoryCode} hoặc location {$locationCode}, bỏ qua.");
                continue;
            }

            $exists = DB::table('putaway_rules')
                ->where('category_id', $categoryId)
                ->whereNull('product_id')
                ->exists();

            if ($exists) continue;

            DB::table('putaway_rules')->insert([
                'product_id'       => null,
                'category_id'      => $categoryId,
                'location_dest_id' => $locationId,
                'priority'         => $priority,
                'note'             => "Putaway mặc định cho nhóm {$categoryCode} → {$locationCode}",
                'status'           => 1,
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);
        }
    }
}
