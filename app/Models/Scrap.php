<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Scrap extends Model
{
    protected $table = 'scraps';

    protected $fillable = [
        'code', 'created_by', 'approved_by', 'status', 'scrap_date', 'note',
    ];

    protected $casts = [
        'scrap_date' => 'date',
        'status'     => 'integer',
    ];

    // status constants
    const STATUS_DRAFT     = 1;
    const STATUS_PENDING   = 2;
    const STATUS_APPROVED  = 3;
    const STATUS_COMPLETED = 4;
    const STATUS_CANCELLED = 5;

    public static function statusLabel(int $status): string
    {
        return [
            self::STATUS_DRAFT     => 'Nháp',
            self::STATUS_PENDING   => 'Chờ duyệt',
            self::STATUS_APPROVED  => 'Đã duyệt',
            self::STATUS_COMPLETED => 'Hoàn thành',
            self::STATUS_CANCELLED => 'Đã hủy',
        ][$status] ?? 'Không xác định';
    }

    // ===== RELATIONSHIPS =====

    public function details()
    {
        return $this->hasMany(ScrapDetail::class, 'scrap_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['code', 'status', 'scrap_date', 'note'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $event) => match($event) {
                'created' => "Tạo phiếu hủy hàng \"{$this->code}\"",
                'updated' => "Cập nhật phiếu hủy hàng \"{$this->code}\"",
                'deleted' => "Xóa phiếu hủy hàng \"{$this->code}\"",
                default   => $event,
            });
    }
}
