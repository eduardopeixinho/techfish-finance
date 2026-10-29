{{--
    Relatório financeiro do período (FinanceController@reports). Regime de caixa: data da transação.
    Valores em R$ via $money. Composição por categoria: lista de barras de um tom por lista
    (receitas / despesas), ordenada do maior para o menor, com valor e % escritos.
--}}
@php
    use App\Support\Format;
    use Carbon\Carbon;

    $totalIncome = (float) $incomeReport->sum('total');
    $totalExpense = (float) $expenseReport->sum('total');
    $netBalance = $totalIncome - $totalExpense;
    $thead = 'border-y border-line bg-paper/60 text-left text-xs uppercase tracking-wider text-ink-2';
    $csvUrl = route('finance.reports.export-csv', ['start_date' => $startDateFormatted, 'end_date' => $endDateFormatted]);
@endphp

<x-finance-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-6">
        <x-ui.page-header title="Relatório financeiro" kicker="Finanças" class="!mb-2">
            <x-slot:description>
                {{ Carbon::parse($startDateFormatted)->format('d/m/Y') }} a {{ Carbon::parse($endDateFormatted)->format('d/m/Y') }}, pela data das transações.
            </x-slot:description>
            <x-slot:actions>
                <x-ui.button variant="secondary" :href="$csvUrl">Exportar CSV</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        {{-- Período --}}
        <x-ui.card>
            <form method="GET" action="{{ route('finance.reports') }}" class="flex flex-wrap items-end gap-4">
                <x-ui.field label="De" for="start_date">
                    <input type="date" id="start_date" name="start_date" value="{{ $startDateFormatted }}" class="ui-input">
                </x-ui.field>
                <x-ui.field label="Até" for="end_date">
                    <input type="date" id="end_date" name="end_date" value="{{ $endDateFormatted }}" class="ui-input">
                </x-ui.field>
                <div class="flex gap-2">
                    <x-ui.button type="submit">Aplicar</x-ui.button>
                    <x-ui.button variant="secondary" :href="route('finance.reports')">Mês atual</x-ui.button>
                </div>
            </form>
        </x-ui.card>

        {{-- Resumo --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-ui.card>
                <p class="text-sm text-ink-2">Receitas</p>
                <p class="mt-1 font-serif text-3xl text-emerald-700">{{ $money->format($totalIncome) }}</p>
            </x-ui.card>
            <x-ui.card>
                <p class="text-sm text-ink-2">Despesas</p>
                <p class="mt-1 font-serif text-3xl text-red-700">{{ $money->format($totalExpense) }}</p>
            </x-ui.card>
            <x-ui.card>
                <p class="text-sm text-ink-2">Saldo do período</p>
                <p class="mt-1 font-serif text-3xl {{ $netBalance >= 0 ? 'text-finance-base' : 'text-red-700' }}">{{ $money->formatSigned($netBalance) }}</p>
            </x-ui.card>
        </div>

        {{-- Por categoria --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @foreach ([
                ['title' => 'Receitas por categoria', 'items' => $incomeReport, 'total' => $totalIncome, 'bar' => 'bg-emerald-700', 'empty' => 'Nenhuma receita no período.'],
                ['title' => 'Despesas por categoria', 'items' => $expenseReport, 'total' => $totalExpense, 'bar' => 'bg-red-700', 'empty' => 'Nenhuma despesa no período.'],
            ] as $group)
                <x-ui.card :title="$group['title']">
                    @if ($group['items']->isEmpty())
                        <p class="text-ink-2">{{ $group['empty'] }}</p>
                    @else
                        <ul class="space-y-4">
                            @foreach ($group['items']->sortByDesc('total') as $row)
                                @php $percentage = $group['total'] > 0 ? ((float) $row->total / $group['total']) * 100 : 0; @endphp
                                <li>
                                    <div class="flex items-baseline justify-between gap-3 text-sm">
                                        <span class="inline-flex items-center gap-1.5 font-medium text-ink truncate">
                                            @if ($row->category)
                                                <span class="w-2 h-2 rounded-full shrink-0" style="background-color: {{ $row->category->color }}" aria-hidden="true"></span>
                                            @endif
                                            {{ $row->category->name ?? 'Sem categoria' }}
                                        </span>
                                        <span class="font-mono text-ink whitespace-nowrap">{{ $money->format((float) $row->total) }}</span>
                                    </div>
                                    <div class="mt-1.5 flex items-center gap-3">
                                        <div class="h-2 flex-1 rounded-full bg-line" aria-hidden="true">
                                            <div class="h-2 rounded-full {{ $group['bar'] }}" style="width: {{ max(round($percentage, 1), 1) }}%"></div>
                                        </div>
                                        <span class="w-12 text-right font-mono text-xs text-ink-2">{{ Format::percent($percentage) }}</span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-ui.card>
            @endforeach
        </div>

        {{-- Por conta --}}
        @if ($accountReport->isNotEmpty())
            <x-ui.card class="!p-0 overflow-hidden">
                <h2 class="font-serif text-xl tracking-tight text-finance-base px-6 pt-6 pb-4">Por conta</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="{{ $thead }}">
                            <tr>
                                <th class="px-6 py-3 font-medium">Conta</th>
                                <th class="px-6 py-3 font-medium text-right">Receitas</th>
                                <th class="px-6 py-3 font-medium text-right">Despesas</th>
                                <th class="px-6 py-3 font-medium text-right">Saldo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($accountReport as $rows)
                                @php
                                    $account = $rows->first()->account;
                                    $income = (float) $rows->where('type', 'income')->sum('total');
                                    $expense = (float) $rows->where('type', 'expense')->sum('total');
                                @endphp
                                <tr>
                                    <td class="px-6 py-3">
                                        <span class="inline-flex items-center gap-2 text-ink">
                                            <span class="w-2 h-2 rounded-full" style="background-color: {{ $account?->color }}" aria-hidden="true"></span>
                                            {{ $account?->name ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-right text-emerald-700">{{ $money->format($income) }}</td>
                                    <td class="px-6 py-3 text-right text-red-700">{{ $money->format($expense) }}</td>
                                    <td class="px-6 py-3 text-right font-medium {{ $income - $expense >= 0 ? 'text-ink' : 'text-red-700' }}">{{ $money->formatSigned($income - $expense) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Últimos 6 meses --}}
            <x-ui.card class="!p-0 overflow-hidden">
                <h2 class="font-serif text-xl tracking-tight text-finance-base px-6 pt-6 pb-4">Últimos 6 meses</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="{{ $thead }}">
                            <tr>
                                <th class="px-6 py-3 font-medium">Mês</th>
                                <th class="px-6 py-3 font-medium text-right">Receitas</th>
                                <th class="px-6 py-3 font-medium text-right">Despesas</th>
                                <th class="px-6 py-3 font-medium text-right">Saldo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($monthlyComparison as $month)
                                <tr>
                                    <td class="px-6 py-3 text-ink">{{ $month['month'] }}</td>
                                    <td class="px-6 py-3 text-right text-emerald-700">{{ $money->format((float) $month['income']) }}</td>
                                    <td class="px-6 py-3 text-right text-red-700">{{ $money->format((float) $month['expense']) }}</td>
                                    <td class="px-6 py-3 text-right font-medium {{ $month['balance'] >= 0 ? 'text-ink' : 'text-red-700' }}">{{ $money->formatSigned((float) $month['balance']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-ui.card>

            {{-- Dia a dia --}}
            <x-ui.card class="!p-0 overflow-hidden">
                <h2 class="font-serif text-xl tracking-tight text-finance-base px-6 pt-6 pb-4">Dia a dia</h2>
                @if ($dailyEvolution->isEmpty())
                    <p class="px-6 pb-8 text-ink-2">Sem movimentação no período.</p>
                @else
                    <div class="overflow-x-auto max-h-96">
                        <table class="min-w-full text-sm">
                            <thead class="{{ $thead }} sticky top-0">
                                <tr>
                                    <th class="px-6 py-3 font-medium">Data</th>
                                    <th class="px-6 py-3 font-medium text-right">Receitas</th>
                                    <th class="px-6 py-3 font-medium text-right">Despesas</th>
                                    <th class="px-6 py-3 font-medium text-right">Saldo</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                @foreach ($dailyEvolution as $date => $rows)
                                    @php
                                        $income = (float) $rows->where('type', 'income')->sum('total');
                                        $expense = (float) $rows->where('type', 'expense')->sum('total');
                                    @endphp
                                    <tr>
                                        <td class="px-6 py-3 font-mono text-ink-2">{{ Carbon::parse($date)->format('d/m/Y') }}</td>
                                        <td class="px-6 py-3 text-right text-emerald-700">{{ $money->format($income) }}</td>
                                        <td class="px-6 py-3 text-right text-red-700">{{ $money->format($expense) }}</td>
                                        <td class="px-6 py-3 text-right font-medium {{ $income - $expense >= 0 ? 'text-ink' : 'text-red-700' }}">{{ $money->formatSigned($income - $expense) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-ui.card>
        </div>
    </div>
</x-finance-layout>
