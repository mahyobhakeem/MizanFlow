<?php

namespace App\Policies;

use App\Models\Purchase;
use App\Models\User;

class PurchasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'manager']);
    }

    public function view(User $user, Purchase $purchase): bool
    {
        return $user->hasRole(['admin', 'manager']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'manager']);
    }

    public function update(User $user, Purchase $purchase): bool
    {
        return $user->hasRole(['admin', 'manager']) && $purchase->canBeEdited();
    }

    public function approve(User $user, Purchase $purchase): bool
    {
        return $user->hasRole(['admin', 'manager']) && $purchase->canBeApproved();
    }

    public function delete(User $user, Purchase $purchase): bool
    {
        return $user->isAdmin() && $purchase->canBeDeleted();
    }
}
