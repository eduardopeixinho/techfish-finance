{{--
    Carteira detalhada (InvestmentController@portfolioReport).
    Espera: $portfolio = InvestmentReportService::getPortfolio() → ['items' => Collection, 'totals' => array].
    Valores em R$ via $money (respeita "ocultar valores"). Só ativos com is_active = true.
--}}
@php
    use App\Support\Format;

    $totals = $portfolio['totals'];
@endphp

<x-finance-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="flex flex-wrap items-center justify-between gap-4 px-4 sm:px-0">
                <h1 class="font-serif text-3xl sm:text-4xl tracking-tight text-finance-base">Carteira detalhada</h1>
                <a href="{{ route('investment.reports') }}" class="btn-secondary">← Relatórios</a>
            </div>

            <div class="bg-finance-paper border border-line rounded-sm">
                <div class="p-6">
                    @if ($portfolio['items']->isEmpty())
                        <p class="text-sm text-ink-2">Nenhum ativo na carteira.
                            <a href="{{ route('investment.investments.create') }}" class="text-finance-base underline underline-offset-2">Cadastrar investimento</a>.
                        </p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-line text-sm">
                                <thead>
                                    <tr class="text-left text-ink-2">
                                        <th class="py-2 pr-4 font-medium">Ativo</th>
                                        <th class="py-2 pr-4 font-medium">Tipo</th>
                                        <th class="py-2 pr-4 font-medium text-right">Quantidade</th>
                                        <th class="py-2 pr-4 font-medium text-right">Investido</th>
                                        <th class="py-2 pr-4 font-medium text-right">Valor atual</th>
                                        <th class="py-2 pr-4 font-medium text-right">Resultado</th>
                                        <th class="py-2 pr-4 font-medium text-right">Proventos</th>
                                        <th class="py-2 font-medium text-right" title="Proventos líquidos recebidos ÷ total investido">Yield on cost</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-line">
                                    @foreach ($portfolio['items'] as $item)
                                        <tr>
                                            <td class="py-2 pr-4">
                                                <a href="{{ $item['url'] }}" class="font-mono font-medium text-finance-base hover:underline">{{ $item['ticker'] }}</a>
                                                <span class="block text-xs text-ink-2">{{ $item['name'] }}</span>
                                            </td>
                                            <td class="py-2 pr-4 text-ink">{{ $item['type'] }}</td>
                                            <td class="py-2 pr-4 text-right">{{ number_format($item['quantity'], 0, ',', '.') }}</td>
                                            <td class="py-2 pr-4 text-right">{{ $money->format($item['invested']) }}</td>
                                            <td class="py-2 pr-4 text-right">{{ $money->format($item['current']) }}</td>
                                            <td class="py-2 pr-4 text-right {{ $item['profit_loss'] >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                                                {{ $money->formatSigned($item['profit_loss']) }}
                                                <span class="block text-xs">{{ Format::percent($item['profit_loss_percentage']) }}</span>
                                            </td>
                                            <td class="py-2 pr-4 text-right">{{ $money->format($item['dividends']) }}</td>
                                            <td class="py-2 text-right">{{ Format::percent($item['yield_on_cost']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="border-t-2 border-line font-medium">
                                    <tr>
                                        <td class="py-2 pr-4" colspan="3">Total ({{ $portfolio['items']->count() }} ativos)</td>
                                        <td class="py-2 pr-4 text-right">{{ $money->format($totals['invested']) }}</td>
                                        <td class="py-2 pr-4 text-right">{{ $money->format($totals['current']) }}</td>
                                        <td class="py-2 pr-4 text-right {{ $totals['profit_loss'] >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                                            {{ $money->formatSigned($totals['profit_loss']) }}
                                            <span class="block text-xs">{{ Format::percent($totals['profit_loss_percentage']) }}</span>
                                        </td>
                                        <td class="py-2 pr-4 text-right">{{ $money->format($totals['dividends']) }}</td>
                                        <td class="py-2 text-right">{{ Format::percent($totals['invested'] > 0 ? ($totals['dividends'] / $totals['invested']) * 100 : 0) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-finance-layout>
