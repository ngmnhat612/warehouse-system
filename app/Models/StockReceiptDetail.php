<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockReceiptDetail extends Model
{
    protected $table = 'stock_receipt_details';
    public $timestamps = false;

    protected $fillable = [
        'stock_receipt_id', 'product_id', 'lot_id', 'serial_id', 'location_id', 'uom_id',
        'expected_qty', 'actual_qty', 'rejected_qty', 'reject_reason',
        'qc_status', 'supplier_id', 'manufacture_date', 'expiry_date', 'note',
    ];

    protected $casts = [
        'expected_qty'     => 'decimal:3',
        'actual_qty'       => 'decimal:3',
        'rejected_qty'     => 'decimal:3',
        'manufacture_date' => 'date',
        'expiry_date'      => 'date',
    ];

    // qc_status constants
    const QC_NOT_REQUIRED = 0;
    const QC_PASS         = 1;
    const QC_FAIL         = 2;
    const QC_PENDING      = 3;

    // ===== RELATIONSHIPS =====

    public function receipt()
    {
        return $this->belongsTo(StockReceipt::class, 'stock_receipt_id');
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

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
