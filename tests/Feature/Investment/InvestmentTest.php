<?php

namespace Tests\Feature\Investment;

use App\Models\Investment\Investment;
use App\Models\Investment\InvestmentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvestmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_investment_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('investment.dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('investment.dashboard');
        $response->assertViewHas('totalInvested');
        $response->assertViewHas('currentValue');
        $response->assertViewHas('profitLoss');
        $response->assertViewHas('monthlyDividends');
    }

    public function test_user_can_list_their_investments(): void
    {
        $user = User::factory()->create();
        $investmentType = InvestmentType::factory()->create();

        Investment::factory()->count(3)->create([
            'user_id' => $user->id,
            'investment_type_id' => $investmentType->id,
        ]);

        $response = $this->actingAs($user)->get(route('investment.investments.index'));

        $response->assertStatus(200);
        $response->assertViewIs('investment.investments.index');
        $investments = $response->viewData('investments');
        $this->assertCount(3, $investments);
    }

    public function test_user_cannot_see_other_users_investments(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $investmentType = InvestmentType::factory()->create();

        Investment::factory()->count(2)->create([
            'user_id' => $user2->id,
            'investment_type_id' => $investmentType->id,
        ]);

        $response = $this->actingAs($user1)->get(route('investment.investments.index'));

        $response->assertStatus(200);
        $investments = $response->viewData('investments');
        $this->assertCount(0, $investments);
    }

    public function test_user_can_create_an_investment(): void
    {
        $user = User::factory()->create();
        $investmentType = InvestmentType::factory()->create();

        $response = $this->actingAs($user)->post(route('investment.investments.store'), [
            'investment_type_id' => $investmentType->id,
            'ticker' => 'PETR4',
            'name' => 'Petrobras PN',
            'description' => 'Ações da Petrobras',
            'current_price' => 35.50,
            'current_quantity' => 100,
        ]);

        $response->assertRedirect(route('investment.investments.index'));
        $this->assertDatabaseHas('investments', [
            'user_id' => $user->id,
            'ticker' => 'PETR4',
            'name' => 'Petrobras PN',
        ]);
    }

    public function test_user_cannot_create_investment_with_invalid_data(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('investment.investments.store'), [
            'ticker' => '', // obrigatório
            'name' => '', // obrigatório
        ]);

        $response->assertSessionHasErrors(['investment_type_id', 'ticker', 'name']);
    }

    public function test_user_can_view_their_investment(): void
    {
        $user = User::factory()->create();
        $investmentType = InvestmentType::factory()->create();
        $investment = Investment::factory()->create([
            'user_id' => $user->id,
            'investment_type_id' => $investmentType->id,
        ]);

        $response = $this->actingAs($user)->get(route('investment.investments.show', $investment));

        $response->assertStatus(200);
        $response->assertViewIs('investment.investments.show');
        $response->assertViewHas('investment');
    }

    public function test_user_cannot_view_other_users_investment(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $investmentType = InvestmentType::factory()->create();
        $investment = Investment::factory()->create([
            'user_id' => $user2->id,
            'investment_type_id' => $investmentType->id,
        ]);

        $response = $this->actingAs($user1)->get(route('investment.investments.show', $investment));

        $response->assertStatus(403);
    }

    public function test_user_can_update_their_investment(): void
    {
        $user = User::factory()->create();
        $investmentType = InvestmentType::factory()->create();
        $investment = Investment::factory()->create([
            'user_id' => $user->id,
            'investment_type_id' => $investmentType->id,
            'ticker' => 'PETR4',
        ]);

        $response = $this->actingAs($user)->put(route('investment.investments.update', $investment), [
            'investment_type_id' => $investmentType->id,
            'ticker' => 'PETR3',
            'name' => 'Petrobras ON',
            'current_price' => 40.00,
            'current_quantity' => 150,
        ]);

        $response->assertRedirect(route('investment.investments.index'));
        $this->assertDatabaseHas('investments', [
            'id' => $investment->id,
            'ticker' => 'PETR3',
            'name' => 'Petrobras ON',
        ]);
    }

    public function test_user_cannot_update_other_users_investment(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $investmentType = InvestmentType::factory()->create();
        $investment = Investment::factory()->create([
            'user_id' => $user2->id,
            'investment_type_id' => $investmentType->id,
        ]);

        $response = $this->actingAs($user1)->put(route('investment.investments.update', $investment), [
            'ticker' => 'XXXX4',
            'name' => 'Test',
        ]);

        $response->assertStatus(403);
    }

    public function test_user_can_delete_their_investment(): void
    {
        $user = User::factory()->create();
        $investmentType = InvestmentType::factory()->create();
        $investment = Investment::factory()->create([
            'user_id' => $user->id,
            'investment_type_id' => $investmentType->id,
        ]);

        $response = $this->actingAs($user)->delete(route('investment.investments.destroy', $investment));

        $response->assertRedirect(route('investment.investments.index'));
        $this->assertSoftDeleted($investment);
    }

    public function test_user_cannot_delete_other_users_investment(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $investmentType = InvestmentType::factory()->create();
        $investment = Investment::factory()->create([
            'user_id' => $user2->id,
            'investment_type_id' => $investmentType->id,
        ]);

        $response = $this->actingAs($user1)->delete(route('investment.investments.destroy', $investment));

        $response->assertStatus(403);
        $this->assertDatabaseHas('investments', ['id' => $investment->id]);
    }

    public function test_unauthenticated_user_cannot_access_investments(): void
    {
        $response = $this->get(route('investment.investments.index'));

        $response->assertRedirect(route('login'));
    }

    /**
     * Regressão: a tabela tem índice único (user_id, ticker) e o ticker repetido estourava 500.
     */
    public function test_duplicate_ticker_is_a_validation_error_not_a_crash(): void
    {
        $user = User::factory()->create();
        $type = InvestmentType::factory()->create();
        Investment::factory()->create(['user_id' => $user->id, 'investment_type_id' => $type->id, 'ticker' => 'VALE3']);
        Investment::factory()->create(['user_id' => $user->id, 'investment_type_id' => $type->id, 'ticker' => 'OIBR3'])->delete();

        foreach (['vale3', 'OIBR3'] as $ticker) {
            $this->actingAs($user)
                ->post(route('investment.investments.store'), [
                    'investment_type_id' => $type->id, 'ticker' => $ticker, 'name' => 'Duplicado',
                ])
                ->assertSessionHasErrors('ticker');
        }

        $this->assertSame(1, Investment::where('user_id', $user->id)->where('ticker', 'VALE3')->count());
    }

    public function test_same_ticker_is_allowed_for_another_user_and_is_uppercased(): void
    {
        $type = InvestmentType::factory()->create();
        Investment::factory()->create(['user_id' => User::factory()->create()->id, 'investment_type_id' => $type->id, 'ticker' => 'PETR4']);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('investment.investments.store'), [
                'investment_type_id' => $type->id, 'ticker' => ' petr4 ', 'name' => 'Petrobras',
            ])
            ->assertRedirect(route('investment.investments.index'));

        $this->assertDatabaseHas('investments', ['user_id' => $user->id, 'ticker' => 'PETR4']);
    }

    public function test_update_keeps_own_ticker_without_unique_error(): void
    {
        $user = User::factory()->create();
        $type = InvestmentType::factory()->create();
        $investment = Investment::factory()->create(['user_id' => $user->id, 'investment_type_id' => $type->id, 'ticker' => 'ITSA4']);

        $this->actingAs($user)
            ->put(route('investment.investments.update', $investment), [
                'investment_type_id' => $type->id, 'ticker' => 'itsa4', 'name' => 'Itaúsa',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('investment.investments.index'));

        $this->assertSame('Itaúsa', $investment->fresh()->name);
    }
}
