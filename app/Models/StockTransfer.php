<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    protected $table = 'stock_transfers';

    protected $fillable = [
        'code', 'transfer_type', 'created_by', 'confirmed_by',
        'approved_by',
        'status', 'transfer_date', 'note',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'status'        => 'integer',
    ];

    // transfer_type constants
    const TYPE_REARRANGE   = 1;
    const TYPE_QUARANTINE  = 2;
    const TYPE_OTHER       = 3;

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
        return $this->hasMany(StockTransferDetail::class, 'stock_transfer_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function confirmedBy()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
