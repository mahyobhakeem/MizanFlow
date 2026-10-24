<?php

namespace App\Actions\Sales;

use App\Models\Customer;
use App\Models\Sale;
use App\Services\Audit\AuditService;
use App\Services\Inventory\StockService;
use DomainException;
use Illuminate\Support\Facades\DB;

class VoidSaleAction
{
    public function __construct(
        protected StockService $stockService
    ) {}

    /**
     * Reversals and voids an approved sale invoice:
     * - Restores stock quantities to the warehouse
     * - Creates immutable reverse ledger entries ('sale_return')
     * - Adjusts customer receivables balance if sale had unpaid credit
     * - Marks the sale as 'voided' with reason and audit timestamp
     *
     * @throws DomainException
     */
    public function execute(Sale $sale, int $voidedByUserId, ?string $reason = null): Sale
    {
        return DB::transaction(function () use ($sale, $voidedByUserId, $reason) {
            $lockedSale = Sale::where('id', $sale->id)
                ->with(['items.product', 'customer'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedSale->status !== 'completed') {
                throw new DomainException('لا يمكن إلغاء فاتورة مبيعات غير معتمدة أو ملغاة مسبقاً.');
            }

            // 1. Revert inventory movements for each item
            foreach ($lockedSale->items as $item) {
                $qtyToReturn = (float) $item->quantity;
                $costSnapshot = (float) $item->unit_cost_snapshot;

                // Positive quantity adds stock back to warehouse
                $this->stockService->recordMovement(
                    $lockedSale->warehouse_id,
                    $item->product_id,
                    +$qtyToReturn,
                    $costSnapshot,
                    'sale_return',
                    $lockedSale,
                    $voidedByUserId
                );
            }

            // 2. Adjust customer balance if credit was issued
            $unpaid = round((float) $lockedSale->total_amount - (float) $lockedSale->paid_amount, 2);
            if ($unpaid > 0.0 && $lockedSale->customer_id) {
                $customer = Customer::where('id', $lockedSale->customer_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $customer->balance = max(0.0, round((float) $customer->balance - $unpaid, 2));
                $customer->save();
            }

            // 3. Mark sale as voided
            $lockedSale->status = 'voided';
            $lockedSale->voided_at = now();
            $lockedSale->voided_by_user_id = $voidedByUserId;
            $lockedSale->void_reason = $reason ?? 'إلغاء وإرجاع الفاتورة بالكامل من قبل الإدارة';
            $lockedSale->save();

            // 4. Audit Log Entry
            app(AuditService::class)->record(
                'sale.voided',
                $lockedSale,
                "إلغاء عكسي واسترجاع مخزون فاتورة المبيعات [{$lockedSale->invoice_number}]. سبب الإلغاء: {$lockedSale->void_reason}",
                ['status' => 'completed'],
                [
                    'status' => 'voided',
                    'void_reason' => $lockedSale->void_reason,
                    'voided_by_user_id' => $voidedByUserId,
                ],
                $voidedByUserId
            );

            return $lockedSale;
        });
    }
}
