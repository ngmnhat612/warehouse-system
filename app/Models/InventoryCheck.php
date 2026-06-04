<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class InventoryCheck extends Model
{
    use LogsActivity;

    protected $table = 'inventory_checks';

    protected $fillable = [
        'code', 'check_type', 'created_by', 'assigned_to',
        'status', 'check_date', 'note', 'completed_at',
    ];

    protected $casts = [
        'check_date'   => 'date',
        'completed_at' => 'datetime',
        'status'       => 'integer',
        'check_type'   => 'integer',
    ];

    // check_type constants
    const TYPE_ALL      = 1; // Toàn kho
    const TYPE_LOCATION = 2; // Theo khu vực
    const TYPE_PRODUCT  = 3; // Theo mặt hàng

    // status constants
    const STATUS_DRAFT       = 1;
    const STATUS_IN_PROGRESS = 2;
    const STATUS_DONE        = 3;
    const STATUS_CANCELLED   = 4;

    public static function statusLabel(int $status): string
    {
        return [
            self::STATUS_DRAFT       => 'Nháp',
            self::STATUS_IN_PROGRESS => 'Đang kiểm kê',
            self::STATUS_DONE        => 'Hoàn thành',
            self::STATUS_CANCELLED   => 'Đã hủy',
        ][$status] ?? 'Không xác định';
    }

    // ===== RELATIONSHIPS =====

    public function lines()
    {
        return $this->hasMany(InventoryCheckLine::class, 'inventory_check_id');
    }

    public function freeze()
    {
        return $this->hasOne(InventoryFreeze::class, 'check_id');
    }

    public function adjustments()
    {
        return $this->hasMany(StockAdjustment::class, 'inventory_check_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    // ===== HELPERS =====

    public function isFrozen(): bool
    {
        return $this->freeze()->whereNull('unfrozen_at')->exists();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['code', 'status', 'check_type', 'check_date', 'assigned_to', 'note'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $event) => match($event) {
                'created' => "Tạo phiếu kiểm kê \"{$this->code}\"",
                'updated' => "Cập nhật phiếu kiểm kê \"{$this->code}\"",
                'deleted' => "Xóa phiếu kiểm kê \"{$this->code}\"",
                default   => $event,
            });
    }
}
