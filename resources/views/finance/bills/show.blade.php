{{--
    Detalhe de uma fatura de cartão ("MM-AAAA" = mês do vencimento). Valores em R$ via $money.
    Total = despesas − créditos (estornos). Pagamentos: transferência de uma conta para o cartão
    ("Pagar fatura") ou só marcar como paga (histórico não lançado, sem mexer em saldo).
    Composição por categoria: barras em um único tom, do maior para o menor, com valor e % escritos.
--}}
@php
    use Carbon\Carbon;

    $total = (float) $summary['total_amount'];
    $remaining = (float) $summary['remaining_amount'];
    [$statusLabel, $statusTone] = $summary['status'];
    $categories = collect($summary['categories'])->sortByDesc('total_amount')->values();
    $billMonth = $summary['due_date'] ? Carbon::parse($summary['due_date']) : Carbon::createFromFormat('!m-Y', str_replace('/', '-', $summary['bill_identifier']));
    $billLabel = ucfirst($billMonth->translatedFormat('F \d\e Y'));
    $canPay = $remaining > 0 && preg_match('/^\d{2}-\d{4}$/', $summary['bill_identifier']);
    $routeParams = ['account' => $account->id, 'billIdentifier' => $summary['bill_identifier']];
    $invalid = fn (string $field): string => $errors->has($field) ? 'aria-invalid=true aria-describedby='.$field.'-error' : '';
@endphp

