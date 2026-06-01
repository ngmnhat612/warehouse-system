<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryFreezeDetail extends Model
{
    protected $table = 'inventory_freeze_details';
    public $timestamps = false;

    protected $fillable = [
        'freeze_id', 'freeze_scope', 'location_id', 'product_id',
    ];

    // freeze_scope constants
    const SCOPE_ALL      = 1;
    const SCOPE_LOCATION = 2;
    const SCOPE_PRODUCT  = 3;

    // ===== RELATIONSHIPS =====

    public function freeze()
    {
        return $this->belongsTo(InventoryFreeze::class, 'freeze_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
