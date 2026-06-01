<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockLedger extends Model
{
    protected $table = 'stock_ledger';
    public $timestamps = false;

    // Không cho sửa/xóa ledger — chỉ insert
    protected $fillable = [
        'product_id', 'stock_id', 'lot_id', 'serial_id', 'location_id',
        'transaction_type', 'reference_id', 'reference_type', 'reference_code',
        'direction', 'quantity', 'balance_after',
        'created_by', 'note', 'transaction_date',
    ];

    protected $casts = [
        'quantity'         => 'decimal:3',
        'balance_after'    => 'decimal:3',
        'transaction_date' => 'datetime',
    ];

    // transaction_type constants
    const TYPE_RECEIPT       = 'RECEIPT';
    const TYPE_ISSUE         = 'ISSUE';
    const TYPE_TRANSFER      = 'TRANSFER';
    const TYPE_SCRAP         = 'SCRAP';
    const TYPE_ADJUST        = 'ADJUST';
    const TYPE_TRANSFORM     = 'TRANSFORM';
    const TYPE_RETURN        = 'RETURN';

    // direction constants
    const DIR_IN  = 1;
    const DIR_OUT = 2;

    // ===== RELATIONSHIPS =====

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    public function lot()
    {
        return $this->belongsTo(Lot::class);
    }

    public function serial()
    {
        return $this->belongsTo(Serial::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ===== SCOPES =====

    public function scopeIn($query)
    {
        return $query->where('direction', self::DIR_IN);
    }

    public function scopeOut($query)
    {
        return $query->where('direction', self::DIR_OUT);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('transaction_type', $type);
    }

    public function scopeBetweenDates($query, $from, $to)
    {
        return $query->whereBetween('transaction_date', [$from, $to]);
    }
}
