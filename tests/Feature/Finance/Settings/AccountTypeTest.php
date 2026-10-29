<?php

namespace Tests\Feature\Finance\Settings;

use App\Models\Finance\Settings\AccountType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\HasAdminUser;

class AccountTypeTest extends TestCase
{
    use HasAdminUser, RefreshDatabase;

    public function test_admin_can_list_account_types(): void
    {
        $admin = $this->createAdminUser();
        AccountType::factory()->count(3)->create();

        $response = $this->actingAs($admin)->get(route('settings.account-types.index'));

        $response->assertStatus(200);
        $response->assertViewIs('finance.settings.account-types.index');
        $response->assertViewHas('accountTypes');
    }

    public function test_regular_user_cannot_list_account_types(): void
    {
        $user = $this->createRegularUser();

        $response = $this->actingAs($user)->get(route('settings.account-types.index'));

        $response->assertStatus(403);
    }

    public function test_admin_can_create_account_type(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->post(route('settings.account-types.store'), [
            'name' => 'Conta Corrente',
            'description' => 'Conta corrente bancária',
        ]);

        $response->assertRedirect(route('settings.account-types.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('account_types', [
            'name' => 'Conta Corrente',
            'description' => 'Conta corrente bancária',
        ]);
    }

    public function test_admin_cannot_create_account_type_with_duplicate_name(): void
    {
        $admin = $this->createAdminUser();
        AccountType::factory()->create(['name' => 'Conta Corrente']);

        $response = $this->actingAs($admin)->post(route('settings.account-types.store'), [
            'name' => 'Conta Corrente',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_admin_can_update_account_type(): void
    {
        $admin = $this->createAdminUser();
        $accountType = AccountType::factory()->create();

        $response = $this->actingAs($admin)->put(route('settings.account-types.update', $accountType), [
            'name' => 'Conta Atualizada',
            'description' => 'Descrição atualizada',
        ]);

        $response->assertRedirect(route('settings.account-types.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('account_types', [
            'id' => $accountType->id,
            'name' => 'Conta Atualizada',
        ]);
    }

    public function test_admin_can_delete_account_type(): void
    {
        $admin = $this->createAdminUser();
        $accountType = AccountType::factory()->create();

        $response = $this->actingAs($admin)->delete(route('settings.account-types.destroy', $accountType));

        $response->assertRedirect(route('settings.account-types.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('account_types', ['id' => $accountType->id]);
    }

    public function test_unauthenticated_user_cannot_access_account_types(): void
    {
        $response = $this->get(route('settings.account-types.index'));

        $response->assertRedirect(route('login'));
    }
}
