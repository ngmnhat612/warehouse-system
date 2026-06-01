<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lot extends Model
{
    protected $table = 'lots';

    protected $fillable = [
        'product_id', 'lot_number', 'supplier_id',
        'manufacture_date', 'received_date', 'expiry_date', 'status',
    ];

    protected $casts = [
        'manufacture_date' => 'date',
        'received_date'    => 'date',
        'expiry_date'      => 'date',
    ];

    // ===== CONSTANTS =====

    const STATUS_ACTIVE     = 1;
    const STATUS_QUARANTINE = 2;
    const STATUS_EXPIRED    = 3;
    const STATUS_CONSUMED   = 4;

    public static function statuses(): array
    {
        return [
            self::STATUS_ACTIVE     => 'Active',
            self::STATUS_QUARANTINE => 'Quarantine',
            self::STATUS_EXPIRED    => 'Expired',
            self::STATUS_CONSUMED   => 'Consumed',
        ];
    }

    public static function statusColors(): array
    {
        return [
            self::STATUS_ACTIVE     => 'success',
            self::STATUS_QUARANTINE => 'warning',
            self::STATUS_EXPIRED    => 'danger',
            self::STATUS_CONSUMED   => 'secondary',
        ];
    }

    // ===== RELATIONSHIPS =====

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function serials()
    {
        return $this->hasMany(Serial::class, 'lot_id');
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class, 'lot_id');
    }

    // ===== SCOPES =====

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->whereNotNull('expiry_date')
                     ->whereBetween('expiry_date', [now(), now()->addDays($days)]);
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

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }
}