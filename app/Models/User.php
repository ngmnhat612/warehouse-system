<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;  // ← thêm

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;  // ← thêm HasRoles

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // ===== RELATIONSHIPS =====

    public function employee()
    {
        return $this->hasOne(Employee::class, 'user_id');
    }

    // ===== HELPERS =====

    public function isManager(): bool
    {
        return $this->hasRole('warehouse_manager');
    }

    public function displayRole(): string
    {
        return match(true) {
            $this->hasRole('warehouse_manager') => 'Thủ kho',
            $this->hasRole('warehouse_staff')   => 'Nhân viên kho',
            default                              => 'Chưa phân quyền',
        };
    }
}