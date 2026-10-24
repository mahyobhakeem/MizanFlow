<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);

        $users = User::query()
            ->with('warehouse')
            ->search($request->input('search'))
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->input('role')))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->boolean('status')))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        Gate::authorize('create', User::class);

        $warehouses = Warehouse::active()->orderBy('name')->get();

        return view('users.create', compact('warehouses'));
    }

    public function store(UserRequest $request): RedirectResponse
    {
        Gate::authorize('create', User::class);

        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);

        User::create($data);

        return redirect()->route('users.index')
            ->with('success', 'تم إنشاء حساب المستخدم بنجاح.');
    }

    public function edit(User $user): View
    {
        Gate::authorize('update', $user);

        $warehouses = Warehouse::active()->orderBy('name')->get();

        return view('users.edit', compact('user', 'warehouses'));
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        Gate::authorize('update', $user);

        $data = $request->validated();
        if (filled($data['password'] ?? null)) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('users.index')
            ->with('success', 'تم تحديث بيانات المستخدم بنجاح.');
    }

    public function destroy(User $user): RedirectResponse
    {
        Gate::authorize('delete', $user);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'لا يمكنك حذف حسابك الشخصي الحالي.');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'تم حذف المستخدم بنجاح.');
    }
}
