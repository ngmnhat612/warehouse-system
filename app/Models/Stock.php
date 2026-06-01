<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $table = 'stock';
    public $timestamps = false;

    protected $fillable = [
        'product_id', 'location_id', 'lot_id', 'serial_id',
        'quantity', 'reserved_qty',
        'supplier_id', 'manufacture_date', 'received_date', 'expiry_date',
        'status', 'updated_at',
    ];

    protected $casts = [
        'quantity'         => 'decimal:3',
        'reserved_qty'     => 'decimal:3',
        'manufacture_date' => 'date',
        'received_date'    => 'date',
        'expiry_date'      => 'date',
        'updated_at'       => 'datetime',
    ];

    // ===== CONSTANTS =====

    const STATUS_NORMAL     = 1;
    const STATUS_QUARANTINE = 2;
    const STATUS_EXPIRED    = 3;

    // ===== RELATIONSHIPS =====

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function lot()
    {
        return $this->belongsTo(Lot::class, 'lot_id');
    }

    public function serial()
    {
        return $this->belongsTo(Serial::class, 'serial_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function ledgers()
    {
        return $this->hasMany(StockLedger::class, 'stock_id');
    }

    // ===== SCOPES =====

    public function scopeAvailable($query)
    {
        return $query->where('available_qty', '>', 0);
    }

    public function scopeAtLocation($query, int $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * FEFO: hết hạn sớm nhất lên đầu. Fallback FIFO theo received_date.
     */
    public function scopeFefo($query)
    {
        return $query->orderByRaw('
            CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END,
            expiry_date ASC,
            received_date ASC
        ');
    }

    public function scopeFifo($query)
    {
        return $query->orderBy('received_date')->orderBy('id');
    }

    // ===== HELPERS =====

    /**
     * available_qty là PERSISTED computed column trên SQL Server.
     * Accessor này dùng làm fallback cho môi trường dev khác (SQLite tests...).
     */
    public function getAvailableQtyAttribute(): float
    {
        // SQL Server trả về sẵn trong attribute
        if (array_key_exists('available_qty', $this->attributes)) {
            return (float) $this->attributes['available_qty'];
        }
        return (float) $this->quantity - (float) $this->reserved_qty;
    }

    public function isSerial(): bool
    {
        return $this->serial_id !== null;
    }

    public function isLot(): bool
    {
        return $this->lot_id !== null && $this->serial_id === null;
    }

    public function isPlain(): bool
    {
        return $this->lot_id === null && $this->serial_id === null;
    }
}