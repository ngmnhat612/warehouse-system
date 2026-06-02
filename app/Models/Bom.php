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

    /**
     * Kiểm tra đệ quy xem việc thêm $produceProductIds vào Produce của BOM này
     * có tạo ra vòng lặp với các Consume không.
     *
     * Logic vòng lặp trong BOM:
     * - Vòng lặp trực tiếp: sản phẩm vừa là Consume vừa là Produce trong cùng 1 BOM.
     * - Vòng lặp gián tiếp: A Produce → B; B là Consume trong BOM khác mà BOM đó Produce → A.
     *
     * @param  array $produceProductIds  Danh sách product_id sẽ là đầu ra (Produce)
     * @param  array $consumeProductIds  Danh sách product_id sẽ là đầu vào (Consume)
     * @param  int|null $excludeBomId    BOM đang edit (bỏ qua chính nó khi duyệt graph)
     * @return array ['has_cycle' => bool, 'path' => string]
     */
    public static function detectCircularReference(
        array $produceProductIds,
        array $consumeProductIds,
        ?int $excludeBomId = null
    ): array {
        // Bước 1: Kiểm tra vòng lặp trực tiếp trong cùng 1 BOM
        $overlap = array_intersect($produceProductIds, $consumeProductIds);
        if (!empty($overlap)) {
            $productCodes = Product::whereIn('id', $overlap)->pluck('code', 'id');
            $names = implode(', ', $productCodes->toArray());
            return [
                'has_cycle' => true,
                'path'      => "Hàng hóa [{$names}] vừa là Consume vừa là Produce trong cùng BOM này.",
            ];
        }

        // Bước 2: Kiểm tra vòng lặp gián tiếp qua đệ quy
        // Xây dựng đồ thị: produce_product_id → [consume_product_ids] từ tất cả BOM đang active
        // Câu hỏi: Từ một Consume product của BOM này, có thể "đi ngược" tới một Produce product không?

        $cycleResult = self::findCycleDFS(
            startProducts:   $consumeProductIds,
            targetProducts:  $produceProductIds,
            excludeBomId:    $excludeBomId,
            visited:         [],
            path:            []
        );

        return $cycleResult;
    }

    /**
     * DFS tìm đường đi từ $startProducts tới $targetProducts trong đồ thị BOM.
     *
     * Đồ thị BOM: product X "dẫn tới" product Y nếu tồn tại BOM nào đó có
     *             X là Produce và Y là Consume.
     * (Chiều đi: Produce → Consume trong 1 BOM, tức là "X được tạo ra rồi được dùng tiếp")
     *
     * Nếu một Consume của BOM mới, sau khi đi qua các BOM trung gian, lại tạo ra
     * một trong các Produce của BOM mới → vòng lặp.
     */
    private static function findCycleDFS(
        array $startProducts,
        array $targetProducts,
        ?int  $excludeBomId,
        array $visited,
        array $path
    ): array {
        foreach ($startProducts as $productId) {
            if (in_array($productId, $visited)) {
                continue; // Tránh lặp vô hạn khi graph đã có cycle trước
            }

            $visited[] = $productId;
            $currentPath = array_merge($path, [$productId]);

            // Nếu product hiện tại là một trong các Produce đích → vòng lặp!
            if (in_array($productId, $targetProducts)) {
                $productCodes = Product::whereIn('id', $currentPath)->pluck('code', 'id');
                $pathStr = implode(' → ', $currentPath);
                foreach ($currentPath as $pid) {
                    $pathStr = str_replace((string)$pid, $productCodes[$pid] ?? $pid, $pathStr);
                }
                return [
                    'has_cycle' => true,
                    'path'      => "Phát hiện vòng lặp gián tiếp: {$pathStr}",
                ];
            }

            // Tìm tất cả BOM có product này là Produce → lấy các Consume của nó để đi tiếp
            $nextConsumes = BomDetail::query()
                ->join('bom_details as consume', 'consume.bom_id', '=', 'bom_details.bom_id')
                ->where('bom_details.line_type', BomDetail::TYPE_PRODUCE)
                ->where('bom_details.product_id', $productId)
                ->where('consume.line_type', BomDetail::TYPE_CONSUME)
                ->when($excludeBomId, fn($q) => $q->where('bom_details.bom_id', '!=', $excludeBomId))
                ->pluck('consume.product_id')
                ->unique()
                ->toArray();

            if (!empty($nextConsumes)) {
                $result = self::findCycleDFS(
                    startProducts:  $nextConsumes,
                    targetProducts: $targetProducts,
                    excludeBomId:   $excludeBomId,
                    visited:        $visited,
                    path:           $currentPath
                );

                if ($result['has_cycle']) {
                    return $result;
                }
            }
        }

        return ['has_cycle' => false, 'path' => ''];
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