<?php

namespace App\Actions\Inventory;

use App\Exceptions\InsufficientStockException;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Services\Audit\AuditService;
use App\Services\Inventory\StockService;
use App\Services\Inventory\WacCalculationService;
use DomainException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StockTransferAction
{
    public function __construct(
        protected StockService $stockService,
        protected WacCalculationService $wacService
    ) {}

    /**
     * Dispatch an existing draft transfer: deducts items from source warehouse and locks unit cost snapshot.
     */
    public function dispatch(StockTransfer $transfer, int $userId): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $userId) {
            $locked = StockTransfer::where('id', $transfer->id)
                ->with('items')
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== 'draft') {
                throw new DomainException('لا يمكن شحن أمر تحويل تم شحنه أو إتمامه مسبقاً.');
            }

            if ($locked->items->isEmpty()) {
                throw new DomainException('لا يمكن شحن أمر تحويل بدون بنود أصناف.');
            }

            if ($locked->from_warehouse_id === $locked->to_warehouse_id) {
                throw new InvalidArgumentException('المستودع المصدر لا يمكن أن يكون هو نفسه المستودع الوجهة.');
            }

            foreach ($locked->items as $item) {
                // Get source stock with lock
                $sourceStock = $this->stockService->getOrCreateStock(
                    $item->product_id,
                    $locked->from_warehouse_id,
                    lock: true
                );

                if ((float) $sourceStock->quantity < (float) $item->quantity_sent) {
                    throw new InsufficientStockException(
                        "الكمية المتوفرة في المستودع المصدر غير كافية لتحويل الصنف [معرف: {$item->product_id}]. المتوفر: {$sourceStock->quantity}، المطلوب: {$item->quantity_sent}."
                    );
                }

                // Freeze current source WAC as snapshot
                $unitCost = (float) $sourceStock->weighted_average_cost;
                $item->unit_cost_snapshot = $unitCost;
                $item->save();

                // Deduct from source warehouse
                $this->stockService->recordMovement(
                    $locked->from_warehouse_id,
                    $item->product_id,
                    -1 * (float) $item->quantity_sent,
                    $unitCost,
                    'transfer_out',
                    $locked,
                    $userId
                );
            }

            $locked->status = 'in_transit';
            $locked->dispatched_at = now();
            $locked->save();

            // Audit log
            app(AuditService::class)->record(
                'transfer.dispatched',
                $locked,
                "شحن أمر تحويل مخزني [{$locked->transfer_number}] من [{$locked->fromWarehouse->name}] إلى [{$locked->toWarehouse->name}]",
                ['status' => 'draft'],
                ['status' => 'in_transit', 'items_count' => $locked->items->count()],
                $userId
            );

            return $locked;
        });
    }

    /**
     * Receive an in-transit transfer at destination warehouse: adds items and recalculates destination WAC.
     */
    public function receive(StockTransfer $transfer, int $userId): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $userId) {
            $locked = StockTransfer::where('id', $transfer->id)
                ->with('items')
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== 'in_transit') {
                throw new DomainException('لا يمكن استلام أمر تحويل ليس في حالة شحن أو في الطريق.');
            }

            foreach ($locked->items as $item) {
                $qty = (float) $item->quantity_sent;
                $cost = (float) $item->unit_cost_snapshot;

                // Get destination stock with lock
                $destStock = $this->stockService->getOrCreateStock(
                    $item->product_id,
                    $locked->to_warehouse_id,
                    lock: true
                );

                $currentQty = (float) $destStock->quantity;
                $currentWac = (float) $destStock->weighted_average_cost;

                // Recalculate destination WAC
                $newWac = $this->wacService->calculateIncoming($currentQty, $currentWac, $qty, $cost);
                $destStock->weighted_average_cost = $newWac;
                $destStock->save();

                // Add to destination warehouse
                $this->stockService->recordMovement(
                    $locked->to_warehouse_id,
                    $item->product_id,
                    $qty,
                    $cost,
                    'transfer_in',
                    $locked,
                    $userId
                );

                $item->quantity_received = $qty;
                $item->save();
            }

            $locked->status = 'completed';
            $locked->received_at = now();
            $locked->received_by_user_id = $userId;
            $locked->save();

            // Audit log
            app(AuditService::class)->record(
                'transfer.received',
                $locked,
                "استلام وتأكيد وصول أمر التحويل المخزني [{$locked->transfer_number}] في المستودع [{$locked->toWarehouse->name}]",
                ['status' => 'in_transit'],
                ['status' => 'completed'],
                $userId
            );

            return $locked;
        });
    }

    /**
     * Complete a direct transfer between warehouses within a single atomic database transaction.
     * Deducts from source and immediately adds to destination while maintaining cost consistency.
     *
     * @param  array  $items  Array of ['product_id' => int, 'quantity' => float]
     */
    public function directTransfer(
        int $fromWarehouseId,
        int $toWarehouseId,
        array $items,
        int $userId,
        ?string $notes = null
    ): StockTransfer {
        if ($fromWarehouseId === $toWarehouseId) {
            throw new InvalidArgumentException('لا يمكن التحويل لنفس المستودع.');
        }

        if (empty($items)) {
            throw new InvalidArgumentException('يجب تحديد بند واحد على الأقل لإتمام التحويل.');
        }

        return DB::transaction(function () use ($fromWarehouseId, $toWarehouseId, $items, $userId, $notes) {
            $transfer = StockTransfer::create([
                'transfer_number' => StockTransfer::generateTransferNumber(),
                'from_warehouse_id' => $fromWarehouseId,
                'to_warehouse_id' => $toWarehouseId,
                'created_by_user_id' => $userId,
                'received_by_user_id' => $userId,
                'status' => 'completed',
                'dispatched_at' => now(),
                'received_at' => now(),
                'notes' => $notes,
            ]);

            foreach ($items as $itemData) {
                $productId = (int) $itemData['product_id'];
                $quantity = (float) $itemData['quantity'];

                if ($quantity <= 0.0) {
                    throw new InvalidArgumentException('كمية التحويل يجب أن تكون أكبر من الصفر.');
                }

                // 1. Lock and check source warehouse
                $sourceStock = $this->stockService->getOrCreateStock($productId, $fromWarehouseId, lock: true);
                if ((float) $sourceStock->quantity < $quantity) {
                    throw new InsufficientStockException(
                        "الرصيد المتاح في المستودع المصدر غير كافٍ للصنف [معرف: {$productId}]. المتوفر: {$sourceStock->quantity}، المطلوب: {$quantity}."
                    );
                }

                $unitCost = (float) $sourceStock->weighted_average_cost;

                // Create transfer item record
                StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'product_id' => $productId,
                    'quantity_sent' => $quantity,
                    'quantity_received' => $quantity,
                    'unit_cost_snapshot' => $unitCost,
                ]);

                // 2. Deduct from source warehouse
                $this->stockService->recordMovement(
                    $fromWarehouseId,
                    $productId,
                    -1 * $quantity,
                    $unitCost,
                    'transfer_out',
                    $transfer,
                    $userId
                );

                // 3. Lock and update destination warehouse
                $destStock = $this->stockService->getOrCreateStock($productId, $toWarehouseId, lock: true);
                $destCurrentQty = (float) $destStock->quantity;
                $destCurrentWac = (float) $destStock->weighted_average_cost;

                $newDestWac = $this->wacService->calculateIncoming($destCurrentQty, $destCurrentWac, $quantity, $unitCost);
                $destStock->weighted_average_cost = $newDestWac;
                $destStock->save();

                // 4. Add to destination warehouse
                $this->stockService->recordMovement(
                    $toWarehouseId,
                    $productId,
                    $quantity,
                    $unitCost,
                    'transfer_in',
                    $transfer,
                    $userId
                );
            }

            // Audit log
            app(AuditService::class)->record(
                'transfer.received',
                $transfer,

                "تنفيذ تحويل مخزني مباشر وفوري [{$transfer->transfer_number}] من [{$transfer->fromWarehouse->name}] إلى [{$transfer->toWarehouse->name}]",
                null,
                ['status' => 'completed', 'items_count' => count($items)],
                $userId
            );

            return $transfer;
        });
    }
}
