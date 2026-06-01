<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockIssue extends Model
{
    protected $table = 'stock_issues';

    protected $fillable = [
        'code', 'issue_type', 'requester_id', 'created_by', 'confirmed_by',
        'status', 'issue_date', 'expected_return_date', 'reference_no', 'note',
    ];

    protected $casts = [
        'issue_date'           => 'date',
        'expected_return_date' => 'date',
    ];

    // issue_type constants
    const TYPE_PRODUCTION   = 1;
    const TYPE_MAINTENANCE  = 2;
    const TYPE_BORROW       = 3;
    const TYPE_OTHER        = 4;

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
        return $this->hasMany(StockIssueDetail::class, 'stock_issue_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function confirmedBy()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    // ===== SCOPES =====

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }
}