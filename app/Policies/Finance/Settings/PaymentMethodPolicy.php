<?php

namespace App\Policies\Finance\Settings;

use App\Models\Finance\Settings\PaymentMethod;
use App\Models\User;

class PaymentMethodPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, PaymentMethod $paymentMethod): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, PaymentMethod $paymentMethod): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, PaymentMethod $paymentMethod): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, PaymentMethod $paymentMethod): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, PaymentMethod $paymentMethod): bool
    {
        return $user->isAdmin();
    }
}
