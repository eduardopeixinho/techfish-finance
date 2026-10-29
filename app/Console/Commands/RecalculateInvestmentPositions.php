<?php

namespace App\Console\Commands;

use App\Models\Investment\Investment;
use App\Services\InvestmentPositionService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Recalcula a posição (quantidade, custo médio, valor atual, lucro) de todos os ativos
 * pelas compras e vendas. Use --dry-run primeiro: mostra o antes/depois sem gravar.
 */
#[Signature('investment:recalculate {--dry-run : Só mostra o que mudaria, sem gravar} {--user= : Só os ativos deste usuário (id)}')]
#[Description('Recalcula a posição dos ativos pelas compras e vendas (custo médio, com taxas)')]
class RecalculateInvestmentPositions extends Command
{
    public function handle(InvestmentPositionService $positions): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $rows = [];
        $changed = 0;

        $investments = Investment::query()
            ->when($this->option('user'), fn ($query, $userId) => $query->where('user_id', $userId))
            ->withCount('transactions')
            ->orderBy('user_id')->orderBy('ticker')
            ->get();

        foreach ($investments as $investment) {
            $after = $positions->calculate($investment);
            $before = [
                'current_quantity' => (int) $investment->current_quantity,
                'total_invested' => (float) $investment->total_invested,
                'current_value' => (float) $investment->current_value,
            ];

            $differs = $before['current_quantity'] !== $after['current_quantity']
                || abs($before['total_invested'] - $after['total_invested']) >= 0.01
                || abs($before['current_value'] - $after['current_value']) >= 0.01;

            if (! $differs) {
                continue;
            }

            $changed++;
            $rows[] = [
                $investment->user_id,
                $investment->ticker,
                $investment->transactions_count ?: 'manual',
                $before['current_quantity'].' → '.$after['current_quantity'],
                $this->brl($before['total_invested']).' → '.$this->brl($after['total_invested']),
                $this->brl($before['current_value']).' → '.$this->brl($after['current_value']),
            ];

            if (! $dryRun) {
                $investment->forceFill($after)->save();
            }
        }

        if ($rows === []) {
            $this->info('Todas as posições já estão corretas.');

            return self::SUCCESS;
        }

        $this->table(['Usuário', 'Ativo', 'Lançamentos', 'Quantidade', 'Investido', 'Valor atual'], $rows);

        $dryRun
            ? $this->warn("{$changed} ativo(s) mudariam. Nada foi gravado (--dry-run).")
            : $this->info("{$changed} ativo(s) atualizados.");

        return self::SUCCESS;
    }

    private function brl(float $value): string
    {
        return 'R$ '.number_format($value, 2, ',', '.');
    }
}
