<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransformationDetail extends Model
{
    protected $table = 'stock_transformation_details';
    public $timestamps = false;

    protected $fillable = [
        'stock_transformation_id', 'product_id', 'lot_id', 'serial_id',
        'location_id', 'uom_id', 'direction', 'quantity', 'expiry_date',
    ];

    protected $casts = [
        'quantity'    => 'decimal:3',
        'expiry_date' => 'date',
    ];

    // direction constants
    const DIR_CONSUME = 1;
    const DIR_PRODUCE = 2;

    // ===== RELATIONSHIPS =====

    public function transformation()
    {
        return $this->belongsTo(StockTransformation::class, 'stock_transformation_id');
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
