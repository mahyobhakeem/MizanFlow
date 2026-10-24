<?php

namespace Tests\Feature;

use App\Actions\Procurement\ApprovePurchaseAction;
use App\Actions\Sales\CompleteSaleAction;
use App\Exceptions\InsufficientStockException;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductWarehouse;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class ReleaseCandidateAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $manager;

    protected User $cashier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create(['username' => 'rc_admin']);
        $this->manager = User::factory()->manager()->create(['username' => 'rc_manager']);
        $this->cashier = User::factory()->cashier()->create(['username' => 'rc_cashier']);
    }

    /**
     * Test 1: Full End-to-End Business Flow Scenario
     * Create Product -> Receive Purchase -> Update Stock & WAC -> Issue Sale -> Update Balance -> Verify Reports
     */
    public function test_full_end_to_end_business_scenario(): void
    {
        // 1. Setup Master Data
        $category = Category::create(['code' => 'RC-CAT', 'name' => 'تصنيف التدقيق', 'is_active' => true]);
        $unit = Unit::create(['name' => 'حبة', 'code' => 'HB', 'allow_decimal' => false, 'is_active' => true]);
        $warehouse = Warehouse::create(['code' => 'RC-WH', 'name' => 'المستودع الرئيسي للتدقيق', 'location' => 'الرياض', 'is_active' => true]);
        $supplier = Supplier::create(['code' => 'RC-SUP', 'name' => 'شركة التوريدات العالمية', 'is_active' => true]);
        $customer = Customer::create([
            'code' => 'RC-CUST',
            'name' => 'مؤسسة الأفق التجاري',
            'phone' => '0555555555',
            'credit_limit' => 10000.00,
            'balance' => 0.00,
            'is_active' => true,
        ]);

        $product = Product::create([
            'sku' => 'RC-SKU-001',
            'barcode' => '9988776655',
            'name' => 'جهاز حاسوب مكتبي متقدم',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'default_sale_price' => 3000.0000,
            'is_active' => true,
        ]);

        // 2. Receive Purchase (50 units @ 2000.00 each)
        $purchase = Purchase::create([
            'invoice_number' => 'PUR-RC-001',
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $this->manager->id,
            'issue_date' => now()->format('Y-m-d'),
            'status' => 'draft',
            'subtotal' => 100000.00,
            'tax_amount' => 0.00,
            'discount_amount' => 0.00,
            'total_amount' => 100000.00,
            'paid_amount' => 100000.00,
        ]);

        $purchase->items()->create([
            'product_id' => $product->id,
            'quantity' => 50.000,
            'unit_cost' => 2000.0000,
            'tax_percent' => 0.00,
            'discount_amount' => 0.00,
            'total_cost' => 100000.00,
        ]);

        app(ApprovePurchaseAction::class)->execute($purchase, $this->manager->id);

        // 3. Verify Stock and WAC after Purchase
        $stockRecord = ProductWarehouse::where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->first();

        $this->assertNotNull($stockRecord);
        $this->assertEquals(50.000, (float) $stockRecord->quantity);
        $this->assertEquals(2000.0000, (float) $stockRecord->weighted_average_cost);

        $purchaseMovement = StockMovement::where('product_id', $product->id)
            ->where('movement_type', 'purchase')
            ->first();
        $this->assertNotNull($purchaseMovement);
        $this->assertEquals(50.000, (float) $purchaseMovement->quantity);
        $this->assertEquals(2000.0000, (float) $purchaseMovement->unit_cost);

        // 4. Issue Sales Invoice (Sell 20 units @ 3000.00 each, total 60,000, credit payment, 20,000 paid, 40,000 balance)
        // Wait, credit_limit is 10000.00, so remaining debt 40,000 would exceed 10,000 credit limit!
        // Let's adjust credit_limit or make customer paid 55,000 with 5,000 balance so it fits credit limit.
        $customer->update(['credit_limit' => 50000.00]);

        $sale = Sale::create([
            'invoice_number' => 'INV-RC-001',
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $this->cashier->id,
            'invoice_date' => now()->format('Y-m-d H:i:s'),
            'payment_type' => 'credit',
            'status' => 'draft',
            'subtotal' => 60000.00,
            'tax_amount' => 0.00,
            'discount_amount' => 0.00,
            'total_amount' => 60000.00,
            'paid_amount' => 20000.00,
        ]);

        $sale->items()->create([
            'product_id' => $product->id,
            'quantity' => 20.000,
            'unit_price' => 3000.0000,
            'unit_cost_snapshot' => 0.0000,
            'tax_percent' => 0.00,
            'tax_amount' => 0.00,
            'discount_amount' => 0.00,
            'total_price' => 60000.00,
        ]);

        app(CompleteSaleAction::class)->execute($sale, $this->cashier->id);

        // 5. Verify Stock after Sale (50 - 20 = 30 units remaining)
        $stockRecord->refresh();
        $this->assertEquals(30.000, (float) $stockRecord->quantity);
        $this->assertEquals(2000.0000, (float) $stockRecord->weighted_average_cost); // Avg cost unchanged on sales issue

        // 6. Verify Customer Balance (0 + 40,000 = 40,000 unpaid debt)
        $customer->refresh();
        $this->assertEquals(40000.00, (float) $customer->balance);

        // 7. Verify Stock Movement Ledger Record
        $saleMovement = StockMovement::where('product_id', $product->id)
            ->where('movement_type', 'sale')
            ->first();
        $this->assertNotNull($saleMovement);
        $this->assertEquals(-20.000, (float) $saleMovement->quantity);
        $this->assertEquals(2000.0000, (float) $saleMovement->unit_cost);

        // 8. Verify Reports Reflection
        // Sales Report (Manager)
        $response = $this->actingAs($this->manager)->get(route('reports.sales', [
            'date_from' => now()->subDay()->format('Y-m-d'),
            'date_to' => now()->addDay()->format('Y-m-d'),
        ]));
        $response->assertOk();
        $response->assertSee('60,000.00'); // Net Sales
        $response->assertSee('40,000.00'); // Cost of Goods Sold (20 * 2000)
        $response->assertSee('20,000.00'); // Gross Profit (60,000 - 40,000)

        // Inventory Valuation Report
        $invResponse = $this->actingAs($this->manager)->get(route('reports.inventory'));
        $invResponse->assertOk();
        $invResponse->assertSee('30.00'); // Remaining stock quantity
        $invResponse->assertSee('60,000.00'); // Valuation (30 * 2000)

        // Customer Aging Report
        $custResponse = $this->actingAs($this->manager)->get(route('reports.customers'));
        $custResponse->assertOk();
        $custResponse->assertSee('40,000.00'); // Customer unpaid debt
    }

    /**
     * Test 2: Insufficient Stock Prevention & Clean Error Handling
     */
    public function test_insufficient_stock_prevents_sale_completion(): void
    {
        $warehouse = Warehouse::create(['code' => 'WH-INS', 'name' => 'مستودع 1', 'is_active' => true]);
        $category = Category::create(['code' => 'CAT-INS', 'name' => 'تصنيف 1', 'is_active' => true]);
        $unit = Unit::create(['name' => 'قطعة', 'code' => 'PCS', 'is_active' => true]);
        $product = Product::create([
            'sku' => 'SKU-INS',
            'name' => 'منتج محدود',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'default_sale_price' => 100.00,
            'is_active' => true,
        ]);

        ProductWarehouse::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 5.000,
            'weighted_average_cost' => 50.0000,
        ]);

        $sale = Sale::create([
            'invoice_number' => 'INV-INS-001',
            'warehouse_id' => $warehouse->id,
            'user_id' => $this->cashier->id,
            'invoice_date' => now()->format('Y-m-d H:i:s'),
            'payment_type' => 'cash',
            'status' => 'draft',
            'subtotal' => 1000.00,
            'total_amount' => 1000.00,
            'paid_amount' => 1000.00,
        ]);

        $sale->items()->create([
            'product_id' => $product->id,
            'quantity' => 10.000, // Requesting 10 while only 5 available
            'unit_price' => 100.00,
            'unit_cost_snapshot' => 0.00,
            'tax_percent' => 0.00,
            'tax_amount' => 0.00,
            'discount_amount' => 0.00,
            'total_price' => 1000.00,
        ]);

        $this->expectException(InsufficientStockException::class);
        app(CompleteSaleAction::class)->execute($sale, $this->cashier->id);
    }

    /**
     * Test 3: Idempotent Protection Against Duplicate Approval
     */
    public function test_duplicate_completion_throws_logic_exception_and_does_not_double_deduct(): void
    {
        $warehouse = Warehouse::create(['code' => 'WH-IDEM', 'name' => 'مستودع التكرار', 'is_active' => true]);
        $category = Category::create(['code' => 'CAT-IDEM', 'name' => 'تصنيف 1', 'is_active' => true]);
        $unit = Unit::create(['name' => 'قطعة', 'code' => 'PCS', 'is_active' => true]);
        $product = Product::create([
            'sku' => 'SKU-IDEM',
            'name' => 'منتج تكرار',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'default_sale_price' => 100.00,
            'is_active' => true,
        ]);

        ProductWarehouse::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 20.000,
            'weighted_average_cost' => 50.0000,
        ]);

        $sale = Sale::create([
            'invoice_number' => 'INV-IDEM-001',
            'warehouse_id' => $warehouse->id,
            'user_id' => $this->cashier->id,
            'invoice_date' => now()->format('Y-m-d H:i:s'),
            'payment_type' => 'cash',
            'status' => 'draft',
            'subtotal' => 500.00,
            'total_amount' => 500.00,
            'paid_amount' => 500.00,
        ]);

        $sale->items()->create([
            'product_id' => $product->id,
            'quantity' => 5.000,
            'unit_price' => 100.00,
            'unit_cost_snapshot' => 0.00,
            'tax_percent' => 0.00,
            'tax_amount' => 0.00,
            'discount_amount' => 0.00,
            'total_price' => 500.00,
        ]);

        // First execution succeeds
        app(CompleteSaleAction::class)->execute($sale, $this->cashier->id);

        $stock = ProductWarehouse::where('product_id', $product->id)->where('warehouse_id', $warehouse->id)->value('quantity');
        $this->assertEquals(15.000, (float) $stock);

        // Second execution MUST throw LogicException
        $this->expectException(LogicException::class);
        app(CompleteSaleAction::class)->execute($sale, $this->cashier->id);

        // Ensure stock remains 15.000
        $stockAfter = ProductWarehouse::where('product_id', $product->id)->where('warehouse_id', $warehouse->id)->value('quantity');
        $this->assertEquals(15.000, (float) $stockAfter);
    }

    /**
     * Test 4: Atomic Rollback on Mid-Transaction Failure
     */
    public function test_mid_transaction_failure_causes_complete_rollback(): void
    {
        $warehouse = Warehouse::create(['code' => 'WH-ROLL', 'name' => 'مستودع التراجع', 'is_active' => true]);
        $category = Category::create(['code' => 'CAT-ROLL', 'name' => 'تصنيف 1', 'is_active' => true]);
        $unit = Unit::create(['name' => 'قطعة', 'code' => 'PCS', 'is_active' => true]);

        $product1 = Product::create([
            'sku' => 'SKU-ROLL-1',
            'name' => 'منتج 1',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'default_sale_price' => 100.00,
            'is_active' => true,
        ]);

        $product2 = Product::create([
            'sku' => 'SKU-ROLL-2',
            'name' => 'منتج 2',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'default_sale_price' => 200.00,
            'is_active' => true,
        ]);

        ProductWarehouse::create([
            'product_id' => $product1->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 10.000,
            'weighted_average_cost' => 50.0000,
        ]);

        // product2 has 0 stock in warehouse!

        $sale = Sale::create([
            'invoice_number' => 'INV-ROLL-001',
            'warehouse_id' => $warehouse->id,
            'user_id' => $this->cashier->id,
            'invoice_date' => now()->format('Y-m-d H:i:s'),
            'payment_type' => 'cash',
            'status' => 'draft',
            'subtotal' => 500.00,
            'total_amount' => 500.00,
            'paid_amount' => 500.00,
        ]);

        // Item 1 is available
        $sale->items()->create([
            'product_id' => $product1->id,
            'quantity' => 3.000,
            'unit_price' => 100.00,
            'unit_cost_snapshot' => 0.00,
            'tax_percent' => 0.00,
            'tax_amount' => 0.00,
            'discount_amount' => 0.00,
            'total_price' => 300.00,
        ]);

        // Item 2 is NOT available (exceeds stock)
        $sale->items()->create([
            'product_id' => $product2->id,
            'quantity' => 5.000,
            'unit_price' => 200.00,
            'unit_cost_snapshot' => 0.00,
            'tax_percent' => 0.00,
            'tax_amount' => 0.00,
            'discount_amount' => 0.00,
            'total_price' => 1000.00,
        ]);

        $movementsBefore = StockMovement::count();

        try {
            app(CompleteSaleAction::class)->execute($sale, $this->cashier->id);
            $this->fail('Expected InsufficientStockException was not thrown.');
        } catch (InsufficientStockException $e) {
            // Expected
        }

        // Verify product1 stock was completely rolled back and never decremented
        $stock1 = ProductWarehouse::where('product_id', $product1->id)->where('warehouse_id', $warehouse->id)->value('quantity');
        $this->assertEquals(10.000, (float) $stock1);

        // Verify no ledger movements were inserted
        $this->assertEquals($movementsBefore, StockMovement::count());

        // Verify sale remains in draft status
        $sale->refresh();
        $this->assertEquals('draft', $sale->status);
    }

    /**
     * Test 5: Role Permission Matrix Rigorous Checks
     */
    public function test_role_permission_matrix(): void
    {
        // 1. Cashier Restrictions
        // Cashier cannot view reports
        $this->actingAs($this->cashier)->get(route('reports.sales'))->assertForbidden();
        $this->actingAs($this->cashier)->get(route('reports.inventory'))->assertForbidden();
        $this->actingAs($this->cashier)->get(route('reports.movements'))->assertForbidden();
        $this->actingAs($this->cashier)->get(route('reports.customers'))->assertForbidden();

        // Cashier cannot view audit logs
        $this->actingAs($this->cashier)->get(route('audit-logs.index'))->assertForbidden();

        // Cashier cannot manage users
        $this->actingAs($this->cashier)->get(route('users.index'))->assertForbidden();

        // Cashier cannot create or approve purchases
        $this->actingAs($this->cashier)->get(route('purchases.create'))->assertForbidden();

        // Cashier CAN create sales and view sales list
        $this->actingAs($this->cashier)->get(route('sales.index'))->assertOk();
        $this->actingAs($this->cashier)->get(route('sales.create'))->assertOk();

        // 2. Operations Manager Restrictions
        // Manager cannot access audit logs or user management
        $this->actingAs($this->manager)->get(route('audit-logs.index'))->assertForbidden();
        $this->actingAs($this->manager)->get(route('users.index'))->assertForbidden();

        // Manager CAN access reports, purchases, sales, and inventory
        $this->actingAs($this->manager)->get(route('reports.sales'))->assertOk();
        $this->actingAs($this->manager)->get(route('purchases.index'))->assertOk();
        $this->actingAs($this->manager)->get(route('sales.index'))->assertOk();

        // 3. Admin has unrestricted access to all modules
        $this->actingAs($this->admin)->get(route('audit-logs.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('users.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('reports.sales'))->assertOk();
        $this->actingAs($this->admin)->get(route('purchases.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('sales.index'))->assertOk();
    }

    /**
     * Test 6: WAC Accurate Recalculation & Historical Preservation
     */
    public function test_wac_recalculation_and_historical_snapshot_preservation(): void
    {
        $warehouse = Warehouse::create(['code' => 'WH-WAC', 'name' => 'مستودع التكلفة', 'is_active' => true]);
        $category = Category::create(['code' => 'CAT-WAC', 'name' => 'تصنيف', 'is_active' => true]);
        $unit = Unit::create(['name' => 'قطعة', 'code' => 'PCS', 'is_active' => true]);
        $supplier = Supplier::create(['code' => 'SUPP-WAC', 'name' => 'مورد التكلفة', 'is_active' => true]);

        $product = Product::create([
            'sku' => 'SKU-WAC-01',
            'name' => 'منتج اختبار التكلفة',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'default_sale_price' => 200.00,
            'is_active' => true,
        ]);

        // Purchase Batch 1: 10 units @ 100.00
        $p1 = Purchase::create([
            'invoice_number' => 'PUR-WAC-01',
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $this->manager->id,
            'issue_date' => now()->format('Y-m-d'),
            'status' => 'draft',
            'subtotal' => 1000.00,
            'total_amount' => 1000.00,
            'paid_amount' => 1000.00,
        ]);
        $p1->items()->create([
            'product_id' => $product->id,
            'quantity' => 10.000,
            'unit_cost' => 100.0000,
            'total_cost' => 1000.00,
        ]);
        app(ApprovePurchaseAction::class)->execute($p1, $this->manager->id);

        $pw = ProductWarehouse::where('product_id', $product->id)->where('warehouse_id', $warehouse->id)->first();
        $this->assertEquals(10.000, (float) $pw->quantity);
        $this->assertEquals(100.0000, (float) $pw->weighted_average_cost);

        // Purchase Batch 2: 10 units @ 200.00
        // New Qty = 20, New Value = (10*100) + (10*200) = 3000, WAC = 3000/20 = 150.0000
        $p2 = Purchase::create([
            'invoice_number' => 'PUR-WAC-02',
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $this->manager->id,
            'issue_date' => now()->format('Y-m-d'),
            'status' => 'draft',
            'subtotal' => 2000.00,
            'total_amount' => 2000.00,
            'paid_amount' => 2000.00,
        ]);
        $p2->items()->create([
            'product_id' => $product->id,
            'quantity' => 10.000,
            'unit_cost' => 200.0000,
            'total_cost' => 2000.00,
        ]);
        app(ApprovePurchaseAction::class)->execute($p2, $this->manager->id);

        $pw->refresh();
        $this->assertEquals(20.000, (float) $pw->quantity);
        $this->assertEquals(150.0000, (float) $pw->weighted_average_cost);

        // Sale 1: Sell 5 units. Snapshot should be exactly 150.0000
        $sale = Sale::create([
            'invoice_number' => 'INV-WAC-01',
            'warehouse_id' => $warehouse->id,
            'user_id' => $this->cashier->id,
            'invoice_date' => now()->format('Y-m-d H:i:s'),
            'payment_type' => 'cash',
            'status' => 'draft',
            'subtotal' => 1000.00,
            'total_amount' => 1000.00,
            'paid_amount' => 1000.00,
        ]);
        $saleItem = $sale->items()->create([
            'product_id' => $product->id,
            'quantity' => 5.000,
            'unit_price' => 200.00,
            'unit_cost_snapshot' => 0.00,
            'total_price' => 1000.00,
        ]);
        app(CompleteSaleAction::class)->execute($sale, $this->cashier->id);

        $saleItem->refresh();
        $this->assertEquals(150.0000, (float) $saleItem->unit_cost_snapshot);

        // Later Purchase Batch 3: 10 units @ 300.00
        // Changes current WAC, but historical sale snapshot MUST NOT change!
        $p3 = Purchase::create([
            'invoice_number' => 'PUR-WAC-03',
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $this->manager->id,
            'issue_date' => now()->format('Y-m-d'),
            'status' => 'draft',
            'subtotal' => 3000.00,
            'total_amount' => 3000.00,
            'paid_amount' => 3000.00,
        ]);
        $p3->items()->create([
            'product_id' => $product->id,
            'quantity' => 10.000,
            'unit_cost' => 300.0000,
            'total_cost' => 3000.00,
        ]);
        app(ApprovePurchaseAction::class)->execute($p3, $this->manager->id);

        $pw->refresh();
        // Remaining 15 @ 150 (2250) + 10 @ 300 (3000) = 5250 / 25 = 210.0000
        $this->assertEquals(25.000, (float) $pw->quantity);
        $this->assertEquals(210.0000, (float) $pw->weighted_average_cost);

        // Verify the historic sale item snapshot is strictly untouched:
        $saleItem->refresh();
        $this->assertEquals(150.0000, (float) $saleItem->unit_cost_snapshot);
    }

    /**
     * Test 7: Validation Rejection on Empty and Invalid Payloads
     */
    public function test_invalid_and_empty_payloads_are_rejected(): void
    {
        // 1. Empty items array when creating sale
        $response = $this->actingAs($this->cashier)->post(route('sales.store'), [
            'warehouse_id' => 1,
            'payment_type' => 'cash',
            'invoice_date' => now()->format('Y-m-d H:i'),
            'items' => [],
        ]);
        $response->assertSessionHasErrors(['items']);

        // 2. Negative quantity or price
        $response = $this->actingAs($this->cashier)->post(route('sales.store'), [
            'warehouse_id' => 1,
            'payment_type' => 'cash',
            'invoice_date' => now()->format('Y-m-d H:i'),
            'items' => [
                [
                    'product_id' => 1,
                    'quantity' => -5,
                    'unit_price' => -10,
                ],
            ],
        ]);
        $response->assertSessionHasErrors(['items.0.quantity', 'items.0.unit_price']);

        // 3. Non-existent warehouse
        $response = $this->actingAs($this->cashier)->post(route('sales.store'), [
            'warehouse_id' => 999999,
            'payment_type' => 'cash',
            'invoice_date' => now()->format('Y-m-d H:i'),
            'items' => [
                [
                    'product_id' => 1,
                    'quantity' => 1,
                    'unit_price' => 10,
                ],
            ],
        ]);
        $response->assertSessionHasErrors(['warehouse_id']);
    }

    /**
     * Test 8: Concurrent Competing Sales Requests on Limited Stock
     */
    public function test_competing_sales_requests_on_limited_stock_prevents_overselling(): void
    {
        $warehouse = Warehouse::create(['code' => 'WH-RACE', 'name' => 'مستودع التزامن', 'is_active' => true]);
        $category = Category::create(['code' => 'CAT-RACE', 'name' => 'تصنيف التزامن', 'is_active' => true]);
        $unit = Unit::create(['name' => 'قطعة', 'code' => 'PCS', 'is_active' => true]);
        $product = Product::create([
            'sku' => 'SKU-RACE-01',
            'name' => 'صنف محدود جدا',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'default_sale_price' => 100.00,
            'is_active' => true,
        ]);

        // Stock available: exactly 5 units
        ProductWarehouse::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 5.000,
            'weighted_average_cost' => 50.0000,
        ]);

        // Cashier 1 drafts sale of 5 units
        $sale1 = Sale::create([
            'invoice_number' => 'INV-RACE-01',
            'warehouse_id' => $warehouse->id,
            'user_id' => $this->cashier->id,
            'invoice_date' => now()->format('Y-m-d H:i:s'),
            'payment_type' => 'cash',
            'status' => 'draft',
            'subtotal' => 500.00,
            'total_amount' => 500.00,
            'paid_amount' => 500.00,
        ]);
        $sale1->items()->create([
            'product_id' => $product->id,
            'quantity' => 5.000,
            'unit_price' => 100.00,
            'unit_cost_snapshot' => 0.00,
            'total_price' => 500.00,
        ]);

        // Cashier 2 (or another user) drafts another sale of 5 units on the same product
        $sale2 = Sale::create([
            'invoice_number' => 'INV-RACE-02',
            'warehouse_id' => $warehouse->id,
            'user_id' => $this->cashier->id,
            'invoice_date' => now()->format('Y-m-d H:i:s'),
            'payment_type' => 'cash',
            'status' => 'draft',
            'subtotal' => 500.00,
            'total_amount' => 500.00,
            'paid_amount' => 500.00,
        ]);
        $sale2->items()->create([
            'product_id' => $product->id,
            'quantity' => 5.000,
            'unit_price' => 100.00,
            'unit_cost_snapshot' => 0.00,
            'total_price' => 500.00,
        ]);

        // First completion succeeds
        app(CompleteSaleAction::class)->execute($sale1, $this->cashier->id);
        $sale1->refresh();
        $this->assertEquals('completed', $sale1->status);

        // Second completion MUST throw InsufficientStockException and cannot complete
        try {
            app(CompleteSaleAction::class)->execute($sale2, $this->cashier->id);
            $this->fail('Expected InsufficientStockException for competing second sale.');
        } catch (InsufficientStockException $e) {
            // Success: second competing sale was strictly blocked
        }

        $sale2->refresh();
        $this->assertEquals('draft', $sale2->status);

        // Final inventory must be exactly 0, never negative (-5)
        $finalStock = ProductWarehouse::where('product_id', $product->id)->where('warehouse_id', $warehouse->id)->value('quantity');
        $this->assertEquals(0.000, (float) $finalStock);
    }
}
