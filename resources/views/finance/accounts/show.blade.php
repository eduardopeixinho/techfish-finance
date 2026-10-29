{{-- Detalhe da conta. Totais em R$ (regime de caixa: soma das transações lançadas na conta). --}}
@php
    $totalIncome = (float) $account->transactions()->where('type', 'income')->sum('amount');
    $totalExpense = (float) $account->transactions()->where('type', 'expense')->sum('amount');
    $variation = (float) $account->current_balance - (float) $account->initial_balance;
@endphp

<x-finance-layout>
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-ui.page-header :title="$account->name" :kicker="$account->type_label.($account->bank ? ' · '.$account->bank : '')">
            @if ($account->description)
                <x-slot:description>{{ $account->description }}</x-slot:description>
            @endif
            <x-slot:actions>
                <x-ui.button :href="route('finance.transactions.create', ['account_id' => $account->id])">Nova transação</x-ui.button>
                <x-ui.button variant="accent" :href="route('finance.accounts.edit', $account)">Editar</x-ui.button>
                <x-ui.button variant="secondary" :href="route('finance.accounts.index')">Contas</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.flash />

        @unless ($account->is_active)
            <p class="mb-6"><x-ui.badge tone="warning">Conta inativa: não aparece ao lançar transações</x-ui.badge></p>
        @endunless

        {{-- Resumo --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <x-ui.card class="border-l-4" style="border-left-color: {{ $account->color }}">
                <p class="text-sm text-ink-2">Saldo atual</p>
                <p class="mt-1 font-serif text-2xl {{ $account->current_balance >= 0 ? 'text-ink' : 'text-red-700' }}">{{ $money->format((float) $account->current_balance) }}</p>
                <p class="text-xs text-ink-2 font-mono">inicial {{ $money->format((float) $account->initial_balance) }}</p>
            </x-ui.card>
            <x-ui.card>
                <p class="text-sm text-ink-2">Receitas</p>
                <p class="mt-1 font-serif text-2xl text-emerald-700">{{ $money->format($totalIncome) }}</p>
            </x-ui.card>
            <x-ui.card>
                <p class="text-sm text-ink-2">Despesas</p>
                <p class="mt-1 font-serif text-2xl text-red-700">{{ $money->format($totalExpense) }}</p>
            </x-ui.card>
            <x-ui.card>
                <p class="text-sm text-ink-2">Variação desde o início</p>
                <p class="mt-1 font-serif text-2xl {{ $variation >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ $money->formatSigned($variation) }}</p>
            </x-ui.card>
        </div>

        {{-- Transações --}}
        <x-ui.card class="!p-0 overflow-hidden">
            <h2 class="font-serif text-xl tracking-tight text-finance-base px-6 pt-6 pb-4">
                Transações <span class="text-base text-ink-2 font-sans">({{ $transactions->total() }})</span>
            </h2>

            @if ($transactions->isEmpty())
                <div class="px-6 pb-8 text-center">
                    <p class="text-ink-2 mb-4">Nenhuma transação nesta conta.</p>
                    <x-ui.button :href="route('finance.transactions.create', ['account_id' => $account->id])">Lançar a primeira</x-ui.button>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="border-y border-line bg-paper/60 text-left text-xs uppercase tracking-wider text-ink-2">
                            <tr>
                                <th class="px-6 py-3 font-medium">Data</th>
                                <th class="px-6 py-3 font-medium">Descrição</th>
                                <th class="px-6 py-3 font-medium">Categoria</th>
                                <th class="px-6 py-3 font-medium">Tipo</th>
                                <th class="px-6 py-3 font-medium text-right">Valor</th>
                                <th class="px-6 py-3"><span class="sr-only">Ações</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($transactions as $transaction)
                                @php $isIncome = $transaction->type === 'income'; @endphp
                                <tr class="hover:bg-white/60">
                                    <td class="px-6 py-3 whitespace-nowrap font-mono text-ink-2">{{ $transaction->transaction_date->format('d/m/Y') }}</td>
                                    <td class="px-6 py-3">
                                        <a href="{{ route('finance.transactions.show', $transaction) }}" class="font-medium text-ink hover:text-finance-base">{{ $transaction->description }}</a>
                                        @if ($transaction->notes)
                                            <span class="block text-ink-2">{{ Str::limit($transaction->notes, 50) }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap">
                                        @if ($transaction->category)
                                            <span class="inline-flex items-center gap-1.5 text-ink">
                                                <span class="w-2 h-2 rounded-full" style="background-color: {{ $transaction->category->color }}" aria-hidden="true"></span>
                                                {{ $transaction->category->name }}
                                            </span>
                                        @else
                                            <span class="text-ink-2">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap"><x-ui.badge :tone="$transaction->type">{{ $transaction->type_label }}</x-ui.badge></td>
                                    <td class="px-6 py-3 whitespace-nowrap text-right font-medium {{ $isIncome ? 'text-emerald-700' : 'text-red-700' }}">
                                        {{ $isIncome ? '+' : '−' }} {{ $money->format((float) $transaction->amount) }}
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-right">
                                        <x-ui.button variant="secondary" size="sm" :href="route('finance.transactions.edit', $transaction)">Editar</x-ui.button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($transactions->hasPages())
                    <div class="px-6 py-3 border-t border-line">{{ $transactions->links() }}</div>
                @endif
            @endif
        </x-ui.card>
    </div>
</x-finance-layout>
