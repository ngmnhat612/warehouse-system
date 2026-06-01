<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $staffRole = Role::firstOrCreate(['name' => 'warehouse_staff', 'guard_name' => 'web']);

        // ── Nhân viên 2: có tài khoản, role = staff ──────────────────────
        $user2 = User::firstOrCreate(
            ['email' => 'nv002@warehouse.local'],
            [
                'name'     => 'Nguyễn Văn Kho',
                'password' => Hash::make('Staff@1234'),
            ]
        );
        $user2->syncRoles([$staffRole]);

        Employee::firstOrCreate(
            ['code' => 'NV002'],
            [
                'user_id'   => $user2->id,
                'full_name' => 'Nguyễn Văn Kho',
                'phone'     => '0912000001',
                'email'     => 'nv002@warehouse.local',
                'status'    => 1,
            ]
        );

        // ── Nhân viên 3: có tài khoản, role = staff ──────────────────────
        $user3 = User::firstOrCreate(
            ['email' => 'nv003@warehouse.local'],
            [
                'name'     => 'Trần Thị Nhập',
                'password' => Hash::make('Staff@1234'),
            ]
        );
        $user3->syncRoles([$staffRole]);

        Employee::firstOrCreate(
            ['code' => 'NV003'],
            [
                'user_id'   => $user3->id,
                'full_name' => 'Trần Thị Nhập',
                'phone'     => '0912000002',
                'email'     => 'nv003@warehouse.local',
                'status'    => 1,
            ]
        );

        // ── Nhân viên 4: chưa có tài khoản hệ thống ──────────────────────
        Employee::firstOrCreate(
            ['code' => 'NV004'],
            [
                'user_id'   => null,
                'full_name' => 'Lê Văn Xuất',
                'phone'     => '0912000003',
                'email'     => null,
                'status'    => 1,
            ]
        );
    }
}
