<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockIssueDetail extends Model
{
    protected $table = 'stock_issue_details';
    public $timestamps = false;

    protected $fillable = [
        'stock_issue_id', 'product_id', 'lot_id', 'serial_id',
        'location_id', 'uom_id', 'quantity', 'note',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
    ];

    // ===== RELATIONSHIPS =====

    public function issue()
    {
        return $this->belongsTo(StockIssue::class, 'stock_issue_id');
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