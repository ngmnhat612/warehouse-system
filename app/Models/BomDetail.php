<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BomDetail extends Model
{
    protected $table = 'bom_details';
    public $timestamps = false;

    protected $fillable = ['bom_id', 'product_id', 'line_type', 'qty', 'uom_id', 'note'];

    // ===== CONSTANTS =====

    const TYPE_CONSUME = 1; // Nguyên liệu đầu vào
    const TYPE_PRODUCE = 2; // Sản phẩm đầu ra

    // ===== RELATIONSHIPS =====

    public function bom()
    {
        return $this->belongsTo(Bom::class, 'bom_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function uom()
    {
        return $this->belongsTo(Uom::class, 'uom_id');
    }
}