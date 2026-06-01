<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Uom extends Model
{
    protected $table = 'uoms';

    protected $fillable = [
        'name',
        'status',
    ];

    // Không dùng timestamps mặc định nếu bảng không có
    public $timestamps = false;

    // ===== RELATIONSHIPS =====

    public function products()
    {
        return $this->hasMany(Product::class, 'uom_id');
    }

    public function productsAsPurchase()
    {
        return $this->hasMany(Product::class, 'uom_purchase_id');
    }

    public function conversionsFrom()
    {
        return $this->hasMany(UomConversion::class, 'from_uom_id');
    }

    public function conversionsTo()
    {
        return $this->hasMany(UomConversion::class, 'to_uom_id');
    }

    // ===== ACTIVITY LOG =====

    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'status'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => match($eventName) {
                'created' => "Thêm đơn vị tính \"{$this->name}\"",
                'updated' => "Cập nhật đơn vị tính \"{$this->name}\"",
                'deleted' => "Xóa đơn vị tính \"{$this->name}\"",
                default   => $eventName,
            });
    }

    // ===== SCOPES =====

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
