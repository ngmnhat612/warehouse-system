<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockAdjustmentDetail extends Model
{
    protected $table = 'stock_adjustment_details';
    public $timestamps = false;

    protected $fillable = [
        'stock_adjustment_id', 'inventory_check_line_id',
        'product_id', 'lot_id', 'serial_id', 'uom_id', 'location_id',
        'system_qty', 'actual_qty',
    ];

    protected $casts = [
        'system_qty' => 'decimal:3',
        'actual_qty' => 'decimal:3',
    ];

    // diff_qty là computed column (SQL Server PERSISTED) — chỉ đọc
    public function getDiffQtyAttribute(): float
    {
        if (isset($this->attributes['diff_qty'])) {
            return (float) $this->attributes['diff_qty'];
        }
        return (float) $this->actual_qty - (float) $this->system_qty;
    }

    // ===== RELATIONSHIPS =====

    public function adjustment()
    {
        return $this->belongsTo(StockAdjustment::class, 'stock_adjustment_id');
    }

    public function inventoryCheckLine()
    {
        return $this->belongsTo(InventoryCheckLine::class, 'inventory_check_line_id');
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
}
