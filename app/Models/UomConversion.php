<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class UomConversion extends Model
{
    use LogsActivity;
    
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['from_uom_id', 'to_uom_id', 'factor', 'status'])
            ->logOnlyDirty()
            ->useLogName('uom_conversion')
            ->setDescriptionForEvent(fn(string $event) => match($event) {
                'created' => "Thêm quy đổi đơn vị tính (ID: {$this->id})",
                'updated' => "Cập nhật quy đổi đơn vị tính (ID: {$this->id})",
                'deleted' => "Xóa quy đổi đơn vị tính (ID: {$this->id})",
                default   => $event,
            });
    }

    // ===== SCOPES =====

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}