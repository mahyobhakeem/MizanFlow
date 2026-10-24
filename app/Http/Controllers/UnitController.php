<?php

namespace App\Http\Controllers;

use App\Http\Requests\UnitRequest;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class UnitController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Unit::class);

        $units = Unit::query()
            ->withCount('products')
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = $request->input('search');
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('code', 'like', "%{$term}%");
            })
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->boolean('status')))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('units.index', compact('units'));
    }

    public function create(): View
    {
        Gate::authorize('create', Unit::class);

        return view('units.create');
    }

    public function store(UnitRequest $request): RedirectResponse
    {
        Gate::authorize('create', Unit::class);

        Unit::create($request->validated());

        return redirect()->route('units.index')
            ->with('success', 'تم إنشاء وحدة القياس بنجاح.');
    }

    public function edit(Unit $unit): View
    {
        Gate::authorize('update', $unit);

        return view('units.edit', compact('unit'));
    }

    public function update(UnitRequest $request, Unit $unit): RedirectResponse
    {
        Gate::authorize('update', $unit);

        $unit->update($request->validated());

        return redirect()->route('units.index')
            ->with('success', 'تم تحديث وحدة القياس بنجاح.');
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        Gate::authorize('delete', $unit);

        if (! $unit->canBeDeleted()) {
            return back()->with('error', 'لا يمكن حذف هذه الوحدة لوجود منتجات مرتبطة بها.');
        }

        $unit->delete();

        return redirect()->route('units.index')
            ->with('success', 'تم حذف وحدة القياس بنجاح.');
    }
}
