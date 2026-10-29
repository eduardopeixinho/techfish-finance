<x-finance-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Header --}}
        <div class="mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="font-serif text-3xl font-semibold text-finance-base tracking-tight">
                        Dashboard Financeiro
                    </h1>
                    <p class="mt-2 text-ink-2">
                        Visão geral das suas finanças
                    </p>
                </div>

                {{-- Seletor de Mês/Ano --}}
                <form method="GET" action="{{ route('finance.dashboard') }}" class="flex items-center gap-2">
                    <select name="month" aria-label="Mês" class="min-w-[9.5rem] pl-3 pr-9 py-2 border border-finance-base/20 rounded-sm text-sm focus:ring-finance-base focus:border-finance-base">
                        @foreach(range(1, 12) as $m)
                            <option value="{{ $m }}" {{ $selectedMonth == $m ? 'selected' : '' }}>
                                {{ ucfirst(Carbon\Carbon::createFromDate(2000, $m, 1)->translatedFormat('F')) }}
                            </option>
                        @endforeach
                    </select>
                    <select name="year" aria-label="Ano" class="min-w-[6.5rem] pl-3 pr-9 py-2 border border-finance-base/20 rounded-sm text-sm focus:ring-finance-base focus:border-finance-base">
                        @foreach(range(Carbon\Carbon::now()->year - 2, Carbon\Carbon::now()->year + 1) as $y)
                            <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>
                                {{ $y }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="px-4 py-2 bg-finance-base text-white text-sm rounded-sm hover:bg-finance-accent transition-colors">
                        Filtrar
                    </button>
                </form>
            </div>
        </div>

        {{-- Cards de Resumo --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

            {{-- Saldo Total --}}
            <div class="bg-finance-paper border border-finance-base/10 rounded-sm p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 bg-finance-base/10 rounded-sm flex items-center justify-center">
                            <svg class="w-5 h-5 text-finance-base" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-xs font-mono tracking-widest uppercase text-ink-2">Saldo Total</p>
                        <p class="text-2xl font-semibold text-finance-base tabular-nums">
                            {{ $money->format((float) $totalBalance) }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Receitas do Mês --}}
            <div class="bg-finance-paper border border-finance-base/10 rounded-sm p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 bg-green-500/10 rounded-sm flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M7 11l5-5m0 0l5 5m-5-5v12" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-xs font-mono tracking-widest uppercase text-ink-2">Receitas do Mês</p>
                        <p class="text-2xl font-semibold text-green-600 tabular-nums">
                            {{ $money->format((float) $monthlyIncome) }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Despesas do Mês --}}
            <div class="bg-finance-paper border border-finance-base/10 rounded-sm p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 bg-red-500/10 rounded-sm flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 13l-5 5m0 0l-5-5m5 5V6" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-xs font-mono tracking-widest uppercase text-ink-2">Despesas do Mês</p>
                        <p class="text-2xl font-semibold text-red-600 tabular-nums">
                            {{ $money->format((float) $monthlyExpenses) }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Resultado do Mês --}}
            <div class="bg-finance-paper border border-finance-base/10 rounded-sm p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 {{ $monthlyBalance >= 0 ? 'bg-green-500/10' : 'bg-red-500/10' }} rounded-sm flex items-center justify-center">
                            <svg class="w-5 h-5 {{ $monthlyBalance >= 0 ? 'text-green-600' : 'text-red-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-xs font-mono tracking-widest uppercase text-ink-2">Resultado do Mês</p>
                        <p class="text-2xl font-semibold {{ $monthlyBalance >= 0 ? 'text-green-600' : 'text-red-600' }} tabular-nums">
                            {{ $money->format((float) $monthlyBalance) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Grid: Transações Recentes + Contas --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">

            {{-- Transações Recentes --}}
            <div class="bg-finance-paper border border-finance-base/10 rounded-sm">
                <div class="px-6 py-4 border-b border-finance-base/10">
                    <h3 class="font-serif text-lg font-semibold text-finance-base">
                        Transações Recentes
                    </h3>
                </div>
                <div class="p-6">
                    @if($recentTransactions->isEmpty())
                        <p class="text-ink-2 text-center py-8">Nenhuma transação encontrada</p>
                    @else
                        <div class="space-y-4">
                            @foreach($recentTransactions as $transaction)
                                <div class="flex items-center justify-between p-3 bg-paper rounded-sm border border-finance-base/5">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 {{ $transaction->type === 'income' ? 'bg-green-500/10' : 'bg-red-500/10' }} rounded-sm flex items-center justify-center mr-3">
                                            @if($transaction->type === 'income')
                                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                                </svg>
                                            @else
                                                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M18 12H6" />
                                                </svg>
                                            @endif
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-ink">{{ $transaction->description }}</p>
                                            <p class="text-xs text-ink-2">
                                                {{ $transaction->account->name }}
                                                @if($transaction->category)
                                                    · {{ $transaction->category->name }}
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-semibold {{ $transaction->type === 'income' ? 'text-green-600' : 'text-red-600' }} tabular-nums">
                                            {{ $transaction->type === 'income' ? '+' : '-' }} {{ $money->format((float) $transaction->amount) }}
                                        </p>
                                        <p class="text-xs text-ink-2">{{ $transaction->formatted_date }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Contas --}}
            <div class="bg-finance-paper border border-finance-base/10 rounded-sm">
                <div class="px-6 py-4 border-b border-finance-base/10">
                    <h3 class="font-serif text-lg font-semibold text-finance-base">
                        Suas Contas
                    </h3>
                </div>
                <div class="p-6">
                    @if($accounts->isEmpty())
                        <p class="text-ink-2 text-center py-8">Nenhuma conta cadastrada</p>
                    @else
                        <div class="space-y-4">
                            @foreach($accounts as $account)
                                <div class="flex items-center justify-between p-3 bg-paper rounded-sm border border-finance-base/5">
                                    <div class="flex items-center">
                                        <div class="w-4 h-4 rounded-full mr-3" style="background-color: {{ $account->color }}"></div>
                                        <div>
                                            <p class="text-sm font-medium text-ink">{{ $account->name }}</p>
                                            <p class="text-xs text-ink-2">{{ \App\Models\Finance\Account::TYPES[$account->type] ?? $account->type }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-semibold text-finance-base tabular-nums">
                                            {{ $money->format((float) $account->current_balance) }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Botões de Ação Rápida --}}
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('finance.transactions.create') }}"
               class="inline-flex items-center px-5 py-2.5 bg-finance-base text-paper text-sm font-medium rounded-sm hover:bg-finance-accent transition-colors">
                Nova Transação
            </a>
            <a href="{{ route('finance.accounts.index') }}"
               class="inline-flex items-center px-5 py-2.5 bg-green-600 text-paper text-sm font-medium rounded-sm hover:bg-green-700 transition-colors">
                Gerenciar Contas
            </a>
            <a href="{{ route('finance.categories.index') }}"
               class="inline-flex items-center px-5 py-2.5 bg-purple-600 text-paper text-sm font-medium rounded-sm hover:bg-purple-700 transition-colors">
                Categorias
            </a>
            {{-- Catálogos globais (tipos de conta, meios de pagamento): só admin --}}
            @can('admin.super')
                <a href="{{ route('settings.index') }}"
                   class="inline-flex items-center px-5 py-2.5 bg-orange-600 text-paper text-sm font-medium rounded-sm hover:bg-orange-700 transition-colors">
                    Configurações
                </a>
            @endcan
            <a href="{{ route('finance.reports') }}"
               class="inline-flex items-center px-5 py-2.5 bg-gray-600 text-paper text-sm font-medium rounded-sm hover:bg-gray-700 transition-colors">
                Relatórios
            </a>
        </div>

    </div>
</x-finance-layout>
