<?php

namespace App\Services\Inventory;

use App\Exceptions\InsufficientStockException;
use App\Models\ProductWarehouse;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class StockService
{
    /**
     * Get or create the product stock record for a specific warehouse, optionally applying a pessimistic lock.
     */
    public function getOrCreateStock(int $productId, int $warehouseId, bool $lock = false): ProductWarehouse
    {
        $query = ProductWarehouse::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId);

        if ($lock) {
            $query->lockForUpdate();
        }

        $stock = $query->first();

        if (! $stock) {
            $stock = ProductWarehouse::create([
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'quantity' => 0.000,
                'reserved_quantity' => 0.000,
                'weighted_average_cost' => 0.0000,
            ]);

            if ($lock) {
                $stock = ProductWarehouse::where('id', $stock->id)->lockForUpdate()->first();
            }
        }

        return $stock;
    }

    /**
     * Record a stock movement in the immutable ledger and update the warehouse stock balance.
     *
     * @param  float  $quantity  Positive for incoming, negative for outgoing
     *
     * @throws InsufficientStockException
     */
    public function recordMovement(
        int $warehouseId,
        int $productId,
        float $quantity,
        float $unitCost,
        string $movementType,
        Model $reference,
        int $userId
    ): StockMovement {
        if ($quantity == 0.0) {
            throw new InvalidArgumentException('كمية الحركة المخزنية لا يمكن أن تكون صفراً.');
        }

        if ($unitCost < 0.0) {
            throw new InvalidArgumentException('تكلفة وحدة الحركة المخزنية لا يمكن أن تكون سالبة.');
        }

        // Get stock record with pessimistic lock
        $stock = $this->getOrCreateStock($productId, $warehouseId, lock: true);

        $beforeQuantity = (float) $stock->quantity;
        $afterQuantity = round($beforeQuantity + $quantity, 3);

        // Disallow negative stock
        if ($afterQuantity < 0.0) {
            throw new InsufficientStockException(
                "الرصيد المتاح من المنتج [معرف: {$productId}] في المستودع الحالي غير كافٍ. المطلوب: ".abs($quantity)."، المتوفر: {$beforeQuantity}."
            );
        }

        // Update warehouse stock quantity
        $stock->quantity = $afterQuantity;
        $stock->save();

        // Create immutable ledger movement
        return StockMovement::create([
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'movement_type' => $movementType,
            'reference_type' => get_class($reference),
            'reference_id' => $reference->getKey(),
            'quantity' => $quantity,
            'before_quantity' => $beforeQuantity,
            'after_quantity' => $afterQuantity,
            'unit_cost' => $unitCost,
            'total_cost' => round(abs($quantity) * $unitCost, 4),
            'user_id' => $userId,
            'created_at' => now(),
        ]);
    }
}
