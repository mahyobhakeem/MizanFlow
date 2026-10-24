<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Customer $customer): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true; // Cashier, Manager, Admin can all create customers
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->hasRole(['admin', 'manager']);
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->isAdmin();
    }
}
