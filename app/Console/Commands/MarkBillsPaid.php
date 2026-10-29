<?php

namespace App\Console\Commands;

use App\Models\Finance\Account;
use App\Services\BillPaymentService;
use App\Services\CreditCardBillService;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Marca como pagas (sem lançamento, sem mexer em saldo) as faturas com vencimento até uma data.
 * Para o histórico que foi pago fora do app. Use --dry-run primeiro.
 */
#[Signature('bills:mark-paid {--until= : Vencimento até esta data (AAAA-MM-DD)} {--user= : Só os cartões deste usuário (id)} {--dry-run : Só mostra, sem gravar}')]
#[Description('Marca como pagas as faturas de cartão vencidas até uma data (histórico pago fora do app)')]
class MarkBillsPaid extends Command
{
    public function handle(CreditCardBillService $bills, BillPaymentService $payments): int
    {
        if (! $this->option('until')) {
            $this->error('Informe --until=AAAA-MM-DD (vencimento até essa data).');

            return self::FAILURE;
        }

        $until = Carbon::parse($this->option('until'))->endOfDay();
        $dryRun = (bool) $this->option('dry-run');
        $rows = [];
        $sum = 0.0;

        $cards = Account::where('type', 'credit_card')
            ->when($this->option('user'), fn ($query, $userId) => $query->where('user_id', $userId))
            ->orderBy('user_id')->orderBy('name')
            ->get();

        foreach ($cards as $card) {
            foreach ($bills->getAvailableBills($card) as $bill) {
                if (! $bill['due_date'] || Carbon::parse($bill['due_date'])->gt($until) || $bill['remaining_amount'] <= 0) {
                    continue;
                }

                $rows[] = [$card->user_id, $card->name, $bill['bill_identifier'], Carbon::parse($bill['due_date'])->format('d/m/Y'), $this->brl($bill['remaining_amount'])];
                $sum += $bill['remaining_amount'];

                if (! $dryRun) {
                    $payments->markPaid($card, $bill['bill_identifier'], $bill['remaining_amount'], Carbon::parse($bill['due_date'])->toDateString());
                }
            }
        }

        if ($rows === []) {
            $this->info('Nenhuma fatura em aberto com vencimento até '.$until->format('d/m/Y').'.');

            return self::SUCCESS;
        }

        $this->table(['Usuário', 'Cartão', 'Fatura', 'Vencimento', 'Em aberto'], $rows);

        $count = count($rows);
        $dryRun
            ? $this->warn("{$count} fatura(s), {$this->brl($sum)}, seriam marcadas como pagas. Nada foi gravado (--dry-run).")
            : $this->info("{$count} fatura(s) marcadas como pagas ({$this->brl($sum)}).");

        return self::SUCCESS;
    }

    private function brl(float $value): string
    {
        return 'R$ '.number_format($value, 2, ',', '.');
    }
}
