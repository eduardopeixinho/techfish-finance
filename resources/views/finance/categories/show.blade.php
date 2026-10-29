@php $isIncome = $category->type === 'income'; @endphp

<x-finance-layout>
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-ui.page-header :title="$category->name" :kicker="$isIncome ? 'Categoria de receita' : 'Categoria de despesa'">
            @if ($category->description)
                <x-slot:description>{{ $category->description }}</x-slot:description>
            @endif
            <x-slot:actions>
                <x-ui.button variant="accent" :href="route('finance.categories.edit', $category)">Editar</x-ui.button>
                <x-ui.button variant="secondary" :href="route('finance.categories.index')">Categorias</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.flash />

        {{-- Resumo --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <x-ui.card>
                <p class="text-sm text-ink-2">Transações</p>
                <p class="mt-1 font-serif text-3xl text-ink">{{ $transactions->total() }}</p>
            </x-ui.card>
            <x-ui.card>
                <p class="text-sm text-ink-2">Valor total</p>
                <p class="mt-1 font-serif text-3xl {{ $isIncome ? 'text-emerald-700' : 'text-red-700' }}">
                    {{ $money->format((float) $category->getTotalTransactionsAmount()) }}
                </p>
            </x-ui.card>
            <x-ui.card>
                <p class="text-sm text-ink-2">Status</p>
                <p class="mt-1 flex items-center gap-2 font-serif text-3xl text-ink">
                    <span class="w-3 h-3 rounded-full ring-1 ring-ink/10" style="background-color: {{ $category->color }}" aria-hidden="true"></span>
                    {{ $category->is_active ? 'Ativa' : 'Inativa' }}
                </p>
            </x-ui.card>
        </div>

        {{-- Transações --}}
        <x-ui.card class="!p-0 overflow-hidden">
            <h2 class="font-serif text-xl tracking-tight text-finance-base px-6 pt-6 pb-4">Transações desta categoria</h2>

            @if ($transactions->isEmpty())
                <div class="px-6 pb-8 text-center">
                    <p class="text-ink-2 mb-4">Nenhuma transação nesta categoria.</p>
                    <x-ui.button :href="route('finance.transactions.create')">Nova transação</x-ui.button>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="border-y border-line bg-paper/60 text-left text-xs uppercase tracking-wider text-ink-2">
                            <tr>
                                <th class="px-6 py-3 font-medium">Data</th>
                                <th class="px-6 py-3 font-medium">Descrição</th>
                                <th class="px-6 py-3 font-medium">Conta</th>
                                <th class="px-6 py-3 font-medium text-right">Valor</th>
                                <th class="px-6 py-3"><span class="sr-only">Ações</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($transactions as $transaction)
                                <tr class="hover:bg-white/60">
                                    <td class="px-6 py-3 whitespace-nowrap font-mono text-ink-2">{{ $transaction->formatted_date }}</td>
                                    <td class="px-6 py-3">
                                        <span class="font-medium text-ink">{{ $transaction->description }}</span>
                                        @if ($transaction->notes)
                                            <span class="block text-ink-2">{{ Str::limit($transaction->notes, 50) }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-ink">{{ $transaction->account->name }}</td>
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
