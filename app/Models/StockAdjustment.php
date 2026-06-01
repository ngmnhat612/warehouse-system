<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    protected $table = 'stock_adjustments';

    protected $fillable = [
        'code', 'inventory_check_id', 'approved_by', 'created_by', 'confirmed_by',
        'status', 'adjustment_date', 'note',
    ];

    protected $casts = [
        'adjustment_date' => 'date',
    ];

    // status constants
    const STATUS_DRAFT    = 1;
    const STATUS_PENDING  = 2;
    const STATUS_APPROVED = 3;
    const STATUS_APPLIED  = 4;
    const STATUS_REJECTED = 5;

    public static function statusLabel(int $status): string
    {
        return [
            self::STATUS_DRAFT    => 'Nháp',
            self::STATUS_PENDING  => 'Chờ duyệt',
            self::STATUS_APPROVED => 'Đã duyệt',
            self::STATUS_APPLIED  => 'Đã áp dụng',
            self::STATUS_REJECTED => 'Từ chối',
        ][$status] ?? 'Không xác định';
    }

    // ===== RELATIONSHIPS =====

    public function details()
    {
        return $this->hasMany(StockAdjustmentDetail::class, 'stock_adjustment_id');
    }

    public function inventoryCheck()
    {
        return $this->belongsTo(InventoryCheck::class, 'inventory_check_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function confirmedBy()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
