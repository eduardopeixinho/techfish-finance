{{-- Tipos de investimento (catálogo global, admin). `investments_count` soma todos os usuários (só o número). --}}
<x-finance-layout>
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-ui.page-header title="Tipos de investimento" kicker="Investimentos · Admin">
            <x-slot:description>Catálogo compartilhado por todos os usuários. "Paga proventos" libera o registro de proventos para ativos do tipo.</x-slot:description>
            <x-slot:actions>
                <x-ui.button :href="route('investment.types.create')">Novo tipo</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.flash />

        <x-ui.card class="!p-0 overflow-hidden">
            @if ($investmentTypes->isEmpty())
                <div class="px-6 py-12 text-center">
                    <p class="text-ink-2 mb-4">Nenhum tipo cadastrado.</p>
                    <x-ui.button :href="route('investment.types.create')">Novo tipo</x-ui.button>
                </div>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($investmentTypes as $type)
                        <li class="flex flex-wrap items-center justify-between gap-3 px-6 py-4">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="w-3 h-3 rounded-full shrink-0 ring-1 ring-ink/10" style="background-color: {{ $type->color }}" aria-hidden="true"></span>
                                <div class="min-w-0">
                                    <a href="{{ route('investment.types.show', $type) }}" class="font-medium text-ink hover:text-finance-base">{{ $type->name }}</a>
                                    <span class="ml-1 font-mono text-xs text-ink-2">{{ $type->code }}</span>
                                    @if ($type->has_dividends) <x-ui.badge tone="income" class="ml-1">Paga proventos</x-ui.badge> @endif
                                    @if ($type->description)
                                        <p class="text-sm text-ink-2 truncate">{{ $type->description }}</p>
                                    @endif
                                    <p class="text-xs text-ink-2/80 font-mono">{{ $type->investments_count }} {{ $type->investments_count === 1 ? 'ativo' : 'ativos' }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <x-ui.button variant="accent" size="sm" :href="route('investment.types.edit', $type)">Editar</x-ui.button>
                                @if ($type->investments_count === 0)
                                    <x-ui.confirm-delete :action="route('investment.types.destroy', $type)" />
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>

                @if ($investmentTypes->hasPages())
                    <div class="px-6 py-3 border-t border-line">{{ $investmentTypes->links() }}</div>
                @endif
            @endif
        </x-ui.card>
    </div>
</x-finance-layout>
