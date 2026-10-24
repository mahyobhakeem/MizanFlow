<?php

namespace App\Services\Alert;

use App\Models\Customer;
use App\Models\ProductWarehouse;
use App\Models\Sale;
use Illuminate\Support\Collection;

class AlertService
{
    /**
     * Get all active system alerts grouped by category.
     */
    public function getSummary(): array
    {
        $lowStock = $this->getLowStockAlerts();
        $creditAlerts = $this->getCreditLimitAlerts();
        $unpaidSales = $this->getUnpaidInvoicesAlerts();

        $totalCount = $lowStock->count() + $creditAlerts->count() + $unpaidSales->count();
        $criticalCount = $lowStock->where('severity', 'danger')->count() + $creditAlerts->where('severity', 'danger')->count();

        return [
            'total_count' => $totalCount,
            'critical_count' => $criticalCount,
            'low_stock' => $lowStock,
            'credit_alerts' => $creditAlerts,
            'unpaid_sales' => $unpaidSales,
        ];
    }

    /**
     * Products with inventory below or equal to their min_reorder_level in active warehouses.
     */
    public function getLowStockAlerts(): Collection
    {
        return ProductWarehouse::with(['product.unit', 'warehouse'])
            ->whereHas('warehouse', function ($q) {
                $q->where('is_active', true);
            })
            ->whereHas('product', function ($q) {
                $q->where('is_active', true)->where('min_reorder_level', '>', 0);
            })
            ->whereRaw('quantity <= (SELECT min_reorder_level FROM products WHERE products.id = product_warehouse.product_id)')
            ->get()
            ->map(function (ProductWarehouse $pw) {
                $qty = (float) $pw->quantity;
                $minLevel = (float) $pw->product->min_reorder_level;
                $severity = $qty <= 0.0 ? 'danger' : 'warning';
                $deficit = max(0.0, round($minLevel - $qty, 3));

                return [
                    'type' => 'low_stock',
                    'title' => $qty <= 0.0 ? 'نفاد تام للمخزون' : 'انخفاض تحت حد الطلب',
                    'product_id' => $pw->product_id,
                    'product_name' => $pw->product->name,
                    'sku' => $pw->product->sku,
                    'warehouse_id' => $pw->warehouse_id,
                    'warehouse_name' => $pw->warehouse->name,
                    'current_quantity' => $qty,
                    'min_reorder_level' => $minLevel,
                    'deficit' => $deficit,
                    'unit' => $pw->product->unit?->name ?? 'وحدة',
                    'severity' => $severity,
                ];
            })
            ->values();
    }

    /**
     * Customers who have exceeded or are close to reaching their credit limit (>= 85%).
     * Includes blocked/inactive customers if they have an active outstanding balance.
     */
    public function getCreditLimitAlerts(): Collection
    {
        return Customer::where('credit_limit', '>', 0)
            ->where(function ($q) {
                $q->where('is_active', true)->orWhere('balance', '>', 0);
            })
            ->whereRaw('balance >= (credit_limit * 0.85)')
            ->get()
            ->filter(function (Customer $cust) {
                $balance = round((float) $cust->balance, 2);
                $limit = round((float) $cust->credit_limit, 2);

                return $balance >= round($limit * 0.85, 2);
            })
            ->map(function (Customer $cust) {
                $balance = round((float) $cust->balance, 2);
                $limit = round((float) $cust->credit_limit, 2);
                $pct = $limit > 0 ? round(($balance / $limit) * 100, 1) : 0.0;
                $isExceeded = $balance > $limit;

                return [
                    'type' => 'credit_limit',
                    'title' => $isExceeded ? 'تجاوز سقف الائتمان المعتمد' : 'اقتراب من سقف الائتمان',
                    'customer_id' => $cust->id,
                    'customer_name' => $cust->name,
                    'code' => $cust->code,
                    'phone' => $cust->phone,
                    'balance' => $balance,
                    'credit_limit' => $limit,
                    'utilization_pct' => $pct,
                    'severity' => $isExceeded ? 'danger' : 'warning',
                ];
            })
            ->values();
    }

    /**
     * Completed credit/split invoices with remaining unpaid balances.
     */
    public function getUnpaidInvoicesAlerts(): Collection
    {
        return Sale::with(['customer', 'warehouse'])
            ->where('status', 'completed')
            ->whereIn('payment_type', ['credit', 'split'])
            ->whereColumn('paid_amount', '<', 'total_amount')
            ->get()
            ->map(function (Sale $sale) {
                $remaining = $sale->remainingAmount();
                $days = max(0, (int) $sale->invoice_date->startOfDay()->diffInDays(now()->startOfDay(), false));

                return [
                    'type' => 'unpaid_invoice',
                    'title' => 'فاتورة بيع آجلة غير مسددة بالكامل',
                    'sale_id' => $sale->id,
                    'invoice_number' => $sale->invoice_number,
                    'customer_id' => $sale->customer_id,
                    'customer_name' => $sale->customer?->name ?? 'عميل نقدي',
                    'warehouse_name' => $sale->warehouse->name,
                    'total_amount' => (float) $sale->total_amount,
                    'paid_amount' => (float) $sale->paid_amount,
                    'remaining_amount' => $remaining,
                    'invoice_date' => $sale->invoice_date->format('Y-m-d'),
                    'days_elapsed' => $days,
                    'severity' => $days > 14 ? 'danger' : 'warning',
                ];
            })
            ->values();
    }
}
