{{--
    Menu principal do app: Painel · Transações · Contas · Faturas · Investimentos · Relatórios · [Admin].

    O item ativo é detectado pela rota atual (não precisa de prop).
    Em telas grandes os links ficam em linha; abaixo de `lg`, vira um botão com o
    item atual que abre a lista (menu compacto).
    "Admin" só aparece se Gate::allows('admin.access').
--}}
@php
    $items = [
        'dashboard' => ['label' => 'Painel', 'url' => route('finance.dashboard'), 'active' => ['finance.dashboard']],
        'transactions' => ['label' => 'Transações', 'url' => route('finance.transactions.index'), 'active' => ['finance.transactions.*']],
        'accounts' => ['label' => 'Contas', 'url' => route('finance.accounts.index'), 'active' => ['finance.accounts.*', 'finance.categories.*', 'settings.*']],
        'bills' => ['label' => 'Faturas', 'url' => route('finance.bills.index'), 'active' => ['finance.bills.*']],
        'investments' => ['label' => 'Investimentos', 'url' => route('investment.dashboard'), 'active' => ['investment.*']],
        'reports' => ['label' => 'Relatórios', 'url' => route('finance.reports'), 'active' => ['finance.reports*']],
    ];
    if (\Illuminate\Support\Facades\Gate::allows('admin.access')) {
        $items['admin'] = ['label' => 'Admin', 'url' => route('admin.user-roles.index'), 'active' => ['admin.*']];
    }

    $current = collect($items)->search(fn (array $item): bool => request()->routeIs(...$item['active']));

    $linkClass = 'inline-flex items-center px-3 py-1.5 rounded-sm transition-colors';
    $activeClass = 'bg-finance-base/10 text-finance-base font-medium';
    $idleClass = 'text-ink-2 hover:text-ink';
@endphp

{{-- Telas grandes: links em linha --}}
<nav {{ $attributes->merge(['class' => 'hidden lg:flex items-center gap-1 text-sm']) }} aria-label="Menu principal">
    @foreach ($items as $key => $item)
        <a href="{{ $item['url'] }}" @if ($current === $key) aria-current="page" @endif
           class="{{ $linkClass }} {{ $current === $key ? $activeClass : $idleClass }}">
            {{ $item['label'] }}
        </a>
    @endforeach
</nav>

{{-- Telas menores: botão com o item atual que abre a lista --}}
<div class="lg:hidden relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
    <button type="button" @click="open = ! open" :aria-expanded="open.toString()" aria-haspopup="menu" aria-label="Menu principal"
            class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-sm text-sm {{ $activeClass }}">
        {{ $current ? $items[$current]['label'] : 'Menu' }}
        <span aria-hidden="true" class="text-xs">▾</span>
    </button>
    <div x-show="open" x-transition.opacity x-cloak role="menu"
         class="absolute left-1/2 -translate-x-1/2 mt-2 w-48 rounded-sm border border-line bg-paper shadow-xl z-50 py-1">
        @foreach ($items as $key => $item)
            <a href="{{ $item['url'] }}" role="menuitem"
               class="block px-4 py-2 text-sm {{ $current === $key ? $activeClass : $idleClass }}">
                {{ $item['label'] }}
            </a>
        @endforeach
    </div>
</div>
