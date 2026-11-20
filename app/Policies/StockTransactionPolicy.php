<?php

namespace App\Policies;

use App\Enums\StockTransactionType;
use App\Models\StockTransaction;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class StockTransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, StockTransaction $transaction): bool
    {
        return true;
    }

    /**
     * Everyone can receive and issue stock; adjustments change the count
     * without a matching delivery or sale, so they are limited to admins.
     */
    public function create(User $user, StockTransactionType $type): bool
    {
        return $type !== StockTransactionType::Adjustment || Gate::forUser($user)->allows('adjust-stock');
    }

    /**
     * History is permanent for everyone. A mistake is corrected by recording
     * a new adjustment, which keeps both the error and the fix visible.
     */
    public function update(User $user, StockTransaction $transaction): bool
    {
        return false;
    }

    public function delete(User $user, StockTransaction $transaction): bool
    {
        return false;
    }
}
