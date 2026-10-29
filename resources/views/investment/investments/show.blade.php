{{--
    Detalhe do ativo: posição, histórico de compras/vendas e de proventos.
    Valores em R$ via $money; valor por cota (6 casas) é cotação e fica visível.
--}}
@php
    use App\Support\Format;

    $isPositive = $investment->profit_loss >= 0;
    $dividendsNet = (float) $investment->dividends->sum('net_amount');
@endphp

<x-finance-layout>
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-ui.page-header :title="$investment->ticker" :kicker="($investment->investmentType?->name ?? 'Investimento').' · '.$investment->name">
            @if ($investment->description)
                <x-slot:description>{{ $investment->description }}</x-slot:description>
            @endif
            <x-slot:actions>
                <x-ui.button :href="route('investment.transactions.create', ['investment_id' => $investment->id])">Nova compra/venda</x-ui.button>
                @if ($investment->investmentType?->has_dividends)
                    <x-ui.button variant="secondary" :href="route('investment.dividends.create', ['investment_id' => $investment->id])">Registrar provento</x-ui.button>
                @endif
                <x-ui.button variant="accent" :href="route('investment.investments.edit', $investment)">Editar</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.flash />

        {{-- Posição --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <x-ui.card>
                <p class="text-sm text-ink-2">Quantidade × cotação</p>
                <p class="mt-1 font-serif text-2xl text-ink">{{ number_format((float) $investment->current_quantity, 0, ',', '.') }}</p>
                <p class="text-xs text-ink-2 font-mono">× {{ $money->format((float) $investment->current_price) }}</p>
            </x-ui.card>
            <x-ui.card>
                <p class="text-sm text-ink-2">Valor atual</p>
                <p class="mt-1 font-serif text-2xl text-ink">{{ $money->format((float) $investment->current_value) }}</p>
                <p class="text-xs text-ink-2 font-mono">investido {{ $money->format((float) $investment->total_invested) }}</p>
            </x-ui.card>
            <x-ui.card>
                <p class="text-sm text-ink-2">Resultado</p>
                <p class="mt-1 font-serif text-2xl {{ $isPositive ? 'text-emerald-700' : 'text-red-700' }}">{{ $money->formatSigned((float) $investment->profit_loss) }}</p>
                <p class="text-xs {{ $isPositive ? 'text-emerald-700' : 'text-red-700' }}">{{ Format::percent((float) $investment->profit_loss_percentage, 2) }}</p>
            </x-ui.card>
            <x-ui.card>
                <p class="text-sm text-ink-2">Proventos recebidos</p>
                <p class="mt-1 font-serif text-2xl text-emerald-700">{{ $money->format($dividendsNet) }}</p>
                <p class="text-xs text-ink-2">líquido, {{ $investment->dividends->count() }} {{ $investment->dividends->count() === 1 ? 'pagamento' : 'pagamentos' }}</p>
            </x-ui.card>
        </div>

        {{-- Compras e vendas --}}
        <x-ui.card class="!p-0 overflow-hidden mb-6">
            <h2 class="font-serif text-xl tracking-tight text-finance-base px-6 pt-6 pb-4">Compras e vendas</h2>
            @if ($investment->transactions->isEmpty())
                <p class="px-6 pb-8 text-ink-2">Nenhuma transação. Lance a primeira compra para montar a posição.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="border-y border-line bg-paper/60 text-left text-xs uppercase tracking-wider text-ink-2">
                            <tr>
                                <th class="px-6 py-3 font-medium">Data</th>
                                <th class="px-6 py-3 font-medium">Operação</th>
                                <th class="px-6 py-3 font-medium text-right">Quantidade</th>
                                <th class="px-6 py-3 font-medium text-right">Preço</th>
                                <th class="px-6 py-3 font-medium text-right">Total</th>
                                <th class="px-6 py-3 font-medium text-right">Custos</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($investment->transactions->sortByDesc('transaction_date') as $transaction)
                                <tr class="hover:bg-white/60">
                                    <td class="px-6 py-3 whitespace-nowrap font-mono text-ink-2">
                                        <a href="{{ route('investment.transactions.show', $transaction) }}" class="hover:text-finance-base">{{ $transaction->transaction_date->format('d/m/Y') }}</a>
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap">
                                        <x-ui.badge :tone="$transaction->type === 'buy' ? 'income' : 'expense'">{{ $transaction->type === 'buy' ? 'Compra' : 'Venda' }}</x-ui.badge>
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-right font-mono">{{ number_format((float) $transaction->quantity, 0, ',', '.') }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-right">{{ $money->format((float) $transaction->price) }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-right font-medium text-ink">{{ $money->format((float) $transaction->total_amount) }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-right text-ink-2">{{ $money->format((float) $transaction->fees) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.card>

        {{-- Proventos --}}
        @if ($investment->dividends->isNotEmpty())
            <x-ui.card class="!p-0 overflow-hidden">
                <h2 class="font-serif text-xl tracking-tight text-finance-base px-6 pt-6 pb-4">Proventos</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="border-y border-line bg-paper/60 text-left text-xs uppercase tracking-wider text-ink-2">
                            <tr>
                                <th class="px-6 py-3 font-medium">Pagamento</th>
                                <th class="px-6 py-3 font-medium">Tipo</th>
                                <th class="px-6 py-3 font-medium text-right">Por cota</th>
                                <th class="px-6 py-3 font-medium text-right">Cotas</th>
                                <th class="px-6 py-3 font-medium text-right">Bruto</th>
                                <th class="px-6 py-3 font-medium text-right">IR</th>
                                <th class="px-6 py-3 font-medium text-right">Líquido</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($investment->dividends->sortByDesc('payment_date') as $dividend)
                                <tr class="hover:bg-white/60">
                                    <td class="px-6 py-3 whitespace-nowrap font-mono text-ink-2">
                                        <a href="{{ route('investment.dividends.show', $dividend) }}" class="hover:text-finance-base">{{ $dividend->payment_date->format('d/m/Y') }}</a>
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap"><x-ui.badge tone="income">{{ $dividend->type_name }}</x-ui.badge></td>
                                    <td class="px-6 py-3 whitespace-nowrap text-right font-mono">R$ {{ number_format((float) $dividend->amount_per_share, 6, ',', '.') }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-right font-mono">{{ number_format((float) $dividend->quantity_owned, 0, ',', '.') }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-right">{{ $money->format((float) $dividend->amount) }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-right text-ink-2">{{ $money->format((float) $dividend->tax_withheld) }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-right font-medium text-emerald-700">{{ $money->format((float) $dividend->net_amount) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
        @endif
    </div>
</x-finance-layout>
