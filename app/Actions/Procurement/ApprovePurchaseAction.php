<?php

namespace App\Actions\Procurement;

use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\Audit\AuditService;
use App\Services\Inventory\StockService;
use App\Services\Inventory\WacCalculationService;
use DomainException;
use Illuminate\Support\Facades\DB;

class ApprovePurchaseAction
{
    public function __construct(
        protected StockService $stockService,
        protected WacCalculationService $wacService
    ) {}

    /**
     * Approve and post a purchase invoice to inventory and supplier ledger.
     *
     * @throws DomainException
     */
    public function execute(Purchase $purchase, int $approvedByUserId): Purchase
    {
        return DB::transaction(function () use ($purchase, $approvedByUserId) {
            // Lock purchase record pessimistically
            $lockedPurchase = Purchase::where('id', $purchase->id)
                ->with('items')
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedPurchase->status !== 'draft') {
                throw new DomainException('لا يمكن اعتماد فاتورة تم اعتمادها أو إلغاؤها مسبقاً.');
            }

            if ($lockedPurchase->items->isEmpty()) {
                throw new DomainException('لا يمكن اعتماد فاتورة مشتريات خالية من الأصناف والبنود.');
            }

            // Process each item: update WAC and record stock entry
            foreach ($lockedPurchase->items as $item) {
                $stock = $this->stockService->getOrCreateStock(
                    $item->product_id,
                    $lockedPurchase->warehouse_id,
                    lock: true
                );

                $currentQty = (float) $stock->quantity;
                $currentWac = (float) $stock->weighted_average_cost;
                $incomingQty = (float) $item->quantity;
                $incomingCost = (float) $item->unit_cost;

                // 1. Calculate and update new Moving WAC for the warehouse
                $newWac = $this->wacService->calculateIncoming(
                    $currentQty,
                    $currentWac,
                    $incomingQty,
                    $incomingCost
                );

                $stock->weighted_average_cost = $newWac;
                $stock->save();

                // 2. Record incoming movement and update quantity
                $this->stockService->recordMovement(
                    $lockedPurchase->warehouse_id,
                    $item->product_id,
                    $incomingQty,
                    $incomingCost,
                    'purchase',
                    $lockedPurchase,
                    $approvedByUserId
                );
            }

            // 3. Update supplier balance for unpaid portion
            $unpaid = round((float) $lockedPurchase->total_amount - (float) $lockedPurchase->paid_amount, 2);
            if ($unpaid > 0.0) {
                $supplier = Supplier::where('id', $lockedPurchase->supplier_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $supplier->balance = round((float) $supplier->balance + $unpaid, 2);
                $supplier->save();
            }

            // 4. Mark purchase as approved
            $lockedPurchase->status = 'approved';
            $lockedPurchase->approved_at = now();
            $lockedPurchase->approved_by_user_id = $approvedByUserId;
            $lockedPurchase->save();

            // 5. Audit Log Entry
            app(AuditService::class)->record(
                'purchase.approved',
                $lockedPurchase,
                "اعتماد وتوريد فاتورة المشتريات [{$lockedPurchase->invoice_number}] بإجمالي ".number_format($lockedPurchase->total_amount, 2)." ر.س إلى المستودع [{$lockedPurchase->warehouse->name}]",
                ['status' => 'draft'],
                [
                    'status' => 'approved',
                    'total_amount' => $lockedPurchase->total_amount,
                    'supplier_id' => $lockedPurchase->supplier_id,
                    'items_count' => $lockedPurchase->items->count(),
                ],
                $approvedByUserId
            );

            return $lockedPurchase;
        });
    }
}
