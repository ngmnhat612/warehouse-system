<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryFreeze extends Model
{
    protected $table = 'inventory_freezes';
    public $timestamps = false;

    protected $fillable = [
        'check_id', 'check_type', 'frozen_by', 'frozen_at', 'unfrozen_at', 'reason',
    ];

    protected $casts = [
        'frozen_at'   => 'datetime',
        'unfrozen_at' => 'datetime',
    ];

    // check_type mirrors InventoryCheck::TYPE_*
    const SCOPE_ALL      = 1;
    const SCOPE_LOCATION = 2;
    const SCOPE_PRODUCT  = 3;

    // ===== RELATIONSHIPS =====

    public function inventoryCheck()
    {
        return $this->belongsTo(InventoryCheck::class, 'check_id');
    }

    public function details()
    {
        return $this->hasMany(InventoryFreezeDetail::class, 'freeze_id');
    }

    public function frozenBy()
    {
        return $this->belongsTo(User::class, 'frozen_by');
    }

    // ===== HELPERS =====

    public function isActive(): bool
    {
        return $this->unfrozen_at === null;
    }

    public function unfreeze(): void
    {
        $this->update(['unfrozen_at' => now()]);
    }
}
