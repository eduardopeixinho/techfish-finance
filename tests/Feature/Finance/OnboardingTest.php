<?php

namespace Tests\Feature\Finance;

use App\Models\Finance\Account;
use App\Models\Finance\FinancialCategory;
use App\Models\Finance\Transaction;
use App\Models\User;
use App\Services\OnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Primeiros passos" no painel de quem acabou de entrar: conta → categorias → primeira transação.
 */
class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_user_sees_first_steps_with_actions(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('finance.dashboard'))
            ->assertOk()
            ->assertSee('Primeiros passos')
            ->assertSee('0 de 3 concluídos')
            ->assertSee('href="'.route('finance.accounts.create').'"', false)
            ->assertSee('action="'.route('finance.categories.defaults').'"', false)
            ->assertSee('Disponível depois de cadastrar uma conta');
    }

    public function test_steps_are_checked_as_they_are_done(): void
    {
        $user = User::factory()->create();
        Account::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->get(route('finance.dashboard'))
            ->assertSee('1 de 3 concluídos')
            ->assertSee('href="'.route('finance.transactions.create').'"', false);
    }

    public function test_card_disappears_when_all_steps_are_done(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);
        FinancialCategory::factory()->create(['user_id' => $user->id]);
        Transaction::factory()->create(['user_id' => $user->id, 'account_id' => $account->id]);

        $this->actingAs($user)->get(route('finance.dashboard'))
            ->assertOk()
            ->assertDontSee('Primeiros passos');
    }

    public function test_default_categories_are_created_once_and_only_for_the_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($user)->post(route('finance.categories.defaults'))
            ->assertRedirect(route('finance.dashboard'))
            ->assertSessionHas('success');
        $this->actingAs($user)->post(route('finance.categories.defaults'))
            ->assertSessionHas('success', 'Você já tem todas as categorias sugeridas.');

        $this->assertSame(count(OnboardingService::DEFAULT_CATEGORIES), FinancialCategory::where('user_id', $user->id)->count());
        $this->assertSame(0, FinancialCategory::where('user_id', $other->id)->count());
        $this->assertDatabaseHas('financial_categories', ['user_id' => $user->id, 'name' => 'Salário', 'type' => 'income']);
    }

    public function test_guest_cannot_create_default_categories(): void
    {
        $this->post(route('finance.categories.defaults'))->assertRedirect(route('login'));
        $this->assertDatabaseCount('financial_categories', 0);
    }
}
