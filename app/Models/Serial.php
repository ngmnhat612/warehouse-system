<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Serial extends Model
{
    protected $table = 'serials';

    protected $fillable = [
        'product_id', 'serial_number', 'lot_id', 'supplier_id',
        'manufacture_date', 'received_date', 'expiry_date', 'status',
    ];

    protected $casts = [
        'manufacture_date' => 'date',
        'received_date'    => 'date',
        'expiry_date'      => 'date',
    ];

    // ===== CONSTANTS =====

    const STATUS_INSTOCK    = 1;
    const STATUS_QUARANTINE = 2;
    const STATUS_DEFECTIVE  = 3;
    const STATUS_ISSUED     = 4;
    const STATUS_RETURNED   = 5;

    public static function statuses(): array
    {
        return [
            self::STATUS_INSTOCK    => 'Trong kho',
            self::STATUS_QUARANTINE => 'Quarantine',
            self::STATUS_DEFECTIVE  => 'Lỗi / Hư',
            self::STATUS_ISSUED     => 'Đã xuất',
            self::STATUS_RETURNED   => 'Đã trả lại',
        ];
    }

    public static function statusColors(): array
    {
        return [
            self::STATUS_INSTOCK    => 'success',
            self::STATUS_QUARANTINE => 'warning',
            self::STATUS_DEFECTIVE  => 'danger',
            self::STATUS_ISSUED     => 'info',
            self::STATUS_RETURNED   => 'secondary',
        ];
    }

    // ===== RELATIONSHIPS =====

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function lot()
    {
        return $this->belongsTo(Lot::class, 'lot_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function stock()
    {
        return $this->hasOne(Stock::class, 'serial_id');
    }

    // ===== SCOPES =====

    public function scopeInStock($query)
    {
        return $query->where('status', self::STATUS_INSTOCK);
    }

    // ===== HELPERS =====

    public function getStatusLabelAttribute(): string
    {
        return self::statuses()[$this->status] ?? '—';
    }

    public function getStatusColorAttribute(): string
    {
        return self::statusColors()[$this->status] ?? 'secondary';
    }
}