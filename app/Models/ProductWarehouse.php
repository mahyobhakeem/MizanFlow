<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductWarehouse extends Model
{
    use HasFactory;

    protected $table = 'product_warehouse';

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'quantity',
        'reserved_quantity',
        'weighted_average_cost',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'reserved_quantity' => 'decimal:3',
            'weighted_average_cost' => 'decimal:4',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function availableQuantity(): float
    {
        return max(0.0, (float) $this->quantity - (float) $this->reserved_quantity);
    }
}
