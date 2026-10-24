<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\ProductWarehouse;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\Alert\AlertService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the main application dashboard with real-time operational metrics.
     */
    public function index(Request $request, AlertService $alertService): View
    {
        $user = Auth::user();
        $canViewFinancials = $user->can('viewReports', Sale::class);

        // 1. Sales & Revenue Metrics (strictly completed sales)
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        $todaySalesQuery = Sale::where('status', 'completed')
            ->whereBetween('invoice_date', [$todayStart, $todayEnd]);

        $todaySalesTotal = (float) $todaySalesQuery->sum('total_amount');
        $todaySalesCount = $todaySalesQuery->count();

        $completedSalesQuery = Sale::where('status', 'completed');
        $totalSalesRevenue = (float) $completedSalesQuery->sum('total_amount');
        $completedSalesCount = $completedSalesQuery->count();

        // 2. Financial Metrics (Strictly restricted to Admin & Manager)
        if ($canViewFinancials) {
            // Net revenue before VAT minus total invoice discounts
            $netRevenueExTax = (float) Sale::where('status', 'completed')
                ->sum(DB::raw('subtotal - discount_amount'));

            // Total COGS from frozen unit_cost_snapshot across all completed sales
            $totalCogs = (float) SaleItem::whereHas('sale', fn ($q) => $q->where('status', 'completed'))
                ->sum(DB::raw('quantity * unit_cost_snapshot'));

            // Gross Profit: Net Revenue Ex Tax - COGS (Unified with ReportController)
            $totalGrossProfit = round($netRevenueExTax - round($totalCogs, 2), 2);

            // Total Inventory Valuation (Moving WAC in active warehouses)
            $totalInventoryValuation = round((float) ProductWarehouse::whereHas('warehouse', fn ($q) => $q->where('is_active', true))
                ->sum(DB::raw('quantity * weighted_average_cost')), 2);

            $totalReceivables = (float) Customer::sum('balance');
            $totalPayables = (float) Supplier::sum('balance');

            // Warehouse Summary Cards
            $warehousesSummary = Warehouse::active()->with('productStocks')->get()->map(function (Warehouse $wh) {
                $val = $wh->productStocks->sum(fn ($s) => (float) $s->quantity * (float) $s->weighted_average_cost);
                $qty = $wh->productStocks->sum('quantity');

                return [
                    'name' => $wh->name,
                    'location' => $wh->location,
                    'valuation' => round($val, 2),
                    'quantity' => round($qty, 3),
                ];
            });
        } else {
            // Cashiers do not receive sensitive profit margins, costs, or company receivables/payables
            $totalGrossProfit = null;
            $totalInventoryValuation = null;
            $totalReceivables = null;
            $totalPayables = null;
            $warehousesSummary = collect();
        }

        // Invoices Breakdown & Physical Quantities
        $draftSalesCount = Sale::where('status', 'draft')->count();
        $voidedSalesCount = Sale::where('status', 'voided')->count();
        $totalStockUnits = round((float) ProductWarehouse::whereHas('warehouse', fn ($q) => $q->where('is_active', true))->sum('quantity'), 3);

        // System Alerts (Filter out company credit risk alerts for cashier)
        $rawAlertsSummary = $alertService->getSummary();
        if ($canViewFinancials) {
            $alertsSummary = $rawAlertsSummary;
        } else {
            $alertsSummary = [
                'total_count' => $rawAlertsSummary['low_stock']->count(),
                'critical_count' => $rawAlertsSummary['low_stock']->where('severity', 'danger')->count(),
                'low_stock' => $rawAlertsSummary['low_stock'],
                'credit_alerts' => collect(),
                'unpaid_sales' => collect(),
            ];
        }

        // 3. Recent Sales Activity (last 6 sales)
        $recentSales = Sale::with(['customer', 'warehouse', 'user'])
            ->latest('id')
            ->take(6)
            ->get();

        // 4. Recent Stock Ledger Movements (last 6 movements)
        $recentMovements = StockMovement::with(['product', 'warehouse'])
            ->latest('id')
            ->take(6)
            ->get();

        return view('dashboard', compact(
            'user',
            'canViewFinancials',
            'todaySalesTotal',
            'todaySalesCount',
            'totalSalesRevenue',
            'completedSalesCount',
            'draftSalesCount',
            'voidedSalesCount',
            'totalGrossProfit',
            'totalInventoryValuation',
            'totalStockUnits',
            'totalReceivables',
            'totalPayables',
            'alertsSummary',
            'recentSales',
            'recentMovements',
            'warehousesSummary'
        ));
    }
}
