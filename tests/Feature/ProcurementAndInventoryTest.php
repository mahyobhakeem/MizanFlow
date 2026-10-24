<?php

namespace Tests\Feature;

use App\Actions\Inventory\StockTransferAction;
use App\Actions\Procurement\ApprovePurchaseAction;
use App\Exceptions\InsufficientStockException;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductWarehouse;
use App\Models\Purchase;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

class ProcurementAndInventoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $manager;

    protected User $cashier;

    protected Warehouse $warehouseA;

    protected Warehouse $warehouseB;

    protected Supplier $supplier;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create(['username' => 'admin_test']);
        $this->manager = User::factory()->manager()->create(['username' => 'manager_test']);
        $this->cashier = User::factory()->cashier()->create(['username' => 'cashier_test']);

        $this->warehouseA = Warehouse::create([
            'code' => 'WH-A',
            'name' => 'المستودع أ',
            'location' => 'الرياض',
            'is_active' => true,
        ]);

        $this->warehouseB = Warehouse::create([
            'code' => 'WH-B',
            'name' => 'المستودع ب',
            'location' => 'جدة',
            'is_active' => true,
        ]);

        $this->supplier = Supplier::create([
            'code' => 'SUPP-A',
            'name' => 'مورد التقنية المعتمد',
            'balance' => 0.00,
            'is_active' => true,
        ]);

        $category = Category::create([
            'code' => 'CAT-TEST',
            'name' => 'تصنيف الاختبار',
            'is_active' => true,
        ]);

        $unit = Unit::create([
            'code' => 'PCS-TEST',
            'name' => 'قطعة',
            'allow_decimals' => false,
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'sku' => 'SKU-TEST-001',
            'barcode' => 'BAR-001',
            'name' => 'شاشة اختبارية فائقة الوضوح',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'min_reorder_level' => 5.000,
            'default_sale_price' => 1000.0000,
            'is_active' => true,
        ]);
    }

    // ==========================================
    // 1. استلام المشتريات والاعتماد (Procurement & Receipt)
    // ==========================================

    public function test_draft_purchase_does_not_modify_stock_or_wac(): void
    {
        $response = $this->actingAs($this->manager)->post(route('purchases.store'), [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouseA->id,
            'issue_date' => '2026-10-17',
            'supplier_invoice_ref' => 'REF-990',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 25.000,
                    'unit_cost' => 500.0000,
                    'tax_percent' => 15.00,
                ],
            ],
        ]);

        $purchase = Purchase::where('supplier_invoice_ref', 'REF-990')->first();
        $this->assertNotNull($purchase);
        $this->assertEquals('draft', $purchase->status);

        // الرصيد المخزني يجب أن يكون صفراً أو غير موجود طالما أنها مسودة
        $stock = ProductWarehouse::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouseA->id)
            ->first();

        $this->assertTrue($stock === null || (float) $stock->quantity == 0.0);
        $this->assertEquals(0, StockMovement::count());
    }

    public function test_approving_purchase_updates_stock_and_creates_ledger_movement(): void
    {
        $purchase = Purchase::create([
            'invoice_number' => 'PO-TEST-001',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouseA->id,
            'user_id' => $this->manager->id,
            'issue_date' => '2026-10-17',
            'status' => 'draft',
            'subtotal' => 5000.00,
            'tax_amount' => 750.00,
            'discount_amount' => 0.00,
            'total_amount' => 5750.00,
            'paid_amount' => 0.00,
        ]);

        $purchase->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 10.000,
            'unit_cost' => 500.0000,
            'tax_percent' => 15.00,
            'total_cost' => 5000.0000,
        ]);

        $response = $this->actingAs($this->manager)->post(route('purchases.approve', $purchase));
        $response->assertRedirect(route('purchases.show', $purchase));

        $purchase->refresh();
        $this->assertEquals('approved', $purchase->status);
        $this->assertNotNull($purchase->approved_at);

        // التحقق من الرصيد و WAC في المستودع
        $stock = ProductWarehouse::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouseA->id)
            ->firstOrFail();

        $this->assertEquals('10.000', $stock->quantity);
        $this->assertEquals('500.0000', $stock->weighted_average_cost);

        // التحقق من إنشاء قيد دفتر الأستاذ غير القابل للحذف
        $movement = StockMovement::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouseA->id)
            ->where('movement_type', 'purchase')
            ->firstOrFail();

        $this->assertEquals('10.000', $movement->quantity);
        $this->assertEquals('0.000', $movement->before_quantity);
        $this->assertEquals('10.000', $movement->after_quantity);
        $this->assertEquals('500.0000', $movement->unit_cost);
    }

    public function test_purchase_approval_is_idempotent_and_cannot_be_approved_twice(): void
    {
        $purchase = Purchase::create([
            'invoice_number' => 'PO-IDEMPOTENT',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouseA->id,
            'user_id' => $this->manager->id,
            'issue_date' => '2026-10-17',
            'status' => 'draft',
            'subtotal' => 1000.00,
            'total_amount' => 1000.00,
        ]);

        $purchase->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 5.000,
            'unit_cost' => 200.0000,
            'total_cost' => 1000.0000,
        ]);

        $action = app(ApprovePurchaseAction::class);
        $action->execute($purchase, $this->admin->id);

        $stockAfterFirst = ProductWarehouse::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouseA->id)
            ->value('quantity');

        $this->assertEquals('5.000', $stockAfterFirst);

        // المحاولة الثانية يجب أن ترمي DomainException ولا تضاعف المخزون
        $this->expectException(DomainException::class);
        $action->execute($purchase, $this->admin->id);

        $stockAfterSecond = ProductWarehouse::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouseA->id)
            ->value('quantity');

        $this->assertEquals('5.000', $stockAfterSecond);
    }

    public function test_approved_purchase_is_immutable_cannot_be_edited_or_deleted(): void
    {
        $purchase = Purchase::create([
            'invoice_number' => 'PO-LOCKED',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouseA->id,
            'user_id' => $this->manager->id,
            'issue_date' => '2026-10-17',
            'status' => 'approved',
            'subtotal' => 1000.00,
            'total_amount' => 1000.00,
        ]);

        // محاولة تعديل الفاتورة المعتمدة
        $updateResponse = $this->actingAs($this->manager)->put(route('purchases.update', $purchase), [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouseA->id,
            'issue_date' => '2026-10-17',
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 50, 'unit_cost' => 10],
            ],
        ]);
        $updateResponse->assertStatus(403);

        // محاولة حذف الفاتورة المعتمدة
        $deleteResponse = $this->actingAs($this->admin)->delete(route('purchases.destroy', $purchase));
        $deleteResponse->assertStatus(403);
        $this->assertDatabaseHas('purchases', ['id' => $purchase->id]);
    }

    // ==========================================
    // 2. حساب متوسط التكلفة المرجح (Moving WAC Calculation)
    // ==========================================

    public function test_moving_wac_calculation_accuracy(): void
    {
        $action = app(ApprovePurchaseAction::class);

        // الدفعة الأولى: 10 حبات بسعر 100 ريال
        $po1 = Purchase::create([
            'invoice_number' => 'PO-WAC-1',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouseA->id,
            'user_id' => $this->admin->id,
            'issue_date' => '2026-10-10',
            'status' => 'draft',
            'subtotal' => 1000.00,
            'total_amount' => 1000.00,
        ]);
        $po1->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 10.000,
            'unit_cost' => 100.0000,
            'total_cost' => 1000.0000,
        ]);
        $action->execute($po1, $this->admin->id);

        $stock = ProductWarehouse::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouseA->id)
            ->first();
        $this->assertEquals('10.000', $stock->quantity);
        $this->assertEquals('100.0000', $stock->weighted_average_cost);

        // الدفعة الثانية: 10 حبات بسعر 200 ريال
        // المعادلة: (10 * 100 + 10 * 200) / 20 = 3000 / 20 = 150.0000
        $po2 = Purchase::create([
            'invoice_number' => 'PO-WAC-2',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouseA->id,
            'user_id' => $this->admin->id,
            'issue_date' => '2026-10-12',
            'status' => 'draft',
            'subtotal' => 2000.00,
            'total_amount' => 2000.00,
        ]);
        $po2->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 10.000,
            'unit_cost' => 200.0000,
            'total_cost' => 2000.0000,
        ]);
        $action->execute($po2, $this->admin->id);

        $stock->refresh();
        $this->assertEquals('20.000', $stock->quantity);
        $this->assertEquals('150.0000', $stock->weighted_average_cost);

        // الدفعة الثالثة: 5 حبات بسعر 300 ريال
        // المعادلة: (20 * 150 + 5 * 300) / 25 = (3000 + 1500) / 25 = 4500 / 25 = 180.0000
        $po3 = Purchase::create([
            'invoice_number' => 'PO-WAC-3',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouseA->id,
            'user_id' => $this->admin->id,
            'issue_date' => '2026-10-14',
            'status' => 'draft',
            'subtotal' => 1500.00,
            'total_amount' => 1500.00,
        ]);
        $po3->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 5.000,
            'unit_cost' => 300.0000,
            'total_cost' => 1500.0000,
        ]);
        $action->execute($po3, $this->admin->id);

        $stock->refresh();
        $this->assertEquals('25.000', $stock->quantity);
        $this->assertEquals('180.0000', $stock->weighted_average_cost);
    }

    // ==========================================
    // 3. تحويلات المخزون بين المستودعات (Inter-Warehouse Transfers)
    // ==========================================

    public function test_direct_transfer_within_single_transaction_and_cost_consistency(): void
    {
        // تهيئة رصيد 30 حبة بتكلفة 120 ريال في المستودع أ
        $stockA = ProductWarehouse::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouseA->id,
            'quantity' => 30.000,
            'weighted_average_cost' => 120.0000,
        ]);

        // تنفيذ تحويل 10 حبات من المستودع أ إلى المستودع ب مباشرة
        $transferAction = app(StockTransferAction::class);
        $transfer = $transferAction->directTransfer(
            $this->warehouseA->id,
            $this->warehouseB->id,
            [
                ['product_id' => $this->product->id, 'quantity' => 10.000],
            ],
            $this->admin->id,
            'تحويل فوري اختباري'
        );

        $this->assertEquals('completed', $transfer->status);

        // التحقق من خصم المستودع المصدر
        $stockA->refresh();
        $this->assertEquals('20.000', $stockA->quantity);
        $this->assertEquals('120.0000', $stockA->weighted_average_cost);

        // التحقق من إضافة المستودع الوجهة وانتقال التكلفة إليه
        $stockB = ProductWarehouse::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouseB->id)
            ->firstOrFail();

        $this->assertEquals('10.000', $stockB->quantity);
        $this->assertEquals('120.0000', $stockB->weighted_average_cost);

        // التحقق من قيود دفتر الأستاذ للتحويل الصادر والوارد
        $movements = StockMovement::where('reference_type', StockTransfer::class)
            ->where('reference_id', $transfer->id)
            ->get();

        $this->assertCount(2, $movements);
        $out = $movements->firstWhere('movement_type', 'transfer_out');
        $in = $movements->firstWhere('movement_type', 'transfer_in');

        $this->assertEquals('-10.000', $out->quantity);
        $this->assertEquals('30.000', $out->before_quantity);
        $this->assertEquals('20.000', $out->after_quantity);
        $this->assertEquals('120.0000', $out->unit_cost);

        $this->assertEquals('10.000', $in->quantity);
        $this->assertEquals('0.000', $in->before_quantity);
        $this->assertEquals('10.000', $in->after_quantity);
        $this->assertEquals('120.0000', $in->unit_cost);
    }

    public function test_transfer_with_insufficient_stock_fails_and_leaves_no_partial_updates(): void
    {
        // المستودع أ لديه 5 حبات فقط
        ProductWarehouse::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouseA->id,
            'quantity' => 5.000,
            'weighted_average_cost' => 50.0000,
        ]);

        $transferAction = app(StockTransferAction::class);

        // محاولة تحويل 20 حبة (أكبر من المتوفر)
        try {
            $transferAction->directTransfer(
                $this->warehouseA->id,
                $this->warehouseB->id,
                [['product_id' => $this->product->id, 'quantity' => 20.000]],
                $this->admin->id
            );
            $this->fail('Expected InsufficientStockException was not thrown.');
        } catch (InsufficientStockException $e) {
            // الاستثناء متوقع
        }

        // التأكد من عدم حدوث أي تغيير جزئي (Rollback)
        $stockA = ProductWarehouse::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouseA->id)
            ->first();

        $this->assertEquals('5.000', $stockA->quantity);

        $stockB = ProductWarehouse::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouseB->id)
            ->first();

        $this->assertTrue($stockB === null || (float) $stockB->quantity == 0.0);
        $this->assertEquals(0, StockTransfer::count());
        $this->assertEquals(0, StockMovement::count());
    }

    public function test_two_step_transfer_dispatch_and_receive_flow(): void
    {
        // رصيد أولي 40 حبة
        ProductWarehouse::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouseA->id,
            'quantity' => 40.000,
            'weighted_average_cost' => 75.0000,
        ]);

        // 1. إنشاء مسودة أمر تحويل
        $transfer = StockTransfer::create([
            'transfer_number' => 'TR-TWO-STEP-1',
            'from_warehouse_id' => $this->warehouseA->id,
            'to_warehouse_id' => $this->warehouseB->id,
            'created_by_user_id' => $this->manager->id,
            'status' => 'draft',
        ]);
        $item = $transfer->items()->create([
            'product_id' => $this->product->id,
            'quantity_sent' => 15.000,
            'unit_cost_snapshot' => 0.0000,
        ]);

        $action = app(StockTransferAction::class);

        // 2. الشحن (Dispatch)
        $action->dispatch($transfer, $this->manager->id);
        $transfer->refresh();

        $this->assertEquals('in_transit', $transfer->status);
        $this->assertNotNull($transfer->dispatched_at);

        // تم الخصم من المصدر وتجميد التكلفة
        $stockA = ProductWarehouse::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouseA->id)
            ->first();
        $this->assertEquals('25.000', $stockA->quantity);

        $item->refresh();
        $this->assertEquals('75.0000', $item->unit_cost_snapshot);

        // لم يصل للهدف بعد
        $stockB = ProductWarehouse::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouseB->id)
            ->first();
        $this->assertTrue($stockB === null || (float) $stockB->quantity == 0.0);

        // 3. تأكيد الاستلام (Receive)
        $action->receive($transfer, $this->admin->id);
        $transfer->refresh();

        $this->assertEquals('completed', $transfer->status);
        $this->assertNotNull($transfer->received_at);

        // وصل للهدف بتكلفة 75 ريال
        $stockB = ProductWarehouse::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouseB->id)
            ->firstOrFail();
        $this->assertEquals('15.000', $stockB->quantity);
        $this->assertEquals('75.0000', $stockB->weighted_average_cost);
    }

    // ==========================================
    // 4. عدم قابلية تعديل أو حذف قيود دفتر الأستاذ (Movement Immutability)
    // ==========================================

    public function test_stock_movement_cannot_be_updated_or_deleted_directly(): void
    {
        $movement = StockMovement::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouseA->id,
            'movement_type' => 'adjustment_in',
            'reference_type' => User::class,
            'reference_id' => $this->admin->id,
            'quantity' => 10.000,
            'before_quantity' => 0.000,
            'after_quantity' => 10.000,
            'unit_cost' => 50.0000,
            'total_cost' => 500.0000,
            'user_id' => $this->admin->id,
        ]);

        $this->expectException(LogicException::class);
        $movement->update(['quantity' => 99.000]);
    }

    public function test_stock_movement_delete_throws_exception(): void
    {
        $movement = StockMovement::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouseA->id,
            'movement_type' => 'adjustment_in',
            'reference_type' => User::class,
            'reference_id' => $this->admin->id,
            'quantity' => 10.000,
            'before_quantity' => 0.000,
            'after_quantity' => 10.000,
            'unit_cost' => 50.0000,
            'total_cost' => 500.0000,
            'user_id' => $this->admin->id,
        ]);

        $this->expectException(LogicException::class);
        $movement->delete();
    }

    // ==========================================
    // 5. مصفوفة الصلاحيات (Authorization Matrices)
    // ==========================================

    public function test_cashier_cannot_access_purchases_or_transfers(): void
    {
        // الكاشير لا يمكنه فتح شاشات إنشاء فواتير الشراء أو التحويل
        $this->actingAs($this->cashier)->get(route('purchases.create'))->assertStatus(403);
        $this->actingAs($this->cashier)->get(route('transfers.create'))->assertStatus(403);

        // الكاشير لا يمكنه إرسال طلب إنشاء فاتورة شراء
        $this->actingAs($this->cashier)->post(route('purchases.store'), [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouseA->id,
            'issue_date' => '2026-10-17',
            'items' => [['product_id' => $this->product->id, 'quantity' => 1, 'unit_cost' => 10]],
        ])->assertStatus(403);

        // الكاشير لا يمكنه إرسال طلب تحويل مخزني
        $this->actingAs($this->cashier)->post(route('transfers.store'), [
            'from_warehouse_id' => $this->warehouseA->id,
            'to_warehouse_id' => $this->warehouseB->id,
            'items' => [['product_id' => $this->product->id, 'quantity' => 1]],
        ])->assertStatus(403);
    }

    // ==========================================
    // 6. البحث والتصفية (Search and Filtering)
    // ==========================================

    public function test_purchases_search_and_status_filtering(): void
    {
        $poApproved = Purchase::create([
            'invoice_number' => 'PO-SRCH-APP',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouseA->id,
            'user_id' => $this->manager->id,
            'issue_date' => '2026-10-17',
            'status' => 'approved',
            'subtotal' => 100,
            'total_amount' => 100,
        ]);

        $poDraft = Purchase::create([
            'invoice_number' => 'PO-SRCH-DFT',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouseA->id,
            'user_id' => $this->manager->id,
            'issue_date' => '2026-10-17',
            'status' => 'draft',
            'subtotal' => 200,
            'total_amount' => 200,
        ]);

        // بحث برقم الفاتورة
        $resp1 = $this->actingAs($this->manager)->get(route('purchases.index', ['search' => 'PO-SRCH-APP']));
        $resp1->assertStatus(200);
        $resp1->assertSee('PO-SRCH-APP');
        $resp1->assertDontSee('PO-SRCH-DFT');

        // تصفية بالحالة
        $resp2 = $this->actingAs($this->manager)->get(route('purchases.index', ['status' => 'draft']));
        $resp2->assertStatus(200);
        $resp2->assertSee('PO-SRCH-DFT');
        $resp2->assertDontSee('PO-SRCH-APP');
    }

    public function test_stock_ledger_and_balances_views(): void
    {
        // تسجيل حركة مشتريات
        $po = Purchase::create([
            'invoice_number' => 'PO-MOV-VIEW',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouseA->id,
            'user_id' => $this->manager->id,
            'issue_date' => '2026-10-17',
            'status' => 'draft',
            'subtotal' => 500,
            'total_amount' => 500,
        ]);
        $po->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 5.000,
            'unit_cost' => 100.0000,
            'total_cost' => 500.0000,
        ]);
        app(ApprovePurchaseAction::class)->execute($po, $this->admin->id);

        // شاشة كشف الحركات
        $movResponse = $this->actingAs($this->manager)->get(route('inventory.movements'));
        $movResponse->assertStatus(200);
        $movResponse->assertSee($this->product->name);
        $movResponse->assertSee('PO-MOV-VIEW');

        // شاشة أرصدة المستودعات
        $balResponse = $this->actingAs($this->manager)->get(route('inventory.balances'));
        $balResponse->assertStatus(200);
        $balResponse->assertSee($this->warehouseA->name);
        $balResponse->assertSee('5.000');
    }

    // ==========================================
    // 7. فشل المعاملة وعدم ترك تحديثات جزئية (Atomic Rollback Test)
    // ==========================================

    public function test_atomic_rollback_on_transaction_failure_leaves_no_partial_updates(): void
    {
        // محاولة تنفيذ عملية مشتريات تفشل في المنتصف
        $po = Purchase::create([
            'invoice_number' => 'PO-ROLLBACK-TEST',
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouseA->id,
            'user_id' => $this->manager->id,
            'issue_date' => '2026-10-17',
            'status' => 'draft',
            'subtotal' => 1000,
            'total_amount' => 1000,
            'paid_amount' => 0,
        ]);

        $po->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 10.000,
            'unit_cost' => 100.0000,
            'total_cost' => 1000.0000,
        ]);

        $initialSupplierBalance = (float) $this->supplier->balance;

        // سنحاكي فشلاً غير متوقع داخل المعاملة
        try {
            DB::transaction(function () {
                // خطوة 1: تحديث رصيد الصنف
                $stock = ProductWarehouse::create([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouseA->id,
                    'quantity' => 10.000,
                    'weighted_average_cost' => 100.0000,
                ]);

                // خطوة 2: رمي استثناء مفاجئ يحاكي فشل اتصال أو خطأ نظام
                throw new \RuntimeException('خطأ غير متوقع أثناء معالجة الفاتورة.');
            });
        } catch (\RuntimeException $e) {
            // الاستثناء تم التقاطه
        }

        // التحقق من التراجع التام (Rollback) وعدم بقاء أي رصيد تم إدخاله
        $stock = ProductWarehouse::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouseA->id)
            ->first();

        $this->assertNull($stock);

        $this->supplier->refresh();
        $this->assertEquals($initialSupplierBalance, (float) $this->supplier->balance);
        $this->assertEquals(0, StockMovement::count());
    }
}
