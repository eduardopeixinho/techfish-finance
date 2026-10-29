<?php

namespace Tests\Feature\Investment;

use App\Models\Investment\Investment;
use App\Models\Investment\InvestmentTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Posição do ativo pelas compras e vendas: custo médio com taxas (InvestmentPositionService).
 * Valores em R$; quantidades em cotas inteiras.
 */
class InvestmentPositionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Investment $investment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->investment = Investment::factory()->for($this->user)->create([
            'ticker' => 'TEST11', 'current_price' => 12.00, 'current_quantity' => 0,
            'total_invested' => 0, 'current_value' => 0, 'profit_loss' => 0, 'profit_loss_percentage' => 0,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function trade(string $type, int $quantity, float $price, string $date, float $fees = 0, array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->user)->post(route('investment.transactions.store'), array_merge([
            'investment_id' => $this->investment->id,
            'type' => $type,
            'quantity' => $quantity,
            'price' => $price,
            'fees' => $fees,
            'transaction_date' => $date,
        ], $overrides));
    }

    public function test_buy_sets_quantity_cost_with_fees_value_and_profit(): void
    {
        $this->trade('buy', 100, 10.00, '2026-01-10', fees: 5.00)->assertSessionHasNoErrors();

        $this->investment->refresh();
        $this->assertSame(100, $this->investment->current_quantity);
        $this->assertEquals(1005.00, (float) $this->investment->total_invested);   // 100 × 10 + 5 de taxa
        $this->assertEquals(1200.00, (float) $this->investment->current_value);    // 100 × 12
        $this->assertEquals(195.00, (float) $this->investment->profit_loss);
        $this->assertEquals(19.40, (float) $this->investment->profit_loss_percentage);
    }

    public function test_partial_sell_reduces_cost_by_average_cost(): void
    {
        $this->trade('buy', 100, 10.00, '2026-01-10');
        $this->trade('buy', 100, 14.00, '2026-02-10');               // custo médio = 12,00
        $this->trade('sell', 50, 20.00, '2026-03-10', fees: 3.00)    // taxa da venda não mexe no custo
            ->assertSessionHasNoErrors();

        $this->investment->refresh();
        $this->assertSame(150, $this->investment->current_quantity);
        $this->assertEquals(1800.00, (float) $this->investment->total_invested);   // 2400 − 50 × 12
        $this->assertEquals(1800.00, (float) $this->investment->current_value);    // 150 × 12
    }

    public function test_selling_everything_resets_cost(): void
    {
        $this->trade('buy', 10, 10.00, '2026-01-10');
        $this->trade('sell', 10, 15.00, '2026-02-10');

        $this->investment->refresh();
        $this->assertSame(0, $this->investment->current_quantity);
        $this->assertEquals(0.0, (float) $this->investment->total_invested);
        $this->assertEquals(0.0, (float) $this->investment->current_value);
    }

    public function test_sell_larger_than_position_is_rejected(): void
    {
        $this->trade('buy', 10, 10.00, '2026-01-10');

        $this->trade('sell', 11, 10.00, '2026-02-10')->assertSessionHasErrors('quantity');

        $this->assertSame(1, InvestmentTransaction::count());
        $this->assertSame(10, $this->investment->fresh()->current_quantity);
    }

    public function test_sell_dated_before_the_buy_is_rejected(): void
    {
        $this->trade('buy', 10, 10.00, '2026-03-10');

        $this->trade('sell', 5, 10.00, '2026-02-10')->assertSessionHasErrors('quantity');
    }

    public function test_editing_a_buy_cannot_break_a_later_sell(): void
    {
        $this->trade('buy', 10, 10.00, '2026-01-10');
        $this->trade('sell', 8, 12.00, '2026-02-10');
        $buy = InvestmentTransaction::where('type', 'buy')->firstOrFail();

        $this->actingAs($this->user)->put(route('investment.transactions.update', $buy), [
            'investment_id' => $this->investment->id, 'type' => 'buy', 'quantity' => 5,
            'price' => 10.00, 'transaction_date' => '2026-01-10',
        ])->assertSessionHasErrors('quantity');

        $this->assertSame(10, $buy->fresh()->quantity);
    }

    public function test_editing_a_transaction_recalculates_the_position(): void
    {
        $this->trade('buy', 10, 10.00, '2026-01-10');
        $buy = InvestmentTransaction::firstOrFail();

        $this->actingAs($this->user)->put(route('investment.transactions.update', $buy), [
            'investment_id' => $this->investment->id, 'type' => 'buy', 'quantity' => 20,
            'price' => 10.00, 'transaction_date' => '2026-01-10',
        ])->assertSessionHasNoErrors();

        $this->assertSame(20, $this->investment->fresh()->current_quantity);
        $this->assertEquals(200.00, (float) $this->investment->fresh()->total_invested);
    }

    public function test_moving_a_transaction_to_another_asset_recalculates_both(): void
    {
        $other = Investment::factory()->for($this->user)->create(['ticker' => 'OUTR3', 'current_price' => 5.00, 'current_quantity' => 0, 'total_invested' => 0]);
        $this->trade('buy', 10, 10.00, '2026-01-10');
        $buy = InvestmentTransaction::firstOrFail();

        $this->actingAs($this->user)->put(route('investment.transactions.update', $buy), [
            'investment_id' => $other->id, 'type' => 'buy', 'quantity' => 10,
            'price' => 10.00, 'transaction_date' => '2026-01-10',
        ])->assertSessionHasNoErrors();

        $this->assertSame(0, $this->investment->fresh()->current_quantity);
        $this->assertSame(10, $other->fresh()->current_quantity);
    }

    public function test_deleting_a_transaction_recalculates_the_position(): void
    {
        $this->trade('buy', 10, 10.00, '2026-01-10');
        $this->trade('buy', 5, 10.00, '2026-02-10');
        $second = InvestmentTransaction::latest('id')->firstOrFail();

        $this->actingAs($this->user)->delete(route('investment.transactions.destroy', $second))->assertRedirect();

        $this->assertSame(10, $this->investment->fresh()->current_quantity);
    }

    public function test_deleting_the_only_transaction_zeroes_the_position(): void
    {
        $this->trade('buy', 10, 10.00, '2026-01-10');

        $this->actingAs($this->user)->delete(route('investment.transactions.destroy', InvestmentTransaction::firstOrFail()));

        $this->assertSame(0, $this->investment->fresh()->current_quantity);
        $this->assertEquals(0.0, (float) $this->investment->fresh()->total_invested);
    }

    public function test_deleting_a_buy_needed_by_a_later_sell_is_blocked(): void
    {
        $this->trade('buy', 10, 10.00, '2026-01-10');
        $this->trade('sell', 8, 12.00, '2026-02-10');
        $buy = InvestmentTransaction::where('type', 'buy')->firstOrFail();

        $this->actingAs($this->user)->delete(route('investment.transactions.destroy', $buy))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertNotSoftDeleted($buy);
        $this->assertSame(2, $this->investment->fresh()->current_quantity);
    }

    public function test_price_update_recalculates_value_and_history_overrides_typed_quantity(): void
    {
        $this->trade('buy', 10, 10.00, '2026-01-10');

        $this->actingAs($this->user)->put(route('investment.investments.update', $this->investment), [
            'investment_type_id' => $this->investment->investment_type_id,
            'ticker' => 'TEST11', 'name' => $this->investment->name,
            'current_price' => 15.00, 'current_quantity' => 999,
        ])->assertSessionHasNoErrors();

        $this->investment->refresh();
        $this->assertSame(10, $this->investment->current_quantity);
        $this->assertEquals(150.00, (float) $this->investment->current_value);
        $this->assertEquals(50.00, (float) $this->investment->profit_loss);
    }

    public function test_asset_without_history_keeps_typed_quantity_and_has_no_profit(): void
    {
        $this->actingAs($this->user)->post(route('investment.investments.store'), [
            'investment_type_id' => $this->investment->investment_type_id,
            'ticker' => 'MANU3', 'name' => 'Ativo manual', 'current_price' => 8.00, 'current_quantity' => 50,
        ])->assertSessionHasNoErrors();

        $manual = Investment::where('ticker', 'MANU3')->firstOrFail();
        $this->assertSame(50, $manual->current_quantity);
        $this->assertEquals(400.00, (float) $manual->current_value);
        $this->assertEquals(0.0, (float) $manual->profit_loss); // sem custo conhecido
    }

    public function test_quantity_field_is_read_only_once_there_is_history(): void
    {
        $this->actingAs($this->user)->get(route('investment.investments.edit', $this->investment))
            ->assertOk()
            ->assertDontSee('aria-readonly="true"', false);

        $this->trade('buy', 10, 10.00, '2026-01-10');

        $this->actingAs($this->user)->get(route('investment.investments.edit', $this->investment))
            ->assertOk()
            ->assertSee('aria-readonly="true"', false)
            ->assertSee('Calculada pelas compras e vendas');
    }

    public function test_recalculate_command_dry_run_shows_changes_without_writing(): void
    {
        // Dados antigos: lançamento gravado sem recálculo (como na produção antes da correção)
        InvestmentTransaction::factory()->for($this->user)->for($this->investment)->create([
            'type' => 'buy', 'quantity' => 10, 'price' => 10, 'total_amount' => 100, 'fees' => 0, 'transaction_date' => '2026-01-10',
        ]);

        $this->artisan('investment:recalculate', ['--dry-run' => true])
            ->expectsOutputToContain('TEST11')
            ->expectsOutputToContain('Nada foi gravado')
            ->assertSuccessful();
        $this->assertSame(0, $this->investment->fresh()->current_quantity);

        $this->artisan('investment:recalculate')->expectsOutputToContain('1 ativo(s) atualizados')->assertSuccessful();
        $this->assertSame(10, $this->investment->fresh()->current_quantity);
        $this->assertEquals(100.00, (float) $this->investment->fresh()->total_invested);

        $this->artisan('investment:recalculate')->expectsOutputToContain('Todas as posições já estão corretas')->assertSuccessful();
    }
}
