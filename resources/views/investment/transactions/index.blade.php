{{-- Compras e vendas de ativos. Totais em R$ via $money (todas as operações do usuário). --}}
<x-finance-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-ui.page-header title="Compras e vendas" kicker="Investimentos">
            <x-slot:actions>
                <x-ui.button :href="route('investment.transactions.create')">Nova operação</x-ui.button>
                <x-ui.button variant="secondary" :href="route('investment.investments.index')">Carteira</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.flash />

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <x-ui.card>
                <p class="text-sm text-ink-2">Operações</p>
                <p class="mt-1 font-serif text-2xl text-ink">{{ $stats['total_transactions'] }}</p>
            </x-ui.card>
            <x-ui.card>
                <p class="text-sm text-ink-2">Total comprado</p>
                <p class="mt-1 font-serif text-2xl text-ink">{{ $money->format((float) ($stats['total_invested'] ?? 0)) }}</p>
            </x-ui.card>
            <x-ui.card>
                <p class="text-sm text-ink-2">Total vendido</p>
                <p class="mt-1 font-serif text-2xl text-ink">{{ $money->format((float) ($stats['total_received'] ?? 0)) }}</p>
            </x-ui.card>
            <x-ui.card>
                <p class="text-sm text-ink-2">Ticket médio</p>
                <p class="mt-1 font-serif text-2xl text-ink">{{ $money->format((float) ($stats['average_transaction'] ?? 0)) }}</p>
            </x-ui.card>
        </div>

        <x-ui.card class="!p-0 overflow-hidden">
            @if ($transactions->isEmpty())
                <div class="px-6 py-12 text-center">
                    <p class="text-ink-2 mb-4">Nenhuma operação registrada.</p>
                    <x-ui.button :href="route('investment.transactions.create')">Registrar a primeira</x-ui.button>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="border-b border-line bg-paper/60 text-left text-xs uppercase tracking-wider text-ink-2">
                            <tr>
                                <th class="px-6 py-3 font-medium">Data</th>
                                <th class="px-6 py-3 font-medium">Ativo</th>
                                <th class="px-6 py-3 font-medium">Operação</th>
                                <th class="px-6 py-3 font-medium text-right">Quantidade</th>
                                <th class="px-6 py-3 font-medium text-right">Preço</th>
                                <th class="px-6 py-3 font-medium text-right">Total</th>
                                <th class="px-6 py-3"><span class="sr-only">Ações</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($transactions as $transaction)
                                <tr class="hover:bg-white/60">
                                    <td class="px-6 py-3 whitespace-nowrap font-mono text-ink-2">
                                        <a href="{{ route('investment.transactions.show', $transaction) }}" class="hover:text-finance-base">{{ $transaction->transaction_date->format('d/m/Y') }}</a>
                                    </td>
                                    <td class="px-6 py-3">
                                        <span class="font-mono font-medium text-ink">{{ $transaction->investment?->ticker ?? '—' }}</span>
                                        @if ($transaction->investment?->trashed()) <x-ui.badge tone="warning" class="ml-1">Excluído</x-ui.badge> @endif
                                        <span class="block text-ink-2">{{ $transaction->investment?->name }}</span>
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap">
                                        <x-ui.badge :tone="$transaction->type === 'buy' ? 'income' : 'expense'">{{ $transaction->type === 'buy' ? 'Compra' : 'Venda' }}</x-ui.badge>
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-right font-mono">{{ number_format((float) $transaction->quantity, 0, ',', '.') }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-right">{{ $money->format((float) $transaction->price) }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-right font-medium text-ink">
                                        {{ $money->format((float) $transaction->total_amount) }}
                                        @if ((float) $transaction->fees > 0)
                                            <span class="block text-xs font-normal text-ink-2">+ custos {{ $money->format((float) $transaction->fees) }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-right">
                                        <div class="inline-flex items-center gap-2">
                                            <x-ui.button variant="accent" size="sm" :href="route('investment.transactions.edit', $transaction)">Editar</x-ui.button>
                                            <x-ui.confirm-delete :action="route('investment.transactions.destroy', $transaction)" />
                                        </div>
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
