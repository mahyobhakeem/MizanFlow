<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'product_id',
        'quantity',
        'unit_price',
        'unit_cost_snapshot',
        'tax_percent',
        'discount_amount',
        'total_price',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_price' => 'decimal:4',
            'unit_cost_snapshot' => 'decimal:4',
            'tax_percent' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_price' => 'decimal:4',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Historical unit gross profit at time of sale.
     */
    public function unitProfit(): float
    {
        return round((float) $this->unit_price - (float) $this->unit_cost_snapshot, 4);
    }

    /**
     * Historical total line gross profit at time of sale.
     */
    public function totalProfit(): float
    {
        $netLine = (float) $this->total_price;
        $totalCost = round((float) $this->quantity * (float) $this->unit_cost_snapshot, 4);

        return round($netLine - $totalCost, 2);
    }

    /**
     * Gross profit margin percentage.
     */
    public function profitMargin(): float
    {
        $price = (float) $this->unit_price;
        if ($price <= 0.0) {
            return 0.0;
        }

        return round(($this->unitProfit() / $price) * 100, 2);
    }
}
