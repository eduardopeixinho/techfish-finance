<?php

namespace App\Policies\Finance\Settings;

use App\Models\Finance\Settings\AccountType;
use App\Models\User;

class AccountTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, AccountType $accountType): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, AccountType $accountType): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, AccountType $accountType): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, AccountType $accountType): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, AccountType $accountType): bool
    {
        return $user->isAdmin();
    }
}
