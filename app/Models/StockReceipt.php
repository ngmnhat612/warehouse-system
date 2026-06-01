<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockReceipt extends Model
{
    protected $table = 'stock_receipts';

    protected $fillable = [
        'code', 'receipt_type', 'supplier_id', 'reference_no',
        'created_by', 'confirmed_by', 'status', 'receipt_date', 'note',
    ];

    protected $casts = [
        'receipt_date' => 'date',
    ];

    // receipt_type constants
    const TYPE_SUPPLIER = 1;
    const TYPE_RETURN   = 2;
    const TYPE_OTHER    = 3;

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
        return $this->hasMany(StockReceiptDetail::class, 'stock_receipt_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
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
