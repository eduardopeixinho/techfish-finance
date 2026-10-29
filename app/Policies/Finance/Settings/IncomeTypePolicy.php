<?php

namespace App\Policies\Finance\Settings;

use App\Models\Finance\Settings\IncomeType;
use App\Models\User;

class IncomeTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, IncomeType $incomeType): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, IncomeType $incomeType): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, IncomeType $incomeType): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, IncomeType $incomeType): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, IncomeType $incomeType): bool
    {
        return $user->isAdmin();
    }
}
