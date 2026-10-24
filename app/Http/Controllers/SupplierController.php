<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupplierRequest;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Supplier::class);

        $suppliers = Supplier::query()
            ->search($request->input('search'))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->boolean('status')))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('suppliers.index', compact('suppliers'));
    }

    public function create(): View
    {
        Gate::authorize('create', Supplier::class);

        return view('suppliers.create');
    }

    public function store(SupplierRequest $request): RedirectResponse
    {
        Gate::authorize('create', Supplier::class);

        Supplier::create($request->validated());

        return redirect()->route('suppliers.index')
            ->with('success', 'تم إضافة المورد بنجاح.');
    }

    public function edit(Supplier $supplier): View
    {
        Gate::authorize('update', $supplier);

        return view('suppliers.edit', compact('supplier'));
    }

    public function update(SupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        Gate::authorize('update', $supplier);

        $supplier->update($request->validated());

        return redirect()->route('suppliers.index')
            ->with('success', 'تم تحديث بيانات المورد بنجاح.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        Gate::authorize('delete', $supplier);

        if (! $supplier->canBeDeleted()) {
            return back()->with('error', 'لا يمكن حذف المورد لوجود مستحقات مالية قائمة له.');
        }

        $supplier->delete();

        return redirect()->route('suppliers.index')
            ->with('success', 'تم حذف المورد بنجاح.');
    }
}
