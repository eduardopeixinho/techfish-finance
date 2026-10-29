{{--
    Relatório de proventos (InvestmentReportService::getDividendReport).
    Filtros: ano, mês e tipo (GET). Mês = data de pagamento (caixa). Valores em R$ via $money.
--}}
@php
    use App\Models\Investment\InvestmentDividend;
    use App\Support\Format;

    $hasFilters = collect(request()->only(['year', 'month', 'type']))->filter()->isNotEmpty();
    $thead = 'border-y border-line bg-paper/60 text-left text-xs uppercase tracking-wider text-ink-2';
@endphp

<x-finance-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-6">
        <x-ui.page-header title="Relatório de proventos" kicker="Investimentos" class="!mb-2">
            <x-slot:actions>
                <x-ui.button :href="route('investment.dividends.create')">Registrar provento</x-ui.button>
                <x-ui.button variant="secondary" :href="route('investment.reports')">Relatórios</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        {{-- Filtros --}}
        <x-ui.card>
            <form method="GET" action="{{ route('investment.reports.dividends') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
                <x-ui.field label="Ano" for="year">
                    <select id="year" name="year" class="ui-input">
                        <option value="">Todos</option>
                        @for ($year = now()->year; $year >= 2020; $year--)
                            <option value="{{ $year }}" @selected((string) request('year') === (string) $year)>{{ $year }}</option>
                        @endfor
                    </select>
                </x-ui.field>
                <x-ui.field label="Mês" for="month">
                    <select id="month" name="month" class="ui-input">
                        <option value="">Todos</option>
                        @for ($month = 1; $month <= 12; $month++)
                            <option value="{{ $month }}" @selected((string) request('month') === (string) $month)>{{ ucfirst(\Carbon\Carbon::create(null, $month, 1)->translatedFormat('F')) }}</option>
                        @endfor
                    </select>
                </x-ui.field>
                <x-ui.field label="Tipo" for="type">
                    <select id="type" name="type" class="ui-input">
                        <option value="">Todos</option>
                        @foreach (InvestmentDividend::TYPES as $value => $label)
                            <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </x-ui.field>
                <div class="flex gap-2">
                    <x-ui.button type="submit">Filtrar</x-ui.button>
                    @if ($hasFilters)
                        <x-ui.button variant="secondary" :href="route('investment.reports.dividends')">Limpar</x-ui.button>
                    @endif
                </div>
            </form>
        </x-ui.card>

        {{-- Resumo --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <x-ui.card>
                <p class="text-sm text-ink-2">Bruto</p>
                <p class="mt-1 font-serif text-2xl text-ink">{{ $money->format((float) $totalGross) }}</p>
            </x-ui.card>
            <x-ui.card>
                <p class="text-sm text-ink-2">IR retido</p>
                <p class="mt-1 font-serif text-2xl text-red-700">{{ $money->format((float) $totalTax) }}</p>
            </x-ui.card>
            <x-ui.card>
                <p class="text-sm text-ink-2">Líquido</p>
                <p class="mt-1 font-serif text-2xl text-emerald-700">{{ $money->format((float) $totalNet) }}</p>
            </x-ui.card>
            <x-ui.card>
                <p class="text-sm text-ink-2">Pagamentos</p>
                <p class="mt-1 font-serif text-2xl text-ink">{{ $dividends->count() }}</p>
            </x-ui.card>
        </div>

        @if ($dividends->isEmpty())
            <x-ui.card class="text-center py-12">
                <p class="text-ink-2">{{ $hasFilters ? 'Nenhum provento com esses filtros.' : 'Nenhum provento registrado ainda.' }}</p>
            </x-ui.card>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Por tipo --}}
                <x-ui.card class="!p-0 overflow-hidden">
                    <h2 class="font-serif text-xl tracking-tight text-finance-base px-6 pt-6 pb-4">Por tipo</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="{{ $thead }}">
                                <tr>
                                    <th class="px-6 py-3 font-medium">Tipo</th>
                                    <th class="px-6 py-3 font-medium text-right">Pagamentos</th>
                                    <th class="px-6 py-3 font-medium text-right">Líquido</th>
                                    <th class="px-6 py-3 font-medium text-right">% do total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                @foreach ($byType as $type => $data)
                                    <tr>
                                        <td class="px-6 py-3"><x-ui.badge tone="income">{{ InvestmentDividend::TYPES[$type] ?? $type }}</x-ui.badge></td>
                                        <td class="px-6 py-3 text-right font-mono">{{ $data['count'] }}</td>
                                        <td class="px-6 py-3 text-right font-medium text-emerald-700">{{ $money->format((float) $data['net']) }}</td>
                                        <td class="px-6 py-3 text-right font-mono">{{ Format::percent($totalNet > 0 ? ($data['net'] / $totalNet) * 100 : 0) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-ui.card>

                {{-- Evolução mensal --}}
                <x-ui.card class="!p-0 overflow-hidden">
                    <h2 class="font-serif text-xl tracking-tight text-finance-base px-6 pt-6 pb-4">Por mês de pagamento</h2>
                    <div class="overflow-x-auto max-h-96">
                        <table class="min-w-full text-sm">
                            <thead class="{{ $thead }} sticky top-0">
                                <tr>
                                    <th class="px-6 py-3 font-medium">Mês</th>
                                    <th class="px-6 py-3 font-medium text-right">Líquido</th>
                                    <th class="px-6 py-3 font-medium text-right">vs. mês anterior</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                @php $previousNet = null; @endphp
                                @foreach ($monthlyEvolution as $evolution)
                                    @php
                                        $growth = $previousNet > 0 ? (($evolution->net - $previousNet) / $previousNet) * 100 : null;
                                        $previousNet = $evolution->net;
                                    @endphp
                                    <tr>
                                        <td class="px-6 py-3 font-mono text-ink-2">{{ $evolution->month_year }}</td>
                                        <td class="px-6 py-3 text-right font-medium text-emerald-700">{{ $money->format((float) $evolution->net) }}</td>
                                        <td class="px-6 py-3 text-right font-mono {{ $growth === null ? 'text-ink-2' : ($growth >= 0 ? 'text-emerald-700' : 'text-red-700') }}">
                                            {{ $growth === null ? '—' : ($growth >= 0 ? '+' : '').Format::percent($growth) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-ui.card>
            </div>

            {{-- Por ativo --}}
            <x-ui.card class="!p-0 overflow-hidden">
                <h2 class="font-serif text-xl tracking-tight text-finance-base px-6 pt-6 pb-4">Por ativo</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="{{ $thead }}">
                            <tr>
                                <th class="px-6 py-3 font-medium">Ativo</th>
                                <th class="px-6 py-3 font-medium">Tipo</th>
                                <th class="px-6 py-3 font-medium text-right">Pagamentos</th>
                                <th class="px-6 py-3 font-medium text-right">Bruto</th>
                                <th class="px-6 py-3 font-medium text-right">Líquido</th>
                                <th class="px-6 py-3 font-medium text-right" title="Proventos brutos ÷ (cotação atual × cotas)">Yield médio</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($byInvestment->sortByDesc('net') as $data)
                                <tr>
                                    <td class="px-6 py-3">
                                        <span class="font-mono font-medium text-ink">{{ $data['ticker'] }}</span>
                                        <span class="block text-xs text-ink-2">{{ $data['name'] }}</span>
                                    </td>
                                    <td class="px-6 py-3 text-ink-2">{{ $data['type'] }}</td>
                                    <td class="px-6 py-3 text-right font-mono">{{ $data['count'] }}</td>
                                    <td class="px-6 py-3 text-right">{{ $money->format((float) $data['gross']) }}</td>
                                    <td class="px-6 py-3 text-right font-medium text-emerald-700">{{ $money->format((float) $data['net']) }}</td>
                                    <td class="px-6 py-3 text-right font-mono">{{ ($data['avg_yield'] ?? 0) > 0 ? Format::percent($data['avg_yield'], 2) : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-ui.card>

            {{-- Lista detalhada --}}
            <details class="group">
                <summary class="inline-flex items-center gap-2 cursor-pointer select-none text-sm font-medium text-finance-base">
                    <span class="transition-transform group-open:rotate-90" aria-hidden="true">▸</span>
                    Lista detalhada ({{ $dividends->count() }} {{ $dividends->count() === 1 ? 'pagamento' : 'pagamentos' }})
                </summary>
                <x-ui.card class="!p-0 overflow-hidden mt-3">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="{{ $thead }}">
                                <tr>
                                    <th class="px-6 py-3 font-medium">Pagamento</th>
                                    <th class="px-6 py-3 font-medium">Ativo</th>
                                    <th class="px-6 py-3 font-medium">Tipo</th>
                                    <th class="px-6 py-3 font-medium text-right">Por cota</th>
                                    <th class="px-6 py-3 font-medium text-right">Cotas</th>
                                    <th class="px-6 py-3 font-medium text-right">Bruto</th>
                                    <th class="px-6 py-3 font-medium text-right">IR</th>
                                    <th class="px-6 py-3 font-medium text-right">Líquido</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                @foreach ($dividends as $dividend)
                                    <tr class="hover:bg-white/60">
                                        <td class="px-6 py-3 whitespace-nowrap font-mono text-ink-2">{{ $dividend->payment_date->format('d/m/Y') }}</td>
                                        <td class="px-6 py-3 whitespace-nowrap">
                                            <a href="{{ route('investment.dividends.show', $dividend) }}" class="font-mono font-medium text-ink hover:text-finance-base">{{ $dividend->investment?->ticker ?? '—' }}</a>
                                        </td>
                                        <td class="px-6 py-3 whitespace-nowrap">{{ $dividend->type_name }}</td>
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
            </details>
        @endif
    </div>
</x-finance-layout>
