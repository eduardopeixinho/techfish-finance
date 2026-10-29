<?php

namespace App\Services;

use App\Models\Investment\Investment;
use App\Models\Investment\InvestmentTransaction;
use Illuminate\Support\Collection;

/**
 * Posição de um ativo (quantidade, custo e valor atual) calculada pelas compras e vendas.
 *
 * Regra: custo médio (a da Receita Federal), com taxas no custo.
 *   - Compra: quantidade += cotas; custo += valor (cotas × preço) + taxas, em R$.
 *   - Venda: o custo cai pelo custo médio das cotas vendidas; as taxas da venda não mexem no custo.
 *   - Zerou a posição: o custo volta a zero (a próxima compra começa um custo médio novo).
 * Os lançamentos são processados em ordem de data (e de id, no mesmo dia).
 *
 * Ativo que nunca teve lançamento (nem excluído): a quantidade é a digitada no cadastro (manual)
 * e o custo fica como está. Se já teve e perdeu todos, a posição é zero.
 * Valor atual = quantidade × cotação (current_price); lucro = valor atual − custo.
 */
class InvestmentPositionService
{
    /**
     * Grava no ativo a posição calculada.
     *
     * @param  bool  $fromHistory  força o cálculo pelo histórico (ex.: o ativo acabou de perder um lançamento movido para outro)
     */
    public function recalculate(Investment $investment, bool $fromHistory = false): Investment
    {
        $investment->forceFill($this->calculate($investment, $fromHistory))->save();

        return $investment;
    }

    /**
     * Posição calculada, sem gravar (usado também pelo `investment:recalculate --dry-run`).
     *
     * @return array{current_quantity: int, total_invested: float, average_price: float, current_value: float, profit_loss: float, profit_loss_percentage: float}
     */
    public function calculate(Investment $investment, bool $fromHistory = false): array
    {
        $transactions = $investment->transactions()->get();
        $isManual = ! $fromHistory && $transactions->isEmpty()
            && ! $investment->transactions()->onlyTrashed()->exists();

        if ($isManual) {
            $quantity = (int) $investment->current_quantity;
            $invested = (float) $investment->total_invested;
        } else {
            ['quantity' => $quantity, 'invested' => $invested] = $this->position($transactions);
        }

        $currentValue = round($quantity * (float) $investment->current_price, 2);
        // Sem custo conhecido (ativo manual sem histórico), não há como medir lucro
        $profitLoss = $invested > 0 ? round($currentValue - $invested, 2) : 0.0;

        return [
            'current_quantity' => max(0, $quantity),
            'total_invested' => round($invested, 2),
            'average_price' => $quantity > 0 ? round($invested / $quantity, 4) : 0.0,
            'current_value' => $currentValue,
            'profit_loss' => $profitLoss,
            'profit_loss_percentage' => $invested > 0 ? round($profitLoss / $invested * 100, 2) : 0.0,
        ];
    }

    /**
     * Quantidade e custo a partir dos lançamentos; `valid` = false se alguma venda passa da posição na data.
     *
     * @param  Collection<int, InvestmentTransaction>  $transactions
     * @return array{quantity: int, invested: float, valid: bool}
     */
    public function position(Collection $transactions): array
    {
        $quantity = 0;
        $invested = 0.0;
        $valid = true;

        $ordered = $transactions->sortBy([
            fn (InvestmentTransaction $a, InvestmentTransaction $b): int => $a->transaction_date <=> $b->transaction_date,
            // Lançamento novo (sem id) vai por último no mesmo dia
            fn (InvestmentTransaction $a, InvestmentTransaction $b): int => ($a->id ?? PHP_INT_MAX) <=> ($b->id ?? PHP_INT_MAX),
        ]);

        foreach ($ordered as $transaction) {
            $shares = (int) $transaction->quantity;

            if ($transaction->type === 'buy') {
                $quantity += $shares;
                $invested += (float) $transaction->total_amount + (float) $transaction->fees;

                continue;
            }

            if ($shares > $quantity) {
                $valid = false;
            }

            $averageCost = $quantity > 0 ? $invested / $quantity : 0.0;
            $quantity -= $shares;
            $invested = $quantity > 0 ? $invested - $averageCost * $shares : 0.0;
        }

        return ['quantity' => $quantity, 'invested' => max(0.0, $invested), 'valid' => $valid];
    }

    /**
     * O histórico continua coerente (nenhuma venda maior que a posição) se trocarmos/removermos um lançamento?
     *
     * @param  InvestmentTransaction|null  $without  lançamento que sai do histórico (editado ou excluído)
     * @param  InvestmentTransaction|null  $with  lançamento que entra (novo ou a versão editada, ainda não gravada)
     */
    public function historyStaysValid(Investment $investment, ?InvestmentTransaction $without = null, ?InvestmentTransaction $with = null): bool
    {
        $transactions = $investment->transactions()->get()
            ->reject(fn (InvestmentTransaction $transaction): bool => $without !== null && $transaction->is($without));

        if ($with !== null) {
            $transactions->push($with);
        }

        return $this->position($transactions)['valid'];
    }
}
