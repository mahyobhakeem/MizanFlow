<?php

namespace Database\Seeders;

use App\Actions\Inventory\StockTransferAction;
use App\Actions\Procurement\ApprovePurchaseAction;
use App\Actions\Sales\CompleteSaleAction;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\StockTransfer;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds for reproducible master data.
     */
    public function run(): void
    {
        // 1. المستودعات (Warehouses)
        $whMain = Warehouse::updateOrCreate(
            ['code' => 'WH-MAIN'],
            [
                'name' => 'المستودع الرئيسي',
                'location' => 'الرياض - المنطقة الصناعية الثانية',
                'manager_name' => 'سعد المنصور',
                'phone' => '0112345678',
                'is_active' => true,
            ]
        );

        $whNorth = Warehouse::updateOrCreate(
            ['code' => 'WH-NORTH'],
            [
                'name' => 'مستودع فرع الشمال',
                'location' => 'الرياض - طريق الملك عبد العزيز، حي الملقا',
                'manager_name' => 'عبد الله القحطاني',
                'phone' => '0118765432',
                'is_active' => true,
            ]
        );

        $whEast = Warehouse::updateOrCreate(
            ['code' => 'WH-EAST'],
            [
                'name' => 'مستودع فرع الشرقية',
                'location' => 'الدمام - حي الشاطئ',
                'manager_name' => 'فهد الدوسري',
                'phone' => '0138901234',
                'is_active' => true,
            ]
        );

        // ربط المستودع الافتراضي بالمستخدمين
        User::where('username', 'manager')->update(['default_warehouse_id' => $whMain->id]);
        User::where('username', 'cashier')->update(['default_warehouse_id' => $whMain->id]);

        // 2. وحدات القياس (Units)
        $unitPcs = Unit::updateOrCreate(
            ['code' => 'PCS'],
            [
                'name' => 'حبة',
                'allow_decimals' => false,
                'is_active' => true,
            ]
        );

        $unitBox = Unit::updateOrCreate(
            ['code' => 'BOX'],
            [
                'name' => 'كرتون',
                'allow_decimals' => false,
                'is_active' => true,
            ]
        );

        $unitKg = Unit::updateOrCreate(
            ['code' => 'KG'],
            [
                'name' => 'كيلوجرام',
                'allow_decimals' => true,
                'is_active' => true,
            ]
        );

        $unitMtr = Unit::updateOrCreate(
            ['code' => 'MTR'],
            [
                'name' => 'متر',
                'allow_decimals' => true,
                'is_active' => true,
            ]
        );

        $unitSet = Unit::updateOrCreate(
            ['code' => 'SET'],
            [
                'name' => 'طقم / عبوة',
                'allow_decimals' => false,
                'is_active' => true,
            ]
        );

        // 3. التصنيفات (Categories)
        $catElec = Category::updateOrCreate(
            ['code' => 'CAT-ELEC'],
            [
                'name' => 'الإلكترونيات والملحقات',
                'description' => 'الأجهزة الإلكترونية، الشاشات، الكابلات، والملحقات التقنية',
                'parent_id' => null,
                'is_active' => true,
            ]
        );

        $catElecPC = Category::updateOrCreate(
            ['code' => 'CAT-ELEC-PC'],
            [
                'name' => 'الشاشات وأجهزة الحاسوب',
                'description' => 'شاشات العرض المكتبية وملحقات الحواسيب',
                'parent_id' => $catElec->id,
                'is_active' => true,
            ]
        );

        $catFood = Category::updateOrCreate(
            ['code' => 'CAT-FOOD'],
            [
                'name' => 'المواد الغذائية والتموينية',
                'description' => 'المواد التموينية الاستهلاكية والمعلبات والزيوت',
                'parent_id' => null,
                'is_active' => true,
            ]
        );

        $catOffice = Category::updateOrCreate(
            ['code' => 'CAT-OFFICE'],
            [
                'name' => 'الأدوات المكتبية والقرطاسية',
                'description' => 'أوراق الطباعة ومستلزمات المكاتب والقرطاسية',
                'parent_id' => null,
                'is_active' => true,
            ]
        );

        // 4. المنتجات (Products)
        Product::updateOrCreate(
            ['sku' => 'PRD-SCR-27'],
            [
                'barcode' => '628100100001',
                'name' => 'شاشة سامسونج 27 بوصة IPS FHD',
                'description' => 'شاشة عرض بدقة Full HD مع معدل تحديث 75Hz ومنافذ HDMI',
                'category_id' => $catElecPC->id,
                'unit_id' => $unitPcs->id,
                'min_reorder_level' => 5.000,
                'default_sale_price' => 750.0000,
                'is_active' => true,
            ]
        );

        Product::updateOrCreate(
            ['sku' => 'PRD-CAB-HDMI'],
            [
                'barcode' => '628100100002',
                'name' => 'كابل HDMI فائق السرعة 2 متر',
                'description' => 'يدعم دقة 4K مع رؤوس مطلية بالذهب ومقاومة للتآكل',
                'category_id' => $catElec->id,
                'unit_id' => $unitPcs->id,
                'min_reorder_level' => 15.000,
                'default_sale_price' => 35.0000,
                'is_active' => true,
            ]
        );

        Product::updateOrCreate(
            ['sku' => 'PRD-OIL-1L'],
            [
                'barcode' => '628100200001',
                'name' => 'زيت زيتون بكر ممتاز 1 لتر',
                'description' => 'عصرة أولى على البارد، إنتاج الجوف',
                'category_id' => $catFood->id,
                'unit_id' => $unitPcs->id,
                'min_reorder_level' => 20.000,
                'default_sale_price' => 45.0000,
                'is_active' => true,
            ]
        );

        Product::updateOrCreate(
            ['sku' => 'PRD-RICE-10KG'],
            [
                'barcode' => '628100200002',
                'name' => 'أرز بسمتي هندي عنبر فاخر 10 كجم',
                'description' => 'أرز طويل الحبة معتق كرتون 10 كجم',
                'category_id' => $catFood->id,
                'unit_id' => $unitBox->id,
                'min_reorder_level' => 10.000,
                'default_sale_price' => 85.0000,
                'is_active' => true,
            ]
        );

        Product::updateOrCreate(
            ['sku' => 'PRD-PPR-A4'],
            [
                'barcode' => '628100300001',
                'name' => 'كرتون ورق تصوير A4 80 جم (5 رزم)',
                'description' => 'ورق أبيض عالي النقاوة، 5 رزم بمجموع 2500 ورقة للكرتون',
                'category_id' => $catOffice->id,
                'unit_id' => $unitBox->id,
                'min_reorder_level' => 25.000,
                'default_sale_price' => 95.0000,
                'is_active' => true,
            ]
        );

        // 5. العملاء (Customers)
        Customer::updateOrCreate(
            ['code' => 'CUST-001'],
            [
                'name' => 'شركة الأفق للحلول الرقمية',
                'company_name' => 'شركة الأفق ذ.م.م',
                'phone' => '0501112233',
                'email' => 'contact@alofooq.test',
                'tax_number' => '310123456700003',
                'credit_limit' => 10000.0000,
                'balance' => 0.0000,
                'address' => 'الرياض - حي العليا',
                'is_active' => true,
            ]
        );

        Customer::updateOrCreate(
            ['code' => 'CUST-002'],
            [
                'name' => 'مؤسسة النماء التجارية',
                'company_name' => 'مؤسسة النماء لتجارة الجملة',
                'phone' => '0554445566',
                'email' => 'info@alnamaa.test',
                'tax_number' => '310987654300003',
                'credit_limit' => 25000.0000,
                'balance' => 0.0000,
                'address' => 'جدة - حي الرويس',
                'is_active' => true,
            ]
        );

        Customer::updateOrCreate(
            ['code' => 'CUST-CASH'],
            [
                'name' => 'عميل نقدي عام',
                'company_name' => null,
                'phone' => null,
                'email' => null,
                'tax_number' => null,
                'credit_limit' => 0.0000,
                'balance' => 0.0000,
                'address' => 'مبيعات الكاشير الفورية',
                'is_active' => true,
            ]
        );

        // 6. الموردون (Suppliers)
        $supp1 = Supplier::updateOrCreate(
            ['code' => 'SUPP-001'],
            [
                'name' => 'شركة التوريدات التقنية المحدودة',
                'company_name' => 'شركة التوريدات التقنية ذ.م.م',
                'phone' => '0119988776',
                'email' => 'techsupplies@test.com',
                'tax_number' => '300123456700003',
                'address' => 'الرياض - حي السليمانية',
                'balance' => 0.0000,
                'is_active' => true,
            ]
        );

        $supp2 = Supplier::updateOrCreate(
            ['code' => 'SUPP-002'],
            [
                'name' => 'مؤسسة المحاصيل الحديثة للأغذية',
                'company_name' => 'مؤسسة المحاصيل الحديثة',
                'phone' => '0127766554',
                'email' => 'crops@test.com',
                'tax_number' => '300987654300003',
                'address' => 'جدة - المنطقة الصناعية الأولى',
                'balance' => 0.0000,
                'is_active' => true,
            ]
        );

        // 7. فواتير المشتريات وحركات المخزون الابتدائية (Purchases & Stock Ledger)
        $adminUser = User::where('username', 'admin')->first();
        $mgrUser = User::where('username', 'manager')->first();
        $pScr = Product::where('sku', 'PRD-SCR-27')->first();
        $pCab = Product::where('sku', 'PRD-CAB-HDMI')->first();
        $pOil = Product::where('sku', 'PRD-OIL-1L')->first();
        $pRice = Product::where('sku', 'PRD-RICE-10KG')->first();

        $approveAction = app(ApprovePurchaseAction::class);
        $transferAction = app(StockTransferAction::class);

        // فاتورة مشتريات معتمدة 1: إلكترونيات للمستودع الرئيسي
        $po1 = Purchase::firstOrCreate(
            ['invoice_number' => 'PO-2026-000001'],
            [
                'supplier_invoice_ref' => 'SUP-INV-8891',
                'supplier_id' => $supp1->id,
                'warehouse_id' => $whMain->id,
                'user_id' => $mgrUser->id,
                'issue_date' => now()->subDays(5)->format('Y-m-d'),
                'status' => 'draft',
                'subtotal' => 13000.00,
                'tax_amount' => 1950.00,
                'discount_amount' => 0.00,
                'total_amount' => 14950.00,
                'paid_amount' => 14950.00,
                'notes' => 'توريد شاشات وكابلات معتمد',
            ]
        );

        if ($po1->wasRecentlyCreated) {
            $po1->items()->createMany([
                [
                    'product_id' => $pScr->id,
                    'quantity' => 20.000,
                    'unit_cost' => 600.0000,
                    'tax_percent' => 15.00,
                    'discount_amount' => 0.00,
                    'total_cost' => 12000.0000,
                ],
                [
                    'product_id' => $pCab->id,
                    'quantity' => 50.000,
                    'unit_cost' => 20.0000,
                    'tax_percent' => 15.00,
                    'discount_amount' => 0.00,
                    'total_cost' => 1000.0000,
                ],
            ]);

            $approveAction->execute($po1, $adminUser->id);
        }

        // فاتورة مشتريات معتمدة 2: مواد غذائية للمستودع الرئيسي
        $po2 = Purchase::firstOrCreate(
            ['invoice_number' => 'PO-2026-000002'],
            [
                'supplier_invoice_ref' => 'SUP-AGR-4421',
                'supplier_id' => $supp2->id,
                'warehouse_id' => $whMain->id,
                'user_id' => $mgrUser->id,
                'issue_date' => now()->subDays(3)->format('Y-m-d'),
                'status' => 'draft',
                'subtotal' => 8400.00,
                'tax_amount' => 1260.00,
                'discount_amount' => 100.00,
                'total_amount' => 9560.00,
                'paid_amount' => 5000.00,
                'notes' => 'دفعة زيوت وأرز تمويني',
            ]
        );

        if ($po2->wasRecentlyCreated) {
            $po2->items()->createMany([
                [
                    'product_id' => $pOil->id,
                    'quantity' => 100.000,
                    'unit_cost' => 32.0000,
                    'tax_percent' => 15.00,
                    'discount_amount' => 0.00,
                    'total_cost' => 3200.0000,
                ],
                [
                    'product_id' => $pRice->id,
                    'quantity' => 80.000,
                    'unit_cost' => 65.0000,
                    'tax_percent' => 15.00,
                    'discount_amount' => 0.00,
                    'total_cost' => 5200.0000,
                ],
            ]);

            $approveAction->execute($po2, $adminUser->id);
        }

        // فاتورة مشتريات مسودة (Draft) - لا تؤثر على المخزون إطلاقاً
        $poDraft = Purchase::firstOrCreate(
            ['invoice_number' => 'PO-2026-000003'],
            [
                'supplier_invoice_ref' => 'QUOTE-2026-99',
                'supplier_id' => $supp1->id,
                'warehouse_id' => $whNorth->id,
                'user_id' => $mgrUser->id,
                'issue_date' => now()->format('Y-m-d'),
                'status' => 'draft',
                'subtotal' => 6000.00,
                'tax_amount' => 900.00,
                'discount_amount' => 0.00,
                'total_amount' => 6900.00,
                'paid_amount' => 0.00,
                'notes' => 'مسودة أمر شراء قيد التدقيق والموافقة',
            ]
        );

        if ($poDraft->wasRecentlyCreated) {
            $poDraft->items()->create([
                'product_id' => $pScr->id,
                'quantity' => 10.000,
                'unit_cost' => 600.0000,
                'tax_percent' => 15.00,
                'discount_amount' => 0.00,
                'total_cost' => 6000.0000,
            ]);
        }

        // 8. تحويل مخزني فوري بين المستودعات (Inter-Warehouse Stock Transfer)
        if (! StockTransfer::where('transfer_number', 'TR-2026-000001')->exists()) {
            $transferAction->directTransfer(
                $whMain->id,
                $whNorth->id,
                [
                    ['product_id' => $pScr->id, 'quantity' => 5.000],
                    ['product_id' => $pCab->id, 'quantity' => 10.000],
                ],
                $adminUser->id,
                'تحويل تغذية أولية لفرع الشمال'
            );
        }

        // 9. فواتير المبيعات ونقاط البيع (Sales & POS Invoices)
        $cashierUser = User::where('username', 'cashier')->first() ?? $adminUser;
        $cust1 = Customer::where('code', 'CUST-001')->first();
        $cust2 = Customer::where('code', 'CUST-002')->first();
        $saleAction = app(CompleteSaleAction::class);

        // فاتورة مبيعات معتمدة 1: بيع نقدي فوري في نقطة البيع (POS)
        $sale1 = Sale::firstOrCreate(
            ['invoice_number' => 'INV-2026-000001'],
            [
                'customer_id' => null, // عميل نقدي عابر
                'warehouse_id' => $whMain->id,
                'user_id' => $cashierUser->id,
                'invoice_date' => now()->subDays(2),
                'payment_type' => 'cash',
                'status' => 'draft',
                'subtotal' => 1805.00,
                'tax_amount' => 270.75,
                'discount_amount' => 0.00,
                'total_amount' => 2075.75,
                'paid_amount' => 2075.75,
                'notes' => 'مبيعات نقطة البيع رقم 1 - عميل نقدي',
            ]
        );

        if ($sale1->wasRecentlyCreated) {
            $sale1->items()->createMany([
                [
                    'product_id' => $pScr->id,
                    'quantity' => 2.000,
                    'unit_price' => 850.0000,
                    'unit_cost_snapshot' => 0.0000,
                    'tax_percent' => 15.00,
                    'discount_amount' => 0.00,
                    'total_price' => 1700.0000,
                ],
                [
                    'product_id' => $pCab->id,
                    'quantity' => 3.000,
                    'unit_price' => 35.0000,
                    'unit_cost_snapshot' => 0.0000,
                    'tax_percent' => 15.00,
                    'discount_amount' => 0.00,
                    'total_price' => 105.0000,
                ],
            ]);

            $saleAction->execute($sale1, $cashierUser->id);
        }

        // فاتورة مبيعات معتمدة 2: بيع آجل لعميل تجاري مع فحص سقف الائتمان
        $sale2 = Sale::firstOrCreate(
            ['invoice_number' => 'INV-2026-000002'],
            [
                'customer_id' => $cust1->id,
                'warehouse_id' => $whMain->id,
                'user_id' => $cashierUser->id,
                'invoice_date' => now()->subDay(),
                'payment_type' => 'credit',
                'status' => 'draft',
                'subtotal' => 450.00,
                'tax_amount' => 67.50,
                'discount_amount' => 0.00,
                'total_amount' => 517.50,
                'paid_amount' => 0.00,
                'notes' => 'أمر توريد وتوريد آجل لشركة التقنية المتقدمة',
            ]
        );

        if ($sale2->wasRecentlyCreated) {
            $sale2->items()->create([
                'product_id' => $pRice->id,
                'quantity' => 5.000,
                'unit_price' => 90.0000,
                'unit_cost_snapshot' => 0.0000,
                'tax_percent' => 15.00,
                'discount_amount' => 0.00,
                'total_price' => 450.0000,
            ]);

            $saleAction->execute($sale2, $cashierUser->id);
        }

        // فاتورة مبيعات مسودة 3: عرض أسعار / طلب بيع غير معتمد (لا يؤثر على المخزون)
        $saleDraft = Sale::firstOrCreate(
            ['invoice_number' => 'INV-2026-000003'],
            [
                'customer_id' => $cust2->id,
                'warehouse_id' => $whNorth->id,
                'user_id' => $cashierUser->id,
                'invoice_date' => now(),
                'payment_type' => 'credit',
                'status' => 'draft',
                'subtotal' => 880.00,
                'tax_amount' => 132.00,
                'discount_amount' => 0.00,
                'total_amount' => 1012.00,
                'paid_amount' => 0.00,
                'notes' => 'مسودة عرض أسعار بيع قيد مراجعة العميل',
            ]
        );

        if ($saleDraft->wasRecentlyCreated) {
            $saleDraft->items()->create([
                'product_id' => $pScr->id,
                'quantity' => 1.000,
                'unit_price' => 880.0000,
                'unit_cost_snapshot' => 0.0000,
                'tax_percent' => 15.00,
                'discount_amount' => 0.00,
                'total_price' => 880.0000,
            ]);
        }
    }
}
