<?php

namespace App\Http\Controllers;

use App\Actions\Procurement\ApprovePurchaseAction;
use App\Http\Requests\PurchaseRequest;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Warehouse;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Purchase::class);

        $query = Purchase::with(['supplier', 'warehouse', 'user'])
            ->latest('issue_date')
            ->latest('id');

        if ($request->filled('search')) {
            $query->search($request->input('search'));
        }

        if ($request->filled('status')) {
            $query->status($request->input('status'));
        }

        if ($request->filled('warehouse_id')) {
            $query->warehouse($request->integer('warehouse_id'));
        }

        $purchases = $query->paginate(15)->withQueryString();
        $warehouses = Warehouse::active()->orderBy('name')->get();

        return view('purchases.index', compact('purchases', 'warehouses'));
    }

    public function create(): View
    {
        Gate::authorize('create', Purchase::class);

        $suppliers = Supplier::active()->orderBy('name')->get();
        $warehouses = Warehouse::active()->orderBy('name')->get();
        $products = Product::active()->with('unit')->orderBy('name')->get();
        $defaultWarehouseId = auth()->user()->default_warehouse_id;

        return view('purchases.create', compact('suppliers', 'warehouses', 'products', 'defaultWarehouseId'));
    }

    public function store(PurchaseRequest $request, ApprovePurchaseAction $approveAction): RedirectResponse
    {
        Gate::authorize('create', Purchase::class);

        $data = $request->validated();
        $itemsData = $data['items'];

        $purchase = DB::transaction(function () use ($data, $itemsData) {
            $subtotal = 0.0;
            $taxAmount = 0.0;
            $itemsToCreate = [];

            foreach ($itemsData as $item) {
                $qty = (float) $item['quantity'];
                $unitCost = (float) $item['unit_cost'];
                $taxPercent = (float) ($item['tax_percent'] ?? 0.0);
                $lineTotal = round($qty * $unitCost, 4);
                $lineTax = round($lineTotal * ($taxPercent / 100), 2);

                $subtotal += $lineTotal;
                $taxAmount += $lineTax;

                $itemsToCreate[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $qty,
                    'unit_cost' => $unitCost,
                    'tax_percent' => $taxPercent,
                    'discount_amount' => 0.00,
                    'total_cost' => $lineTotal,
                ];
            }

            $discount = (float) ($data['discount_amount'] ?? 0.0);
            $totalAmount = max(0.0, round($subtotal + $taxAmount - $discount, 2));
            $paidAmount = min($totalAmount, (float) ($data['paid_amount'] ?? 0.0));

            $purchase = Purchase::create([
                'invoice_number' => Purchase::generateInvoiceNumber(),
                'supplier_invoice_ref' => $data['supplier_invoice_ref'] ?? null,
                'supplier_id' => $data['supplier_id'],
                'warehouse_id' => $data['warehouse_id'],
                'user_id' => auth()->id(),
                'issue_date' => $data['issue_date'],
                'status' => 'draft',
                'subtotal' => round($subtotal, 2),
                'tax_amount' => round($taxAmount, 2),
                'discount_amount' => round($discount, 2),
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($itemsToCreate as $itemData) {
                $purchase->items()->create($itemData);
            }

            return $purchase;
        });

        // Approve immediately if requested
        if ($request->boolean('approve_immediately')) {
            try {
                $approveAction->execute($purchase, auth()->id());

                return redirect()->route('purchases.show', $purchase)
                    ->with('success', 'تم حفظ فاتورة المشتريات واعتمادها وتحديث أرصدة المخزون والتكلفة بنجاح.');
            } catch (DomainException $e) {
                return redirect()->route('purchases.show', $purchase)
                    ->with('warning', 'تم حفظ الفاتورة كمسودة ولكن تعذر اعتمادها: '.$e->getMessage());
            }
        }

        return redirect()->route('purchases.show', $purchase)
            ->with('success', 'تم حفظ فاتورة المشتريات كمسودة بنجاح. لن يتم التأثير على المخزون حتى يتم الاعتماد.');
    }

    public function show(Purchase $purchase): View
    {
        Gate::authorize('view', $purchase);

        $purchase->load([
            'supplier',
            'warehouse',
            'user',
            'approvedByUser',
            'items.product.unit',
            'movements',
        ]);

        return view('purchases.show', compact('purchase'));
    }

    public function edit(Purchase $purchase): View|RedirectResponse
    {
        Gate::authorize('update', $purchase);

        if (! $purchase->canBeEdited()) {
            return redirect()->route('purchases.show', $purchase)
                ->with('error', 'لا يمكن تعديل فاتورة مشتريات معتمدة نهائياً.');
        }

        $suppliers = Supplier::active()->orderBy('name')->get();
        $warehouses = Warehouse::active()->orderBy('name')->get();
        $products = Product::active()->with('unit')->orderBy('name')->get();
        $purchase->load('items.product');

        return view('purchases.edit', compact('purchase', 'suppliers', 'warehouses', 'products'));
    }

    public function update(PurchaseRequest $request, Purchase $purchase, ApprovePurchaseAction $approveAction): RedirectResponse
    {
        Gate::authorize('update', $purchase);

        if (! $purchase->canBeEdited()) {
            return redirect()->route('purchases.show', $purchase)
                ->with('error', 'لا يمكن تعديل فاتورة مشتريات معتمدة.');
        }

        $data = $request->validated();
        $itemsData = $data['items'];

        DB::transaction(function () use ($purchase, $data, $itemsData) {
            $subtotal = 0.0;
            $taxAmount = 0.0;

            // Delete old items and insert fresh
            $purchase->items()->delete();

            foreach ($itemsData as $item) {
                $qty = (float) $item['quantity'];
                $unitCost = (float) $item['unit_cost'];
                $taxPercent = (float) ($item['tax_percent'] ?? 0.0);
                $lineTotal = round($qty * $unitCost, 4);
                $lineTax = round($lineTotal * ($taxPercent / 100), 2);

                $subtotal += $lineTotal;
                $taxAmount += $lineTax;

                $purchase->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $qty,
                    'unit_cost' => $unitCost,
                    'tax_percent' => $taxPercent,
                    'discount_amount' => 0.00,
                    'total_cost' => $lineTotal,
                ]);
            }

            $discount = (float) ($data['discount_amount'] ?? 0.0);
            $totalAmount = max(0.0, round($subtotal + $taxAmount - $discount, 2));
            $paidAmount = min($totalAmount, (float) ($data['paid_amount'] ?? 0.0));

            $purchase->update([
                'supplier_invoice_ref' => $data['supplier_invoice_ref'] ?? null,
                'supplier_id' => $data['supplier_id'],
                'warehouse_id' => $data['warehouse_id'],
                'issue_date' => $data['issue_date'],
                'subtotal' => round($subtotal, 2),
                'tax_amount' => round($taxAmount, 2),
                'discount_amount' => round($discount, 2),
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'notes' => $data['notes'] ?? null,
            ]);
        });

        if ($request->boolean('approve_immediately')) {
            try {
                $approveAction->execute($purchase, auth()->id());

                return redirect()->route('purchases.show', $purchase)
                    ->with('success', 'تم تعديل الفاتورة واعتمادها بنجاح.');
            } catch (DomainException $e) {
                return redirect()->route('purchases.show', $purchase)
                    ->with('warning', 'تم حفظ التعديلات ولكن تعذر اعتماد الفاتورة: '.$e->getMessage());
            }
        }

        return redirect()->route('purchases.show', $purchase)
            ->with('success', 'تم تحديث مسودة الفاتورة بنجاح.');
    }

    public function approve(Purchase $purchase, ApprovePurchaseAction $approveAction): RedirectResponse
    {
        Gate::authorize('approve', $purchase);

        try {
            $approveAction->execute($purchase, auth()->id());

            return redirect()->route('purchases.show', $purchase)
                ->with('success', 'تم اعتماد فاتورة المشتريات وإيداع البضاعة وتحديث متوسط التكلفة WAC بنجاح.');
        } catch (DomainException $e) {
            return redirect()->route('purchases.show', $purchase)
                ->with('error', $e->getMessage());
        }
    }

    public function destroy(Purchase $purchase): RedirectResponse
    {
        Gate::authorize('delete', $purchase);

        if (! $purchase->canBeDeleted()) {
            return back()->with('error', 'لا يمكن حذف فاتورة مشتريات معتمدة إطلاقاً.');
        }

        $purchase->items()->delete();
        $purchase->delete();

        return redirect()->route('purchases.index')
            ->with('success', 'تم حذف مسودة الفاتورة بنجاح.');
    }
}
