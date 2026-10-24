<?php

namespace App\Actions\Sales;

use App\Exceptions\InsufficientStockException;
use App\Models\Customer;
use App\Models\Sale;
use App\Services\Audit\AuditService;
use App\Services\Inventory\StockService;
use DomainException;
use Illuminate\Support\Facades\DB;

class CompleteSaleAction
{
    public function __construct(
        protected StockService $stockService
    ) {}

    /**
     * Approve and finalize a sales invoice atomically:
     * - Validates and locks warehouse stock with pessimistic locking
     * - Freezes historical WAC cost snapshot on each item
     * - Decrements warehouse stock and creates immutable ledger movements ('sale')
     * - Validates customer credit limit and posts balance for credit sales
     * - Marks the sale as completed
     *
     * @throws DomainException
     * @throws InsufficientStockException
     */
    public function execute(Sale $sale, int $completedByUserId): Sale
    {
        return DB::transaction(function () use ($sale, $completedByUserId) {
            // Lock sale record to prevent concurrent approvals (idempotency guard)
            $lockedSale = Sale::where('id', $sale->id)
                ->with(['items.product', 'customer', 'warehouse'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedSale->status !== 'draft') {
                throw new DomainException('لا يمكن اعتماد فاتورة مبيعات تم اعتمادها أو إلغاؤها مسبقاً.');
            }

            if ($lockedSale->items->isEmpty()) {
                throw new DomainException('لا يمكن اعتماد فاتورة مبيعات خالية من الأصناف.');
            }

            // 1. Process each item: verify stock, capture cost snapshot, record movement
            foreach ($lockedSale->items as $item) {
                // Lock the product stock row in the warehouse
                $stock = $this->stockService->getOrCreateStock(
                    $item->product_id,
                    $lockedSale->warehouse_id,
                    lock: true
                );

                $availableQty = (float) $stock->quantity;
                $requestedQty = (float) $item->quantity;

                if ($requestedQty <= 0.0) {
                    throw new DomainException("الكمية المطلوبة للصنف [{$item->product->name}] غير صالحة ({$requestedQty}).");
                }

                if ($availableQty < $requestedQty) {
                    $prodName = $item->product?->name ?? "معرف: {$item->product_id}";
                    throw new InsufficientStockException(
                        "الرصيد المتاح من الصنف [{$prodName}] في المستودع غير كافٍ. المطلوب: {$requestedQty}، المتوفر حالياً: {$availableQty}."
                    );
                }

                // Capture WAC snapshot at exact moment of sale
                $wacSnapshot = (float) $stock->weighted_average_cost;
                $item->unit_cost_snapshot = $wacSnapshot;
                $item->save();

                // Deduct stock and write to immutable ledger
                $this->stockService->recordMovement(
                    $lockedSale->warehouse_id,
                    $item->product_id,
                    -$requestedQty,
                    $wacSnapshot,
                    'sale',
                    $lockedSale,
                    $completedByUserId
                );
            }

            // 2. Validate customer credit limit and update receivables if unpaid balance exists
            $unpaid = round((float) $lockedSale->total_amount - (float) $lockedSale->paid_amount, 2);
            if ($unpaid > 0.0) {
                if (! $lockedSale->customer_id) {
                    throw new DomainException('لا يمكن إتمام عملية بيع آجل أو جزئي لعميل نقدي غير مسجل.');
                }

                $customer = Customer::where('id', $lockedSale->customer_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $currentBalance = (float) $customer->balance;
                $creditLimit = (float) $customer->credit_limit;
                $newBalance = round($currentBalance + $unpaid, 2);

                if ($creditLimit > 0.0 && $newBalance > $creditLimit) {
                    throw new DomainException(
                        "تجاوز العميل سقف الائتمان المسموح به. السقف المعتمد: {$creditLimit}، الرصيد بعد العملية: {$newBalance}."
                    );
                }

                $customer->balance = $newBalance;
                $customer->save();
            }

            // 3. Mark sale as completed
            $lockedSale->status = 'completed';
            $lockedSale->completed_at = now();
            $lockedSale->save();

            // 4. Audit Log Entry
            app(AuditService::class)->record(
                'sale.completed',
                $lockedSale,
                "اعتماد وصرف فاتورة المبيعات [{$lockedSale->invoice_number}] بإجمالي ".number_format($lockedSale->total_amount, 2)." ر.س من المستودع [{$lockedSale->warehouse->name}]",
                ['status' => 'draft'],
                [
                    'status' => 'completed',
                    'total_amount' => $lockedSale->total_amount,
                    'paid_amount' => $lockedSale->paid_amount,
                    'items_count' => $lockedSale->items->count(),
                    'payment_type' => $lockedSale->payment_type,
                ],
                $completedByUserId
            );

            return $lockedSale;
        });
    }
}
