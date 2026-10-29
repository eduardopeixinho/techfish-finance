{{-- Proventos recebidos. Totais em R$ via $money; mês = data de pagamento. --}}
<x-finance-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-ui.page-header title="Proventos" kicker="Investimentos">
            <x-slot:actions>
                <x-ui.button :href="route('investment.dividends.create')">Registrar provento</x-ui.button>
                <x-ui.button variant="secondary" :href="route('investment.reports.dividends')">Relatório</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.flash />

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <x-ui.card>
                <p class="text-sm text-ink-2">Total recebido (líquido)</p>
                <p class="mt-1 font-serif text-2xl text-emerald-700">{{ $money->format((float) $totalReceived) }}</p>
            </x-ui.card>
            <x-ui.card>
                <p class="text-sm text-ink-2">{{ ucfirst(now()->translatedFormat('F/Y')) }}</p>
                <p class="mt-1 font-serif text-2xl text-ink">{{ $money->format((float) $currentMonthReceived) }}</p>
            </x-ui.card>
            <x-ui.card>
                <p class="text-sm text-ink-2">{{ ucfirst(now()->startOfMonth()->subMonth()->translatedFormat('F/Y')) }}</p>
                <p class="mt-1 font-serif text-2xl text-ink">{{ $money->format((float) $lastMonthReceived) }}</p>
            </x-ui.card>
        </div>

        <x-ui.card class="!p-0 overflow-hidden">
            @if ($dividends->isEmpty())
                <div class="px-6 py-12 text-center">
                    <p class="text-ink-2 mb-4">Nenhum provento registrado.</p>
                    <x-ui.button :href="route('investment.dividends.create')">Registrar o primeiro</x-ui.button>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="border-b border-line bg-paper/60 text-left text-xs uppercase tracking-wider text-ink-2">
                            <tr>
                                <th class="px-6 py-3 font-medium">Pagamento</th>
                                <th class="px-6 py-3 font-medium">Ativo</th>
                                <th class="px-6 py-3 font-medium">Tipo</th>
                                <th class="px-6 py-3 font-medium text-right">Por cota</th>
                                <th class="px-6 py-3 font-medium text-right">Cotas</th>
                                <th class="px-6 py-3 font-medium text-right">Bruto</th>
                                <th class="px-6 py-3 font-medium text-right">IR</th>
                                <th class="px-6 py-3 font-medium text-right">Líquido</th>
                                <th class="px-6 py-3"><span class="sr-only">Ações</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($dividends as $dividend)
                                <tr class="hover:bg-white/60">
                                    <td class="px-6 py-3 whitespace-nowrap font-mono text-ink-2">
                                        <a href="{{ route('investment.dividends.show', $dividend) }}" class="hover:text-finance-base">{{ $dividend->payment_date->format('d/m/Y') }}</a>
                                    </td>
                                    <td class="px-6 py-3">
                                        <span class="font-mono font-medium text-ink">{{ $dividend->investment?->ticker ?? '—' }}</span>
                                        @if ($dividend->investment?->trashed()) <x-ui.badge tone="warning" class="ml-1">Excluído</x-ui.badge> @endif
                                        <span class="block text-xs text-ink-2">{{ $dividend->investment?->investmentType?->name }}</span>
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap"><x-ui.badge tone="income">{{ $dividend->type_name }}</x-ui.badge></td>
                                    <td class="px-6 py-3 whitespace-nowrap text-right font-mono">R$ {{ number_format((float) $dividend->amount_per_share, 6, ',', '.') }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-right font-mono">{{ number_format((float) $dividend->quantity_owned, 0, ',', '.') }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-right">{{ $money->format((float) $dividend->amount) }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-right text-ink-2">{{ $money->format((float) $dividend->tax_withheld) }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-right font-medium text-emerald-700">{{ $money->format((float) $dividend->net_amount) }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-right">
                                        <div class="inline-flex items-center gap-2">
                                            <x-ui.button variant="accent" size="sm" :href="route('investment.dividends.edit', $dividend)">Editar</x-ui.button>
                                            <x-ui.confirm-delete :action="route('investment.dividends.destroy', $dividend)" />
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($dividends->hasPages())
                    <div class="px-6 py-3 border-t border-line">{{ $dividends->links() }}</div>
                @endif
            @endif
        </x-ui.card>
    </div>
</x-finance-layout>
