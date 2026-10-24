<?php

namespace Tests\Feature;

use App\Actions\Inventory\StockTransferAction;
use App\Actions\Procurement\ApprovePurchaseAction;
use App\Actions\Sales\CompleteSaleAction;
use App\Actions\Sales\VoidSaleAction;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductWarehouse;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\StockTransfer;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Alert\AlertService;
use App\Services\Audit\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAndReportsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $manager;

    protected User $cashier;

    protected Warehouse $warehouse1;

    protected Warehouse $warehouse2;

    protected Category $category;

    protected Unit $unit;

    protected Product $productA;

    protected Product $productB;

    protected Customer $customerRegular;

    protected Customer $customerIndebted;

    protected Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create(['username' => 'admin_dash']);
        $this->manager = User::factory()->manager()->create(['username' => 'manager_dash']);
        $this->cashier = User::factory()->cashier()->create(['username' => 'cashier_dash']);

        $this->warehouse1 = Warehouse::create([
            'code' => 'WH-MAIN',
            'name' => 'المستودع الرئيسي',
            'location' => 'الرياض',
            'is_active' => true,
        ]);

        $this->warehouse2 = Warehouse::create([
            'code' => 'WH-SEC',
            'name' => 'مستودع فرع العليا',
            'location' => 'العليا - الرياض',
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'code' => 'CAT-ELEC',
            'name' => 'الأجهزة الإلكترونية',
            'is_active' => true,
        ]);

        $this->unit = Unit::create([
            'name' => 'قطعة',
            'code' => 'PCS',
            'allow_decimal' => false,
            'is_active' => true,
        ]);

        $this->productA = Product::create([
            'sku' => 'SKU-LAPTOP',
            'barcode' => '1001001',
            'name' => 'حاسوب محمول فائق الأداء',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'default_sale_price' => 3500.0000,
            'min_reorder_level' => 10.000,
            'is_active' => true,
        ]);

        $this->productB = Product::create([
            'sku' => 'SKU-MOUSE',
            'barcode' => '1001002',
            'name' => 'فأرة لاسلكية مريحة',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'default_sale_price' => 120.0000,
            'min_reorder_level' => 15.000,
            'is_active' => true,
        ]);

        $this->customerRegular = Customer::create([
            'code' => 'CUST-001',
            'name' => 'شركة النجاح للتجارة',
            'company_name' => 'شركة النجاح',
            'phone' => '0501112233',
            'credit_limit' => 50000.00,
            'balance' => 0.00,
            'is_active' => true,
        ]);

        $this->customerIndebted = Customer::create([
            'code' => 'CUST-002',
            'name' => 'مؤسسة الأفق للمقاولات',
            'company_name' => 'مؤسسة الأفق',
            'phone' => '0504445566',
            'credit_limit' => 20000.00,
            'balance' => 18000.00, // 90% utilization (warning alert)
            'is_active' => true,
        ]);

        $this->supplier = Supplier::create([
            'code' => 'SUPP-001',
            'name' => 'مجموعة التوريد الحديثة',
            'balance' => 15000.00,
            'is_active' => true,
        ]);
    }

    /**
     * Helper to stock a warehouse via an approved purchase.
     */
    protected function stockProduct(Product $product, Warehouse $warehouse, float $qty, float $cost): void
    {
        $purchase = Purchase::create([
            'invoice_number' => Purchase::generateInvoiceNumber(),
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $this->admin->id,
            'issue_date' => now()->format('Y-m-d'),
            'status' => 'draft',
            'subtotal' => $qty * $cost,
            'tax_amount' => 0.00,
            'discount_amount' => 0.00,
            'total_amount' => $qty * $cost,
            'paid_amount' => $qty * $cost,
        ]);

        $purchase->items()->create([
            'product_id' => $product->id,
            'quantity' => $qty,
            'unit_cost' => $cost,
            'total_cost' => $qty * $cost,
        ]);

        app(ApprovePurchaseAction::class)->execute($purchase, $this->admin->id);
    }

    // =========================================================================
    // 1. DASHBOARD TESTS
    // =========================================================================

    public function test_admin_and_manager_can_access_dashboard_with_accurate_real_time_metrics(): void
    {
        // 1. Stock Product A: 20 units @ 2000.00 (valuation = 40,000)
        $this->stockProduct($this->productA, $this->warehouse1, 20, 2000.00);

        // 2. Complete a sale: 2 units of Product A @ 3500.00 (Revenue = 7000, Cost = 4000, Gross Profit = 3000)
        $sale = Sale::create([
            'invoice_number' => Sale::generateInvoiceNumber(),
            'customer_id' => $this->customerRegular->id,
            'warehouse_id' => $this->warehouse1->id,
            'user_id' => $this->manager->id,
            'invoice_date' => now(),
            'payment_type' => 'cash',
            'status' => 'draft',
            'subtotal' => 7000.00,
            'tax_amount' => 0.00,
            'discount_amount' => 0.00,
            'total_amount' => 7000.00,
            'paid_amount' => 7000.00,
        ]);

        $sale->items()->create([
            'product_id' => $this->productA->id,
            'quantity' => 2,
            'unit_price' => 3500.00,
            'total_price' => 7000.00,
            'unit_cost_snapshot' => 2000.00,
        ]);

        app(CompleteSaleAction::class)->execute($sale, $this->manager->id);

        // Admin visit
        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertOk();
        $response->assertViewIs('dashboard');
        $response->assertViewHas('totalSalesRevenue', 7000.00);
        $response->assertViewHas('totalGrossProfit', 3000.00);
        $response->assertViewHas('completedSalesCount', 1);

        // Remaining inventory: 18 units @ 2000.00 = 36,000.00
        $response->assertViewHas('totalInventoryValuation', 36000.00);
        $response->assertViewHas('totalReceivables', 18000.00); // from customerIndebted
        $response->assertViewHas('totalPayables', 15000.00); // from supplier

        // Manager visit
        $managerResponse = $this->actingAs($this->manager)->get(route('dashboard'));
        $managerResponse->assertOk();
    }

    public function test_cashier_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->cashier)->get(route('dashboard'));
        $response->assertOk();
        $response->assertViewIs('dashboard');
    }

    public function test_dashboard_handles_zero_state_gracefully_without_division_by_zero(): void
    {
        // No sales, no purchases, no stock
        Customer::query()->update(['balance' => 0]);
        Supplier::query()->update(['balance' => 0]);

        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertOk();
        $response->assertViewHas('totalSalesRevenue', 0.0);
        $response->assertViewHas('totalGrossProfit', 0.0);
        $response->assertViewHas('totalInventoryValuation', 0.0);
        $response->assertViewHas('totalReceivables', 0.0);
        $response->assertViewHas('totalPayables', 0.0);
    }

    // =========================================================================
    // 2. OPERATIONAL REPORTS TESTS
    // =========================================================================

    public function test_sales_report_calculates_cogs_gross_profit_and_margin_accurately(): void
    {
        // Stock 50 laptops @ 2000 cost
        $this->stockProduct($this->productA, $this->warehouse1, 50, 2000.00);

        // Sale 1: 5 laptops @ 3500 (Gross = 17500, Discount = 500, Net = 17000, Cost = 10000, Profit = 7000)
        $sale1 = Sale::create([
            'invoice_number' => Sale::generateInvoiceNumber(),
            'customer_id' => $this->customerRegular->id,
            'warehouse_id' => $this->warehouse1->id,
            'user_id' => $this->admin->id,
            'invoice_date' => now()->subDay(),
            'payment_type' => 'cash',
            'status' => 'draft',
            'subtotal' => 17500.00,
            'discount_amount' => 500.00,
            'tax_amount' => 0.00,
            'total_amount' => 17000.00,
            'paid_amount' => 17000.00,
        ]);

        $sale1->items()->create([
            'product_id' => $this->productA->id,
            'quantity' => 5,
            'unit_price' => 3500.00,
            'total_price' => 17500.00,
            'unit_cost_snapshot' => 2000.00,
        ]);

        app(CompleteSaleAction::class)->execute($sale1, $this->admin->id);

        $response = $this->actingAs($this->admin)->get(route('reports.sales'));
        $response->assertOk();
        $response->assertViewIs('reports.sales');

        $response->assertViewHas('netSalesTotal', 17000.00);
        $response->assertViewHas('totalDiscount', 500.00);
        $response->assertViewHas('netRevenueExTax', 17000.00);
        $response->assertViewHas('totalCogs', 10000.00);
        $response->assertViewHas('grossProfit', 7000.00);
        // Margin: (7000 / 17000) * 100 = 41.18%
        $response->assertViewHas('grossMarginPct', 41.18);
    }

    public function test_sales_report_filters_by_warehouse_and_payment_type(): void
    {
        $this->stockProduct($this->productA, $this->warehouse1, 20, 2000.00);
        $this->stockProduct($this->productA, $this->warehouse2, 20, 2000.00);

        // Sale in warehouse 1 (cash)
        $saleWh1 = Sale::create([
            'invoice_number' => Sale::generateInvoiceNumber(),
            'customer_id' => $this->customerRegular->id,
            'warehouse_id' => $this->warehouse1->id,
            'user_id' => $this->admin->id,
            'invoice_date' => now(),
            'payment_type' => 'cash',
            'status' => 'draft',
            'subtotal' => 3500.00,
            'tax_amount' => 0.00,
            'discount_amount' => 0.00,
            'total_amount' => 3500.00,
            'paid_amount' => 3500.00,
        ]);
        $saleWh1->items()->create([
            'product_id' => $this->productA->id,
            'quantity' => 1,
            'unit_price' => 3500.00,
            'total_price' => 3500.00,
            'unit_cost_snapshot' => 2000.00,
        ]);
        app(CompleteSaleAction::class)->execute($saleWh1, $this->admin->id);

        // Sale in warehouse 2 (credit)
        $saleWh2 = Sale::create([
            'invoice_number' => Sale::generateInvoiceNumber(),
            'customer_id' => $this->customerRegular->id,
            'warehouse_id' => $this->warehouse2->id,
            'user_id' => $this->admin->id,
            'invoice_date' => now(),
            'payment_type' => 'credit',
            'status' => 'draft',
            'subtotal' => 7000.00,
            'tax_amount' => 0.00,
            'discount_amount' => 0.00,
            'total_amount' => 7000.00,
            'paid_amount' => 0.00,
        ]);
        $saleWh2->items()->create([
            'product_id' => $this->productA->id,
            'quantity' => 2,
            'unit_price' => 3500.00,
            'total_price' => 7000.00,
            'unit_cost_snapshot' => 2000.00,
        ]);
        app(CompleteSaleAction::class)->execute($saleWh2, $this->admin->id);

        // Filter by warehouse 1
        $wh1Response = $this->actingAs($this->admin)->get(route('reports.sales', [
            'warehouse_id' => $this->warehouse1->id,
        ]));
        $wh1Response->assertOk();
        $wh1Response->assertViewHas('netSalesTotal', 3500.00);

        // Filter by payment type: credit
        $creditResponse = $this->actingAs($this->admin)->get(route('reports.sales', [
            'payment_type' => 'credit',
        ]));
        $creditResponse->assertOk();
        $creditResponse->assertViewHas('netSalesTotal', 7000.00);
    }

    public function test_inventory_valuation_report_calculates_wac_valuation_and_low_stock_correctly(): void
    {
        // Product A in WH1: 5 units @ 2000 = 10,000 (min_reorder_level = 10 => Low Stock!)
        $this->stockProduct($this->productA, $this->warehouse1, 5, 2000.00);

        // Product B in WH1: 30 units @ 80 = 2,400 (min_reorder_level = 15 => Adequate Stock)
        $this->stockProduct($this->productB, $this->warehouse1, 30, 80.00);

        $response = $this->actingAs($this->admin)->get(route('reports.inventory'));
        $response->assertOk();
        $response->assertViewIs('reports.inventory');

        // Total Valuation = 10,000 + 2,400 = 12,400.00
        $response->assertViewHas('totalValuation', 12400.00);
        $response->assertViewHas('totalQuantity', 35.000);
        $response->assertViewHas('lowStockCount', 1); // Product A
        $response->assertViewHas('outOfStockCount', 0);

        // Filter by status: low_stock
        $lowStockResponse = $this->actingAs($this->admin)->get(route('reports.inventory', [
            'status' => 'low_stock',
        ]));
        $lowStockResponse->assertOk();
    }

    public function test_movement_ledger_report_displays_chronological_movements_and_aggregates(): void
    {
        // Inward movement via purchase: 10 units @ 2000
        $this->stockProduct($this->productA, $this->warehouse1, 10, 2000.00);

        // Outward movement via sale: 2 units
        $sale = Sale::create([
            'invoice_number' => Sale::generateInvoiceNumber(),
            'customer_id' => $this->customerRegular->id,
            'warehouse_id' => $this->warehouse1->id,
            'user_id' => $this->admin->id,
            'invoice_date' => now(),
            'payment_type' => 'cash',
            'status' => 'draft',
            'subtotal' => 7000.00,
            'tax_amount' => 0.00,
            'discount_amount' => 0.00,
            'total_amount' => 7000.00,
            'paid_amount' => 7000.00,
        ]);
        $sale->items()->create([
            'product_id' => $this->productA->id,
            'quantity' => 2,
            'unit_price' => 3500.00,
            'total_price' => 7000.00,
            'unit_cost_snapshot' => 2000.00,
        ]);
        app(CompleteSaleAction::class)->execute($sale, $this->admin->id);

        $response = $this->actingAs($this->admin)->get(route('reports.movements', [
            'product_id' => $this->productA->id,
        ]));

        $response->assertOk();
        $response->assertViewIs('reports.movements');
        $response->assertViewHas('totalIn', 10.000);
        $response->assertViewHas('totalOut', 2.000);
    }

    public function test_customer_receivables_report_shows_balances_and_credit_limit_flags(): void
    {
        // customerRegular has balance 0, customerIndebted has balance 18,000
        $response = $this->actingAs($this->admin)->get(route('reports.customers'));
        $response->assertOk();
        $response->assertViewIs('reports.customers');
        $response->assertViewHas('totalReceivables', 18000.00);
        $response->assertViewHas('indebtedCustomersCount', 1);

        // Filter by with_balance
        $filteredResponse = $this->actingAs($this->admin)->get(route('reports.customers', [
            'filter' => 'with_balance',
        ]));
        $filteredResponse->assertOk();
    }

    public function test_cashier_is_forbidden_from_viewing_all_reports(): void
    {
        $this->actingAs($this->cashier)->get(route('reports.sales'))->assertForbidden();
        $this->actingAs($this->cashier)->get(route('reports.inventory'))->assertForbidden();
        $this->actingAs($this->cashier)->get(route('reports.movements'))->assertForbidden();
        $this->actingAs($this->cashier)->get(route('reports.customers'))->assertForbidden();
    }

    // =========================================================================
    // 3. SYSTEM ALERTS TESTS
    // =========================================================================

    public function test_low_stock_alert_triggers_when_quantity_is_at_or_below_min_level(): void
    {
        // Stock 5 units of Product A (min_reorder_level = 10)
        $this->stockProduct($this->productA, $this->warehouse1, 5, 2000.00);

        /** @var AlertService $alertService */
        $alertService = app(AlertService::class);
        $lowStock = $alertService->getLowStockAlerts();

        $this->assertCount(1, $lowStock);
        $alert = $lowStock->first();
        $this->assertEquals($this->productA->id, $alert['product_id']);
        $this->assertEquals(5.0, $alert['current_quantity']);
        $this->assertEquals(10.0, $alert['min_reorder_level']);
        $this->assertEquals(5.0, $alert['deficit']);
        $this->assertEquals('warning', $alert['severity']);
    }

    public function test_credit_limit_alert_triggers_when_customer_balance_is_at_or_above_85_percent(): void
    {
        /** @var AlertService $alertService */
        $alertService = app(AlertService::class);
        $alerts = $alertService->getCreditLimitAlerts();

        // customerIndebted has credit_limit = 20,000 and balance = 18,000 (90%)
        $this->assertTrue($alerts->contains('customer_id', $this->customerIndebted->id));

        $indebtedAlert = $alerts->firstWhere('customer_id', $this->customerIndebted->id);
        $this->assertEquals(90.0, $indebtedAlert['utilization_pct']);
        $this->assertEquals('warning', $indebtedAlert['severity']);

        // Exceed credit limit -> severity becomes 'danger'
        $this->customerIndebted->balance = 25000.00;
        $this->customerIndebted->save();

        $updatedAlerts = $alertService->getCreditLimitAlerts();
        $dangerAlert = $updatedAlerts->firstWhere('customer_id', $this->customerIndebted->id);
        $this->assertEquals('danger', $dangerAlert['severity']);
    }

    public function test_unpaid_sales_alert_triggers_for_overdue_credit_invoices(): void
    {
        $this->stockProduct($this->productA, $this->warehouse1, 10, 2000.00);

        $sale = Sale::create([
            'invoice_number' => Sale::generateInvoiceNumber(),
            'customer_id' => $this->customerRegular->id,
            'warehouse_id' => $this->warehouse1->id,
            'user_id' => $this->admin->id,
            'invoice_date' => now()->subDays(20),
            'payment_type' => 'credit',
            'status' => 'draft',
            'subtotal' => 3500.00,
            'tax_amount' => 0.00,
            'discount_amount' => 0.00,
            'total_amount' => 3500.00,
            'paid_amount' => 500.00, // 3000 remaining
        ]);
        $sale->items()->create([
            'product_id' => $this->productA->id,
            'quantity' => 1,
            'unit_price' => 3500.00,
            'total_price' => 3500.00,
            'unit_cost_snapshot' => 2000.00,
        ]);
        app(CompleteSaleAction::class)->execute($sale, $this->admin->id);

        /** @var AlertService $alertService */
        $alertService = app(AlertService::class);
        $unpaidAlerts = $alertService->getUnpaidInvoicesAlerts();

        $this->assertTrue($unpaidAlerts->contains('sale_id', $sale->id));
        $saleAlert = $unpaidAlerts->firstWhere('sale_id', $sale->id);
        $this->assertEquals(3000.00, $saleAlert['remaining_amount']);
        $this->assertEquals('danger', $saleAlert['severity']); // >14 days
    }

    public function test_alerts_index_view_renders_all_active_alerts(): void
    {
        $response = $this->actingAs($this->admin)->get(route('alerts.index'));
        $response->assertOk();
        $response->assertViewIs('alerts.index');
        $response->assertViewHas('summary');
    }

    // =========================================================================
    // 4. AUDIT LOGGING & TRAIL TESTS
    // =========================================================================

    public function test_audit_logging_records_auth_login_and_logout(): void
    {
        // Login
        $this->post('/login', [
            'login' => 'admin_dash',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'auth.login',
            'user_id' => $this->admin->id,
        ]);

        // Logout
        $this->actingAs($this->admin)->post('/logout')->assertRedirect('/login');

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'auth.logout',
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_audit_logging_records_sales_completion_and_voiding(): void
    {
        $this->stockProduct($this->productA, $this->warehouse1, 10, 2000.00);

        $sale = Sale::create([
            'invoice_number' => Sale::generateInvoiceNumber(),
            'customer_id' => $this->customerRegular->id,
            'warehouse_id' => $this->warehouse1->id,
            'user_id' => $this->admin->id,
            'invoice_date' => now(),
            'payment_type' => 'cash',
            'status' => 'draft',
            'subtotal' => 3500.00,
            'tax_amount' => 0.00,
            'discount_amount' => 0.00,
            'total_amount' => 3500.00,
            'paid_amount' => 3500.00,
        ]);
        $sale->items()->create([
            'product_id' => $this->productA->id,
            'quantity' => 1,
            'unit_price' => 3500.00,
            'total_price' => 3500.00,
            'unit_cost_snapshot' => 2000.00,
        ]);

        // Complete sale
        app(CompleteSaleAction::class)->execute($sale, $this->admin->id);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'sale.completed',
            'auditable_type' => Sale::class,
            'auditable_id' => $sale->id,
            'user_id' => $this->admin->id,
        ]);

        // Void sale
        app(VoidSaleAction::class)->execute($sale, $this->admin->id, 'خطأ في طلب العميل');

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'sale.voided',
            'auditable_type' => Sale::class,
            'auditable_id' => $sale->id,
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_audit_logging_records_purchase_approval(): void
    {
        $this->stockProduct($this->productB, $this->warehouse1, 25, 80.00);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'purchase.approved',
            'auditable_type' => Purchase::class,
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_audit_logging_records_stock_transfer_dispatch_and_receive(): void
    {
        $this->stockProduct($this->productA, $this->warehouse1, 10, 2000.00);

        $transfer = StockTransfer::create([
            'transfer_number' => StockTransfer::generateTransferNumber(),
            'from_warehouse_id' => $this->warehouse1->id,
            'to_warehouse_id' => $this->warehouse2->id,
            'status' => 'draft',
            'created_by_user_id' => $this->admin->id,
        ]);

        $transfer->items()->create([
            'product_id' => $this->productA->id,
            'quantity_sent' => 4,
            'unit_cost_snapshot' => 0.0000,
        ]);

        /** @var StockTransferAction $transferAction */
        $transferAction = app(StockTransferAction::class);

        // Dispatch
        $transferAction->dispatch($transfer, $this->admin->id);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'transfer.dispatched',
            'auditable_type' => StockTransfer::class,
            'auditable_id' => $transfer->id,
        ]);

        // Receive
        $transferAction->receive($transfer, $this->admin->id);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'transfer.received',
            'auditable_type' => StockTransfer::class,
            'auditable_id' => $transfer->id,
        ]);
    }

    public function test_audit_logs_strictly_sanitize_sensitive_parameters(): void
    {
        /** @var AuditService $auditService */
        $auditService = app(AuditService::class);

        $dirtyData = [
            'username' => 'test_user',
            'password' => 'SuperSecret123!',
            'password_confirmation' => 'SuperSecret123!',
            'remember_token' => 'token123456',
            'secret' => 'apiKeySecret',
            'role' => 'manager',
        ];

        $sanitized = $auditService->sanitize($dirtyData);

        $this->assertArrayNotHasKey('password', $sanitized);
        $this->assertArrayNotHasKey('password_confirmation', $sanitized);
        $this->assertArrayNotHasKey('remember_token', $sanitized);
        $this->assertArrayNotHasKey('secret', $sanitized);
        $this->assertArrayHasKey('username', $sanitized);
        $this->assertArrayHasKey('role', $sanitized);
    }

    public function test_audit_logs_viewer_is_restricted_strictly_to_administrator(): void
    {
        // Admin: 200 OK
        $this->actingAs($this->admin)->get(route('audit-logs.index'))->assertOk();

        // Manager: 403 Forbidden
        $this->actingAs($this->manager)->get(route('audit-logs.index'))->assertForbidden();

        // Cashier: 403 Forbidden
        $this->actingAs($this->cashier)->get(route('audit-logs.index'))->assertForbidden();

        // Guest: 302 Redirect to login
        $this->post('/logout');
        $this->get(route('audit-logs.index'))->assertRedirect(route('login'));
    }

    // =========================================================================
    // 5. REGRESSION & INDEPENDENT REVIEW AUDIT VERIFICATION TESTS
    // =========================================================================

    public function test_dashboard_and_sales_report_gross_profit_match_when_invoice_discount_exists(): void
    {
        $this->stockProduct($this->productA, $this->warehouse1, 10, 2000.00);

        // Sale: subtotal = 3500, discount_amount = 500, total_amount = 3000, cost = 2000
        // Correct Gross Profit = Net Revenue (3500 - 500 = 3000) - Cost (2000) = 1000.00
        $sale = Sale::create([
            'invoice_number' => Sale::generateInvoiceNumber(),
            'customer_id' => $this->customerRegular->id,
            'warehouse_id' => $this->warehouse1->id,
            'user_id' => $this->admin->id,
            'invoice_date' => now(),
            'payment_type' => 'cash',
            'status' => 'draft',
            'subtotal' => 3500.00,
            'tax_amount' => 0.00,
            'discount_amount' => 500.00,
            'total_amount' => 3000.00,
            'paid_amount' => 3000.00,
        ]);

        $sale->items()->create([
            'product_id' => $this->productA->id,
            'quantity' => 1,
            'unit_price' => 3500.00,
            'total_price' => 3500.00,
            'unit_cost_snapshot' => 2000.00,
        ]);

        app(CompleteSaleAction::class)->execute($sale, $this->admin->id);

        $dashboardResponse = $this->actingAs($this->admin)->get(route('dashboard'));
        $reportResponse = $this->actingAs($this->admin)->get(route('reports.sales'));

        $dashboardProfit = (float) $dashboardResponse->viewData('totalGrossProfit');
        $reportProfit = (float) $reportResponse->viewData('grossProfit');

        $this->assertEquals(1000.00, $reportProfit);
        $this->assertEquals($reportProfit, $dashboardProfit);
    }

    public function test_cashier_does_not_receive_sensitive_profit_and_valuation_metrics_on_dashboard(): void
    {
        $response = $this->actingAs($this->cashier)->get(route('dashboard'));
        $response->assertOk();

        $this->assertNull($response->viewData('totalGrossProfit'));
        $this->assertNull($response->viewData('totalInventoryValuation'));
        $this->assertNull($response->viewData('totalPayables'));
        $this->assertNull($response->viewData('totalReceivables'));
        $this->assertCount(0, $response->viewData('warehousesSummary'));

        // Cashier sees only low stock alerts, not credit or unpaid sales
        $alertsResponse = $this->actingAs($this->cashier)->get(route('alerts.index'));
        $alertsResponse->assertOk();
        $summary = $alertsResponse->viewData('summary');
        $this->assertCount(0, $summary['credit_alerts']);
        $this->assertCount(0, $summary['unpaid_sales']);
    }

    public function test_unpaid_invoices_alert_does_not_hydrate_fully_paid_credit_sales(): void
    {
        $this->stockProduct($this->productA, $this->warehouse1, 10, 2000.00);

        // Fully paid credit sale
        $paidSale = Sale::create([
            'invoice_number' => Sale::generateInvoiceNumber(),
            'customer_id' => $this->customerRegular->id,
            'warehouse_id' => $this->warehouse1->id,
            'user_id' => $this->admin->id,
            'invoice_date' => now()->subDays(5),
            'payment_type' => 'credit',
            'status' => 'draft',
            'subtotal' => 3500.00,
            'tax_amount' => 0.00,
            'discount_amount' => 0.00,
            'total_amount' => 3500.00,
            'paid_amount' => 3500.00, // Fully paid!
        ]);
        $paidSale->items()->create([
            'product_id' => $this->productA->id,
            'quantity' => 1,
            'unit_price' => 3500.00,
            'total_price' => 3500.00,
            'unit_cost_snapshot' => 2000.00,
        ]);
        app(CompleteSaleAction::class)->execute($paidSale, $this->admin->id);

        /** @var AlertService $alertService */
        $alertService = app(AlertService::class);
        $unpaidAlerts = $alertService->getUnpaidInvoicesAlerts();

        $this->assertFalse($unpaidAlerts->contains('sale_id', $paidSale->id));
    }

    public function test_inactive_warehouse_does_not_trigger_low_stock_alerts(): void
    {
        $inactiveWh = Warehouse::create([
            'code' => 'WH-CLOSED',
            'name' => 'المستودع المغلق',
            'location' => 'جدة',
            'is_active' => false,
        ]);

        ProductWarehouse::create([
            'product_id' => $this->productA->id,
            'warehouse_id' => $inactiveWh->id,
            'quantity' => 0.000,
            'weighted_average_cost' => 2000.00,
        ]);

        /** @var AlertService $alertService */
        $alertService = app(AlertService::class);
        $alerts = $alertService->getLowStockAlerts();

        $this->assertFalse($alerts->contains('warehouse_id', $inactiveWh->id));
    }

    public function test_blocked_debtor_still_triggers_credit_alert(): void
    {
        // Deactivate an indebted customer (balance = 18,000, limit = 20,000 -> 90%)
        $this->customerIndebted->is_active = false;
        $this->customerIndebted->save();

        /** @var AlertService $alertService */
        $alertService = app(AlertService::class);
        $alerts = $alertService->getCreditLimitAlerts();

        $this->assertTrue($alerts->contains('customer_id', $this->customerIndebted->id));
    }

    public function test_sales_report_total_remaining_does_not_offset_across_unrelated_invoices(): void
    {
        $this->stockProduct($this->productA, $this->warehouse1, 10, 2000.00);

        // Invoice 1: Total 1000, Paid 1000 (Remaining 0)
        $sale1 = Sale::create([
            'invoice_number' => Sale::generateInvoiceNumber(),
            'customer_id' => $this->customerRegular->id,
            'warehouse_id' => $this->warehouse1->id,
            'user_id' => $this->admin->id,
            'invoice_date' => now(),
            'payment_type' => 'cash',
            'status' => 'draft',
            'subtotal' => 1000.00,
            'tax_amount' => 0.00,
            'discount_amount' => 0.00,
            'total_amount' => 1000.00,
            'paid_amount' => 1000.00,
        ]);
        $sale1->items()->create([
            'product_id' => $this->productA->id,
            'quantity' => 1,
            'unit_price' => 1000.00,
            'total_price' => 1000.00,
            'unit_cost_snapshot' => 500.00,
        ]);
        app(CompleteSaleAction::class)->execute($sale1, $this->admin->id);

        // Invoice 2: Total 2000, Paid 500 (Remaining 1500)
        $sale2 = Sale::create([
            'invoice_number' => Sale::generateInvoiceNumber(),
            'customer_id' => $this->customerRegular->id,
            'warehouse_id' => $this->warehouse1->id,
            'user_id' => $this->admin->id,
            'invoice_date' => now(),
            'payment_type' => 'credit',
            'status' => 'draft',
            'subtotal' => 2000.00,
            'tax_amount' => 0.00,
            'discount_amount' => 0.00,
            'total_amount' => 2000.00,
            'paid_amount' => 500.00,
        ]);
        $sale2->items()->create([
            'product_id' => $this->productA->id,
            'quantity' => 1,
            'unit_price' => 2000.00,
            'total_price' => 2000.00,
            'unit_cost_snapshot' => 500.00,
        ]);
        app(CompleteSaleAction::class)->execute($sale2, $this->admin->id);

        $response = $this->actingAs($this->admin)->get(route('reports.sales'));
        $response->assertOk();

        // Remaining should be exactly 1500.00
        $this->assertEquals(1500.00, $response->viewData('totalRemaining'));
    }

    public function test_sales_report_filters_accurately_by_same_day_date_from_and_date_to(): void
    {
        $this->stockProduct($this->productA, $this->warehouse1, 10, 2000.00);

        $today = now()->format('Y-m-d');
        $yesterday = now()->subDay()->format('Y-m-d');

        // Today's sale
        $saleToday = Sale::create([
            'invoice_number' => Sale::generateInvoiceNumber(),
            'customer_id' => $this->customerRegular->id,
            'warehouse_id' => $this->warehouse1->id,
            'user_id' => $this->admin->id,
            'invoice_date' => now(),
            'payment_type' => 'cash',
            'status' => 'draft',
            'subtotal' => 1000.00,
            'tax_amount' => 0.00,
            'discount_amount' => 0.00,
            'total_amount' => 1000.00,
            'paid_amount' => 1000.00,
        ]);
        $saleToday->items()->create([
            'product_id' => $this->productA->id,
            'quantity' => 1,
            'unit_price' => 1000.00,
            'total_price' => 1000.00,
            'unit_cost_snapshot' => 500.00,
        ]);
        app(CompleteSaleAction::class)->execute($saleToday, $this->admin->id);

        // Filter for today only
        $response = $this->actingAs($this->admin)->get(route('reports.sales', [
            'date_from' => $today,
            'date_to' => $today,
        ]));
        $response->assertOk();
        $this->assertEquals(1000.00, $response->viewData('netSalesTotal'));

        // Filter for yesterday only (expect 0)
        $responseYesterday = $this->actingAs($this->admin)->get(route('reports.sales', [
            'date_from' => $yesterday,
            'date_to' => $yesterday,
        ]));
        $responseYesterday->assertOk();
        $this->assertEquals(0.00, $responseYesterday->viewData('netSalesTotal'));
    }

    public function test_audit_service_sanitizes_compound_secret_keys_and_nested_objects(): void
    {
        /** @var AuditService $auditService */
        $auditService = app(AuditService::class);

        $dirtyData = [
            'current_password' => 'Pass1234!',
            'user_secret' => 'SecretValue',
            'client_api_key' => 'Key123',
            'card_num_cvv' => '999',
            'normal_field' => 'NormalValue',
            'nested' => (object) [
                'access_token' => 'tok_abc123',
                'safe_info' => 'InfoValue',
            ],
        ];

        $sanitized = $auditService->sanitize($dirtyData);

        $this->assertArrayNotHasKey('current_password', $sanitized);
        $this->assertArrayNotHasKey('user_secret', $sanitized);
        $this->assertArrayNotHasKey('client_api_key', $sanitized);
        $this->assertArrayNotHasKey('card_num_cvv', $sanitized);
        $this->assertEquals('NormalValue', $sanitized['normal_field']);

        $this->assertIsArray($sanitized['nested']);
        $this->assertArrayNotHasKey('access_token', $sanitized['nested']);
        $this->assertEquals('InfoValue', $sanitized['nested']['safe_info']);
    }

    public function test_audit_service_truncates_long_description_safely(): void
    {
        /** @var AuditService $auditService */
        $auditService = app(AuditService::class);

        $longDescription = str_repeat('بيان توضيحي لعملية ملغاة مفصلة ', 25); // > 500 chars
        $this->assertGreaterThan(500, mb_strlen($longDescription));

        $log = $auditService->record('sale.voided', null, $longDescription);

        $this->assertNotNull($log->id);
        $this->assertLessThanOrEqual(500, mb_strlen($log->description));
    }

    public function test_credit_limit_alert_handles_floating_point_boundary_accurately(): void
    {
        // Customer with boundary floating limit
        $customer = Customer::create([
            'code' => 'CUST-BOUNDARY',
            'name' => 'عميل نقطة التعادل',
            'company_name' => 'شركة التعادل',
            'credit_limit' => 19.90,
            'balance' => 16.915, // 16.915 is >= (19.90 * 0.85 = 16.915)
            'is_active' => true,
        ]);

        /** @var AlertService $alertService */
        $alertService = app(AlertService::class);
        $alerts = $alertService->getCreditLimitAlerts();

        $this->assertTrue($alerts->contains('customer_id', $customer->id));
    }

    public function test_sales_report_includes_inactive_warehouses_and_customers_in_filter_lists(): void
    {
        $inactiveWh = Warehouse::create([
            'code' => 'WH-OLD',
            'name' => 'المستودع القديم المؤرشف',
            'location' => 'مكة',
            'is_active' => false,
        ]);

        $inactiveCust = Customer::create([
            'code' => 'CUST-OLD',
            'name' => 'العميل الموقوف المؤرشف',
            'credit_limit' => 5000.00,
            'balance' => 0.00,
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->admin)->get(route('reports.sales'));
        $response->assertOk();

        $warehouses = $response->viewData('warehouses');
        $customers = $response->viewData('customers');

        $this->assertTrue($warehouses->contains('id', $inactiveWh->id));
        $this->assertTrue($customers->contains('id', $inactiveCust->id));
    }
}
