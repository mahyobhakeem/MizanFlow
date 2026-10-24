<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Customer::class);

        $customers = Customer::query()
            ->search($request->input('search'))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->boolean('status')))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('customers.index', compact('customers'));
    }

    public function create(): View
    {
        Gate::authorize('create', Customer::class);

        return view('customers.create');
    }

    public function store(CustomerRequest $request): RedirectResponse
    {
        Gate::authorize('create', Customer::class);

        Customer::create($request->validated());

        return redirect()->route('customers.index')
            ->with('success', 'تم تسجيل العميل بنجاح.');
    }

    public function edit(Customer $customer): View
    {
        Gate::authorize('update', $customer);

        return view('customers.edit', compact('customer'));
    }

    public function update(CustomerRequest $request, Customer $customer): RedirectResponse
    {
        Gate::authorize('update', $customer);

        $customer->update($request->validated());

        return redirect()->route('customers.index')
            ->with('success', 'تم تحديث بيانات العميل بنجاح.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        Gate::authorize('delete', $customer);

        if (! $customer->canBeDeleted()) {
            return back()->with('error', 'لا يمكن حذف العميل لوجود رصيد مالي قائم بذمته.');
        }

        $customer->delete();

        return redirect()->route('customers.index')
            ->with('success', 'تم حذف العميل بنجاح.');
    }
}
