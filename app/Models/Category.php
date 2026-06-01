<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Category extends Model
{
    protected $table = 'categories';
    public $timestamps = false;

    protected $fillable = [
        'code',
        'name',
        'parent_id',
        'description',
        'status',
    ];

    // ===== RELATIONSHIPS =====

    /** Danh mục cha */
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /** Danh mục con */
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /** Đệ quy lấy tất cả con cháu */
    public function allChildren()
    {
        return $this->children()->with('allChildren');
    }

    /** Hàng hóa thuộc danh mục này */
    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    // ===== SCOPES =====

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    // ===== HELPERS =====

    /** Lấy tên đầy đủ kèm cha: "Linh kiện > Động cơ" */
    public function getFullNameAttribute(): string
    {
        return $this->parent
            ? $this->parent->name . ' › ' . $this->name
            : $this->name;
    }

    /** Kiểm tra có phải danh mục gốc không */
    public function isRoot(): bool
    {
        return is_null($this->parent_id);
    }

    /** Kiểm tra có con không */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /** Lấy danh sách ID con cháu (để tránh circular reference) */
    public function getDescendantIds(): array
    {
        $ids = [];
        foreach ($this->children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $child->getDescendantIds());
        }
        return $ids;
    }

    // ===== ACTIVITY LOG =====

    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['code', 'name', 'parent_id', 'status'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => match($eventName) {
                'created' => "Thêm danh mục \"{$this->name}\"",
                'updated' => "Cập nhật danh mục \"{$this->name}\"",
                'deleted' => "Xóa danh mục \"{$this->name}\"",
                default   => $eventName,
            });
    }
}