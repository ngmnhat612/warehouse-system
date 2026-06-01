<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Location extends Model
{
    protected $table = 'locations';
    public $timestamps = false;

    protected $fillable = [
        'parent_id', 'code', 'name', 'type',
        'barcode', 'capacity_limit', 'status',
    ];

    // ===== CONSTANTS =====

    const TYPE_INTERNAL   = 1;
    const TYPE_SUPPLIER   = 2;
    const TYPE_CUSTOMER   = 3;
    const TYPE_SCRAP      = 4;
    const TYPE_QUARANTINE = 5;

    public static function types(): array
    {
        return [
            self::TYPE_INTERNAL   => 'Internal — Vị trí thực trong kho',
            self::TYPE_SUPPLIER   => 'Virtual/Supplier — Nguồn nhập hàng',
            self::TYPE_CUSTOMER   => 'Virtual/Customer — Điểm xuất hàng',
            self::TYPE_SCRAP      => 'Virtual/Scrap — Khu vực hủy hàng',
            self::TYPE_QUARANTINE => 'Virtual/Quarantine — Khu cách ly QC',
        ];
    }

    public static function typeLabels(): array
    {
        return [
            self::TYPE_INTERNAL   => 'Internal',
            self::TYPE_SUPPLIER   => 'Supplier',
            self::TYPE_CUSTOMER   => 'Customer',
            self::TYPE_SCRAP      => 'Scrap',
            self::TYPE_QUARANTINE => 'Quarantine',
        ];
    }

    public static function typeColors(): array
    {
        return [
            self::TYPE_INTERNAL   => 'primary',
            self::TYPE_SUPPLIER   => 'info',
            self::TYPE_CUSTOMER   => 'success',
            self::TYPE_SCRAP      => 'danger',
            self::TYPE_QUARANTINE => 'warning',
        ];
    }

    // ===== RELATIONSHIPS =====

    public function parent()
    {
        return $this->belongsTo(Location::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Location::class, 'parent_id');
    }

    public function allChildren()
    {
        return $this->children()->with('allChildren');
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class, 'location_id');
    }

    // ===== SCOPES =====

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeInternal($query)
    {
        return $query->where('type', self::TYPE_INTERNAL);
    }

    public function scopeVirtual($query)
    {
        return $query->where('type', '>', self::TYPE_INTERNAL);
    }

    // ===== HELPERS =====

    public function getFullPathAttribute(): string
    {
        return $this->parent
            ? $this->parent->full_path . ' › ' . $this->name
            : $this->name;
    }

    public function getTypeLabelAttribute(): string
    {
        return self::typeLabels()[$this->type] ?? '—';
    }

    public function getTypeColorAttribute(): string
    {
        return self::typeColors()[$this->type] ?? 'secondary';
    }

    public function isVirtual(): bool
    {
        return $this->type > self::TYPE_INTERNAL;
    }

    public function isInternal(): bool
    {
        return $this->type === self::TYPE_INTERNAL;
    }

    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    public function hasStock(): bool
    {
        return $this->stocks()->where('quantity', '>', 0)->exists();
    }

    public function getDescendantIds(): array
    {
        $ids = [];
        foreach ($this->children as $child) {
            $ids[] = $child->id;
            $ids   = array_merge($ids, $child->getDescendantIds());
        }
        return $ids;
    }

    // ===== ACTIVITY LOG =====

    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['code', 'name', 'type', 'parent_id', 'status'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => match($eventName) {
                'created' => "Thêm vị trí kho \"{$this->name}\"",
                'updated' => "Cập nhật vị trí kho \"{$this->name}\"",
                'deleted' => "Xóa vị trí kho \"{$this->name}\"",
                default   => $eventName,
            });
    }
}