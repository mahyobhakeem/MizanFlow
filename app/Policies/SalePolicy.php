<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;

class SalePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'manager', 'cashier']);
    }

    public function view(User $user, Sale $sale): bool
    {
        return $user->hasRole(['admin', 'manager', 'cashier']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'manager', 'cashier']);
    }

    public function update(User $user, Sale $sale): bool
    {
        if (! $sale->canBeEdited()) {
            return false;
        }

        return $user->hasRole(['admin', 'manager', 'cashier']);
    }

    public function complete(User $user, Sale $sale): bool
    {
        if (! $sale->canBeCompleted()) {
            return false;
        }

        return $user->hasRole(['admin', 'manager', 'cashier']);
    }

    public function void(User $user, Sale $sale): bool
    {
        // Only managers and admins can void a completed sale invoice
        return $user->hasRole(['admin', 'manager']) && $sale->canBeVoided();
    }

    public function delete(User $user, Sale $sale): bool
    {
        // Only admins can delete, and ONLY if it is still a draft
        return $user->isAdmin() && $sale->canBeDeleted();
    }

    public function print(User $user, Sale $sale): bool
    {
        return $user->hasRole(['admin', 'manager', 'cashier']);
    }

    public function viewReports(User $user): bool
    {
        return $user->hasRole(['admin', 'manager']);
    }
}
