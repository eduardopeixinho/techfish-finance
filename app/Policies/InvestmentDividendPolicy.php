<?php

namespace App\Policies;

use App\Models\Investment\InvestmentDividend;
use App\Models\User;

class InvestmentDividendPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, InvestmentDividend $investmentDividend): bool
    {
        return $user->id === $investmentDividend->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, InvestmentDividend $investmentDividend): bool
    {
        return $user->id === $investmentDividend->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, InvestmentDividend $investmentDividend): bool
    {
        return $user->id === $investmentDividend->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, InvestmentDividend $investmentDividend): bool
    {
        return $user->id === $investmentDividend->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, InvestmentDividend $investmentDividend): bool
    {
        return $user->id === $investmentDividend->user_id;
    }
}
