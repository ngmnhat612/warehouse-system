<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Supplier extends Model
{
    protected $table = 'suppliers';

    protected $fillable = [
        'code', 'name', 'tax_code', 'phone', 'email', 'address', 'status',
    ];

    // ===== RELATIONSHIPS =====

    public function stockReceipts()
    {
        return $this->hasMany(\App\Models\StockReceipt::class, 'supplier_id');
    }

    // ===== SCOPES =====

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    // ===== ACTIVITY LOG =====

    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['code', 'name', 'tax_code', 'phone', 'email', 'status'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => match($eventName) {
                'created' => "Thêm nhà cung cấp \"{$this->name}\"",
                'updated' => "Cập nhật nhà cung cấp \"{$this->name}\"",
                'deleted' => "Xóa nhà cung cấp \"{$this->name}\"",
                default   => $eventName,
            });
    }
}