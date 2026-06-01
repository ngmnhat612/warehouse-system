<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UomConversionSeeder extends Seeder
{
    public function run(): void
    {
        // Migration đã seed uoms: Cái=1, Cuộn=2, Kg=3, Hộp=4, Bộ=5, Mét=6, Lít=7, Tấm=8
        // Dùng firstOrCreate qua DB để tránh trùng lặp khi chạy lại
        $conversions = [
            // 1 Hộp = 12 Cái
            ['from_uom_id' => 4, 'to_uom_id' => 1, 'factor' => 12.000000, 'status' => 1],
            // 1 Bộ = 4 Cái
            ['from_uom_id' => 5, 'to_uom_id' => 1, 'factor' => 4.000000,  'status' => 1],
            // 1 Cuộn = 100 Mét
            ['from_uom_id' => 2, 'to_uom_id' => 6, 'factor' => 100.000000,'status' => 1],
            // 1 Tấm = 1000 Kg  (ví dụ: tấm thép 1 tấn)
            ['from_uom_id' => 8, 'to_uom_id' => 3, 'factor' => 1000.000000,'status' => 1],
        ];

        foreach ($conversions as $row) {
            $exists = DB::table('uom_conversions')
                ->where('from_uom_id', $row['from_uom_id'])
                ->where('to_uom_id', $row['to_uom_id'])
                ->exists();

            if (! $exists) {
                DB::table('uom_conversions')->insert($row);
            }
        }
    }
}