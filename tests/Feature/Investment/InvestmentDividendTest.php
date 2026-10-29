<?php

namespace Tests\Feature\Investment;

use App\Models\Investment\Investment;
use App\Models\Investment\InvestmentDividend;
use App\Models\Investment\InvestmentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvestmentDividendTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_their_dividends(): void
    {
        $user = User::factory()->create();
        $investmentType = InvestmentType::factory()->create();
        $investment = Investment::factory()->create([
            'user_id' => $user->id,
            'investment_type_id' => $investmentType->id,
        ]);

        InvestmentDividend::factory()->count(3)->create([
            'user_id' => $user->id,
            'investment_id' => $investment->id,
        ]);

        $response = $this->actingAs($user)->get(route('investment.dividends.index'));

        $response->assertStatus(200);
        $response->assertViewIs('investment.dividends.index');
        $dividends = $response->viewData('dividends');
        $this->assertCount(3, $dividends);
    }

    public function test_user_cannot_see_other_users_dividends(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $investmentType = InvestmentType::factory()->create();
        $investment = Investment::factory()->create([
            'user_id' => $user2->id,
            'investment_type_id' => $investmentType->id,
        ]);

        InvestmentDividend::factory()->count(2)->create([
            'user_id' => $user2->id,
            'investment_id' => $investment->id,
        ]);

        $response = $this->actingAs($user1)->get(route('investment.dividends.index'));

        $response->assertStatus(200);
        $dividends = $response->viewData('dividends');
        $this->assertCount(0, $dividends);
    }

    public function test_user_can_create_a_dividend(): void
    {
        $user = User::factory()->create();
        $investmentType = InvestmentType::factory()->create();
        $investment = Investment::factory()->create([
            'user_id' => $user->id,
            'investment_type_id' => $investmentType->id,
        ]);

        $response = $this->actingAs($user)->post(route('investment.dividends.store'), [
            'investment_id' => $investment->id,
            'type' => 'dividend',
            'amount_per_share' => 1.50,
            'quantity_owned' => 100,
            'record_date' => '2025-06-10',
            'payment_date' => '2025-06-15',
            'tax_withheld' => 15.00,
            'notes' => 'Dividendo teste',
        ]);

        $response->assertRedirect(route('investment.dividends.index'));
        $this->assertDatabaseHas('investment_dividends', [
            'user_id' => $user->id,
            'investment_id' => $investment->id,
            'type' => 'dividend',
            'amount_per_share' => 1.50,
        ]);
    }

    public function test_user_cannot_create_dividend_for_other_users_investment(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $investmentType = InvestmentType::factory()->create();
        $investment = Investment::factory()->create([
            'user_id' => $user2->id,
            'investment_type_id' => $investmentType->id,
        ]);

        $response = $this->actingAs($user1)->post(route('investment.dividends.store'), [
            'investment_id' => $investment->id,
            'type' => 'dividend',
            'amount_per_share' => 1.50,
            'quantity_owned' => 100,
            'record_date' => '2025-06-10',
            'payment_date' => '2025-06-15',
            'tax_withheld' => 15.00,
        ]);

        // O controller usa findOrFail() que retorna 404 quando o investment não pertence ao usuário
        $response->assertStatus(404);
    }

    public function test_user_cannot_create_dividend_with_invalid_data(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('investment.dividends.store'), [
            'investment_id' => '', // obrigatório
            'type' => '', // obrigatório
            'amount_per_share' => '', // obrigatório
            'quantity_owned' => '', // obrigatório
            'record_date' => '', // obrigatório
            'payment_date' => '', // obrigatório
        ]);

        $response->assertSessionHasErrors(['investment_id', 'type', 'amount_per_share', 'quantity_owned', 'record_date', 'payment_date']);
    }

    public function test_user_can_view_their_dividend(): void
    {
        $user = User::factory()->create();
        $investmentType = InvestmentType::factory()->create();
        $investment = Investment::factory()->create([
            'user_id' => $user->id,
            'investment_type_id' => $investmentType->id,
        ]);
        $dividend = InvestmentDividend::factory()->create([
            'user_id' => $user->id,
            'investment_id' => $investment->id,
        ]);

        $response = $this->actingAs($user)->get(route('investment.dividends.show', $dividend));

        $response->assertStatus(200);
        $response->assertViewIs('investment.dividends.show');
        $response->assertViewHas('dividend');
    }

    public function test_user_cannot_view_other_users_dividend(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $investmentType = InvestmentType::factory()->create();
        $investment = Investment::factory()->create([
            'user_id' => $user2->id,
            'investment_type_id' => $investmentType->id,
        ]);
        $dividend = InvestmentDividend::factory()->create([
            'user_id' => $user2->id,
            'investment_id' => $investment->id,
        ]);

        $response = $this->actingAs($user1)->get(route('investment.dividends.show', $dividend));

        $response->assertStatus(403);
    }

    public function test_user_can_update_their_dividend(): void
    {
        $user = User::factory()->create();
        $investmentType = InvestmentType::factory()->create();
        $investment = Investment::factory()->create([
            'user_id' => $user->id,
            'investment_type_id' => $investmentType->id,
        ]);
        $dividend = InvestmentDividend::factory()->create([
            'user_id' => $user->id,
            'investment_id' => $investment->id,
            'amount_per_share' => 1.00,
        ]);

        $response = $this->actingAs($user)->put(route('investment.dividends.update', $dividend), [
            'investment_id' => $investment->id,
            'type' => 'jscp',
            'amount_per_share' => 2.00,
            'quantity_owned' => 100,
            'record_date' => '2025-07-10',
            'payment_date' => '2025-07-15',
            'tax_withheld' => 20.00,
        ]);

        $response->assertRedirect(route('investment.dividends.index'));
        $this->assertDatabaseHas('investment_dividends', [
            'id' => $dividend->id,
            'type' => 'jscp',
            'amount_per_share' => 2.00,
        ]);
    }

    public function test_user_cannot_update_other_users_dividend(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $investmentType = InvestmentType::factory()->create();
        $investment = Investment::factory()->create([
            'user_id' => $user2->id,
            'investment_type_id' => $investmentType->id,
        ]);
        $dividend = InvestmentDividend::factory()->create([
            'user_id' => $user2->id,
            'investment_id' => $investment->id,
        ]);

        $response = $this->actingAs($user1)->put(route('investment.dividends.update', $dividend), [
            'investment_id' => $investment->id,
            'type' => 'dividend',
            'amount_per_share' => 2.00,
            'quantity_owned' => 100,
            'record_date' => '2025-07-10',
            'payment_date' => '2025-07-15',
        ]);

        $response->assertStatus(403);
    }

    public function test_user_can_delete_their_dividend(): void
    {
        $user = User::factory()->create();
        $investmentType = InvestmentType::factory()->create();
        $investment = Investment::factory()->create([
            'user_id' => $user->id,
            'investment_type_id' => $investmentType->id,
        ]);
        $dividend = InvestmentDividend::factory()->create([
            'user_id' => $user->id,
            'investment_id' => $investment->id,
        ]);

        $response = $this->actingAs($user)->delete(route('investment.dividends.destroy', $dividend));

        $response->assertRedirect(route('investment.dividends.index'));
        $this->assertSoftDeleted($dividend);
    }

    public function test_user_cannot_delete_other_users_dividend(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $investmentType = InvestmentType::factory()->create();
        $investment = Investment::factory()->create([
            'user_id' => $user2->id,
            'investment_type_id' => $investmentType->id,
        ]);
        $dividend = InvestmentDividend::factory()->create([
            'user_id' => $user2->id,
            'investment_id' => $investment->id,
        ]);

        $response = $this->actingAs($user1)->delete(route('investment.dividends.destroy', $dividend));

        $response->assertStatus(403);
        $this->assertDatabaseHas('investment_dividends', ['id' => $dividend->id]);
    }

    public function test_user_can_view_dividend_reports(): void
    {
        $user = User::factory()->create();
        $investmentType = InvestmentType::factory()->create();
        $investment = Investment::factory()->create([
            'user_id' => $user->id,
            'investment_type_id' => $investmentType->id,
        ]);

        InvestmentDividend::factory()->count(3)->create([
            'user_id' => $user->id,
            'investment_id' => $investment->id,
        ]);

        $response = $this->actingAs($user)->get(route('investment.reports.dividends'));

        $response->assertStatus(200);
        $response->assertViewIs('investment.dividends.reports');
        $response->assertViewHas('dividends');
        $response->assertViewHas('totalGross');
        $response->assertViewHas('totalTax');
        $response->assertViewHas('totalNet');
        $response->assertViewHas('byType');
        $response->assertViewHas('byInvestment');
        $response->assertViewHas('monthlyEvolution');
    }

    public function test_dividend_reports_with_filters(): void
    {
        $user = User::factory()->create();
        $investmentType = InvestmentType::factory()->create();
        $investment = Investment::factory()->create([
            'user_id' => $user->id,
            'investment_type_id' => $investmentType->id,
        ]);

        InvestmentDividend::factory()->create([
            'user_id' => $user->id,
            'investment_id' => $investment->id,
            'type' => 'dividend',
            'payment_date' => '2025-06-15',
        ]);

        InvestmentDividend::factory()->create([
            'user_id' => $user->id,
            'investment_id' => $investment->id,
            'type' => 'jscp',
            'payment_date' => '2025-07-15',
        ]);

        $response = $this->actingAs($user)->get(route('investment.reports.dividends', [
            'year' => 2025,
            'month' => 6,
            'type' => 'dividend',
        ]));

        $response->assertStatus(200);
        $dividends = $response->viewData('dividends');
        $this->assertCount(1, $dividends);
    }

    public function test_unauthenticated_user_cannot_access_dividends(): void
    {
        $response = $this->get(route('investment.dividends.index'));

        $response->assertRedirect(route('login'));
    }

    /**
     * Regressões: (1) a página do ativo manda ?investment_id= mas o controller lia ?investment=;
     * (2) a lista filtrava por nomes fixos de tipo em vez de has_dividends.
     */
    public function test_create_form_lists_dividend_paying_assets_and_preselects(): void
    {
        $user = User::factory()->create();
        $payer = InvestmentType::factory()->create(['name' => 'Fundo de Infra', 'has_dividends' => true]);
        $nonPayer = InvestmentType::factory()->create(['name' => 'Tesouro', 'has_dividends' => false]);
        $infra = Investment::factory()->create(['user_id' => $user->id, 'investment_type_id' => $payer->id, 'ticker' => 'KDIF11']);
        Investment::factory()->create(['user_id' => $user->id, 'investment_type_id' => $nonPayer->id, 'ticker' => 'TESOURO']);

        $response = $this->actingAs($user)
            ->get(route('investment.dividends.create', ['investment_id' => $infra->id]))
            ->assertOk()
            ->assertSee('KDIF11')
            ->assertDontSee('TESOURO');

        $this->assertMatchesRegularExpression('/<option value="'.$infra->id.'"[^>]*\\s+selected/', $response->getContent());
    }

    public function test_reports_page_uses_portuguese_month_names_and_type_labels(): void
    {
        $user = User::factory()->create();
        $investment = Investment::factory()->create(['user_id' => $user->id]);
        InvestmentDividend::factory()->create(['user_id' => $user->id, 'investment_id' => $investment->id, 'type' => 'jscp']);

        $this->actingAs($user)->get(route('investment.reports.dividends'))
            ->assertOk()
            ->assertSee('Março')
            ->assertDontSee('March')
            ->assertSee('JCP');
    }

    public function test_show_and_edit_pages_render(): void
    {
        $user = User::factory()->create();
        $investment = Investment::factory()->create(['user_id' => $user->id, 'ticker' => 'TAEE11']);
        $dividend = InvestmentDividend::factory()->create(['user_id' => $user->id, 'investment_id' => $investment->id, 'type' => 'dividend']);

        $this->actingAs($user)->get(route('investment.dividends.show', $dividend))
            ->assertOk()
            ->assertSee('Dividendo de TAEE11');

        $this->actingAs($user)->get(route('investment.dividends.edit', $dividend))
            ->assertOk()
            ->assertSee('Excluir provento');
    }
}
