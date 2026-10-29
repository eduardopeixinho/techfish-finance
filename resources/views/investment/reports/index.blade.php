{{--
    Relatório geral de investimentos (InvestmentController@reports).
    Espera: $stats (InvestmentReportService::getInvestmentStats) e
            $monthlyDividends (::getDividendMonthlyEvolution, 12 meses, competência = data de pagamento).
    Valores em R$ via $money (respeita "ocultar valores").
--}}
@php
    use App\Support\Format;

    $totalCurrent = (float) $stats['current_value'];
    $dividendsNet12m = collect($monthlyDividends)->sum('net');
@endphp

<x-finance-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="flex flex-wrap items-center justify-between gap-4 px-4 sm:px-0">
                <h1 class="font-serif text-3xl sm:text-4xl tracking-tight text-finance-base">Relatórios de investimentos</h1>
                <div class="flex gap-2">
                    <a href="{{ route('investment.reports.portfolio') }}" class="btn-secondary">Carteira detalhada</a>
                    <a href="{{ route('investment.reports.dividends') }}" class="btn-secondary">Proventos</a>
                </div>
            </div>

            {{-- Resumo --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 px-4 sm:px-0">
                <div class="bg-finance-paper border border-line rounded-sm p-5">
                    <p class="text-sm text-ink-2">Total investido</p>
                    <p class="mt-1 font-serif text-3xl sm:text-4xl tracking-tight text-finance-base">{{ $money->format($stats['total_invested']) }}</p>
                </div>
                <div class="bg-finance-paper border border-line rounded-sm p-5">
                    <p class="text-sm text-ink-2">Valor atual</p>
                    <p class="mt-1 font-serif text-3xl sm:text-4xl tracking-tight text-finance-base">{{ $money->format($totalCurrent) }}</p>
                </div>
                <div class="bg-finance-paper border border-line rounded-sm p-5">
                    <p class="text-sm text-ink-2">Resultado</p>
                    <p class="mt-1 font-serif text-2xl {{ $stats['profit_loss'] >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                        {{ $money->formatSigned($stats['profit_loss']) }}
                    </p>
                    <p class="text-sm {{ $stats['profit_loss'] >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ Format::percent($stats['profit_loss_percentage']) }}</p>
                </div>
                <div class="bg-finance-paper border border-line rounded-sm p-5">
                    <p class="text-sm text-ink-2">Proventos (12 meses, líquido)</p>
                    <p class="mt-1 font-serif text-3xl sm:text-4xl tracking-tight text-finance-base">{{ $money->format($dividendsNet12m) }}</p>
                </div>
            </div>

            {{-- Alocação por tipo --}}
            <div class="bg-finance-paper border border-line rounded-sm">
                <div class="p-6">
                    <h2 class="font-serif text-xl tracking-tight text-finance-base mb-4">Alocação por tipo</h2>

                    @if ($stats['by_type']->isEmpty())
                        <p class="text-sm text-ink-2">Nenhum investimento cadastrado ainda.
                            <a href="{{ route('investment.investments.create') }}" class="text-finance-base underline underline-offset-2">Cadastrar o primeiro</a>.
                        </p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-line text-sm">
                                <thead>
                                    <tr class="text-left text-ink-2">
                                        <th class="py-2 pr-4 font-medium">Tipo</th>
                                        <th class="py-2 pr-4 font-medium text-right">Ativos</th>
                                        <th class="py-2 pr-4 font-medium text-right">Investido</th>
                                        <th class="py-2 pr-4 font-medium text-right">Valor atual</th>
                                        <th class="py-2 pr-4 font-medium text-right">% da carteira</th>
                                        <th class="py-2 font-medium text-right">Resultado</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-line">
                                    @foreach ($stats['by_type']->sortByDesc('current_value') as $typeName => $row)
                                        <tr>
                                            <td class="py-2 pr-4 text-ink">{{ $typeName }}</td>
                                            <td class="py-2 pr-4 text-right">{{ $row['count'] }}</td>
                                            <td class="py-2 pr-4 text-right">{{ $money->format($row['total_invested']) }}</td>
                                            <td class="py-2 pr-4 text-right">{{ $money->format($row['current_value']) }}</td>
                                            <td class="py-2 pr-4 text-right">{{ Format::percent($totalCurrent > 0 ? ($row['current_value'] / $totalCurrent) * 100 : 0) }}</td>
                                            <td class="py-2 text-right {{ $row['profit_loss'] >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ $money->formatSigned($row['profit_loss']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Proventos por mês --}}
            <div class="bg-finance-paper border border-line rounded-sm">
                <div class="p-6">
                    <h2 class="font-serif text-xl tracking-tight text-finance-base mb-1">Proventos por mês</h2>
                    <p class="text-sm text-ink-2 mb-4">Últimos 12 meses, pela data de pagamento.</p>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-line text-sm">
                            <thead>
                                <tr class="text-left text-ink-2">
                                    <th class="py-2 pr-4 font-medium">Mês</th>
                                    <th class="py-2 pr-4 font-medium text-right">Bruto</th>
                                    <th class="py-2 pr-4 font-medium text-right">Imposto retido</th>
                                    <th class="py-2 font-medium text-right">Líquido</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                @foreach (array_reverse($monthlyDividends) as $month)
                                    <tr class="{{ $month['net'] > 0 ? '' : 'text-ink-2/70' }}">
                                        <td class="py-2 pr-4">{{ $month['month'] }}</td>
                                        <td class="py-2 pr-4 text-right">{{ $money->format($month['gross']) }}</td>
                                        <td class="py-2 pr-4 text-right">{{ $money->format($month['tax']) }}</td>
                                        <td class="py-2 text-right font-medium">{{ $money->format($month['net']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-finance-layout>
