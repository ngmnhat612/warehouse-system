<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $suppliers = [
            [
                'code'       => 'NCC001',
                'name'       => 'Công ty TNHH Cơ Khí Phú Hưng',
                'tax_code'   => '0312345678',
                'phone'      => '0901234567',
                'email'      => 'info@phuhung.vn',
                'address'    => '123 Nguyễn Văn Linh, Q.7, TP.HCM',
                'status'     => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code'       => 'NCC002',
                'name'       => 'Công ty CP Kỹ Thuật Điện Miền Nam',
                'tax_code'   => '0312345679',
                'phone'      => '0907654321',
                'email'      => 'sales@ktdmn.vn',
                'address'    => '45 Trường Chinh, Q.Tân Bình, TP.HCM',
                'status'     => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code'       => 'NCC003',
                'name'       => 'Công ty TNHH Vật Tư Công Nghiệp Đại Việt',
                'tax_code'   => '0109876543',
                'phone'      => '02438123456',
                'email'      => 'order@daiviet-mro.vn',
                'address'    => '67 Giải Phóng, Q.Hai Bà Trưng, Hà Nội',
                'status'     => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($suppliers as $row) {
            DB::table('suppliers')->insertOrIgnore($row);
        }
    }
}
