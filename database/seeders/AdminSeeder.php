<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Tạo roles nếu chưa có
        $managerRole = Role::firstOrCreate(['name' => 'warehouse_manager', 'guard_name' => 'web']);
        $staffRole   = Role::firstOrCreate(['name' => 'warehouse_staff',   'guard_name' => 'web']);

        // Tạo tài khoản admin mặc định
        $admin = User::firstOrCreate(
            ['email' => 'admin@warehouse.local'],
            [
                'name'     => 'Administrator',
                'password' => Hash::make('Admin@1234'),
            ]
        );
        $admin->syncRoles([$managerRole]);

        // Tạo hồ sơ nhân viên cho admin nếu chưa có
        Employee::firstOrCreate(
            ['user_id' => $admin->id],
            [
                'code'      => 'NV001',
                'full_name' => 'Nguyễn Thủ Kho',
                'email'     => 'admin@warehouse.local',
                'status'    => 1,
            ]
        );
    }
}