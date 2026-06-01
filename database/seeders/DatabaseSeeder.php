<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {

        User::firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'password' => bcrypt('password')]
        );

        $this->call([
            // 1. Roles + Admin account (B đã làm)
            AdminSeeder::class,

            // 2. Quy đổi đơn vị tính
            UomConversionSeeder::class,

            // 3. Danh mục con (bổ sung cho migration đã seed cha)
            CategorySeeder::class,

            // 4. Nhà cung cấp
            SupplierSeeder::class,

            // 5. Nhân viên bổ sung (ngoài admin)
            EmployeeSeeder::class,

            // 6. Vị trí kho chi tiết (bổ sung cho migration đã seed vị trí gốc)
            LocationSeeder::class,

            // 7. Sản phẩm
            ProductSeeder::class,

            // 8. Lot + Serial (phải sau Product + Supplier)
            LotSerialSeeder::class,

            // 9. Tồn kho ban đầu (phải sau tất cả phía trên)
            StockSeeder::class,
        ]);
    }
}