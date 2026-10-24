<?php

namespace App\Http\Controllers;

use App\Actions\Sales\CompleteSaleAction;
use App\Actions\Sales\VoidSaleAction;
use App\Exceptions\InsufficientStockException;
use App\Http\Requests\SaleRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Warehouse;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Sale::class);

        $query = Sale::with(['customer', 'warehouse', 'user'])
            ->latest('invoice_date')
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

        if ($request->filled('payment_type')) {
            $query->paymentType($request->input('payment_type'));
        }

        $sales = $query->paginate(15)->withQueryString();
        $warehouses = Warehouse::active()->orderBy('name')->get();

        return view('sales.index', compact('sales', 'warehouses'));
    }

    public function create(): View
    {
        Gate::authorize('create', Sale::class);

        $customers = Customer::active()->orderBy('name')->get();
        $warehouses = Warehouse::active()->orderBy('name')->get();
        $products = Product::active()->with(['unit', 'warehouseStocks'])->orderBy('name')->get();
        $defaultWarehouseId = auth()->user()->default_warehouse_id ?? $warehouses->first()?->id;

        return view('sales.create', compact('customers', 'warehouses', 'products', 'defaultWarehouseId'));
    }

    public function store(SaleRequest $request, CompleteSaleAction $completeAction): RedirectResponse
    {
        Gate::authorize('create', Sale::class);

        $data = $request->validated();
        $itemsData = $data['items'];

        $sale = DB::transaction(function () use ($data, $itemsData) {
            $subtotal = 0.0;
            $taxAmount = 0.0;
            $itemsToCreate = [];

            foreach ($itemsData as $item) {
                $qty = (float) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];
                $taxPercent = (float) ($item['tax_percent'] ?? 15.00);
                $discount = (float) ($item['discount_amount'] ?? 0.00);

                // Server-side calculation of line total
                $lineSubtotal = max(0.0, round(($qty * $unitPrice) - $discount, 4));
                $lineTax = round($lineSubtotal * ($taxPercent / 100), 2);

                $subtotal += $lineSubtotal;
                $taxAmount += $lineTax;

                $itemsToCreate[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'unit_cost_snapshot' => 0.0000, // Captured at completion
                    'tax_percent' => $taxPercent,
                    'discount_amount' => $discount,
                    'total_price' => $lineSubtotal,
                ];
            }

            $orderDiscount = (float) ($data['discount_amount'] ?? 0.0);
            $totalAmount = max(0.0, round($subtotal + $taxAmount - $orderDiscount, 2));

            // Server-side payment handling
            $paymentType = $data['payment_type'];
            if ($paymentType === 'cash' || $paymentType === 'card') {
                $paidAmount = $totalAmount;
            } elseif ($paymentType === 'credit') {
                $paidAmount = 0.00;
            } else { // split
                $paidAmount = min($totalAmount, (float) ($data['paid_amount'] ?? 0.0));
            }

            $sale = Sale::create([
                'invoice_number' => Sale::generateInvoiceNumber(),
                'customer_id' => $data['customer_id'] ?? null,
                'warehouse_id' => $data['warehouse_id'],
                'user_id' => auth()->id(),
                'invoice_date' => $data['invoice_date'],
                'payment_type' => $paymentType,
                'status' => 'draft',
                'subtotal' => round($subtotal, 2),
                'tax_amount' => round($taxAmount, 2),
                'discount_amount' => round($orderDiscount, 2),
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($itemsToCreate as $itemData) {
                $sale->items()->create($itemData);
            }

            return $sale;
        });

        // Complete immediately if requested (POS / Instant sale)
        if ($request->boolean('complete_immediately')) {
            try {
                $completeAction->execute($sale, auth()->id());

                return redirect()->route('sales.show', $sale)
                    ->with('success', 'تم إنشاء فاتورة المبيعات واعتمادها وخصم المخزون بنجاح.');
            } catch (InsufficientStockException|DomainException $e) {
                return redirect()->route('sales.show', $sale)
                    ->with('warning', 'تم حفظ الفاتورة كمسودة، ولكن تعذر اعتمادها فوراً: '.$e->getMessage());
            }
        }

        return redirect()->route('sales.show', $sale)
            ->with('success', 'تم حفظ فاتورة المبيعات كمسودة بنجاح. لن يتم خصم المخزون حتى يتم الاعتماد.');
    }

    public function show(Sale $sale): View
    {
        Gate::authorize('view', $sale);

        $sale->load([
            'customer',
            'warehouse',
            'user',
            'voidedByUser',
            'items.product.unit',
            'movements',
        ]);

        return view('sales.show', compact('sale'));
    }

    public function edit(Sale $sale): View|RedirectResponse
    {
        Gate::authorize('update', $sale);

        if (! $sale->canBeEdited()) {
            return redirect()->route('sales.show', $sale)
                ->with('error', 'لا يمكن تعديل فاتورة مبيعات معتمدة أو ملغاة.');
        }

        $sale->load('items.product.unit');
        $customers = Customer::active()->orderBy('name')->get();
        $warehouses = Warehouse::active()->orderBy('name')->get();
        $products = Product::active()->with(['unit', 'warehouseStocks'])->orderBy('name')->get();

        return view('sales.edit', compact('sale', 'customers', 'warehouses', 'products'));
    }

    public function update(SaleRequest $request, Sale $sale, CompleteSaleAction $completeAction): RedirectResponse
    {
        Gate::authorize('update', $sale);

        if (! $sale->canBeEdited()) {
            return redirect()->route('sales.show', $sale)
                ->with('error', 'لا يمكن تعديل فاتورة مبيعات معتمدة أو ملغاة.');
        }

        $data = $request->validated();
        $itemsData = $data['items'];

        DB::transaction(function () use ($sale, $data, $itemsData) {
            $subtotal = 0.0;
            $taxAmount = 0.0;

            // Delete previous draft items
            $sale->items()->delete();

            foreach ($itemsData as $item) {
                $qty = (float) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];
                $taxPercent = (float) ($item['tax_percent'] ?? 15.00);
                $discount = (float) ($item['discount_amount'] ?? 0.00);

                $lineSubtotal = max(0.0, round(($qty * $unitPrice) - $discount, 4));
                $lineTax = round($lineSubtotal * ($taxPercent / 100), 2);

                $subtotal += $lineSubtotal;
                $taxAmount += $lineTax;

                $sale->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'unit_cost_snapshot' => 0.0000,
                    'tax_percent' => $taxPercent,
                    'discount_amount' => $discount,
                    'total_price' => $lineSubtotal,
                ]);
            }

            $orderDiscount = (float) ($data['discount_amount'] ?? 0.0);
            $totalAmount = max(0.0, round($subtotal + $taxAmount - $orderDiscount, 2));

            $paymentType = $data['payment_type'];
            if ($paymentType === 'cash' || $paymentType === 'card') {
                $paidAmount = $totalAmount;
            } elseif ($paymentType === 'credit') {
                $paidAmount = 0.00;
            } else {
                $paidAmount = min($totalAmount, (float) ($data['paid_amount'] ?? 0.0));
            }

            $sale->update([
                'customer_id' => $data['customer_id'] ?? null,
                'warehouse_id' => $data['warehouse_id'],
                'invoice_date' => $data['invoice_date'],
                'payment_type' => $paymentType,
                'subtotal' => round($subtotal, 2),
                'tax_amount' => round($taxAmount, 2),
                'discount_amount' => round($orderDiscount, 2),
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'notes' => $data['notes'] ?? null,
            ]);
        });

        // Complete immediately if requested (POS / Instant sale)
        if ($request->boolean('complete_immediately')) {
            try {
                $completeAction->execute($sale, auth()->id());

                return redirect()->route('sales.show', $sale)
                    ->with('success', 'تم تعديل فاتورة المبيعات واعتمادها وخصم المخزون بنجاح.');
            } catch (InsufficientStockException|DomainException $e) {
                return redirect()->route('sales.show', $sale)
                    ->with('warning', 'تم حفظ التعديلات على المسودة، ولكن تعذر اعتمادها فوراً: '.$e->getMessage());
            }
        }

        return redirect()->route('sales.show', $sale)
            ->with('success', 'تم تحديث مسودة فاتورة المبيعات بنجاح.');
    }

    public function complete(Sale $sale, CompleteSaleAction $completeAction): RedirectResponse
    {
        Gate::authorize('complete', $sale);

        try {
            $completeAction->execute($sale, auth()->id());

            return redirect()->route('sales.show', $sale)
                ->with('success', 'تم اعتماد فاتورة المبيعات وخصم المخزون بنجاح.');
        } catch (InsufficientStockException $e) {
            return redirect()->route('sales.show', $sale)
                ->with('error', $e->getMessage());
        } catch (DomainException $e) {
            return redirect()->route('sales.show', $sale)
                ->with('error', 'تعذر اعتماد الفاتورة: '.$e->getMessage());
        }
    }

    public function void(Request $request, Sale $sale, VoidSaleAction $voidAction): RedirectResponse
    {
        Gate::authorize('void', $sale);

        $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $voidAction->execute($sale, auth()->id(), $request->input('reason'));

            return redirect()->route('sales.show', $sale)
                ->with('success', 'تم إلغاء فاتورة المبيعات واسترجاع أرصدة المخزون وتسجيل الحركات العكسية بنجاح.');
        } catch (DomainException $e) {
            return redirect()->route('sales.show', $sale)
                ->with('error', 'تعذر إلغاء الفاتورة: '.$e->getMessage());
        }
    }

    public function print(Sale $sale): View
    {
        Gate::authorize('print', $sale);

        $sale->load([
            'customer',
            'warehouse',
            'user',
            'items.product.unit',
        ]);

        return view('sales.print', compact('sale'));
    }

    public function destroy(Sale $sale): RedirectResponse
    {
        Gate::authorize('delete', $sale);

        if (! $sale->canBeDeleted()) {
            return redirect()->route('sales.show', $sale)
                ->with('error', 'لا يمكن حذف فاتورة مبيعات معتمدة. يمكنك استخدام الإلغاء العكسي بدلاً من ذلك.');
        }

        $sale->delete();

        return redirect()->route('sales.index')
            ->with('success', 'تم حذف مسودة الفاتورة بنجاح.');
    }
}
