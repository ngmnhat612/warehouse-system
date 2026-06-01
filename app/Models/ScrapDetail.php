<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScrapDetail extends Model
{
    protected $table = 'scrap_details';
    public $timestamps = false;

    protected $fillable = [
        'scrap_id', 'product_id', 'lot_id', 'serial_id',
        'location_id', 'uom_id', 'quantity', 'reason',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
    ];

    // ===== RELATIONSHIPS =====

    public function scrap()
    {
        return $this->belongsTo(Scrap::class, 'scrap_id');
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
