<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransformation extends Model
{
    protected $table = 'stock_transformations';

    protected $fillable = [
        'code', 'type', 'bom_id', 'created_by', 'confirmed_by',
        'status', 'transformation_date', 'note',
    ];

    protected $casts = [
        'transformation_date' => 'date',
    ];

    // type constants
    const TYPE_SPLIT  = 1; // Tách
    const TYPE_MERGE  = 2; // Ghép

    // status constants
    const STATUS_DRAFT     = 1;
    const STATUS_PENDING   = 2;
    const STATUS_APPROVED  = 3;
    const STATUS_COMPLETED = 4;
    const STATUS_CANCELLED = 5;

    // ===== RELATIONSHIPS =====

    public function details()
    {
        return $this->hasMany(StockTransformationDetail::class, 'stock_transformation_id');
    }

    public function consumeDetails()
    {
        return $this->hasMany(StockTransformationDetail::class, 'stock_transformation_id')
                    ->where('direction', StockTransformationDetail::DIR_CONSUME);
    }

    public function produceDetails()
    {
        return $this->hasMany(StockTransformationDetail::class, 'stock_transformation_id')
                    ->where('direction', StockTransformationDetail::DIR_PRODUCE);
    }

    public function bom()
    {
        return $this->belongsTo(Bom::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function confirmedBy()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
