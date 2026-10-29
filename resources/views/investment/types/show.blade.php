{{--
    Detalhe do tipo (admin). Isolamento: `investments_count` é só o total agregado de
    todos os usuários; a lista mostra apenas os ativos do PRÓPRIO usuário ($myInvestments).
--}}
<x-finance-layout>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-ui.page-header :title="$type->name" :kicker="'Tipos de investimento · '.$type->code">
            @if ($type->description)
                <x-slot:description>{{ $type->description }}</x-slot:description>
            @endif
            <x-slot:actions>
                <x-ui.button variant="accent" :href="route('investment.types.edit', $type)">Editar</x-ui.button>
                <x-ui.button variant="secondary" :href="route('investment.types.index')">Tipos</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.flash />

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <x-ui.card>
                <p class="text-sm text-ink-2">Cor</p>
                <p class="mt-2 flex items-center gap-2 font-mono text-ink">
                    <span class="w-5 h-5 rounded-sm ring-1 ring-ink/10" style="background-color: {{ $type->color }}" aria-hidden="true"></span>
                    {{ $type->color }}
                </p>
            </x-ui.card>
            <x-ui.card>
                <p class="text-sm text-ink-2">Proventos</p>
                <p class="mt-2">
                    @if ($type->has_dividends)
                        <x-ui.badge tone="income">Paga proventos</x-ui.badge>
                    @else
                        <x-ui.badge>Não paga</x-ui.badge>
                    @endif
                </p>
            </x-ui.card>
            <x-ui.card>
                <p class="text-sm text-ink-2">Ativos deste tipo</p>
                <p class="mt-1 font-serif text-2xl text-ink">{{ $type->investments_count }}</p>
                <p class="text-xs text-ink-2">somando todos os usuários</p>
            </x-ui.card>
        </div>

        <x-ui.card class="!p-0 overflow-hidden">
            <h2 class="font-serif text-xl tracking-tight text-finance-base px-6 pt-6 pb-4">Seus ativos deste tipo</h2>
            @if ($myInvestments->isEmpty())
                <div class="px-6 pb-8">
                    <p class="text-ink-2 mb-4">Você não tem ativos deste tipo.</p>
                    <x-ui.button variant="secondary" :href="route('investment.investments.create')">Novo investimento</x-ui.button>
                </div>
            @else
                <ul class="divide-y divide-line border-t border-line">
                    @foreach ($myInvestments as $investment)
                        <li class="flex items-center justify-between gap-3 px-6 py-3 text-sm">
                            <div>
                                <a href="{{ route('investment.investments.show', $investment) }}" class="font-mono font-medium text-ink hover:text-finance-base">{{ $investment->ticker }}</a>
                                <span class="text-ink-2">· {{ $investment->name }}</span>
                                @unless ($investment->is_active) <x-ui.badge tone="warning" class="ml-1">Encerrado</x-ui.badge> @endunless
                            </div>
                            <span class="text-ink">{{ $money->format((float) $investment->current_value) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>
    </div>
</x-finance-layout>
