<?php

namespace App\Policies;

use App\Models\StockMovement;
use App\Models\User;

class StockMovementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'manager']);
    }

    public function view(User $user, StockMovement $movement): bool
    {
        return $user->hasRole(['admin', 'manager']);
    }

    public function create(User $user): bool
    {
        return false; // Direct manual creation of ledger movements is forbidden
    }

    public function update(User $user, StockMovement $movement): bool
    {
        return false; // Stock movements are strictly immutable
    }

    public function delete(User $user, StockMovement $movement): bool
    {
        return false; // Stock movements cannot be deleted
    }
}
