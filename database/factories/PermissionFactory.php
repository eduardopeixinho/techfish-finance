<?php

namespace Database\Factories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

/**
 * @extends Factory<Permission>
 */
class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // unique(): name e slug têm índice único; sortear com repetição colidia (~1 em 20 com count(2))
        [$module, $action] = $this->faker->unique()->randomElement(
            Arr::crossJoin(['finance', 'investment', 'admin', 'user'], ['read', 'create', 'update', 'delete', 'manage'])
        );
        $name = "$module.$action";

        return [
            'name' => ucfirst(str_replace('.', ' ', $name)),
            'slug' => $name,
            'description' => $this->faker->sentence(),
            'module' => $module,
            'action' => $action,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the permission is for finance module.
     */
    public function finance(string $action = 'read'): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => "Finance $action",
            'slug' => "finance.$action",
            'module' => 'finance',
            'action' => $action,
        ]);
    }

    /**
     * Indicate that the permission is for investment module.
     */
    public function investment(string $action = 'read'): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => "Investment $action",
            'slug' => "investment.$action",
            'module' => 'investment',
            'action' => $action,
        ]);
    }

    /**
     * Indicate that the permission is for admin module.
     */
    public function admin(string $action = 'access'): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => "Admin $action",
            'slug' => "admin.$action",
            'module' => 'admin',
            'action' => $action,
        ]);
    }

    /**
     * Indicate that the permission is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
