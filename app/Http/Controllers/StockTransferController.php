<?php

namespace App\Http\Controllers;

use App\Actions\Inventory\StockTransferAction;
use App\Exceptions\InsufficientStockException;
use App\Http\Requests\StockTransferRequest;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use InvalidArgumentException;

class StockTransferController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', StockTransfer::class);

        $query = StockTransfer::with(['fromWarehouse', 'toWarehouse', 'createdByUser'])
            ->latest('id');

        if ($request->filled('search')) {
            $query->search($request->input('search'));
        }

        if ($request->filled('status')) {
            $query->status($request->input('status'));
        }

        $transfers = $query->paginate(15)->withQueryString();
        $warehouses = Warehouse::active()->orderBy('name')->get();

        return view('transfers.index', compact('transfers', 'warehouses'));
    }

    public function create(): View
    {
        Gate::authorize('create', StockTransfer::class);

        $warehouses = Warehouse::active()->orderBy('name')->get();
        $products = Product::active()->with(['unit', 'warehouseStocks'])->orderBy('name')->get();
        $defaultWarehouseId = auth()->user()->default_warehouse_id;

        return view('transfers.create', compact('warehouses', 'products', 'defaultWarehouseId'));
    }

    public function store(StockTransferRequest $request, StockTransferAction $transferAction): RedirectResponse
    {
        Gate::authorize('create', StockTransfer::class);

        $data = $request->validated();
        $fromWarehouseId = (int) $data['from_warehouse_id'];
        $toWarehouseId = (int) $data['to_warehouse_id'];
        $items = $data['items'];
        $notes = $data['notes'] ?? null;
        $isDirect = $request->boolean('direct_transfer', true);

        try {
            if ($isDirect) {
                // Execute direct transfer within a single transaction
                $transfer = $transferAction->directTransfer(
                    $fromWarehouseId,
                    $toWarehouseId,
                    $items,
                    auth()->id(),
                    $notes
                );

                return redirect()->route('transfers.show', $transfer)
                    ->with('success', 'تم تنفيذ التحويل المخزني الفوري بين المستودعين وتحديث التكاليف والأرصدة بنجاح.');
            } else {
                // Create draft transfer
                $transfer = StockTransfer::create([
                    'transfer_number' => StockTransfer::generateTransferNumber(),
                    'from_warehouse_id' => $fromWarehouseId,
                    'to_warehouse_id' => $toWarehouseId,
                    'created_by_user_id' => auth()->id(),
                    'status' => 'draft',
                    'notes' => $notes,
                ]);

                foreach ($items as $item) {
                    $transfer->items()->create([
                        'product_id' => $item['product_id'],
                        'quantity_sent' => $item['quantity'],
                        'quantity_received' => null,
                        'unit_cost_snapshot' => 0.0000,
                    ]);
                }

                return redirect()->route('transfers.show', $transfer)
                    ->with('success', 'تم حفظ أمر التحويل كمسودة. يمكنك شحن البضاعة عندما تكون جاهزة.');
            }
        } catch (InsufficientStockException|DomainException|InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show(StockTransfer $transfer): View
    {
        Gate::authorize('view', $transfer);

        $transfer->load([
            'fromWarehouse',
            'toWarehouse',
            'createdByUser',
            'receivedByUser',
            'items.product.unit',
            'movements',
        ]);

        return view('transfers.show', compact('transfer'));
    }

    public function dispatchTransfer(StockTransfer $transfer, StockTransferAction $transferAction): RedirectResponse
    {
        Gate::authorize('dispatch', $transfer);

        try {
            $transferAction->dispatch($transfer, auth()->id());

            return redirect()->route('transfers.show', $transfer)
                ->with('success', 'تم شحن أمر التحويل وخصم البضاعة من المستودع المصدر بنجاح.');
        } catch (InsufficientStockException|DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function receiveTransfer(StockTransfer $transfer, StockTransferAction $transferAction): RedirectResponse
    {
        Gate::authorize('receive', $transfer);

        try {
            $transferAction->receive($transfer, auth()->id());

            return redirect()->route('transfers.show', $transfer)
                ->with('success', 'تم تأكيد استلام الشحنة في المستودع الهدف وتحديث الأرصدة ومتوسط التكلفة WAC بنجاح.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(StockTransfer $transfer): RedirectResponse
    {
        Gate::authorize('delete', $transfer);

        if (! $transfer->canBeDeleted()) {
            return back()->with('error', 'لا يمكن حذف أمر تحويل تم شحنه أو تنفيذه.');
        }

        $transfer->items()->delete();
        $transfer->delete();

        return redirect()->route('transfers.index')
            ->with('success', 'تم حذف مسودة أمر التحويل بنجاح.');
    }
}
