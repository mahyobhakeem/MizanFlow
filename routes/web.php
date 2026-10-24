<?php

use App\Http\Controllers\AlertController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

// Redirect root to dashboard or login
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

// Guest Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

// Protected Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Master Data Resources (RBAC governed by Policies)
    Route::resource('warehouses', WarehouseController::class)->except(['show']);
    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::resource('units', UnitController::class)->except(['show']);
    Route::resource('products', ProductController::class)->except(['show']);
    Route::resource('customers', CustomerController::class)->except(['show']);
    Route::resource('suppliers', SupplierController::class)->except(['show']);
    Route::resource('users', UserController::class)->except(['show']);

    // Procurement & Purchases
    Route::post('purchases/{purchase}/approve', [PurchaseController::class, 'approve'])->name('purchases.approve');
    Route::resource('purchases', PurchaseController::class);

    // Inter-Warehouse Stock Transfers
    Route::post('transfers/{transfer}/dispatch', [StockTransferController::class, 'dispatchTransfer'])->name('transfers.dispatch');
    Route::post('transfers/{transfer}/receive', [StockTransferController::class, 'receiveTransfer'])->name('transfers.receive');
    Route::resource('transfers', StockTransferController::class)->except(['edit', 'update']);

    // Sales & Invoices
    Route::post('sales/{sale}/complete', [SaleController::class, 'complete'])->name('sales.complete');
    Route::post('sales/{sale}/void', [SaleController::class, 'void'])->name('sales.void');
    Route::get('sales/{sale}/print', [SaleController::class, 'print'])->name('sales.print');
    Route::resource('sales', SaleController::class);

    // Inventory Movements Ledger & Warehouse Balances
    Route::get('inventory/movements', [StockMovementController::class, 'index'])->name('inventory.movements');
    Route::get('inventory/balances', [StockMovementController::class, 'balances'])->name('inventory.balances');

    // System Alerts Center
    Route::get('alerts', [AlertController::class, 'index'])->name('alerts.index');

    // Operational Reports & Analytics
    Route::get('reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
    Route::get('reports/inventory', [ReportController::class, 'inventory'])->name('reports.inventory');
    Route::get('reports/movements', [ReportController::class, 'movements'])->name('reports.movements');
    Route::get('reports/customers', [ReportController::class, 'customers'])->name('reports.customers');

    // Audit & Security Logs (Admin only)
    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
});
