<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Bom extends Model
{
    protected $table = 'boms';

    protected $fillable = ['code', 'name', 'type', 'note', 'status'];

    // ===== CONSTANTS =====

    const TYPE_DISASSEMBLE = 1; // Tách
    const TYPE_ASSEMBLE    = 2; // Ghép

    public static function types(): array
    {
        return [
            self::TYPE_DISASSEMBLE => 'Tách hàng (Disassemble)',
            self::TYPE_ASSEMBLE    => 'Ghép hàng (Assemble)',
        ];
    }

    // ===== RELATIONSHIPS =====

    public function details()
    {
        return $this->hasMany(BomDetail::class, 'bom_id');
    }

    public function consumeLines()
    {
        return $this->hasMany(BomDetail::class, 'bom_id')
                    ->where('line_type', BomDetail::TYPE_CONSUME);
    }

    public function produceLines()
    {
        return $this->hasMany(BomDetail::class, 'bom_id')
                    ->where('line_type', BomDetail::TYPE_PRODUCE);
    }

    // ===== SCOPES =====

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    // ===== HELPERS =====

    public function getTypeLabelAttribute(): string
    {
        return self::types()[$this->type] ?? '—';
    }

    public function hasCircularReference(int $targetProductId): bool
    {
        // Kiểm tra sản phẩm Produce có xuất hiện lại ở Consume không
        $produceIds = $this->produceLines()->pluck('product_id')->toArray();
        $consumeIds = $this->consumeLines()->pluck('product_id')->toArray();
        return in_array($targetProductId, $consumeIds) && in_array($targetProductId, $produceIds);
    }

    // ===== ACTIVITY LOG =====

    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['code', 'name', 'type', 'status'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $e) => match($e) {
                'created' => "Thêm BOM \"{$this->name}\"",
                'updated' => "Cập nhật BOM \"{$this->name}\"",
                'deleted' => "Xóa BOM \"{$this->name}\"",
                default   => $e,
            });
    }
}