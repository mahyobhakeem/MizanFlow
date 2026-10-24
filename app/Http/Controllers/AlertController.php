<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Services\Alert\AlertService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AlertController extends Controller
{
    public function index(Request $request, AlertService $alertService): View
    {
        $rawSummary = $alertService->getSummary();
        $canViewFinancials = $request->user()->can('viewReports', Sale::class);

        if ($canViewFinancials) {
            $summary = $rawSummary;
        } else {
            // Cashiers only see operational low stock alerts, not company credit exposure
            $summary = [
                'total_count' => $rawSummary['low_stock']->count(),
                'critical_count' => $rawSummary['low_stock']->where('severity', 'danger')->count(),
                'low_stock' => $rawSummary['low_stock'],
                'credit_alerts' => collect(),
                'unpaid_sales' => collect(),
            ];
        }

        $activeTab = $request->input('type', 'all');

        return view('alerts.index', compact('summary', 'activeTab', 'canViewFinancials'));
    }
}
