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
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class SalesWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $manager;

    protected User $cashier;

    protected Warehouse $warehouse;

    protected Customer $customer;

    protected Product $productA;

    protected Product $productB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create(['username' => 'admin_test']);
        $this->manager = User::factory()->manager()->create(['username' => 'manager_test']);
        $this->cashier = User::factory()->cashier()->create(['username' => 'cashier_test']);

        $this->warehouse = Warehouse::create([
            'code' => 'WH-SALE',
            'name' => 'مستودع المبيعات الرئيسي',
            'location' => 'الرياض',
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'code' => 'CUST-TEST',
            'name' => 'مؤسسة الرياض التجارية',
            'company_name' => 'مؤسسة الرياض',
            'phone' => '0501234567',
            'credit_limit' => 3000.00,
            'balance' => 0.00,
            'is_active' => true,
        ]);

        $category = Category::create([
            'code' => 'CAT-SALE',
            'name' => 'تصنيف المبيعات',
            'is_active' => true,
        ]);

        $unit = Unit::create([
            'name' => 'قطعة',
            'code' => 'PCS',
            'allow_decimal' => false,
            'is_active' => true,
        ]);

        $this->productA = Product::create([
            'sku' => 'PRD-A',
            'barcode' => '111111',
            'name' => 'شاشة عرض ذكية',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'default_sale_price' => 150.0000,
            'is_active' => true,
        ]);

        $this->productB = Product::create([
            'sku' => 'PRD-B',
            'barcode' => '222222',
            'name' => 'سماعات رأس لاسلكية',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'default_sale_price' => 80.0000,
            'is_active' => true,
        ]);

        // Seed initial stock in warehouse via approved purchase
        $supplier = Supplier::create([
            'code' => 'SUPP-TEST',
            'name' => 'المورد الأول',
            'is_active' => true,
        ]);

        $purchase = Purchase::create([
            'invoice_number' => Purchase::generateInvoiceNumber(),
            'supplier_id' => $supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->admin->id,
            'issue_date' => now()->format('Y-m-d'),
            'status' => 'draft',
            'subtotal' => 14000.00,
            'tax_amount' => 0.00,
            'discount_amount' => 0.00,
            'total_amount' => 14000.00,
            'paid_amount' => 14000.00,
        ]);

        // 100 units of Product A @ 100.00
        $purchase->items()->create([
            'product_id' => $this->productA->id,
            'quantity' => 100.000,
            'unit_cost' => 100.0000,
            'tax_percent' => 0.00,
            'discount_amount' => 0.00,
            'total_cost' => 10000.0000,
        ]);

        // 50 units of Product B @ 50.00
        $purchase->items()->create([
            'product_id' => $this->productB->id,
            'quantity' => 50.000,
            'unit_cost' => 50.0000,
            'tax_percent' => 0.00,
            'discount_amount' => 0.00,
            'total_cost' => 2500.0000,
        ]);

        app(ApprovePurchaseAction::class)->execute($purchase, $this->admin->id);
    }

    /**
     * 1. Check authorization for viewing sales index.
     */
    public function test_all_roles_can_view_sales_index(): void
    {
        $this->actingAs($this->cashier)->get(route('sales.index'))->assertOk();
        $this->actingAs($this->manager)->get(route('sales.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('sales.index'))->assertOk();
    }

    /**
     * 2. Creating a draft sale invoice does NOT change stock or write ledger movements.
     */
    public function test_can_create_draft_sale_without_affecting_stock(): void
    {
        $initialStockA = ProductWarehouse::where('product_id', $this->productA->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->value('quantity');

        $initialMovements = StockMovement::count();

        $response = $this->actingAs($this->cashier)->post(route('sales.store'), [
            'warehouse_id' => $this->warehouse->id,
            'customer_id' => null,
            'invoice_date' => now()->format('Y-m-d H:i'),
            'payment_type' => 'cash',
            'complete_immediately' => '0',
            'discount_amount' => 0.00,
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'quantity' => 5.000,
                    'unit_price' => 150.0000,
                    'tax_percent' => 15.00,
                    'discount_amount' => 0.00,
                ],
            ],
        ]);

        $response->assertRedirect();

        $sale = Sale::latest('id')->first();
        $this->assertNotNull($sale);
        $this->assertTrue($sale->isDraft());
        $this->assertEquals('draft', $sale->status);
        $this->assertCount(1, $sale->items);

        // Stock quantity must remain strictly unchanged
        $afterStockA = ProductWarehouse::where('product_id', $this->productA->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->value('quantity');

        $this->assertEquals($initialStockA, $afterStockA);
        $this->assertEquals($initialMovements, StockMovement::count());
    }

    /**
     * 3. Draft sale can be edited and updated.
     */
    public function test_draft_sale_can_be_edited_and_updated(): void
    {
        $sale = Sale::create([
            'invoice_number' => Sale::generateInvoiceNumber(),
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->cashier->id,
            'invoice_date' => now(),
            'payment_type' => 'cash',
            'status' => 'draft',
            'subtotal' => 150.00,
            'tax_amount' => 22.50,
            'total_amount' => 172.50,
            'paid_amount' => 172.50,
        ]);

        $sale->items()->create([
            'product_id' => $this->productA->id,
            'quantity' => 1.000,
            'unit_price' => 150.0000,
            'unit_cost_snapshot' => 0.0000,
            'tax_percent' => 15.00,
            'discount_amount' => 0.00,
            'total_price' => 150.0000,
        ]);

        $response = $this->actingAs($this->cashier)->put(route('sales.update', $sale), [
            'warehouse_id' => $this->warehouse->id,
            'customer_id' => $this->customer->id,
            'invoice_date' => now()->format('Y-m-d H:i'),
            'payment_type' => 'card',
            'discount_amount' => 10.00,
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'quantity' => 2.000,
                    'unit_price' => 150.0000,
                    'tax_percent' => 15.00,
                    'discount_amount' => 0.00,
                ],
            ],
        ]);

        $response->assertRedirect(route('sales.show', $sale));

        $sale->refresh();
        $this->assertEquals($this->customer->id, $sale->customer_id);
        $this->assertEquals('card', $sale->payment_type);
        // Subtotal: 2 * 150 = 300. Tax: 300 * 15% = 45. Discount: 10. Net: 335.
        $this->assertEquals(300.00, (float) $sale->subtotal);
        $this->assertEquals(45.00, (float) $sale->tax_amount);
        $this->assertEquals(10.00, (float) $sale->discount_amount);
        $this->assertEquals(335.00, (float) $sale->total_amount);
    }

    public function test_cashier_can_update_and_complete_draft_sale_immediately(): void
    {
        $sale = Sale::create([
            'invoice_number' => Sale::generateInvoiceNumber(),
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->cashier->id,
            'invoice_date' => now(),
            'payment_type' => 'cash',
            'status' => 'draft',
            'subtotal' => 150.00,
            'tax_amount' => 22.50,
            'total_amount' => 172.50,
            'paid_amount' => 172.50,
        ]);

        $sale->items()->create([
            'product_id' => $this->productA->id,
            'quantity' => 1.000,
            'unit_price' => 150.0000,
            'unit_cost_snapshot' => 0.0000,
            'tax_percent' => 15.00,
            'discount_amount' => 0.00,
            'total_price' => 150.0000,
        ]);

        $response = $this->actingAs($this->cashier)->put(route('sales.update', $sale), [
            'warehouse_id' => $this->warehouse->id,
            'customer_id' => $this->customer->id,
            'invoice_date' => now()->format('Y-m-d H:i'),
            'payment_type' => 'cash',
            'complete_immediately' => '1',
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'quantity' => 2.000,
                    'unit_price' => 150.0000,
                    'tax_percent' => 15.00,
                    'discount_amount' => 0.00,
                ],
            ],
        ]);

        $response->assertRedirect(route('sales.show', $sale));

        $sale->refresh();
        $this->assertTrue($sale->isCompleted());
        $this->assertEquals(2, (float) $sale->items->first()->quantity);
    }

    /**
     * 4. Zero-trust: Server recomputes totals and completely ignores tampered client numbers.
     */
    public function test_server_side_calculation_ignores_tampered_client_totals(): void
    {
        // Attacker attempts to send total_amount = 1.00 and tax_amount = 0.00 in payload
        $response = $this->actingAs($this->cashier)->post(route('sales.store'), [
            'warehouse_id' => $this->warehouse->id,
            'customer_id' => null,
            'invoice_date' => now()->format('Y-m-d H:i'),
            'payment_type' => 'cash',
            'complete_immediately' => '0',
            'subtotal' => 1.00,
            'tax_amount' => 0.00,
            'total_amount' => 1.00,
            'items' => [
                [
                    'product_id' => $this->productA->id,
                    'quantity' => 10.000,
                    'unit_price' => 200.0000, // 10 * 200 = 2000.00
                    'tax_percent' => 15.00,   // 2000 * 15% = 300.00
                    'discount_amount' => 50.00, // net line = 1950.00, line tax = 292.50
                    'total_price' => 1.00,    // fake client line total
                ],
            ],
            'discount_amount' => 0.00,
        ]);

        $response->assertRedirect();

        $sale = Sale::latest('id')->first();
        // Server MUST recalculate:
        // Line subtotal = (10 * 200) - 50 = 1950.00
        // Line tax = 1950 * 15% = 292.50
        // Total = 1950 + 292.50 = 2242.50
        $this->assertEquals(1950.00, (float) $sale->subtotal);
        $this->assertEquals(292.50, (float) $sale->tax_amount);
        $this->assertEquals(2242.50, (float) $sale->total_amount);
    }

    /**
     * 5. Completing a sale decrements stock and writes historical WAC snapshot.
     */
    public function test_complete_sale_action_decrements_stock_and_records_wac_snapshot(): void
    {
        $sale = Sale::create([
            'invoice_number' => Sale::generateInvoiceNumber(),
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->cashier->id,
            'invoice_date' => now(),
            'payment_type' => 'cash',
            'status' => 'draft',
            'subtotal' => 1500.00,
            'tax_amount' => 225.00,
            'total_amount' => 1725.00,
            'paid_amount' => 1725.00,
        ]);

        $item = $sale->items()->create([
            'product_id' => $this->productA->id,
            'quantity' => 10.000,
            'unit_price' => 150.0000,
            'unit_cost_snapshot' => 0.0000,
            'tax_percent' => 15.00,
            'discount_amount' => 0.00,
            'total_price' => 1500.0000,
        ]);

        $stockBefore = ProductWarehouse::where('product_id', $this->productA->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();

        $this->assertEquals(100.000, (float) $stockBefore->quantity);
        $this->assertEquals(100.0000, (float) $stockBefore->weighted_average_cost);

        // Execute completion
        $completedSale = app(CompleteSaleAction::class)->execute($sale, $this->cashier->id);

        $this->assertTrue($completedSale->isCompleted());
        $this->assertEquals('completed', $completedSale->status);

        // Stock quantity must be decremented: 100 - 10 = 90
        $stockAfter = ProductWarehouse::where('product_id', $this->productA->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();

        $this->assertEquals(90.000, (float) $stockAfter->quantity);

        // Historical cost snapshot must be 100.0000
        $item->refresh();
        $this->assertEquals(100.0000, (float) $item->unit_cost_snapshot);
        $this->assertEquals(50.0000, $item->unitProfit()); // 150 - 100 = 50
        $this->assertEquals(500.00, $item->totalProfit()); // 50 * 10 = 500

        // Stock movement ledger entry verification
        $movement = StockMovement::where('movement_type', 'sale')
            ->where('reference_type', Sale::class)
            ->where('reference_id', $sale->id)
            ->first();

        $this->assertNotNull($movement);
        $this->assertEquals(-10.000, (float) $movement->quantity);
        $this->assertEquals(100.000, (float) $movement->before_quantity);
        $this->assertEquals(90.000, (float) $movement->after_quantity);
        $this->assertEquals(100.0000, (float) $movement->unit_cost);
    }

    /**
     * 6. Sale is rejected when stock is insufficient, preventing negative inventory.
     */
    public function test_cannot_complete_sale_when_stock_is_insufficient(): void
    {
        $sale = Sale::create([
            'invoice_number' => Sale::generateInvoiceNumber(),
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->cashier->id,
            'invoice_date' => now(),
            'payment_type' => 'cash',
            'status' => 'draft',
            'subtotal' => 30000.00,
            'tax_amount' => 4500.00,
            'total_amount' => 34500.00,
            'paid_amount' => 34500.00,
        ]);

        // Attempt to sell 200 units when only 100 are available in the warehouse
        $sale->items()->create([
            'product_id' => $this->productA->id,
            'quantity' => 200.000,
            'unit_price' => 150.0000,
            'unit_cost_snapshot' => 0.0000,
            'tax_percent' => 15.00,
            'discount_amount' => 0.00,
            'total_price' => 30000.0000,
        ]);

        $this->expectException(InsufficientStockException::class);

        app(CompleteSaleAction::class)->execute($sale, $this->cashier->id);

        $sale->refresh();
        $this->assertTrue($sale->isDraft());

        // Stock quantity must remain intact at 100
        $stock = ProductWarehouse::where('product_id', $this->productA->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->value('quantity');
        $this->assertEquals(100.000, (float) $stock);
    }

    /**
     * 7. Multi-item atomic rollback: If one item fails, no items are deducted.
     */
    public function test_atomic_rollback_on_multi_item_order_when_one_item_exceeds_stock(): void
    {
        $sale = Sale::create([
            'invoice_number' => Sale::generateInvoiceNumber(),
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->cashier->id,
            'invoice_date' => now(),
            'payment_type' => 'cash',
            'status' => 'draft',
            'subtotal' => 10000.00,
            'tax_amount' => 1500.00,
            'total_amount' => 11500.00,
            'paid_amount' => 11500.00,
        ]);

        // Item 1: Valid (10 units of Product A, 100 available)
        $sale->items()->create([
            'product_id' => $this->productA->id,
            'quantity' => 10.000,
            'unit_price' => 150.0000,
            'unit_cost_snapshot' => 0.0000,
            'tax_percent' => 15.00,
            'discount_amount' => 0.00,
            'total_price' => 1500.0000,
        ]);

        // Item 2: Invalid (100 units of Product B, only 50 available)
        $sale->items()->create([
            'product_id' => $this->productB->id,
            'quantity' => 100.000,
            'unit_price' => 80.0000,
            'unit_cost_snapshot' => 0.0000,
            'tax_percent' => 15.00,
            'discount_amount' => 0.00,
            'total_price' => 8000.0000,
        ]);

        try {
            app(CompleteSaleAction::class)->execute($sale, $this->cashier->id);
            $this->fail('Expected InsufficientStockException was not thrown.');
        } catch (InsufficientStockException $e) {
            // Expected
        }

        // Neither Product A nor Product B should have been deducted!
        $qtyA = ProductWarehouse::where('product_id', $this->productA->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->value('quantity');
        $qtyB = ProductWarehouse::where('product_id', $this->productB->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->value('quantity');

        $this->assertEquals(100.000, (float) $qtyA);
        $this->assertEquals(50.000, (float) $qtyB);

        // No movements recorded for this sale
        $this->assertEquals(0, $sale->movements()->count());
    }

    /**
     * 8. Idempotency: Duplicate approval requests are rejected without double-decrementing stock.
     */
    public function test_idempotency_prevents_duplicate_approval_and_double_decrement(): void
    {
        $sale = Sale::create([
            'invoice_number' => Sale::generateInvoiceNumber(),
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->cashier->id,
            'invoice_date' => now(),
            'payment_type' => 'cash',
            'status' => 'draft',
            'subtotal' => 150.00,
            'tax_amount' => 22.50,
            'total_amount' => 172.50,
            'paid_amount' => 172.50,
        ]);

        $sale->items()->create([
            'product_id' => $this->productA->id,
            'quantity' => 5.000,
            'unit_price' => 150.0000,
            'unit_cost_snapshot' => 0.0000,
            'tax_percent' => 15.00,
            'discount_amount' => 0.00,
            'total_price' => 150.0000,
        ]);

        // First completion succeeds
        app(CompleteSaleAction::class)->execute($sale, $this->cashier->id);

        $stockAfterFirst = ProductWarehouse::where('product_id', $this->productA->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->value('quantity');
        $this->assertEquals(95.000, (float) $stockAfterFirst);

        // Second completion MUST throw DomainException and NOT decrement stock again
        $this->expectException(DomainException::class);
        app(CompleteSaleAction::class)->execute($sale, $this->cashier->id);

        $stockAfterSecond = ProductWarehouse::where('product_id', $this->productA->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->value('quantity');
        $this->assertEquals(95.000, (float) $stockAfterSecond);
    }

    /**
     * 9. Credit sale increases customer balance and respects credit limit.
     */
    public function test_credit_sale_updates_customer_balance_and_enforces_credit_limit(): void
    {
        $this->assertEquals(0.00, (float) $this->customer->balance);
        $this->assertEquals(3000.00, (float) $this->customer->credit_limit);

        // Sale within credit limit: 10 units @ 150 = 1500 + 225 tax = 1725.00
        $sale = Sale::create([
            'invoice_number' => Sale::generateInvoiceNumber(),
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->cashier->id,
            'invoice_date' => now(),
            'payment_type' => 'credit',
            'status' => 'draft',
            'subtotal' => 1500.00,
            'tax_amount' => 225.00,
            'total_amount' => 1725.00,
            'paid_amount' => 0.00,
        ]);

        $sale->items()->create([
            'product_id' => $this->productA->id,
            'quantity' => 10.000,
            'unit_price' => 150.0000,
            'unit_cost_snapshot' => 0.0000,
            'tax_percent' => 15.00,
            'discount_amount' => 0.00,
            'total_price' => 1500.0000,
        ]);

        app(CompleteSaleAction::class)->execute($sale, $this->cashier->id);

        $this->customer->refresh();
        $this->assertEquals(1725.00, (float) $this->customer->balance);
    }

    /**
     * 10. Credit sale aborts if customer credit limit is exceeded.
     */
    public function test_credit_sale_aborts_if_credit_limit_is_exceeded(): void
    {
        // Customer limit is 3000. Attempt a sale of 4000.
        $sale = Sale::create([
            'invoice_number' => Sale::generateInvoiceNumber(),
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->cashier->id,
            'invoice_date' => now(),
            'payment_type' => 'credit',
            'status' => 'draft',
            'subtotal' => 4000.00,
            'tax_amount' => 0.00,
            'total_amount' => 4000.00,
            'paid_amount' => 0.00,
        ]);

        $sale->items()->create([
            'product_id' => $this->productA->id,
            'quantity' => 20.000,
            'unit_price' => 200.0000,
            'unit_cost_snapshot' => 0.0000,
            'tax_percent' => 0.00,
            'discount_amount' => 0.00,
            'total_price' => 4000.0000,
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('سقف الائتمان');

        app(CompleteSaleAction::class)->execute($sale, $this->cashier->id);

        $this->customer->refresh();
        $this->assertEquals(0.00, (float) $this->customer->balance);
    }

    /**
     * 11. Historical snapshots are immutable even after product prices or subsequent purchases change.
     */
    public function test_historical_snapshots_are_immutable_when_product_data_changes(): void
    {
        // 1. Sell at 150 when WAC is 100
        $sale = Sale::create([
            'invoice_number' => Sale::generateInvoiceNumber(),
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->cashier->id,
            'invoice_date' => now(),
            'payment_type' => 'cash',
            'status' => 'draft',
            'subtotal' => 150.00,
            'tax_amount' => 0.00,
            'total_amount' => 150.00,
            'paid_amount' => 150.00,
        ]);

        $item = $sale->items()->create([
            'product_id' => $this->productA->id,
            'quantity' => 1.000,
            'unit_price' => 150.0000,
            'unit_cost_snapshot' => 0.0000,
            'tax_percent' => 0.00,
            'discount_amount' => 0.00,
            'total_price' => 150.0000,
        ]);

        app(CompleteSaleAction::class)->execute($sale, $this->cashier->id);

        $item->refresh();
        $this->assertEquals(100.0000, (float) $item->unit_cost_snapshot);
        $this->assertEquals(50.0000, $item->unitProfit());

        // 2. Later: Change product default sale price to 300
        $this->productA->update(['default_sale_price' => 300.0000]);

        // 3. Later: Change warehouse WAC by receiving expensive stock at 250
        $stock = ProductWarehouse::where('product_id', $this->productA->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();
        $stock->weighted_average_cost = 250.0000;
        $stock->save();

        // 4. Verify historical sale item is 100% frozen
        $item->refresh();
        $this->assertEquals(150.0000, (float) $item->unit_price);
        $this->assertEquals(100.0000, (float) $item->unit_cost_snapshot);
        $this->assertEquals(50.0000, $item->unitProfit());
        $this->assertEquals(50.00, $item->totalProfit());
    }

    /**
     * 12. Completed sales cannot be directly deleted.
     */
    public function test_cannot_delete_completed_sale(): void
    {
        $sale = Sale::create([
            'invoice_number' => Sale::generateInvoiceNumber(),
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->cashier->id,
            'invoice_date' => now(),
            'payment_type' => 'cash',
            'status' => 'completed',
            'subtotal' => 150.00,
            'tax_amount' => 0.00,
            'total_amount' => 150.00,
            'paid_amount' => 150.00,
        ]);

        $this->expectException(LogicException::class);
        $sale->delete();
    }

    /**
     * 13. Draft sales can be deleted by admin.
     */
    public function test_draft_sale_can_be_deleted_by_admin(): void
    {
        $sale = Sale::create([
            'invoice_number' => Sale::generateInvoiceNumber(),
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->cashier->id,
            'invoice_date' => now(),
            'payment_type' => 'cash',
            'status' => 'draft',
            'subtotal' => 150.00,
            'tax_amount' => 0.00,
            'total_amount' => 150.00,
            'paid_amount' => 150.00,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('sales.destroy', $sale));
        $response->assertRedirect(route('sales.index'));

        $this->assertDatabaseMissing('sales', ['id' => $sale->id]);
    }

    /**
     * 14. Cashier cannot void a completed sale; only Manager and Admin can.
     */
    public function test_cashier_cannot_void_completed_sale(): void
    {
        $sale = Sale::create([
            'invoice_number' => Sale::generateInvoiceNumber(),
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->cashier->id,
            'invoice_date' => now(),
            'payment_type' => 'cash',
            'status' => 'completed',
            'subtotal' => 150.00,
            'tax_amount' => 0.00,
            'total_amount' => 150.00,
            'paid_amount' => 150.00,
        ]);

        $this->actingAs($this->cashier)
            ->post(route('sales.void', $sale), ['reason' => 'إلغاء غير مصرح'])
            ->assertForbidden();
    }

    /**
     * 15. Voiding a completed sale restores stock and writes reverse ledger movement ('sale_return').
     */
    public function test_manager_or_admin_can_void_completed_sale_and_restore_stock(): void
    {
        $sale = Sale::create([
            'invoice_number' => Sale::generateInvoiceNumber(),
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->cashier->id,
            'invoice_date' => now(),
            'payment_type' => 'credit',
            'status' => 'draft',
            'subtotal' => 1000.00,
            'tax_amount' => 150.00,
            'total_amount' => 1150.00,
            'paid_amount' => 0.00,
        ]);

        $sale->items()->create([
            'product_id' => $this->productA->id,
            'quantity' => 10.000,
            'unit_price' => 100.0000,
            'unit_cost_snapshot' => 0.0000,
            'tax_percent' => 15.00,
            'discount_amount' => 0.00,
            'total_price' => 1000.0000,
        ]);

        // Complete sale
        app(CompleteSaleAction::class)->execute($sale, $this->cashier->id);

        $stockAfterSale = ProductWarehouse::where('product_id', $this->productA->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->value('quantity');
        $this->assertEquals(90.000, (float) $stockAfterSale);

        $this->customer->refresh();
        $this->assertEquals(1150.00, (float) $this->customer->balance);

        // Void sale by manager
        $response = $this->actingAs($this->manager)->post(route('sales.void', $sale), [
            'reason' => 'إلغاء بطلب العميل واسترجاع البضاعة سليمة',
        ]);

        $response->assertRedirect(route('sales.show', $sale));

        $sale->refresh();
        $this->assertTrue($sale->isVoided());
        $this->assertEquals('voided', $sale->status);
        $this->assertEquals($this->manager->id, $sale->voided_by_user_id);
        $this->assertNotNull($sale->voided_at);

        // Stock must be restored: 90 + 10 = 100
        $stockAfterVoid = ProductWarehouse::where('product_id', $this->productA->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->value('quantity');
        $this->assertEquals(100.000, (float) $stockAfterVoid);

        // Reverse stock movement 'sale_return' recorded
        $returnMovement = StockMovement::where('movement_type', 'sale_return')
            ->where('reference_id', $sale->id)
            ->first();

        $this->assertNotNull($returnMovement);
        $this->assertEquals(10.000, (float) $returnMovement->quantity);
        $this->assertEquals(90.000, (float) $returnMovement->before_quantity);
        $this->assertEquals(100.000, (float) $returnMovement->after_quantity);

        // Customer credit balance must be reversed
        $this->customer->refresh();
        $this->assertEquals(0.00, (float) $this->customer->balance);
    }

    /**
     * 16. Customer with sales records cannot be deleted.
     */
    public function test_customer_with_sales_cannot_be_deleted(): void
    {
        $sale = Sale::create([
            'invoice_number' => Sale::generateInvoiceNumber(),
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->cashier->id,
            'invoice_date' => now(),
            'payment_type' => 'cash',
            'status' => 'completed',
            'subtotal' => 150.00,
            'tax_amount' => 0.00,
            'total_amount' => 150.00,
            'paid_amount' => 150.00,
        ]);

        $this->assertFalse($this->customer->canBeDeleted());

        $this->actingAs($this->admin)
            ->delete(route('customers.destroy', $this->customer))
            ->assertRedirect();

        $this->assertDatabaseHas('customers', ['id' => $this->customer->id, 'deleted_at' => null]);
    }

    /**
     * 17. Dedicated printable invoice renders with tax invoice header and details.
     */
    public function test_printable_invoice_renders_with_simplified_tax_invoice_details(): void
    {
        $sale = Sale::create([
            'invoice_number' => 'INV-2026-999999',
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->cashier->id,
            'invoice_date' => now(),
            'payment_type' => 'cash',
            'status' => 'completed',
            'subtotal' => 150.00,
            'tax_amount' => 22.50,
            'total_amount' => 172.50,
            'paid_amount' => 172.50,
        ]);

        $sale->items()->create([
            'product_id' => $this->productA->id,
            'quantity' => 1.000,
            'unit_price' => 150.0000,
            'unit_cost_snapshot' => 100.0000,
            'tax_percent' => 15.00,
            'discount_amount' => 0.00,
            'total_price' => 150.0000,
        ]);

        $response = $this->actingAs($this->cashier)->get(route('sales.print', $sale));

        $response->assertOk();
        $response->assertSee('فاتورة ضريبية مبسطة');
        $response->assertSee('INV-2026-999999');
        $response->assertSee($this->customer->name);
        $response->assertSee($this->productA->name);
        $response->assertSee('172.50');
    }

    /**
     * 18. Split payment sale increments customer balance by the remaining unpaid portion.
     */
    public function test_split_payment_sale_increments_customer_balance_by_remaining_unpaid_amount(): void
    {
        // Total = 172.50. Paid 100.00 cash. Unpaid = 72.50.
        $sale = Sale::create([
            'invoice_number' => Sale::generateInvoiceNumber(),
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->cashier->id,
            'invoice_date' => now(),
            'payment_type' => 'split',
            'status' => 'draft',
            'subtotal' => 150.00,
            'tax_amount' => 22.50,
            'total_amount' => 172.50,
            'paid_amount' => 100.00,
        ]);

        $sale->items()->create([
            'product_id' => $this->productA->id,
            'quantity' => 1.000,
            'unit_price' => 150.0000,
            'unit_cost_snapshot' => 0.0000,
            'tax_percent' => 15.00,
            'discount_amount' => 0.00,
            'total_price' => 150.0000,
        ]);

        app(CompleteSaleAction::class)->execute($sale, $this->cashier->id);

        $this->customer->refresh();
        $this->assertEquals(72.50, (float) $this->customer->balance);
    }
}
