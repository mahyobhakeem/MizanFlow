<?php

namespace App\Http\Controllers;

use App\Http\Requests\WarehouseRequest;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class WarehouseController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Warehouse::class);

        $warehouses = Warehouse::query()
            ->search($request->input('search'))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->boolean('status')))
            ->withCount('users')
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('warehouses.index', compact('warehouses'));
    }

    public function create(): View
    {
        Gate::authorize('create', Warehouse::class);

        return view('warehouses.create');
    }

    public function store(WarehouseRequest $request): RedirectResponse
    {
        Gate::authorize('create', Warehouse::class);

        Warehouse::create($request->validated());

        return redirect()->route('warehouses.index')
            ->with('success', 'تم إنشاء المستودع بنجاح.');
    }

    public function edit(Warehouse $warehouse): View
    {
        Gate::authorize('update', $warehouse);

        return view('warehouses.edit', compact('warehouse'));
    }

    public function update(WarehouseRequest $request, Warehouse $warehouse): RedirectResponse
    {
        Gate::authorize('update', $warehouse);

        $warehouse->update($request->validated());

        return redirect()->route('warehouses.index')
            ->with('success', 'تم تحديث بيانات المستودع بنجاح.');
    }

    public function destroy(Warehouse $warehouse): RedirectResponse
    {
        Gate::authorize('delete', $warehouse);

        if (! $warehouse->canBeDeleted()) {
            return back()->with('error', 'لا يمكن حذف هذا المستودع لوجود مستخدمين أو عمليات مرتبطة به.');
        }

        $warehouse->delete();

        return redirect()->route('warehouses.index')
            ->with('success', 'تم حذف المستودع بنجاح.');
    }
}
