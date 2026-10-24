<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sku',
        'barcode',
        'name',
        'description',
        'category_id',
        'unit_id',
        'min_reorder_level',
        'default_sale_price',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'min_reorder_level' => 'decimal:3',
            'default_sale_price' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function warehouses(): BelongsToMany
    {
        return $this->belongsToMany(Warehouse::class, 'product_warehouse')
            ->withPivot(['quantity', 'reserved_quantity', 'weighted_average_cost'])
            ->withTimestamps();
    }

    public function warehouseStocks(): HasMany
    {
        return $this->hasMany(ProductWarehouse::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function totalQuantity(): float
    {
        return (float) $this->warehouseStocks()->sum('quantity');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('sku', 'like', "%{$term}%")
                ->orWhere('barcode', 'like', "%{$term}%");
        });
    }

    /**
     * Integrity guard: When transactions exist (stock movements or sale items), prevent deletion.
     */
    public function canBeDeleted(): bool
    {
        return $this->movements()->count() === 0 && $this->saleItems()->count() === 0;
    }
}
