<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $target): bool
    {
        return $user->isAdmin();
    }

    /**
     * Admins cannot demote or deactivate themselves, so there is always at
     * least one active administrator who can sign in and fix things.
     */
    public function changeAccess(User $user, User $target): bool
    {
        return $user->isAdmin() && $user->isNot($target);
    }
}
