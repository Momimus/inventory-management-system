<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

/**
 * Staff maintain product details day to day; deleting a product is reserved
 * for administrators.
 */
class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Product $product): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Product $product): bool
    {
        return true;
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->isAdmin();
    }
}
