{{--
    Painel de Investimentos. Totais da carteira ATIVA em R$ via $money; proventos = mês corrente (data de pagamento).
    "Tipos" é catálogo de admin (can:admin.super): o atalho só aparece para quem tem acesso.
--}}
@php
    $shortcuts = [
        ['route' => 'investment.investments.index', 'title' => 'Carteira', 'text' => 'Seus ativos e posições'],
        ['route' => 'investment.transactions.index', 'title' => 'Compras e vendas', 'text' => 'Histórico de operações'],
        ['route' => 'investment.dividends.index', 'title' => 'Proventos', 'text' => 'Dividendos, JCP e rendimentos'],
        ['route' => 'investment.reports', 'title' => 'Relatórios', 'text' => 'Alocação, carteira e proventos'],
    ];
    if (Gate::allows('admin.super')) {
        $shortcuts[] = ['route' => 'investment.types.index', 'title' => 'Tipos', 'text' => 'Catálogo (admin)'];
    }
@endphp

<x-finance-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-ui.page-header title="Investimentos" kicker="Finanças">
            <x-slot:actions>
                <x-ui.button :href="route('investment.transactions.create')">Nova compra/venda</x-ui.button>
                <x-ui.button variant="secondary" :href="route('investment.dividends.create')">Registrar provento</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <x-ui.card>
                <p class="text-sm text-ink-2">Total investido</p>
                <p class="mt-1 font-serif text-2xl text-ink">{{ $money->format((float) $totalInvested) }}</p>
            </x-ui.card>
            <x-ui.card>
                <p class="text-sm text-ink-2">Valor atual</p>
                <p class="mt-1 font-serif text-2xl text-ink">{{ $money->format((float) $currentValue) }}</p>
            </x-ui.card>
            <x-ui.card>
                <p class="text-sm text-ink-2">Resultado</p>
                <p class="mt-1 font-serif text-2xl {{ $profitLoss >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ $money->formatSigned((float) $profitLoss) }}</p>
            </x-ui.card>
            <x-ui.card>
                <p class="text-sm text-ink-2">Proventos em {{ now()->translatedFormat('F') }}</p>
                <p class="mt-1 font-serif text-2xl text-emerald-700">{{ $money->format((float) $monthlyDividends) }}</p>
            </x-ui.card>
        </div>

        {{-- Classes literais (o Tailwind não detecta classes montadas dinamicamente) --}}
        <nav class="grid grid-cols-1 sm:grid-cols-2 {{ count($shortcuts) === 5 ? 'lg:grid-cols-5' : 'lg:grid-cols-4' }} gap-4" aria-label="Seções de investimentos">
            @foreach ($shortcuts as $shortcut)
                <a href="{{ route($shortcut['route']) }}"
                   class="group block bg-finance-paper border border-line rounded-sm p-5 hover:border-finance-base/40 transition-colors">
                    <span class="font-serif text-lg text-finance-base">{{ $shortcut['title'] }}</span>
                    <span class="block mt-1 text-sm text-ink-2">{{ $shortcut['text'] }}</span>
                    <span class="mt-3 inline-block text-sm text-finance-base/70 group-hover:translate-x-1 transition-transform" aria-hidden="true">→</span>
                </a>
            @endforeach
        </nav>
    </div>
</x-finance-layout>
