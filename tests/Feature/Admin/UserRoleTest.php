<?php

namespace Tests\Feature\Admin;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\HasAdminUser;

class UserRoleTest extends TestCase
{
    use HasAdminUser, RefreshDatabase;

    public function test_admin_can_list_users_with_roles(): void
    {
        $admin = $this->createAdminUser();
        User::factory()->count(3)->create();

        $response = $this->actingAs($admin)->get(route('admin.user-roles.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.user-roles.index');
        $response->assertViewHas('users');
    }

    public function test_regular_user_cannot_list_users_with_roles(): void
    {
        $user = $this->createRegularUser();

        $response = $this->actingAs($user)->get(route('admin.user-roles.index'));

        $response->assertStatus(403);
    }

    public function test_user_with_admin_permission_can_list_users(): void
    {
        $user = $this->createRegularUser();
        $role = Role::factory()->create();
        $permission = Permission::factory()->admin('users')->create();
        $role->givePermission($permission);
        $user->assignRole($role);

        $response = $this->actingAs($user->fresh())->get(route('admin.user-roles.index'));

        $response->assertStatus(200);
    }

    public function test_admin_can_view_user_edit_form(): void
    {
        $admin = $this->createAdminUser();
        $user = $this->createRegularUser();
        Role::factory()->count(2)->create();

        $response = $this->actingAs($admin)->get(route('admin.user-roles.edit', $user));

        $response->assertStatus(200);
        $response->assertViewIs('admin.user-roles.edit');
        $response->assertViewHas('user');
        $response->assertViewHas('roles');
    }

    public function test_admin_can_update_user_roles(): void
    {
        $admin = $this->createAdminUser();
        $user = $this->createRegularUser();
        $role1 = Role::factory()->create();
        $role2 = Role::factory()->create();

        $response = $this->actingAs($admin)->patch(route('admin.user-roles.update', $user), [
            'roles' => [$role1->id, $role2->id],
        ]);

        $response->assertRedirect(route('admin.user-roles.index'));
        $response->assertSessionHas('success');

        $this->assertTrue($user->fresh()->hasRole($role1->slug));
        $this->assertTrue($user->fresh()->hasRole($role2->slug));
    }

    public function test_admin_can_remove_all_roles_from_user(): void
    {
        $admin = $this->createAdminUser();
        $user = $this->createRegularUser();
        $role = Role::factory()->create();
        $user->assignRole($role);

        $response = $this->actingAs($admin)->delete(route('admin.user-roles.destroy', $user));

        $response->assertRedirect(route('admin.user-roles.index'));
        $response->assertSessionHas('success');

        $this->assertFalse($user->fresh()->hasRole($role->slug));
    }

    public function test_admin_can_view_user_details(): void
    {
        $admin = $this->createAdminUser();
        $user = $this->createRegularUser();
        $role = Role::factory()->create();
        $user->assignRole($role);

        $response = $this->actingAs($admin)->get(route('admin.user-roles.show', $user));

        $response->assertStatus(200);
        $response->assertViewIs('admin.user-roles.show');
        $response->assertViewHas('user');
    }

    public function test_admin_can_assign_role_to_user(): void
    {
        $admin = $this->createAdminUser();
        $user = $this->createRegularUser();
        $role = Role::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.user-roles.assign', $user), [
            'role_id' => $role->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertTrue($user->fresh()->hasRole($role->slug));
    }

    public function test_admin_can_remove_role_from_user(): void
    {
        $admin = $this->createAdminUser();
        $user = $this->createRegularUser();
        $role = Role::factory()->create();
        $user->assignRole($role);

        $response = $this->actingAs($admin)->delete(route('admin.user-roles.remove', $user), [
            'role_id' => $role->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertFalse($user->fresh()->hasRole($role->slug));
    }

    public function test_admin_cannot_assign_nonexistent_role(): void
    {
        $admin = $this->createAdminUser();
        $user = $this->createRegularUser();

        $response = $this->actingAs($admin)->post(route('admin.user-roles.assign', $user), [
            'role_id' => 9999,
        ]);

        $response->assertSessionHasErrors('role_id');
    }

    public function test_unauthenticated_user_cannot_access_user_roles(): void
    {
        $response = $this->get(route('admin.user-roles.index'));

        $response->assertRedirect(route('login'));
    }
}
