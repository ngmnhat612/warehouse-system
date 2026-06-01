<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransferDetail extends Model
{
    protected $table = 'stock_transfer_details';
    public $timestamps = false;

    protected $fillable = [
        'stock_transfer_id', 'product_id', 'lot_id', 'serial_id',
        'from_location_id', 'to_location_id', 'uom_id', 'quantity', 'note',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
    ];

    // ===== RELATIONSHIPS =====

    public function transfer()
    {
        return $this->belongsTo(StockTransfer::class, 'stock_transfer_id');
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

    public function fromLocation()
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    public function toLocation()
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }

    public function uom()
    {
        return $this->belongsTo(Uom::class);
    }
}
