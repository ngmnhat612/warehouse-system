<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Product extends Model
{
    protected $table = 'products';

    protected $fillable = [
        'code', 'name', 'category_id', 'uom_id', 'uom_purchase_id',
        'weight', 'volume', 'barcode', 'min_stock', 'max_stock',
        'alert_before_expiry', 'tracking_type', 'stock_rotation',
        'image_path', 'description', 'status',
    ];

    protected $casts = [
        'weight'        => 'decimal:3',
        'volume'        => 'decimal:3',
        'min_stock'     => 'decimal:3',
        'max_stock'     => 'decimal:3',
        'tracking_type' => 'integer',
        'stock_rotation'=> 'integer',
    ];

    // ===== CONSTANTS =====

    const TRACKING_NONE          = 1;
    const TRACKING_LOT           = 2;
    const TRACKING_SERIAL        = 3;
    const TRACKING_LOT_AND_SERIAL = 4;

    const ROTATION_FIFO   = 1;
    const ROTATION_FEFO   = 2;
    const ROTATION_MANUAL = 3;

    public static function trackingTypes(): array
    {
        return [
            self::TRACKING_NONE          => 'Không theo dõi',
            self::TRACKING_LOT           => 'Theo lô (Lot)',
            self::TRACKING_SERIAL        => 'Theo số Serial',
            self::TRACKING_LOT_AND_SERIAL => 'Theo lô + Serial',
        ];
    }

    public static function rotationTypes(): array
    {
        return [
            self::ROTATION_FIFO   => 'FIFO — Nhập trước xuất trước',
            self::ROTATION_FEFO   => 'FEFO — Hết hạn trước xuất trước',
            self::ROTATION_MANUAL => 'Thủ công',
        ];
    }

    // ===== RELATIONSHIPS =====

    public function lots()
    {
        return $this->hasMany(Lot::class, 'product_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function uom()
    {
        return $this->belongsTo(Uom::class, 'uom_id');
    }

    public function uomPurchase()
    {
        return $this->belongsTo(Uom::class, 'uom_purchase_id');
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class, 'product_id');
    }

    // ===== SCOPES =====

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    // ===== HELPERS =====

    public function getTrackingLabelAttribute(): string
    {
        return self::trackingTypes()[$this->tracking_type] ?? '—';
    }

    public function getRotationLabelAttribute(): string
    {
        return self::rotationTypes()[$this->stock_rotation] ?? '—';
    }

    public function getTotalStockAttribute(): float
    {
        return $this->stocks()->sum('quantity') ?? 0;
    }

    // ===== ACTIVITY LOG =====

    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['code', 'name', 'category_id', 'uom_id', 'status'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => match($eventName) {
                'created' => "Thêm hàng hóa \"{$this->name}\"",
                'updated' => "Cập nhật hàng hóa \"{$this->name}\"",
                'deleted' => "Xóa hàng hóa \"{$this->name}\"",
                default   => $eventName,
            });
    }
}