<x-finance-layout>
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-ui.page-header :title="'Fatura de '.$billLabel" :kicker="$account->name.($account->bank ? ' · '.$account->bank : '')">
            <x-slot:actions>
                <x-ui.button variant="secondary" :href="route('finance.bills.index')">Faturas</x-ui.button>
                <x-ui.button variant="secondary" :href="route('finance.accounts.show', $account)">Cartão</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.flash />

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <x-ui.card>
                <p class="text-sm text-ink-2">Total da fatura</p>
                <p class="mt-1 font-serif text-3xl text-ink">{{ $money->format($total) }}</p>
                <p class="text-xs text-ink-2">{{ $summary['transaction_count'] }} {{ $summary['transaction_count'] == 1 ? 'lançamento' : 'lançamentos' }}</p>
            </x-ui.card>
            <x-ui.card>
                <p class="text-sm text-ink-2">Pago</p>
                <p class="mt-1 font-serif text-3xl text-ink">{{ $money->format((float) $summary['paid_amount']) }}</p>
            </x-ui.card>
            <x-ui.card>
                <p class="text-sm text-ink-2">Em aberto</p>
                <p class="mt-1 font-serif text-3xl text-ink">{{ $money->format($remaining) }}</p>
                <x-ui.badge :tone="$statusTone" class="mt-1">{{ $statusLabel }}</x-ui.badge>
            </x-ui.card>
            <x-ui.card>
                <p class="text-sm text-ink-2">Vencimento</p>
                <p class="mt-1 font-serif text-3xl text-ink">{{ $summary['due_date'] ? Carbon::parse($summary['due_date'])->format('d/m/Y') : '—' }}</p>
                @if ($summary['due_date'])
                    <p class="text-xs text-ink-2">{{ Carbon::parse($summary['due_date'])->diffForHumans() }}</p>
                @endif
            </x-ui.card>
        </div>

        {{-- Pagamento --}}
        @if ($canPay || $summary['payments']->isNotEmpty())
            <x-ui.card title="Pagamento" class="mb-6">
                @if ($canPay)
                    <form method="POST" action="{{ route('finance.bills.payments.store', $routeParams) }}"
                          class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
                        @csrf
                        <x-ui.field label="Pago com a conta" for="from_account_id" required class="sm:col-span-2">
                            <select id="from_account_id" name="from_account_id" required class="ui-input" {!! $invalid('from_account_id') !!}>
                                <option value="">Selecione</option>
                                @foreach ($fromAccounts as $fromAccount)
                                    <option value="{{ $fromAccount->id }}" @selected((string) old('from_account_id') === (string) $fromAccount->id)>{{ $fromAccount->name }}</option>
                                @endforeach
                            </select>
                        </x-ui.field>
                        <x-ui.field label="Valor (R$)" for="amount" required>
                            <input type="number" id="amount" name="amount" step="0.01" min="0.01" required inputmode="decimal"
                                   {{-- Com "ocultar valores" ligado, o campo não revela o valor em aberto --}}
                                   value="{{ old('amount', $money->hidden ? '' : sprintf('%.2f', $remaining)) }}" placeholder="0,00" class="ui-input" {!! $invalid('amount') !!}>
                        </x-ui.field>
                        <x-ui.field label="Data" for="paid_at" required>
                            <input type="date" id="paid_at" name="paid_at" required
                                   value="{{ old('paid_at', Carbon::today()->format('Y-m-d')) }}" class="ui-input" {!! $invalid('paid_at') !!}>
                        </x-ui.field>
                        <div class="sm:col-span-4 flex flex-wrap items-center gap-3">
                            <x-ui.button type="submit">Pagar fatura</x-ui.button>
                            <span class="text-xs text-ink-2">Registra uma transferência (boleto) da conta escolhida para o cartão.</span>
                        </div>
                    </form>

                    <div class="mt-5 pt-5 border-t border-line flex flex-wrap items-center gap-3">
                        <form method="POST" action="{{ route('finance.bills.mark-paid', $routeParams) }}" class="inline-flex">
                            @csrf
                            <x-ui.button type="submit" variant="secondary" size="sm">Marcar como paga</x-ui.button>
                        </form>
                        <span class="text-xs text-ink-2">Para faturas antigas que você já pagou e não lançou: muda só o status, sem mexer em saldo.</span>
                    </div>
                @endif

                @if ($summary['payments']->isNotEmpty())
                    <ul @class(['divide-y divide-line', 'mt-5 pt-2 border-t border-line' => $canPay])>
                        @foreach ($summary['payments'] as $payment)
                            <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                                <div>
                                    <p class="font-medium text-ink">{{ $money->format((float) $payment->amount) }}</p>
                                    <p class="text-xs text-ink-2">
                                        {{ $payment->paid_at->format('d/m/Y') }} ·
                                        {{ $payment->transaction ? 'pago com '.($payment->transaction->account?->name ?? 'outra conta') : 'marcada como paga (sem lançamento)' }}
                                    </p>
                                </div>
                                <x-ui.confirm-delete :action="route('finance.bills.payments.destroy', $payment)" label="Desfazer" confirm-label="Confirmar" />
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
            {{-- Composição por categoria --}}
            <x-ui.card title="Por categoria" class="lg:col-span-2">
                @if ($categories->isEmpty())
                    <p class="text-ink-2">Sem lançamentos nesta fatura.</p>
                @else
                    <ul class="space-y-4" aria-label="Gastos por categoria, do maior para o menor">
                        @foreach ($categories as $category)
                            @php $percentage = $total > 0 ? ((float) $category['total_amount'] / $total) * 100 : 0; @endphp
                            <li>
                                <div class="flex items-baseline justify-between gap-3 text-sm">
                                    <span class="font-medium text-ink truncate">{{ $category['category_name'] }}</span>
                                    <span class="font-mono text-ink whitespace-nowrap">{{ $money->format((float) $category['total_amount']) }}</span>
                                </div>
                                <div class="mt-1.5 flex items-center gap-3">
                                    <div class="h-2 flex-1 rounded-full bg-line" aria-hidden="true">
                                        <div class="h-2 rounded-full bg-finance-base" style="width: {{ max(round($percentage, 1), 1) }}%"></div>
                                    </div>
                                    <span class="w-12 text-right font-mono text-xs text-ink-2">{{ \App\Support\Format::percent($percentage) }}</span>
                                </div>
                                <p class="mt-0.5 text-xs text-ink-2">
                                    {{ $category['transaction_count'] }} {{ $category['transaction_count'] == 1 ? 'lançamento' : 'lançamentos' }}
                                </p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>

            {{-- Lançamentos --}}
            <x-ui.card class="lg:col-span-3 !p-0 overflow-hidden">
                <h2 class="font-serif text-xl tracking-tight text-finance-base px-6 pt-6 pb-4">Lançamentos</h2>
                @if (count($transactions) === 0)
                    <p class="px-6 pb-8 text-ink-2">Nenhum lançamento nesta fatura.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="border-y border-line bg-paper/60 text-left text-xs uppercase tracking-wider text-ink-2">
                                <tr>
                                    <th class="px-6 py-3 font-medium">Data</th>
                                    <th class="px-6 py-3 font-medium">Descrição</th>
                                    <th class="px-6 py-3 font-medium">Categoria</th>
                                    <th class="px-6 py-3 font-medium text-right">Valor</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                @foreach ($transactions as $transaction)
                                    <tr class="hover:bg-white/60">
                                        <td class="px-6 py-3 whitespace-nowrap font-mono text-ink-2">
                                            {{ Carbon::parse($transaction->transaction_date)->format('d/m/Y') }}
                                        </td>
                                        <td class="px-6 py-3">
                                            <a href="{{ route('finance.transactions.show', $transaction) }}" class="font-medium text-ink hover:text-finance-base">{{ $transaction->description }}</a>
                                            @if ($transaction->notes)
                                                <span class="block text-xs text-ink-2">{{ $transaction->notes }}</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-3 whitespace-nowrap">
                                            @if ($transaction->category)
                                                <span class="inline-flex items-center gap-1.5 text-ink">
                                                    <span class="w-2 h-2 rounded-full" style="background-color: {{ $transaction->category->color }}" aria-hidden="true"></span>
                                                    {{ $transaction->category->name }}
                                                </span>
                                            @else
                                                <span class="text-ink-2">Sem categoria</span>
                                            @endif
                                        </td>
                                        {{-- Crédito (estorno) diminui a fatura --}}
                                        <td @class(['px-6 py-3 whitespace-nowrap text-right font-medium', 'text-emerald-700' => $transaction->type === 'income', 'text-red-700' => $transaction->type !== 'income'])>
                                            {{ $transaction->type === 'income' ? '+' : '−' }} {{ $money->format((float) abs($transaction->amount)) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="border-t-2 border-line">
                                <tr>
                                    <td colspan="3" class="px-6 py-3 text-right font-medium text-ink">Total da fatura</td>
                                    <td class="px-6 py-3 text-right font-medium text-ink">{{ $money->format($total) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </x-ui.card>
        </div>
    </div>
</x-finance-layout>
