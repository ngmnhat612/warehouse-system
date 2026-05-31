<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class PutawayRule extends Model
{
    protected $table = 'putaway_rules';

    protected $fillable = [
        'product_id',
        'category_id',
        'location_dest_id',
        'priority',
        'note',
        'status',
    ];

    // ===== RELATIONSHIPS =====

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function locationDest()
    {
        return $this->belongsTo(Location::class, 'location_dest_id');
    }

    // ===== SCOPES =====

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    // ===== HELPERS =====

    /**
     * Mô tả đối tượng áp dụng rule (hàng hóa hoặc nhóm)
     */
    public function getAppliesOnLabelAttribute(): string
    {
        if ($this->product_id && $this->product) {
            return "[{$this->product->code}] {$this->product->name}";
        }
        if ($this->category_id && $this->category) {
            return "Nhóm: {$this->category->name}";
        }
        return '—';
    }

    /**
     * Tìm vị trí đích cho 1 hàng hóa dựa trên tất cả putaway rules đang active.
     * Ưu tiên: product rule trước, category rule sau; priority thấp hơn = ưu tiên hơn.
     *
     * @param  int       $productId
     * @param  int|null  $categoryId
     * @return Location|null
     */
    public static function resolveDestination(int $productId, ?int $categoryId): ?Location
    {
        // 1. Tìm rule theo product cụ thể
        $rule = self::active()
            ->where('product_id', $productId)
            ->orderBy('priority')
            ->with('locationDest')
            ->first();

        if ($rule) {
            return $rule->locationDest;
        }

        // 2. Tìm rule theo category
        if ($categoryId) {
            $rule = self::active()
                ->where('category_id', $categoryId)
                ->orderBy('priority')
                ->with('locationDest')
                ->first();

            if ($rule) {
                return $rule->locationDest;
            }
        }

        return null;
    }

    // ===== ACTIVITY LOG =====

    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['product_id', 'category_id', 'location_dest_id', 'priority', 'status'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $e) => match ($e) {
                'created' => 'Thêm putaway rule',
                'updated' => 'Cập nhật putaway rule',
                'deleted' => 'Xóa putaway rule',
                default   => $e,
            });
    }
}