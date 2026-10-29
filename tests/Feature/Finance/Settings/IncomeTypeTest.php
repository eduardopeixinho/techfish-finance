<?php

namespace Tests\Feature\Finance\Settings;

use App\Models\Finance\Settings\IncomeType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\HasAdminUser;

class IncomeTypeTest extends TestCase
{
    use HasAdminUser, RefreshDatabase;

    public function test_admin_can_list_income_types(): void
    {
        $admin = $this->createAdminUser();
        IncomeType::factory()->count(3)->create();

        $response = $this->actingAs($admin)->get(route('settings.income-types.index'));

        $response->assertStatus(200);
        $response->assertViewIs('finance.settings.income-types.index');
        $response->assertViewHas('incomeTypes');
    }

    public function test_regular_user_cannot_list_income_types(): void
    {
        $user = $this->createRegularUser();

        $response = $this->actingAs($user)->get(route('settings.income-types.index'));

        $response->assertStatus(403);
    }

    public function test_admin_can_create_income_type(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->post(route('settings.income-types.store'), [
            'name' => 'Salário',
            'description' => 'Rendimento mensal do trabalho',
        ]);

        $response->assertRedirect(route('settings.income-types.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('income_types', [
            'name' => 'Salário',
            'description' => 'Rendimento mensal do trabalho',
        ]);
    }

    public function test_admin_cannot_create_income_type_with_duplicate_name(): void
    {
        $admin = $this->createAdminUser();
        IncomeType::factory()->create(['name' => 'Salário']);

        $response = $this->actingAs($admin)->post(route('settings.income-types.store'), [
            'name' => 'Salário',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_admin_can_update_income_type(): void
    {
        $admin = $this->createAdminUser();
        $incomeType = IncomeType::factory()->create();

        $response = $this->actingAs($admin)->put(route('settings.income-types.update', $incomeType), [
            'name' => 'Salário Atualizado',
            'description' => 'Descrição atualizada',
        ]);

        $response->assertRedirect(route('settings.income-types.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('income_types', [
            'id' => $incomeType->id,
            'name' => 'Salário Atualizado',
        ]);
    }

    public function test_admin_can_delete_income_type(): void
    {
        $admin = $this->createAdminUser();
        $incomeType = IncomeType::factory()->create();

        $response = $this->actingAs($admin)->delete(route('settings.income-types.destroy', $incomeType));

        $response->assertRedirect(route('settings.income-types.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('income_types', ['id' => $incomeType->id]);
    }

    public function test_unauthenticated_user_cannot_access_income_types(): void
    {
        $response = $this->get(route('settings.income-types.index'));

        $response->assertRedirect(route('login'));
    }
}
