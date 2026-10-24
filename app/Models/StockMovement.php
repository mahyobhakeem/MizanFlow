<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

class StockMovement extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'movement_type',
        'reference_type',
        'reference_id',
        'quantity',
        'before_quantity',
        'after_quantity',
        'unit_cost',
        'total_cost',
        'user_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'before_quantity' => 'decimal:3',
            'after_quantity' => 'decimal:3',
            'unit_cost' => 'decimal:4',
            'total_cost' => 'decimal:4',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Enforce strict immutability on stock movements ledger.
     */
    protected static function booted(): void
    {
        static::creating(function (StockMovement $movement) {
            if (empty($movement->created_at)) {
                $movement->created_at = now();
            }
        });

        static::updating(function () {
            throw new LogicException('قيود سجل حركات المخزون غير قابلة للتعديل بعد إنشائها.');
        });

        static::deleting(function () {
            throw new LogicException('يحظر حذف أي قيد من دفتر الأستاذ لحركات المخزون للحفاظ على سلامة التدقيق.');
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeWarehouse(Builder $query, ?int $warehouseId): Builder
    {
        return $warehouseId ? $query->where('warehouse_id', $warehouseId) : $query;
    }

    public function scopeProduct(Builder $query, ?int $productId): Builder
    {
        return $productId ? $query->where('product_id', $productId) : $query;
    }

    public function scopeMovementType(Builder $query, ?string $type): Builder
    {
        return $type ? $query->where('movement_type', $type) : $query;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->whereHas('product', function ($pq) use ($term) {
                $pq->where('name', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%")
                    ->orWhere('barcode', 'like', "%{$term}%");
            })->orWhereHas('warehouse', function ($wq) use ($term) {
                $wq->where('name', 'like', "%{$term}%")
                    ->orWhere('code', 'like', "%{$term}%");
            });
        });
    }
}
