<?php

namespace App\Policies\Finance;

use App\Models\Finance\FinancialCategory;
use App\Models\User;

class FinancialCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, FinancialCategory $financialCategory): bool
    {
        return $user->id === $financialCategory->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, FinancialCategory $financialCategory): bool
    {
        return $user->id === $financialCategory->user_id;
    }

    public function delete(User $user, FinancialCategory $financialCategory): bool
    {
        return $user->id === $financialCategory->user_id;
    }
}
