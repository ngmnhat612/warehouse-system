<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // Migration đã tạo sẵn: MAY=1, LK=2, NVL=3
        // Seeder này bổ sung danh mục con
        $children = [
            // Con của MAY (id=1)
            ['code' => 'MAY-BOM',  'name' => 'Máy bơm',          'parent_id' => 1, 'status' => 1],
            ['code' => 'MAY-DONG', 'name' => 'Động cơ điện',     'parent_id' => 1, 'status' => 1],

            // Con của LK (id=2)
            ['code' => 'LK-VONG',  'name' => 'Vòng bi / Bạc đạn','parent_id' => 2, 'status' => 1],
            ['code' => 'LK-BOARD', 'name' => 'Board điện tử',    'parent_id' => 2, 'status' => 1],

            // Con của NVL (id=3)
            ['code' => 'NVL-CAP',  'name' => 'Cáp / Dây điện',  'parent_id' => 3, 'status' => 1],
            ['code' => 'NVL-ONG',  'name' => 'Ống / Thanh thép','parent_id' => 3, 'status' => 1],
            ['code' => 'NVL-DAU',  'name' => 'Dầu / Hóa chất',  'parent_id' => 3, 'status' => 1],
        ];

        foreach ($children as $row) {
            $row['description'] = null;
            DB::table('categories')->updateOrInsert(
                ['code' => $row['code']],
                $row
            );
        }
    }
}
