<?php

namespace App\Policies;

use App\Models\StockTransfer;
use App\Models\User;

class StockTransferPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'manager']);
    }

    public function view(User $user, StockTransfer $transfer): bool
    {
        return $user->hasRole(['admin', 'manager']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'manager']);
    }

    public function dispatch(User $user, StockTransfer $transfer): bool
    {
        return $user->hasRole(['admin', 'manager']) && $transfer->canBeDispatched();
    }

    public function receive(User $user, StockTransfer $transfer): bool
    {
        return $user->hasRole(['admin', 'manager']) && $transfer->canBeReceived();
    }

    public function delete(User $user, StockTransfer $transfer): bool
    {
        return $user->isAdmin() && $transfer->canBeDeleted();
    }
}
