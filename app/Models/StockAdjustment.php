<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class StockAdjustment extends Model
{
    use LogsActivity;

    protected $table = 'stock_adjustments';

    protected $fillable = [
        'code', 'inventory_check_id', 'approved_by', 'created_by', 'confirmed_by',
        'status', 'adjustment_date', 'note',
    ];

    protected $casts = [
        'adjustment_date' => 'date',
        'status'          => 'integer',
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['code', 'status', 'inventory_check_id', 'adjustment_date', 'note'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $event) => match($event) {
                'created' => "Tạo phiếu điều chỉnh tồn kho \"{$this->code}\"",
                'updated' => "Cập nhật phiếu điều chỉnh \"{$this->code}\"",
                'deleted' => "Xóa phiếu điều chỉnh \"{$this->code}\"",
                default   => $event,
            });
    }
}
