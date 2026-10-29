{{--
    Primeiros passos no painel (some quando os três estão feitos).
    Espera $steps: ['account' => bool, 'categories' => bool, 'transaction' => bool] (OnboardingService::steps).
--}}
@php
    $items = [
        'account' => [
            'title' => 'Cadastre uma conta',
            'text' => 'Conta corrente, poupança, cartão de crédito ou dinheiro. Toda transação pertence a uma conta.',
        ],
        'categories' => [
            'title' => 'Organize por categorias',
            'text' => 'Moradia, mercado, salário… Comece com as sugeridas e ajuste depois.',
        ],
        'transaction' => [
            'title' => 'Lance a primeira transação',
            'text' => 'Uma receita, despesa ou transferência. O painel e os relatórios passam a ter números.',
        ],
    ];
    $done = count(array_filter($steps));
@endphp

<section class="mb-8 bg-finance-paper border border-finance-base/20 rounded-sm p-6" aria-labelledby="onboarding-title">
    <div class="flex flex-wrap items-baseline justify-between gap-2 mb-5">
        <h2 id="onboarding-title" class="font-serif text-xl font-semibold text-finance-base">Primeiros passos</h2>
        <span class="text-xs font-mono text-ink-2">{{ $done }} de 3 concluídos</span>
    </div>

    <ol class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach ($items as $key => $item)
            <li @class(['border rounded-sm p-4', 'border-finance-base/30 bg-finance-base/5' => $steps[$key], 'border-line bg-white/60' => ! $steps[$key]])>
                <p class="flex items-center gap-2 font-medium text-ink">
                    <span @class(['w-5 h-5 rounded-full text-[11px] inline-flex items-center justify-center', 'bg-finance-base text-white' => $steps[$key], 'border border-finance-base/40 text-finance-base' => ! $steps[$key]]) aria-hidden="true">
                        {{ $steps[$key] ? '✓' : $loop->iteration }}
                    </span>
                    {{ $item['title'] }}
                    @if ($steps[$key]) <span class="sr-only">(concluído)</span> @endif
                </p>
                <p class="mt-2 text-sm text-ink-2 leading-relaxed">{{ $item['text'] }}</p>

                @unless ($steps[$key])
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        @switch($key)
                            @case('account')
                                <x-ui.button size="sm" :href="route('finance.accounts.create')">Nova conta</x-ui.button>
                                @break
                            @case('categories')
                                <form method="POST" action="{{ route('finance.categories.defaults') }}" class="inline-flex">
                                    @csrf
                                    <x-ui.button type="submit" size="sm">Criar categorias sugeridas</x-ui.button>
                                </form>
                                <x-ui.button size="sm" variant="secondary" :href="route('finance.categories.create')">Criar do meu jeito</x-ui.button>
                                @break
                            @case('transaction')
                                @if ($steps['account'])
                                    <x-ui.button size="sm" :href="route('finance.transactions.create')">Nova transação</x-ui.button>
                                @else
                                    <p class="text-xs text-ink-2">Disponível depois de cadastrar uma conta.</p>
                                @endif
                                @break
                        @endswitch
                    </div>
                @endunless
            </li>
        @endforeach
    </ol>
</section>
