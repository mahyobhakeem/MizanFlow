<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductWarehouse;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StockMovementController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', StockMovement::class);

        $query = StockMovement::with(['product.unit', 'warehouse', 'user'])
            ->latest('id');

        if ($request->filled('warehouse_id')) {
            $query->warehouse($request->integer('warehouse_id'));
        }

        if ($request->filled('product_id')) {
            $query->product($request->integer('product_id'));
        }

        if ($request->filled('movement_type')) {
            $query->movementType($request->input('movement_type'));
        }

        if ($request->filled('search')) {
            $query->search($request->input('search'));
        }

        $movements = $query->paginate(25)->withQueryString();
        $warehouses = Warehouse::active()->orderBy('name')->get();
        $products = Product::active()->orderBy('name')->get();

        return view('inventory.movements', compact('movements', 'warehouses', 'products'));
    }

    public function balances(Request $request): View
    {
        Gate::authorize('viewAny', StockMovement::class);

        $query = ProductWarehouse::with(['product.unit', 'product.category', 'warehouse'])
            ->orderBy('warehouse_id')
            ->orderBy('product_id');

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->integer('warehouse_id'));
        }

        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->whereHas('product', function ($pq) use ($term) {
                $pq->where('name', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%")
                    ->orWhere('barcode', 'like', "%{$term}%");
            });
        }

        $stocks = $query->paginate(20)->withQueryString();
        $warehouses = Warehouse::active()->orderBy('name')->get();

        return view('inventory.balances', compact('stocks', 'warehouses'));
    }
}
