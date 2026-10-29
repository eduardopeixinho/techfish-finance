{{--
    Faturas de cartão, pelo mês do VENCIMENTO. Valores em R$ via $money.
    Status (CreditCardBillService::billStatus): Paga · Paga em parte · Vencida · Vence em breve · Em aberto.
--}}
@php
    use Carbon\Carbon;
@endphp

<x-finance-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-ui.page-header title="Faturas" kicker="Finanças">
            <x-slot:description>Faturas dos cartões de crédito, pelo mês do vencimento. Abra uma fatura para registrar o pagamento.</x-slot:description>
            <x-slot:actions>
                <x-ui.button variant="secondary" :href="route('finance.accounts.index')">Contas</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.flash />

        <x-ui.card class="!p-0 overflow-hidden">
            @if (count($bills) === 0)
                <div class="px-6 py-12 text-center">
                    <p class="text-ink-2 mb-4">Nenhuma fatura ainda. Elas aparecem ao lançar despesas numa conta do tipo "Cartão de crédito".</p>
                    <x-ui.button variant="secondary" :href="route('finance.accounts.create')">Cadastrar cartão</x-ui.button>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="border-b border-line bg-paper/60 text-left text-xs uppercase tracking-wider text-ink-2">
                            <tr>
                                <th class="px-6 py-3 font-medium">Cartão</th>
                                <th class="px-6 py-3 font-medium">Fatura</th>
                                <th class="px-6 py-3 font-medium">Vencimento</th>
                                <th class="px-6 py-3 font-medium text-right">Total</th>
                                <th class="px-6 py-3 font-medium text-right">Em aberto</th>
                                <th class="px-6 py-3 font-medium">Status</th>
                                <th class="px-6 py-3"><span class="sr-only">Ações</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($bills as $bill)
                                @php
                                    [$statusLabel, $statusTone] = $bill['status'];
                                    $billMonth = $bill['due_date'] ? Carbon::parse($bill['due_date']) : Carbon::createFromFormat('!m-Y', str_replace('/', '-', $bill['bill_identifier']));
                                    $billUrl = route('finance.bills.show', ['account' => $bill['account']->id, 'billIdentifier' => $bill['bill_identifier']]);
                                @endphp
                                <tr class="hover:bg-white/60">
                                    <td class="px-6 py-3">
                                        <span class="inline-flex items-center gap-2">
                                            <span class="w-2.5 h-2.5 rounded-full" style="background-color: {{ $bill['account']->color }}" aria-hidden="true"></span>
                                            <span class="font-medium text-ink">{{ $bill['account']->name }}</span>
                                        </span>
                                        @if ($bill['account']->bank)
                                            <span class="block text-xs text-ink-2">{{ $bill['account']->bank }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-ink">
                                        {{ ucfirst($billMonth->translatedFormat('F \d\e Y')) }}
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap">
                                        @if ($bill['due_date'])
                                            <span class="font-mono text-ink">{{ Carbon::parse($bill['due_date'])->format('d/m/Y') }}</span>
                                            <span class="block text-xs text-ink-2">{{ Carbon::parse($bill['due_date'])->diffForHumans() }}</span>
                                        @else
                                            <span class="text-ink-2">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-right">
                                        <span class="font-medium text-ink">{{ $money->format((float) $bill['total_amount']) }}</span>
                                        <span class="block text-xs text-ink-2">{{ $bill['transaction_count'] }} {{ $bill['transaction_count'] == 1 ? 'lançamento' : 'lançamentos' }}</span>
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-right">
                                        <span @class(['font-medium', 'text-ink' => $bill['remaining_amount'] > 0, 'text-ink-2' => $bill['remaining_amount'] <= 0])>{{ $money->format((float) $bill['remaining_amount']) }}</span>
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap"><x-ui.badge :tone="$statusTone">{{ $statusLabel }}</x-ui.badge></td>
                                    <td class="px-6 py-3 whitespace-nowrap text-right">
                                        <x-ui.button variant="secondary" size="sm" :href="$billUrl">Ver fatura</x-ui.button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.card>
    </div>
</x-finance-layout>
