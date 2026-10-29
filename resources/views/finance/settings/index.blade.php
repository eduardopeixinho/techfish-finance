{{-- Configurações (admin): catálogos globais usados pelo módulo de Finanças. --}}
@php
    use App\Support\SettingsCatalog;

    $cards = [
        ['key' => 'account-types', 'count' => $accountTypesCount, 'extra' => null],
        ['key' => 'income-types', 'count' => $incomeTypesCount, 'extra' => null],
        ['key' => 'payment-methods', 'count' => $paymentMethodsCount, 'extra' => $activePaymentMethodsCount.' ativas'],
    ];
@endphp

<x-finance-layout>
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-ui.page-header title="Configurações" kicker="Finanças · Admin">
            <x-slot:description>Catálogos globais, compartilhados por todos os usuários. Só administradores editam.</x-slot:description>
            <x-slot:actions>
                <x-ui.button variant="secondary" :href="route('finance.dashboard')">Painel de Finanças</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach ($cards as $card)
                @php $config = SettingsCatalog::get($card['key']); @endphp
                <x-ui.card class="flex flex-col">
                    <h2 class="font-serif text-xl text-finance-base">{{ $config['title'] }}</h2>
                    <p class="mt-1 text-sm font-mono text-ink-2">
                        {{ $card['count'] }} cadastrados{{ $card['extra'] ? ' · '.$card['extra'] : '' }}
                    </p>
                    <p class="mt-3 text-sm text-ink-2 flex-1">{{ $config['description'] }}</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <x-ui.button size="sm" :href="route($config['route'].'.index')">Gerenciar</x-ui.button>
                        <x-ui.button variant="secondary" size="sm" :href="route($config['route'].'.create')">{{ $config['new'] }}</x-ui.button>
                    </div>
                </x-ui.card>
            @endforeach
        </div>
    </div>
</x-finance-layout>
