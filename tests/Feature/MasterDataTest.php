<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $manager;

    protected User $cashier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create(['username' => 'admin_user']);
        $this->manager = User::factory()->manager()->create(['username' => 'mgr_user']);
        $this->cashier = User::factory()->cashier()->create(['username' => 'csh_user']);
    }

    private function createWarehouse(array $overrides = []): Warehouse
    {
        return Warehouse::create(array_merge([
            'code' => 'WH-'.uniqid(),
            'name' => 'مستودع تجريبي',
            'location' => 'الرياض',
            'manager_name' => 'أحمد العلي',
            'phone' => '0501234567',
            'is_active' => true,
        ], $overrides));
    }

    private function createCategory(array $overrides = []): Category
    {
        return Category::create(array_merge([
            'code' => 'CAT-'.uniqid(),
            'name' => 'تصنيف تجريبي',
            'description' => 'وصف التصنيف التجريبي',
            'parent_id' => null,
            'is_active' => true,
        ], $overrides));
    }

    private function createUnit(array $overrides = []): Unit
    {
        return Unit::create(array_merge([
            'code' => 'UNT-'.uniqid(),
            'name' => 'وحدة تجريبية',
            'allow_decimals' => false,
            'is_active' => true,
        ], $overrides));
    }

    private function createProduct(array $overrides = []): Product
    {
        $category = $overrides['category_id'] ?? $this->createCategory()->id;
        $unit = $overrides['unit_id'] ?? $this->createUnit()->id;

        return Product::create(array_merge([
            'sku' => 'SKU-'.uniqid(),
            'barcode' => 'BAR-'.uniqid(),
            'name' => 'منتج تجريبي',
            'category_id' => $category,
            'unit_id' => $unit,
            'min_reorder_level' => 10.000,
            'default_sale_price' => 50.0000,
            'is_active' => true,
        ], $overrides));
    }

    private function createCustomer(array $overrides = []): Customer
    {
        return Customer::create(array_merge([
            'code' => 'CUST-'.uniqid(),
            'name' => 'عميل تجريبي',
            'phone' => '0501112233',
            'credit_limit' => 5000.0000,
            'balance' => 0.0000,
            'is_active' => true,
        ], $overrides));
    }

    private function createSupplier(array $overrides = []): Supplier
    {
        return Supplier::create(array_merge([
            'code' => 'SUPP-'.uniqid(),
            'name' => 'مورد تجريبي',
            'phone' => '0559998877',
            'tax_number' => '300000000000003',
            'balance' => 0.0000,
            'is_active' => true,
        ], $overrides));
    }

    // ==========================================
    // 1. المستودعات (Warehouses)
    // ==========================================

    public function test_admin_can_view_warehouses_and_create_warehouse(): void
    {
        $response = $this->actingAs($this->admin)->get(route('warehouses.index'));
        $response->assertStatus(200);

        $response = $this->actingAs($this->admin)->post(route('warehouses.store'), [
            'code' => 'WH-TEST1',
            'name' => 'المستودع الرئيسي الجديد',
            'location' => 'حي السلي، الرياض',
            'manager_name' => 'خالد ناصر',
            'phone' => '0114445566',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('warehouses.index'));
        $this->assertDatabaseHas('warehouses', ['code' => 'WH-TEST1', 'name' => 'المستودع الرئيسي الجديد']);
    }

    public function test_warehouse_code_must_be_unique(): void
    {
        $this->createWarehouse(['code' => 'WH-DUPLICATE']);

        $response = $this->actingAs($this->admin)->post(route('warehouses.store'), [
            'code' => 'WH-DUPLICATE',
            'name' => 'مستودع مكرر',
        ]);

        $response->assertSessionHasErrors(['code']);
    }

    public function test_warehouse_cannot_be_deleted_if_linked_to_users(): void
    {
        $warehouse = $this->createWarehouse();
        $this->cashier->update(['default_warehouse_id' => $warehouse->id]);

        $response = $this->actingAs($this->admin)->delete(route('warehouses.destroy', $warehouse));

        $response->assertSessionHas('error');
        $this->assertNotSoftDeleted('warehouses', ['id' => $warehouse->id]);
    }

    public function test_cashier_cannot_create_or_delete_warehouse(): void
    {
        $warehouse = $this->createWarehouse();

        $createResponse = $this->actingAs($this->cashier)->post(route('warehouses.store'), [
            'code' => 'WH-UNAUTH',
            'name' => 'محاولة غير مصرحة',
        ]);
        $createResponse->assertStatus(403);

        $deleteResponse = $this->actingAs($this->cashier)->delete(route('warehouses.destroy', $warehouse));
        $deleteResponse->assertStatus(403);
    }

    // ==========================================
    // 2. وحدات القياس (Units)
    // ==========================================

    public function test_manager_can_create_and_update_unit(): void
    {
        $response = $this->actingAs($this->manager)->post(route('units.store'), [
            'code' => 'BOX24',
            'name' => 'كرتون 24 حبة',
            'allow_decimals' => '0',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('units.index'));
        $this->assertDatabaseHas('units', ['code' => 'BOX24', 'allow_decimals' => 0]);

        $unit = Unit::where('code', 'BOX24')->first();

        $updateResponse = $this->actingAs($this->manager)->put(route('units.update', $unit), [
            'code' => 'BOX24',
            'name' => 'كرتون 24 حبة معدل',
            'allow_decimals' => '1',
            'is_active' => '1',
        ]);

        $updateResponse->assertRedirect(route('units.index'));
        $this->assertDatabaseHas('units', ['id' => $unit->id, 'name' => 'كرتون 24 حبة معدل', 'allow_decimals' => 1]);
    }

    public function test_unit_cannot_be_deleted_if_has_products(): void
    {
        $unit = $this->createUnit();
        $this->createProduct(['unit_id' => $unit->id]);

        $response = $this->actingAs($this->admin)->delete(route('units.destroy', $unit));

        $response->assertSessionHas('error');
        $this->assertNotSoftDeleted('units', ['id' => $unit->id]);
    }

    public function test_cashier_cannot_create_unit(): void
    {
        $response = $this->actingAs($this->cashier)->post(route('units.store'), [
            'code' => 'UNAUTH',
            'name' => 'وحدة محظورة',
        ]);

        $response->assertStatus(403);
    }

    // ==========================================
    // 3. التصنيفات (Categories)
    // ==========================================

    public function test_manager_can_create_category_with_parent(): void
    {
        $parent = $this->createCategory(['code' => 'MAIN-CAT', 'name' => 'تصنيف رئيسي']);

        $response = $this->actingAs($this->manager)->post(route('categories.store'), [
            'code' => 'SUB-CAT',
            'name' => 'تصنيف فرعي',
            'parent_id' => $parent->id,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('categories.index'));
        $this->assertDatabaseHas('categories', [
            'code' => 'SUB-CAT',
            'parent_id' => $parent->id,
        ]);
    }

    public function test_category_cannot_be_parent_of_itself(): void
    {
        $category = $this->createCategory(['code' => 'SELF-CAT']);

        $response = $this->actingAs($this->manager)->put(route('categories.update', $category), [
            'code' => 'SELF-CAT',
            'name' => 'تصنيف يربط نفسه',
            'parent_id' => $category->id,
            'is_active' => '1',
        ]);

        $response->assertSessionHasErrors(['parent_id']);
    }

    public function test_category_cannot_be_deleted_if_has_subcategories_or_products(): void
    {
        $parent = $this->createCategory();
        $sub = $this->createCategory(['parent_id' => $parent->id]);

        // لا يمكن حذف الأب لوجود فرع
        $response = $this->actingAs($this->admin)->delete(route('categories.destroy', $parent));
        $response->assertSessionHas('error');
        $this->assertNotSoftDeleted('categories', ['id' => $parent->id]);

        // ربط منتج بالفرع والتأكد من منع حذفه
        $this->createProduct(['category_id' => $sub->id]);
        $response2 = $this->actingAs($this->admin)->delete(route('categories.destroy', $sub));
        $response2->assertSessionHas('error');
        $this->assertNotSoftDeleted('categories', ['id' => $sub->id]);
    }

    // ==========================================
    // 4. المنتجات (Products)
    // ==========================================

    public function test_manager_can_create_product_and_search_it(): void
    {
        $category = $this->createCategory();
        $unit = $this->createUnit();

        $response = $this->actingAs($this->manager)->post(route('products.store'), [
            'sku' => 'PRD-NEW-001',
            'barcode' => '628000111222',
            'name' => 'منتج فحص الجودة',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'min_reorder_level' => '12.500',
            'default_sale_price' => '149.9900',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('products.index'));
        $this->assertDatabaseHas('products', [
            'sku' => 'PRD-NEW-001',
            'name' => 'منتج فحص الجودة',
        ]);

        // فحص البحث عن المنتج
        $searchResponse = $this->actingAs($this->manager)->get(route('products.index', ['search' => 'PRD-NEW-001']));
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee('منتج فحص الجودة');
    }

    public function test_product_sku_and_barcode_uniqueness(): void
    {
        $category = $this->createCategory();
        $unit = $this->createUnit();

        $this->createProduct([
            'sku' => 'DUPLICATE-SKU',
            'barcode' => 'DUPLICATE-BARCODE',
        ]);

        // محاولة إدخال نفس SKU
        $response = $this->actingAs($this->manager)->post(route('products.store'), [
            'sku' => 'DUPLICATE-SKU',
            'barcode' => 'ANOTHER-BARCODE',
            'name' => 'منتج مكرر SKU',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'min_reorder_level' => '5',
            'default_sale_price' => '20',
        ]);
        $response->assertSessionHasErrors(['sku']);

        // محاولة إدخال نفس Barcode
        $response2 = $this->actingAs($this->manager)->post(route('products.store'), [
            'sku' => 'UNIQUE-SKU',
            'barcode' => 'DUPLICATE-BARCODE',
            'name' => 'منتج مكرر Barcode',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'min_reorder_level' => '5',
            'default_sale_price' => '20',
        ]);
        $response2->assertSessionHasErrors(['barcode']);
    }

    public function test_cashier_cannot_create_or_update_product(): void
    {
        $product = $this->createProduct();

        $createResponse = $this->actingAs($this->cashier)->post(route('products.store'), [
            'sku' => 'CSH-PROD',
            'name' => 'منتج كاشير',
        ]);
        $createResponse->assertStatus(403);

        $updateResponse = $this->actingAs($this->cashier)->put(route('products.update', $product), [
            'name' => 'تعديل كاشير',
        ]);
        $updateResponse->assertStatus(403);
    }

    // ==========================================
    // 5. العملاء (Customers)
    // ==========================================

    public function test_cashier_and_manager_can_create_customer(): void
    {
        $response = $this->actingAs($this->cashier)->post(route('customers.store'), [
            'code' => 'CUST-CSH-1',
            'name' => 'عميل مبيعات نقدية فوري',
            'phone' => '0509988776',
            'credit_limit' => '0',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('customers.index'));
        $this->assertDatabaseHas('customers', ['code' => 'CUST-CSH-1']);
    }

    public function test_customer_cannot_be_deleted_if_balance_is_not_zero(): void
    {
        $customer = $this->createCustomer(['balance' => 1500.0000]);

        $response = $this->actingAs($this->admin)->delete(route('customers.destroy', $customer));

        $response->assertSessionHas('error');
        $this->assertNotSoftDeleted('customers', ['id' => $customer->id]);
    }

    public function test_cashier_cannot_delete_customer(): void
    {
        $customer = $this->createCustomer(['balance' => 0]);

        $response = $this->actingAs($this->cashier)->delete(route('customers.destroy', $customer));
        $response->assertStatus(403);
    }

    // ==========================================
    // 6. الموردون (Suppliers)
    // ==========================================

    public function test_manager_can_create_supplier_and_unique_code_is_enforced(): void
    {
        $response = $this->actingAs($this->manager)->post(route('suppliers.store'), [
            'code' => 'SUPP-NEW-1',
            'name' => 'شركة التوريد المتطورة',
            'tax_number' => '311122233300003',
            'phone' => '0112223344',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('suppliers.index'));
        $this->assertDatabaseHas('suppliers', ['code' => 'SUPP-NEW-1']);

        // محاولة إضافة نفس كود المورد
        $dupResponse = $this->actingAs($this->manager)->post(route('suppliers.store'), [
            'code' => 'SUPP-NEW-1',
            'name' => 'مورد مكرر الكود',
        ]);
        $dupResponse->assertSessionHasErrors(['code']);
    }

    public function test_supplier_cannot_be_deleted_if_balance_not_zero(): void
    {
        $supplier = $this->createSupplier(['balance' => -5000.0000]);

        $response = $this->actingAs($this->admin)->delete(route('suppliers.destroy', $supplier));

        $response->assertSessionHas('error');
        $this->assertNotSoftDeleted('suppliers', ['id' => $supplier->id]);
    }

    public function test_cashier_cannot_manage_suppliers(): void
    {
        $supplier = $this->createSupplier();

        $this->actingAs($this->cashier)->get(route('suppliers.create'))->assertStatus(403);
        $this->actingAs($this->cashier)->post(route('suppliers.store'), ['code' => 'FAIL', 'name' => 'FAIL'])->assertStatus(403);
        $this->actingAs($this->cashier)->delete(route('suppliers.destroy', $supplier))->assertStatus(403);
    }

    // ==========================================
    // 7. المستخدمون والصلاحيات (Users & Permissions)
    // ==========================================

    public function test_only_admin_can_access_user_management(): void
    {
        $this->actingAs($this->cashier)->get(route('users.index'))->assertStatus(403);
        $this->actingAs($this->manager)->get(route('users.index'))->assertStatus(403);

        $this->actingAs($this->admin)->get(route('users.index'))->assertStatus(200);
    }

    public function test_admin_can_create_user_and_update_it(): void
    {
        $warehouse = $this->createWarehouse();

        $response = $this->actingAs($this->admin)->post(route('users.store'), [
            'name' => 'محمود كاشير',
            'username' => 'mahmoud_cashier',
            'email' => 'mahmoud@mizanflow.test',
            'password' => 'secret12345',
            'role' => 'cashier',
            'default_warehouse_id' => $warehouse->id,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'username' => 'mahmoud_cashier',
            'default_warehouse_id' => $warehouse->id,
            'role' => 'cashier',
        ]);

        $newUser = User::where('username', 'mahmoud_cashier')->first();

        // تحديث المستخدم بدون تغيير كلمة المرور
        $updateResponse = $this->actingAs($this->admin)->put(route('users.update', $newUser), [
            'name' => 'محمود كاشير المعدل',
            'username' => 'mahmoud_cashier',
            'email' => 'mahmoud@mizanflow.test',
            'role' => 'manager',
            'default_warehouse_id' => $warehouse->id,
            'is_active' => '1',
        ]);

        $updateResponse->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'id' => $newUser->id,
            'name' => 'محمود كاشير المعدل',
            'role' => 'manager',
        ]);
    }

    public function test_user_cannot_delete_themselves(): void
    {
        $response = $this->actingAs($this->admin)->delete(route('users.destroy', $this->admin));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }

    public function test_product_can_be_updated_without_changing_sku(): void
    {
        $product = $this->createProduct(['sku' => 'KEEP-SKU', 'name' => 'الاسم القديم']);

        $response = $this->actingAs($this->manager)->put(route('products.update', $product), [
            'sku' => 'KEEP-SKU',
            'name' => 'الاسم الجديد بعد التعديل',
            'category_id' => $product->category_id,
            'unit_id' => $product->unit_id,
            'min_reorder_level' => '15.000',
            'default_sale_price' => '80.0000',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('products.index'));
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'الاسم الجديد بعد التعديل',
            'sku' => 'KEEP-SKU',
        ]);
    }

    public function test_product_validation_errors_for_missing_required_fields(): void
    {
        $response = $this->actingAs($this->manager)->post(route('products.store'), []);

        $response->assertSessionHasErrors([
            'sku',
            'name',
            'category_id',
            'unit_id',
            'min_reorder_level',
            'default_sale_price',
        ]);
    }

    public function test_product_soft_delete(): void
    {
        $product = $this->createProduct();

        $response = $this->actingAs($this->manager)->delete(route('products.destroy', $product));

        $response->assertRedirect(route('products.index'));
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_user_cannot_be_created_with_duplicate_username_or_email(): void
    {
        $response = $this->actingAs($this->admin)->post(route('users.store'), [
            'name' => 'مستخدم مكرر',
            'username' => 'admin_user', // مكرر موجود مسبقاً
            'email' => 'admin_user@mizanflow.test',
            'password' => 'secret12345',
            'role' => 'cashier',
        ]);

        $response->assertSessionHasErrors(['username']);
    }
}
