<?php

namespace Tests\Feature\Investment;

use App\Models\Investment\Investment;
use App\Models\Investment\InvestmentTransaction;
use App\Models\Investment\InvestmentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvestmentTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_their_transactions(): void
    {
        $user = User::factory()->create();
        $type = InvestmentType::factory()->create();
        $investment = Investment::factory()->create([
            'user_id' => $user->id,
            'investment_type_id' => $type->id,
        ]);
        InvestmentTransaction::factory()->count(3)->create([
            'user_id' => $user->id,
            'investment_id' => $investment->id,
        ]);

        $response = $this->actingAs($user)->get(route('investment.transactions.index'));

        $response->assertOk();
        $response->assertViewHas('transactions');
    }

    public function test_user_can_create_a_buy_transaction(): void
    {
        $user = User::factory()->create();
        $type = InvestmentType::factory()->create();
        $investment = Investment::factory()->create([
            'user_id' => $user->id,
            'investment_type_id' => $type->id,
        ]);

        $response = $this->actingAs($user)->post(route('investment.transactions.store'), [
            'investment_id' => $investment->id,
            'type' => 'buy',
            'quantity' => 10,
            'price' => 25.50,
            'transaction_date' => '2025-06-01',
        ]);

        $response->assertRedirect(route('investment.transactions.index'));
        $this->assertDatabaseHas('investment_transactions', [
            'user_id' => $user->id,
            'investment_id' => $investment->id,
            'type' => 'buy',
            'quantity' => 10,
        ]);
    }

    public function test_user_cannot_create_transaction_for_another_users_investment(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $type = InvestmentType::factory()->create();
        $otherInvestment = Investment::factory()->create([
            'user_id' => $other->id,
            'investment_type_id' => $type->id,
        ]);

        $response = $this->actingAs($user)->post(route('investment.transactions.store'), [
            'investment_id' => $otherInvestment->id,
            'type' => 'buy',
            'quantity' => 5,
            'price' => 100.00,
            'transaction_date' => '2025-06-01',
        ]);

        $response->assertNotFound();
    }

    public function test_user_cannot_view_another_users_transaction(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $type = InvestmentType::factory()->create();
        $otherInvestment = Investment::factory()->create([
            'user_id' => $other->id,
            'investment_type_id' => $type->id,
        ]);
        $transaction = InvestmentTransaction::factory()->create([
            'user_id' => $other->id,
            'investment_id' => $otherInvestment->id,
        ]);

        $response = $this->actingAs($user)->get(route('investment.transactions.show', $transaction));

        $response->assertForbidden();
    }

    public function test_user_can_delete_their_transaction(): void
    {
        $user = User::factory()->create();
        $type = InvestmentType::factory()->create();
        $investment = Investment::factory()->create([
            'user_id' => $user->id,
            'investment_type_id' => $type->id,
        ]);
        $transaction = InvestmentTransaction::factory()->create([
            'user_id' => $user->id,
            'investment_id' => $investment->id,
        ]);

        $response = $this->actingAs($user)->delete(route('investment.transactions.destroy', $transaction));

        $response->assertRedirect(route('investment.transactions.index'));
        $this->assertSoftDeleted($transaction);
    }

    public function test_user_cannot_delete_another_users_transaction(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $type = InvestmentType::factory()->create();
        $otherInvestment = Investment::factory()->create([
            'user_id' => $other->id,
            'investment_type_id' => $type->id,
        ]);
        $transaction = InvestmentTransaction::factory()->create([
            'user_id' => $other->id,
            'investment_id' => $otherInvestment->id,
        ]);

        $response = $this->actingAs($user)->delete(route('investment.transactions.destroy', $transaction));

        $response->assertForbidden();
    }

    /**
     * Regressão: a página do ativo manda ?investment_id= mas o controller lia ?investment=.
     */
    public function test_create_form_preselects_investment_from_asset_page(): void
    {
        $user = User::factory()->create();
        Investment::factory()->create(['user_id' => $user->id, 'ticker' => 'AAAA3']);
        $investment = Investment::factory()->create(['user_id' => $user->id, 'ticker' => 'BBBB3', 'current_price' => 12.34]);

        $response = $this->actingAs($user)
            ->get(route('investment.transactions.create', ['investment_id' => $investment->id]))
            ->assertOk()
            ->assertDontSee('name="broker"', false);

        $this->assertMatchesRegularExpression(
            '/<option value="'.$investment->id.'" data-price="12\\.34[0-9]*"\\s+selected/',
            $response->getContent()
        );
    }

    /**
     * Regressão: depois de excluir (soft delete) o ativo, a lista quebrava com relação nula.
     */
    public function test_history_still_renders_after_investment_is_deleted(): void
    {
        $user = User::factory()->create();
        $investment = Investment::factory()->create(['user_id' => $user->id, 'ticker' => 'SUMIU3']);
        $transaction = InvestmentTransaction::factory()->create(['user_id' => $user->id, 'investment_id' => $investment->id]);

        $this->actingAs($user)->delete(route('investment.investments.destroy', $investment))->assertRedirect();

        $this->actingAs($user)->get(route('investment.transactions.index'))
            ->assertOk()
            ->assertSee('SUMIU3')
            ->assertSee('Excluído');

        $this->actingAs($user)->get(route('investment.transactions.show', $transaction))
            ->assertOk()
            ->assertSee('Ativo excluído');

        $this->actingAs($user)->get(route('investment.dividends.index'))->assertOk();
    }

    public function test_edit_form_prefills_operation(): void
    {
        $user = User::factory()->create();
        $investment = Investment::factory()->create(['user_id' => $user->id]);
        $transaction = InvestmentTransaction::factory()->create([
            'user_id' => $user->id, 'investment_id' => $investment->id, 'type' => 'sell', 'quantity' => 7,
        ]);

        $this->actingAs($user)->get(route('investment.transactions.edit', $transaction))
            ->assertOk()
            ->assertSee('\\u0022quantity\\u0022:7', false)
            ->assertSee('Excluir operação');
    }
}
