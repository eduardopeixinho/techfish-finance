<?php

namespace Tests\Traits;

use App\Models\Role;
use App\Models\User;

trait HasAdminUser
{
    /**
     * Cria um usuário com role admin.
     */
    protected function createAdminUser(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $adminRole = Role::factory()->admin()->create();
        $user->assignRole($adminRole);

        return $user->fresh();
    }

    /**
     * Cria um usuário sem role admin.
     */
    protected function createRegularUser(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }
}
