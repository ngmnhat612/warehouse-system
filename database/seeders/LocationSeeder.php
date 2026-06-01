<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        // Migration đã tạo sẵn:
        //   id=1  WH          Kho chính          (Internal, root)
        //   id=2  VIRTUAL-SUP Nguồn nhập (NCC)   (Virtual/Supplier)
        //   id=3  VIRTUAL-CUS Điểm xuất           (Virtual/Customer)
        //   id=4  VIRTUAL-SCR Khu vực hủy         (Virtual/Scrap)
        //   id=5  VIRTUAL-QUA Khu cách ly          (Virtual/Quarantine)
        //   id=6  WH-PALLET   Khu vực Pallet       (Internal, parent=1)
        //   id=7  WH-SHELF    Khu vực Kệ           (Internal, parent=1)

        // Lấy id động để không hardcode
        $pallet = DB::table('locations')->where('code', 'WH-PALLET')->first();
        $shelf  = DB::table('locations')->where('code', 'WH-SHELF')->first();

        if (! $pallet || ! $shelf) {
            $this->command->warn('LocationSeeder: Không tìm thấy WH-PALLET hoặc WH-SHELF. Bỏ qua.');
            return;
        }

        $subLocations = [
            // Con của WH-PALLET
            ['parent_id' => $pallet->id, 'code' => 'WH-PAL-A', 'name' => 'Pallet A', 'type' => 1, 'barcode' => 'LOC-PAL-A', 'capacity_limit' => 5000.000, 'status' => 1],
            ['parent_id' => $pallet->id, 'code' => 'WH-PAL-B', 'name' => 'Pallet B', 'type' => 1, 'barcode' => 'LOC-PAL-B', 'capacity_limit' => 5000.000, 'status' => 1],
            ['parent_id' => $pallet->id, 'code' => 'WH-PAL-C', 'name' => 'Pallet C', 'type' => 1, 'barcode' => 'LOC-PAL-C', 'capacity_limit' => 5000.000, 'status' => 1],

            // Con của WH-SHELF
            ['parent_id' => $shelf->id,  'code' => 'WH-SHELF-A1', 'name' => 'Kệ A - Ngăn 1', 'type' => 1, 'barcode' => 'LOC-SHA1', 'capacity_limit' => null,     'status' => 1],
            ['parent_id' => $shelf->id,  'code' => 'WH-SHELF-A2', 'name' => 'Kệ A - Ngăn 2', 'type' => 1, 'barcode' => 'LOC-SHA2', 'capacity_limit' => null,     'status' => 1],
            ['parent_id' => $shelf->id,  'code' => 'WH-SHELF-B1', 'name' => 'Kệ B - Ngăn 1', 'type' => 1, 'barcode' => 'LOC-SHB1', 'capacity_limit' => null,     'status' => 1],
        ];

        foreach ($subLocations as $row) {
            DB::table('locations')->updateOrInsert(
                ['code' => $row['code']],
                $row
            );
        }
    }
}
