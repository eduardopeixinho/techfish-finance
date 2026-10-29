<?php

namespace Tests\Feature\Integration;

use App\Models\Investment\Investment;
use App\Models\Investment\InvestmentDividend;
use App\Models\Investment\InvestmentTransaction;
use App\Models\Investment\InvestmentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvestmentFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Testa o fluxo completo de investimento:
     * 1. Admin cria tipo de investimento
     * 2. Usuário cria investimento
     * 3. Usuário adiciona transação de compra
     * 4. Usuário adiciona dividendo
     * 5. Usuário visualiza relatório
     */
    public function test_complete_investment_flow(): void
    {
        // Arrange
        $user = User::factory()->create();
        $investmentType = InvestmentType::factory()->create([
            'name' => 'Ação',
            'code' => 'acao',
            'has_dividends' => true,
        ]);

        // Act & Assert - Passo 1: Usuário cria investimento
        $response = $this->actingAs($user)->post(route('investment.investments.store'), [
            'investment_type_id' => $investmentType->id,
            'ticker' => 'PETR4',
            'name' => 'Petrobras PN',
            'description' => 'Ações da Petrobras',
            'current_price' => 35.50,
            'current_quantity' => 100,
        ]);

        $response->assertRedirect(route('investment.investments.index'));

        $investment = Investment::where('ticker', 'PETR4')->first();
        $this->assertNotNull($investment);
        $this->assertEquals($user->id, $investment->user_id);
        $this->assertEquals(100, $investment->current_quantity);

        // Passo 2: Usuário adiciona transação de compra
        $response = $this->actingAs($user)->post(route('investment.transactions.store'), [
            'investment_id' => $investment->id,
            'type' => 'buy',
            'transaction_date' => '2025-01-15',
            'quantity' => 100,
            'price' => 35.50,
            'fees' => 10.00,
            'notes' => 'Compra inicial',
        ]);

        $response->assertRedirect(route('investment.transactions.index'));

        $transaction = InvestmentTransaction::where('investment_id', $investment->id)->first();
        $this->assertNotNull($transaction);
        $this->assertEquals('buy', $transaction->type);
        $this->assertEquals(100, $transaction->quantity);
        $this->assertEquals(3550.00, $transaction->total_amount); // 100 * 35.50

        // Passo 3: Usuário adiciona dividendo
        $response = $this->actingAs($user)->post(route('investment.dividends.store'), [
            'investment_id' => $investment->id,
            'type' => 'dividend',
            'amount_per_share' => 1.50,
            'quantity_owned' => 100,
            'record_date' => '2025-06-10',
            'payment_date' => '2025-06-15',
            'tax_withheld' => 15.00,
        ]);

        $response->assertRedirect(route('investment.dividends.index'));

        $dividend = InvestmentDividend::where('investment_id', $investment->id)->first();
        $this->assertNotNull($dividend);
        $this->assertEquals(150.00, $dividend->amount); // 1.50 * 100
        $this->assertEquals(135.00, $dividend->net_amount); // 150 - 15

        // Passo 4: Usuário visualiza relatório de dividendos
        $response = $this->actingAs($user)->get(route('investment.reports.dividends'));

        $response->assertStatus(200);
        $response->assertViewHas('dividends');
        $response->assertViewHas('totalGross', 150.00);
        $response->assertViewHas('totalNet', 135.00);
    }

    /**
     * Testa o fluxo de múltiplas transações de investimento
     */
    public function test_investment_multiple_transactions_flow(): void
    {
        $user = User::factory()->create();
        $investmentType = InvestmentType::factory()->create();

        // Cria investimento
        $investment = Investment::factory()->create([
            'user_id' => $user->id,
            'investment_type_id' => $investmentType->id,
            'ticker' => 'VALE3',
            'current_quantity' => 0,
            'total_invested' => 0,
        ]);

        // Compra 100 ações a R$ 50,00
        $response = $this->actingAs($user)->post(route('investment.transactions.store'), [
            'investment_id' => $investment->id,
            'type' => 'buy',
            'transaction_date' => '2025-01-10',
            'quantity' => 100,
            'price' => 50.00,
            'fees' => 5.00,
        ]);

        $response->assertRedirect(route('investment.transactions.index'));

        $transaction1 = InvestmentTransaction::where('investment_id', $investment->id)
            ->where('type', 'buy')
            ->first();
        $this->assertNotNull($transaction1);
        $this->assertEquals(100, $transaction1->quantity);
        $this->assertEquals(5000.00, $transaction1->total_amount);

        // Compra mais 50 ações a R$ 55,00
        $response = $this->actingAs($user)->post(route('investment.transactions.store'), [
            'investment_id' => $investment->id,
            'type' => 'buy',
            'transaction_date' => '2025-02-15',
            'quantity' => 50,
            'price' => 55.00,
            'fees' => 5.00,
        ]);

        $response->assertRedirect(route('investment.transactions.index'));

        $transactions = InvestmentTransaction::where('investment_id', $investment->id)
            ->where('type', 'buy')
            ->get();
        $this->assertCount(2, $transactions);

        // Vende 30 ações a R$ 60,00
        $response = $this->actingAs($user)->post(route('investment.transactions.store'), [
            'investment_id' => $investment->id,
            'type' => 'sell',
            'transaction_date' => '2025-03-20',
            'quantity' => 30,
            'price' => 60.00,
            'fees' => 3.00,
        ]);

        $response->assertRedirect(route('investment.transactions.index'));

        $sellTransaction = InvestmentTransaction::where('investment_id', $investment->id)
            ->where('type', 'sell')
            ->first();
        $this->assertNotNull($sellTransaction);
        $this->assertEquals(30, $sellTransaction->quantity);
        $this->assertEquals(1800.00, $sellTransaction->total_amount);
    }
}
