<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryCheckLine extends Model
{
    protected $table = 'inventory_check_lines';
    public $timestamps = false;

    protected $fillable = [
        'inventory_check_id', 'product_id', 'lot_id', 'serial_id',
        'location_id', 'uom_id', 'system_qty', 'actual_qty',
        'counted_by', 'counted_at',
    ];

    protected $casts = [
        'system_qty' => 'decimal:3',
        'actual_qty' => 'decimal:3',
        'counted_at' => 'datetime',
    ];

    // diff_qty là computed column (SQL Server PERSISTED) — chỉ đọc
    // Accessor dự phòng cho môi trường không phải SQL Server
    public function getDiffQtyAttribute(): ?float
    {
        if (isset($this->attributes['diff_qty'])) {
            return (float) $this->attributes['diff_qty'];
        }
        if ($this->actual_qty !== null) {
            return (float) $this->actual_qty - (float) $this->system_qty;
        }
        return null;
    }

    // ===== RELATIONSHIPS =====

    public function inventoryCheck()
    {
        return $this->belongsTo(InventoryCheck::class, 'inventory_check_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
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

    public function uom()
    {
        return $this->belongsTo(Uom::class);
    }

    public function countedBy()
    {
        return $this->belongsTo(User::class, 'counted_by');
    }

    // ===== SCOPES =====

    public function scopeWithDiff($query)
    {
        return $query->whereNotNull('actual_qty')
                     ->whereRaw('actual_qty <> system_qty');
    }

    public function scopeNotCounted($query)
    {
        return $query->whereNull('actual_qty');
    }
}
