<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductWarehouse;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Sales, Revenue, and Gross Profitability Report.
     */
    public function sales(Request $request): View
    {
        Gate::authorize('viewReports', Sale::class);

        $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'warehouse_id' => ['nullable', 'integer'],
            'customer_id' => ['nullable', 'integer'],
            'payment_type' => ['nullable', 'in:cash,credit,split,card,bank_transfer'],
        ]);

        $query = Sale::with(['customer', 'warehouse', 'user', 'items.product'])
            ->where('status', 'completed')
            ->latest('invoice_date');

        if ($request->filled('date_from')) {
            $query->whereDate('invoice_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('invoice_date', '<=', $request->input('date_to'));
        }

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->integer('warehouse_id'));
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->integer('customer_id'));
        }

        if ($request->filled('payment_type')) {
            $query->where('payment_type', $request->input('payment_type'));
        }

        // Fast SQL Aggregations (Zero OOM hydration risk)
        $baseTotalsQuery = (clone $query)->reorder();

        $grossSubtotal = round((float) (clone $baseTotalsQuery)->sum('subtotal'), 2);
        $totalTax = round((float) (clone $baseTotalsQuery)->sum('tax_amount'), 2);
        $totalDiscount = round((float) (clone $baseTotalsQuery)->sum('discount_amount'), 2);
        $netSalesTotal = round((float) (clone $baseTotalsQuery)->sum('total_amount'), 2);
        $totalPaid = round((float) (clone $baseTotalsQuery)->sum('paid_amount'), 2);

        // Correct total remaining: sum of unpaid balances without cross-invoice offsetting
        $totalRemaining = round((float) (clone $baseTotalsQuery)->whereColumn('paid_amount', '<', 'total_amount')
            ->sum(DB::raw('total_amount - paid_amount')), 2);

        // Net revenue excluding VAT
        $netRevenueExTax = max(0.0, round($grossSubtotal - $totalDiscount, 2));

        // Cost of Goods Sold (COGS) based on frozen unit_cost_snapshot
        $matchingSaleIds = (clone $baseTotalsQuery)->select('id');
        $totalCogs = round((float) SaleItem::whereIn('sale_id', $matchingSaleIds)
            ->sum(DB::raw('quantity * unit_cost_snapshot')), 2);

        // Gross Profit and Margin %
        $grossProfit = round($netRevenueExTax - $totalCogs, 2);
        $grossMarginPct = $netRevenueExTax > 0.0 ? round(($grossProfit / $netRevenueExTax) * 100, 2) : 0.0;

        // Top selling products aggregation pushed to database
        $topProducts = SaleItem::with('product')
            ->whereIn('sale_id', $matchingSaleIds)
            ->select('product_id', DB::raw('SUM(quantity) as quantity'), DB::raw('SUM(total_price) as revenue'), DB::raw('SUM(quantity * (unit_price - unit_cost_snapshot)) as profit'))
            ->groupBy('product_id')
            ->orderByDesc('revenue')
            ->take(5)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->product?->name ?? 'غير معروف',
                'sku' => $row->product?->sku ?? '—',
                'quantity' => (float) $row->quantity,
                'revenue' => (float) $row->revenue,
                'profit' => (float) $row->profit,
            ])
            ->all();

        $sales = $query->paginate(20)->withQueryString();

        // Historical filters include inactive entities so past data can be queried
        $warehouses = Warehouse::orderByDesc('is_active')->orderBy('name')->get();
        $customers = Customer::orderByDesc('is_active')->orderBy('name')->get();

        return view('reports.sales', compact(
            'sales',
            'warehouses',
            'customers',
            'grossSubtotal',
            'totalTax',
            'totalDiscount',
            'netSalesTotal',
            'totalPaid',
            'totalRemaining',
            'netRevenueExTax',
            'totalCogs',
            'grossProfit',
            'grossMarginPct',
            'topProducts'
        ));
    }

    /**
     * Inventory Valuation and Stock Status Report.
     */
    public function inventory(Request $request): View
    {
        Gate::authorize('viewReports', Sale::class);

        $request->validate([
            'warehouse_id' => ['nullable', 'integer'],
            'category_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'in:all,low_stock,out_of_stock,in_stock'],
        ]);

        $query = ProductWarehouse::with(['product.unit', 'product.category', 'warehouse']);

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->integer('warehouse_id'));
        }

        if ($request->filled('category_id')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('category_id', $request->integer('category_id'));
            });
        }

        // Fast SQL aggregates
        $baseQuery = clone $query;
        $totalValuation = round((float) (clone $baseQuery)->sum(DB::raw('quantity * weighted_average_cost')), 2);
        $totalQuantity = round((float) (clone $baseQuery)->sum('quantity'), 3);
        $lowStockCount = (clone $baseQuery)->whereHas('product', fn ($q) => $q->where('min_reorder_level', '>', 0))
            ->whereRaw('quantity <= (SELECT min_reorder_level FROM products WHERE products.id = product_warehouse.product_id)')
            ->count();
        $outOfStockCount = (clone $baseQuery)->where('quantity', '<=', 0)->count();

        // Status filter
        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'low_stock') {
                $query->whereHas('product', function ($q) {
                    $q->where('min_reorder_level', '>', 0);
                })->whereRaw('quantity <= (SELECT min_reorder_level FROM products WHERE products.id = product_warehouse.product_id)');
            } elseif ($status === 'out_of_stock') {
                $query->where('quantity', '<=', 0);
            } elseif ($status === 'in_stock') {
                $query->where('quantity', '>', 0);
            }
        }

        $stocks = $query->paginate(25)->withQueryString();
        $warehouses = Warehouse::orderByDesc('is_active')->orderBy('name')->get();
        $categories = Category::orderByDesc('is_active')->orderBy('name')->get();

        return view('reports.inventory', compact(
            'stocks',
            'warehouses',
            'categories',
            'totalValuation',
            'totalQuantity',
            'lowStockCount',
            'outOfStockCount'
        ));
    }

    /**
     * Item Movement Ledger Report (كشف حركة صنف تفصيلي).
     */
    public function movements(Request $request): View
    {
        Gate::authorize('viewReports', Sale::class);

        $request->validate([
            'product_id' => ['nullable', 'integer'],
            'warehouse_id' => ['nullable', 'integer'],
            'movement_type' => ['nullable', 'string'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $query = StockMovement::with(['product.unit', 'warehouse', 'user'])->latest('id');

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->integer('product_id'));
        }

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->integer('warehouse_id'));
        }

        if ($request->filled('movement_type')) {
            $query->where('movement_type', $request->input('movement_type'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $totalIn = round((float) (clone $query)->where('quantity', '>', 0)->sum('quantity'), 3);
        $totalOut = round((float) abs((clone $query)->where('quantity', '<', 0)->sum('quantity')), 3);

        $movements = $query->paginate(25)->withQueryString();
        $products = Product::orderByDesc('is_active')->orderBy('name')->get();
        $warehouses = Warehouse::orderByDesc('is_active')->orderBy('name')->get();

        return view('reports.movements', compact('movements', 'products', 'warehouses', 'totalIn', 'totalOut'));
    }

    /**
     * Customer Receivables and Aging Report.
     */
    public function customers(Request $request): View
    {
        Gate::authorize('viewReports', Sale::class);

        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'filter' => ['nullable', 'in:all,with_balance,exceeded_limit'],
        ]);

        $query = Customer::withCount(['sales' => fn ($q) => $q->where('status', 'completed')])
            ->orderByDesc('balance');

        if ($request->filled('search')) {
            $query->search($request->input('search'));
        }

        if ($request->input('filter') === 'with_balance') {
            $query->where('balance', '>', 0);
        } elseif ($request->input('filter') === 'exceeded_limit') {
            $query->where('credit_limit', '>', 0)->whereRaw('balance > credit_limit');
        }

        $totalReceivables = round((float) (clone $query)->sum('balance'), 2);
        $indebtedCustomersCount = (clone $query)->where('balance', '>', 0)->count();

        $customers = $query->paginate(20)->withQueryString();

        return view('reports.customers', compact('customers', 'totalReceivables', 'indebtedCustomersCount'));
    }
}
