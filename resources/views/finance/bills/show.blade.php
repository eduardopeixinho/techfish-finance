{{--
    Detalhe de uma fatura de cartão (competência = mês da fatura). Valores em R$ via $money.
    Composição por categoria: lista de barras em um único tom (grandeza), ordenada do maior
    para o menor, com valor e % escritos em cada linha (substitui o gráfico de rosca via CDN).
--}}
@php
    use Carbon\Carbon;

    $total = (float) $summary['total_amount'];
    $categories = collect($summary['categories'])->sortByDesc('total_amount')->values();
    $competency = $summary['competency_date'] ? ucfirst(Carbon::parse($summary['competency_date'])->translatedFormat('F \d\e Y')) : $summary['bill_identifier'];
@endphp

<x-finance-layout>
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-ui.page-header :title="'Fatura de '.$competency" :kicker="$account->name.($account->bank ? ' · '.$account->bank : '')">
            <x-slot:actions>
                <x-ui.button variant="secondary" :href="route('finance.bills.index')">Faturas</x-ui.button>
                <x-ui.button variant="secondary" :href="route('finance.accounts.show', $account)">Cartão</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <x-ui.card>
                <p class="text-sm text-ink-2">Total da fatura</p>
                <p class="mt-1 font-serif text-3xl text-ink">{{ $money->format($total) }}</p>
            </x-ui.card>
            <x-ui.card>
                <p class="text-sm text-ink-2">Vencimento</p>
                <p class="mt-1 font-serif text-3xl text-ink">{{ $summary['due_date'] ? Carbon::parse($summary['due_date'])->format('d/m/Y') : '—' }}</p>
                @if ($summary['due_date'])
                    <p class="text-xs text-ink-2">{{ Carbon::parse($summary['due_date'])->diffForHumans() }}</p>
                @endif
            </x-ui.card>
            <x-ui.card>
                <p class="text-sm text-ink-2">Lançamentos</p>
                <p class="mt-1 font-serif text-3xl text-ink">{{ $summary['transaction_count'] }}</p>
            </x-ui.card>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
            {{-- Composição por categoria --}}
            <x-ui.card title="Por categoria" class="lg:col-span-2">
                @if ($categories->isEmpty())
                    <p class="text-ink-2">Sem lançamentos nesta fatura.</p>
                @else
                    <ul class="space-y-4" aria-label="Gastos por categoria, do maior para o menor">
                        @foreach ($categories as $category)
                            @php $percentage = $total > 0 ? ((float) $category['total_amount'] / $total) * 100 : 0; @endphp
                            <li>
                                <div class="flex items-baseline justify-between gap-3 text-sm">
                                    <span class="font-medium text-ink truncate">{{ $category['category_name'] }}</span>
                                    <span class="font-mono text-ink whitespace-nowrap">{{ $money->format((float) $category['total_amount']) }}</span>
                                </div>
                                <div class="mt-1.5 flex items-center gap-3">
                                    <div class="h-2 flex-1 rounded-full bg-line" aria-hidden="true">
                                        <div class="h-2 rounded-full bg-finance-base" style="width: {{ max(round($percentage, 1), 1) }}%"></div>
                                    </div>
                                    <span class="w-12 text-right font-mono text-xs text-ink-2">{{ \App\Support\Format::percent($percentage) }}</span>
                                </div>
                                <p class="mt-0.5 text-xs text-ink-2">
                                    {{ $category['transaction_count'] }} {{ $category['transaction_count'] == 1 ? 'lançamento' : 'lançamentos' }}
                                </p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>

            {{-- Lançamentos --}}
            <x-ui.card class="lg:col-span-3 !p-0 overflow-hidden">
                <h2 class="font-serif text-xl tracking-tight text-finance-base px-6 pt-6 pb-4">Lançamentos</h2>
                @if (count($transactions) === 0)
                    <p class="px-6 pb-8 text-ink-2">Nenhum lançamento nesta fatura.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="border-y border-line bg-paper/60 text-left text-xs uppercase tracking-wider text-ink-2">
                                <tr>
                                    <th class="px-6 py-3 font-medium">Data</th>
                                    <th class="px-6 py-3 font-medium">Descrição</th>
                                    <th class="px-6 py-3 font-medium">Categoria</th>
                                    <th class="px-6 py-3 font-medium text-right">Valor</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                @foreach ($transactions as $transaction)
                                    <tr class="hover:bg-white/60">
                                        <td class="px-6 py-3 whitespace-nowrap font-mono text-ink-2">
                                            {{ Carbon::parse($transaction->transaction_date)->format('d/m/Y') }}
                                        </td>
                                        <td class="px-6 py-3">
                                            <a href="{{ route('finance.transactions.show', $transaction) }}" class="font-medium text-ink hover:text-finance-base">{{ $transaction->description }}</a>
                                            @if ($transaction->notes)
                                                <span class="block text-xs text-ink-2">{{ $transaction->notes }}</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-3 whitespace-nowrap">
                                            @if ($transaction->category)
                                                <span class="inline-flex items-center gap-1.5 text-ink">
                                                    <span class="w-2 h-2 rounded-full" style="background-color: {{ $transaction->category->color }}" aria-hidden="true"></span>
                                                    {{ $transaction->category->name }}
                                                </span>
                                            @else
                                                <span class="text-ink-2">Sem categoria</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-3 whitespace-nowrap text-right font-medium text-red-700">
                                            − {{ $money->format((float) abs($transaction->amount)) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="border-t-2 border-line">
                                <tr>
                                    <td colspan="3" class="px-6 py-3 text-right font-medium text-ink">Total da fatura</td>
                                    <td class="px-6 py-3 text-right font-medium text-ink">{{ $money->format($total) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </x-ui.card>
        </div>
    </div>
</x-finance-layout>
