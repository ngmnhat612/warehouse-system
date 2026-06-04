<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Tạo roles nếu chưa có
        $managerRole = Role::firstOrCreate(['name' => 'warehouse_manager', 'guard_name' => 'web']);
        $staffRole   = Role::firstOrCreate(['name' => 'warehouse_staff',   'guard_name' => 'web']);

        // Tạo permissions
        $permissions = [
            // Master data
            'master.view',
            'master.create',
            'master.edit',
            'master.delete',

            // Phiếu nhập
            'receipt.view',
            'receipt.create',
            'receipt.approve',   // chỉ manager

            // Phiếu xuất
            'issue.view',
            'issue.create',
            'issue.approve',     // chỉ manager

            // Phiếu chuyển kho
            'transfer.view',
            'transfer.create',
            'transfer.approve',  // thêm mới — chỉ manager
            'transfer.confirm',  // thêm mới — chỉ manager
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // Manager có tất cả
        $managerRole->syncPermissions($permissions);

        // Staff không có approve/confirm
        $staffRole->syncPermissions([
            'receipt.view',   'receipt.create',
            'issue.view',     'issue.create',
            'transfer.view',  'transfer.create',
            'transfer.confirm',
        ]);

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