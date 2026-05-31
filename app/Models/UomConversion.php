<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UomConversion extends Model
{
    protected $table = 'uom_conversions';

    protected $fillable = [
        'from_uom_id',
        'to_uom_id',
        'factor',
        'status',
    ];

    public $timestamps = false;

    // ===== RELATIONSHIPS =====

    public function fromUom()
    {
        return $this->belongsTo(Uom::class, 'from_uom_id');
    }

    public function toUom()
    {
        return $this->belongsTo(Uom::class, 'to_uom_id');
    }

    // ===== SCOPES =====

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}