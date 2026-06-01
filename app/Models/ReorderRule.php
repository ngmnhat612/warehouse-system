<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ReorderRule extends Model
{
    protected $table = 'reorder_rules';

    protected $fillable = [
        'product_id',
        'location_id',
        'min_qty',
        'max_qty',
        'alert_email',
        'note',
        'status',
    ];

    protected $casts = [
        'min_qty' => 'decimal:3',
        'max_qty' => 'decimal:3',
    ];

    // ===== RELATIONSHIPS =====

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    // ===== SCOPES =====

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope: chỉ lấy rules đang "dưới ngưỡng" (join với bảng stock)
     * Dùng cho Dashboard cảnh báo và báo cáo.
     */
    public function scopeBelowMin($query)
    {
        return $query
            ->active()
            ->with(['product', 'location'])
            ->selectRaw('reorder_rules.*, COALESCE(s.total_qty, 0) AS current_stock')
            ->leftJoinSub(
                \DB::table('stock')
                    ->selectRaw('product_id, location_id, SUM(available_qty) AS total_qty')
                    ->groupBy('product_id', 'location_id'),
                's',
                function ($join) {
                    $join->on('s.product_id', '=', 'reorder_rules.product_id')
                         ->on('s.location_id', '=', 'reorder_rules.location_id');
                }
            )
            ->havingRaw('COALESCE(s.total_qty, 0) < reorder_rules.min_qty');
    }

    // ===== HELPERS =====

    /**
     * Số lượng cần đặt thêm để đạt max_qty từ tồn hiện tại.
     */
    public function getQtyToOrderAttribute(): float
    {
        // Lấy tồn khả dụng tại vị trí
        $currentQty = \DB::table('stock')
            ->where('product_id', $this->product_id)
            ->where('location_id', $this->location_id)
            ->sum('available_qty');

        $needed = $this->max_qty - $currentQty;
        return $needed > 0 ? (float) $needed : 0;
    }

    // ===== ACTIVITY LOG =====

    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['product_id', 'location_id', 'min_qty', 'max_qty', 'alert_email', 'status'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $e) => match ($e) {
                'created' => 'Thêm reorder rule',
                'updated' => 'Cập nhật reorder rule',
                'deleted' => 'Xóa reorder rule',
                default   => $e,
            });
    }
}