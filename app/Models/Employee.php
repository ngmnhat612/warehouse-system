<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Employee extends Model
{
    protected $table = 'employees';

    protected $fillable = [
        'user_id',
        'code',
        'full_name',
        'phone',
        'email',
        'status',
    ];

    // ===== RELATIONSHIPS =====

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ===== ACTIVITY LOG =====

    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['code', 'full_name', 'phone', 'email', 'status'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $event) => match($event) {
                'created' => "Thêm nhân viên \"{$this->full_name}\"",
                'updated' => "Cập nhật nhân viên \"{$this->full_name}\"",
                'deleted' => "Xóa nhân viên \"{$this->full_name}\"",
                default   => $event,
            });
    }

    // ===== SCOPES =====

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    // ===== HELPERS =====

    public function hasAccount(): bool
    {
        return $this->user_id !== null;
    }
}