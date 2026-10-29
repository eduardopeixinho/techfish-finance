{{-- Contas do usuário. Saldos em R$ via $money (respeita "ocultar valores"). --}}
@php $totalBalance = (float) $accounts->where('is_active', true)->sum('current_balance'); @endphp

<x-finance-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-ui.page-header title="Contas" kicker="Finanças">
            <x-slot:description>Contas bancárias, cartões e carteiras. O saldo atual é recalculado a cada transação.</x-slot:description>
            <x-slot:actions>
                <x-ui.button :href="route('finance.accounts.create')">Nova conta</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.flash />

        @if ($accounts->isEmpty())
            <x-ui.card class="text-center py-12">
                <p class="text-ink-2 mb-4">Você ainda não tem contas cadastradas.</p>
                <x-ui.button :href="route('finance.accounts.create')">Criar primeira conta</x-ui.button>
            </x-ui.card>
        @else
            {{-- Resumo --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <x-ui.card>
                    <p class="text-sm text-ink-2">Saldo total (contas ativas)</p>
                    <p class="mt-1 font-serif text-3xl {{ $totalBalance >= 0 ? 'text-finance-base' : 'text-red-700' }}">{{ $money->format($totalBalance) }}</p>
                </x-ui.card>
                <x-ui.card>
                    <p class="text-sm text-ink-2">Contas ativas</p>
                    <p class="mt-1 font-serif text-3xl text-ink">{{ $accounts->where('is_active', true)->count() }} <span class="text-lg text-ink-2">de {{ $accounts->count() }}</span></p>
                </x-ui.card>
                <x-ui.card>
                    <p class="text-sm text-ink-2">Transações registradas</p>
                    <p class="mt-1 font-serif text-3xl text-ink">{{ $accounts->sum('transactions_count') }}</p>
                </x-ui.card>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($accounts as $account)
                    <x-ui.card class="border-l-4 flex flex-col {{ $account->is_active ? '' : 'opacity-70' }}" style="border-left-color: {{ $account->color }}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <a href="{{ route('finance.accounts.show', $account) }}" class="block font-serif text-xl text-ink hover:text-finance-base truncate">{{ $account->name }}</a>
                                @if ($account->bank)
                                    <p class="text-sm text-ink-2 truncate">{{ $account->bank }}</p>
                                @endif
                            </div>
                            <div class="flex flex-col items-end gap-1">
                                <x-ui.badge>{{ $account->type_label }}</x-ui.badge>
                                @unless ($account->is_active)
                                    <x-ui.badge tone="warning">Inativa</x-ui.badge>
                                @endunless
                            </div>
                        </div>

                        <p class="mt-4 text-sm text-ink-2">Saldo atual</p>
                        <p class="font-serif text-2xl {{ $account->current_balance >= 0 ? 'text-ink' : 'text-red-700' }}">{{ $money->format((float) $account->current_balance) }}</p>

                        <p class="mt-2 text-xs text-ink-2 font-mono">
                            Inicial {{ $money->format((float) $account->initial_balance) }} · {{ $account->transactions_count }} {{ $account->transactions_count === 1 ? 'transação' : 'transações' }}
                        </p>

                        <div class="mt-auto pt-4 flex gap-2">
                            <x-ui.button variant="secondary" size="sm" :href="route('finance.accounts.show', $account)">Ver</x-ui.button>
                            <x-ui.button variant="accent" size="sm" :href="route('finance.accounts.edit', $account)">Editar</x-ui.button>
                        </div>
                    </x-ui.card>
                @endforeach
            </div>
        @endif
    </div>
</x-finance-layout>
