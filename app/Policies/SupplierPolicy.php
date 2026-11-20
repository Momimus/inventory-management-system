<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;

/**
 * Everyone signed in can look up suppliers (staff need them when receiving
 * stock); only administrators change them.
 */
class SupplierPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Supplier $supplier): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        return $user->isAdmin();
    }
}
