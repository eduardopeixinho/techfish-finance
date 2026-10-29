<?php

namespace Tests\Feature\Investment;

use App\Models\Investment\Investment;
use App\Models\Investment\InvestmentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\HasAdminUser;

class InvestmentTypeTest extends TestCase
{
    use HasAdminUser, RefreshDatabase;

    public function test_admin_can_list_investment_types(): void
    {
        $admin = $this->createAdminUser();
        InvestmentType::factory()->count(3)->create();

        $response = $this->actingAs($admin)->get(route('investment.types.index'));

        $response->assertStatus(200);
        $response->assertViewIs('investment.types.index');
        $response->assertViewHas('investmentTypes');
        $this->assertCount(3, $response->viewData('investmentTypes'));
    }

    public function test_regular_user_cannot_list_investment_types(): void
    {
        $user = $this->createRegularUser();

        $response = $this->actingAs($user)->get(route('investment.types.index'));

        $response->assertStatus(403);
    }

    public function test_admin_can_view_create_form(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->get(route('investment.types.create'));

        $response->assertStatus(200);
        $response->assertViewIs('investment.types.create');
    }

    public function test_regular_user_cannot_view_create_form(): void
    {
        $user = $this->createRegularUser();

        $response = $this->actingAs($user)->get(route('investment.types.create'));

        $response->assertStatus(403);
    }

    public function test_admin_can_create_investment_type(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->post(route('investment.types.store'), [
            'name' => 'Ação',
            'code' => 'acao',
            'description' => 'Tipo de investimento em ações',
            'has_dividends' => true,
            'color' => '#FF5733',
        ]);

        $response->assertRedirect(route('investment.types.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('investment_types', [
            'name' => 'Ação',
            'code' => 'acao',
            'has_dividends' => true,
            'color' => '#FF5733',
        ]);
    }

    public function test_admin_cannot_create_investment_type_with_duplicate_code(): void
    {
        $admin = $this->createAdminUser();
        InvestmentType::factory()->create(['code' => 'acao']);

        $response = $this->actingAs($admin)->post(route('investment.types.store'), [
            'name' => 'Ação Preferencial',
            'code' => 'acao',
            'color' => '#FF5733',
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_admin_cannot_create_investment_type_with_invalid_color(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->post(route('investment.types.store'), [
            'name' => 'Ação',
            'code' => 'acao',
            'color' => 'invalid-color',
        ]);

        $response->assertSessionHasErrors('color');
    }

    public function test_admin_can_view_investment_type(): void
    {
        $admin = $this->createAdminUser();
        $type = InvestmentType::factory()->create();

        $response = $this->actingAs($admin)->get(route('investment.types.show', $type));

        $response->assertStatus(200);
        $response->assertViewIs('investment.types.show');
        $response->assertViewHas('type');
    }

    public function test_admin_can_view_edit_form(): void
    {
        $admin = $this->createAdminUser();
        $type = InvestmentType::factory()->create();

        $response = $this->actingAs($admin)->get(route('investment.types.edit', $type));

        $response->assertStatus(200);
        $response->assertViewIs('investment.types.edit');
        $response->assertViewHas('type');
    }

    public function test_admin_can_update_investment_type(): void
    {
        $admin = $this->createAdminUser();
        $type = InvestmentType::factory()->create();

        $response = $this->actingAs($admin)->put(route('investment.types.update', $type), [
            'name' => 'Ação Atualizada',
            'code' => 'acao_updated',
            'description' => 'Descrição atualizada',
            'has_dividends' => false,
            'color' => '#00FF00',
        ]);

        $response->assertRedirect(route('investment.types.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('investment_types', [
            'id' => $type->id,
            'name' => 'Ação Atualizada',
            'code' => 'acao_updated',
            'color' => '#00FF00',
        ]);
    }

    public function test_admin_can_delete_investment_type_without_investments(): void
    {
        $admin = $this->createAdminUser();
        $type = InvestmentType::factory()->create();

        $response = $this->actingAs($admin)->delete(route('investment.types.destroy', $type));

        $response->assertRedirect(route('investment.types.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('investment_types', ['id' => $type->id]);
    }

    public function test_admin_cannot_delete_investment_type_with_investments(): void
    {
        $admin = $this->createAdminUser();
        $type = InvestmentType::factory()->create();
        Investment::factory()->create(['investment_type_id' => $type->id]);

        $response = $this->actingAs($admin)->delete(route('investment.types.destroy', $type));

        $response->assertRedirect(route('investment.types.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('investment_types', ['id' => $type->id]);
    }

    public function test_unauthenticated_user_cannot_access_investment_types(): void
    {
        $response = $this->get(route('investment.types.index'));

        $response->assertRedirect(route('login'));
    }

    /**
     * Regressão de isolamento: a página do tipo listava os ativos de TODOS os usuários.
     */
    public function test_type_page_shows_only_the_admins_own_investments(): void
    {
        $admin = $this->createAdminUser();
        $type = InvestmentType::factory()->create(['name' => 'FII']);
        Investment::factory()->create(['user_id' => $admin->id, 'investment_type_id' => $type->id, 'ticker' => 'MEU11']);
        Investment::factory()->create([
            'user_id' => User::factory()->create()->id,
            'investment_type_id' => $type->id,
            'ticker' => 'ALHEIO11',
            'name' => 'Fundo de outra pessoa',
        ]);

        $this->actingAs($admin)->get(route('investment.types.show', $type))
            ->assertOk()
            ->assertSee('MEU11')
            ->assertDontSee('ALHEIO11')
            ->assertDontSee('Fundo de outra pessoa')
            // Só o total agregado aparece
            ->assertSee('somando todos os usuários');
    }

    public function test_type_pages_render_for_admin(): void
    {
        $admin = $this->createAdminUser();
        $type = InvestmentType::factory()->create(['name' => 'Ação', 'code' => 'acao']);

        $this->actingAs($admin)->get(route('investment.types.index'))->assertOk()->assertSee('Ação')->assertSee('Confirmar exclusão', false);
        $this->actingAs($admin)->get(route('investment.types.create'))->assertOk()->assertSee('name="code"', false);
        $this->actingAs($admin)->get(route('investment.types.edit', $type))->assertOk()->assertSee('value="acao"', false);
    }
}
