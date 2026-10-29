{{--
    Carteira. $summary = totais da carteira ATIVA inteira (controller), não só da página.
    Valores em R$ via $money; percentuais via Format::percent.
--}}
@php use App\Support\Format; @endphp

<x-finance-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-ui.page-header title="Carteira" kicker="Investimentos">
            <x-slot:actions>
                <x-ui.button :href="route('investment.investments.create')">Novo investimento</x-ui.button>
                <x-ui.button variant="secondary" :href="route('investment.reports')">Relatórios</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.flash />

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <x-ui.card>
                <p class="text-sm text-ink-2">Total investido</p>
                <p class="mt-1 font-serif text-2xl text-ink">{{ $money->format($summary['invested']) }}</p>
            </x-ui.card>
            <x-ui.card>
                <p class="text-sm text-ink-2">Valor atual</p>
                <p class="mt-1 font-serif text-2xl text-ink">{{ $money->format($summary['current']) }}</p>
            </x-ui.card>
            <x-ui.card>
                <p class="text-sm text-ink-2">Resultado</p>
                <p class="mt-1 font-serif text-2xl {{ $summary['profit_loss'] >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ $money->formatSigned($summary['profit_loss']) }}</p>
            </x-ui.card>
            <x-ui.card>
                <p class="text-sm text-ink-2">Ativos na carteira</p>
                <p class="mt-1 font-serif text-2xl text-ink">{{ $summary['assets'] }}</p>
            </x-ui.card>
        </div>

        <x-ui.card class="!p-0 overflow-hidden">
            @if ($investments->isEmpty())
                <div class="px-6 py-12 text-center">
                    <p class="text-ink-2 mb-4">Nenhum investimento cadastrado.</p>
                    <x-ui.button :href="route('investment.investments.create')">Adicionar o primeiro</x-ui.button>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="border-b border-line bg-paper/60 text-left text-xs uppercase tracking-wider text-ink-2">
                            <tr>
                                <th class="px-6 py-3 font-medium">Ativo</th>
                                <th class="px-6 py-3 font-medium">Tipo</th>
                                <th class="px-6 py-3 font-medium text-right">Quantidade</th>
                                <th class="px-6 py-3 font-medium text-right">Cotação</th>
                                <th class="px-6 py-3 font-medium text-right">Valor atual</th>
                                <th class="px-6 py-3 font-medium text-right">Resultado</th>
                                <th class="px-6 py-3"><span class="sr-only">Ações</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($investments as $investment)
                                @php $isPositive = $investment->profit_loss >= 0; @endphp
                                <tr class="hover:bg-white/60 {{ $investment->is_active ? '' : 'opacity-60' }}">
                                    <td class="px-6 py-3">
                                        <a href="{{ route('investment.investments.show', $investment) }}" class="font-mono font-medium text-finance-base hover:underline">{{ $investment->ticker }}</a>
                                        @unless ($investment->is_active) <x-ui.badge tone="warning" class="ml-1">Encerrado</x-ui.badge> @endunless
                                        <span class="block text-ink-2">{{ $investment->name }}</span>
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1.5 text-ink">
                                            <span class="w-2 h-2 rounded-full" style="background-color: {{ $investment->investmentType?->color ?? '#0B3D3A' }}" aria-hidden="true"></span>
                                            {{ $investment->investmentType?->name ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-right font-mono">{{ number_format((float) $investment->current_quantity, 0, ',', '.') }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-right">{{ $money->format((float) $investment->current_price) }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-right font-medium text-ink">{{ $money->format((float) $investment->current_value) }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-right {{ $isPositive ? 'text-emerald-700' : 'text-red-700' }}">
                                        {{ $money->formatSigned((float) $investment->profit_loss) }}
                                        <span class="block text-xs">{{ Format::percent((float) $investment->profit_loss_percentage, 2) }}</span>
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-right">
                                        <div class="inline-flex items-center gap-2">
                                            <x-ui.button variant="accent" size="sm" :href="route('investment.investments.edit', $investment)">Editar</x-ui.button>
                                            <x-ui.confirm-delete :action="route('investment.investments.destroy', $investment)" />
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($investments->hasPages())
                    <div class="px-6 py-3 border-t border-line">{{ $investments->links() }}</div>
                @endif
            @endif
        </x-ui.card>
    </div>
</x-finance-layout>
