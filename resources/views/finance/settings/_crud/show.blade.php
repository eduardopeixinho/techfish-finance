{{--
    Detalhe genérico dos catálogos de Configurações (admin).
    Espera $config (ver _crud/index) e $item.
--}}
<x-finance-layout>
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-ui.page-header :title="$item->name" :kicker="$config['title']">
            @if ($item->description)
                <x-slot:description>{{ $item->description }}</x-slot:description>
            @endif
            <x-slot:actions>
                <x-ui.button variant="accent" :href="route($config['route'].'.edit', $item)">Editar</x-ui.button>
                <x-ui.button variant="secondary" :href="route($config['route'].'.index')">{{ $config['title'] }}</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.flash />

        <x-ui.card>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                @if ($config['activeField'])
                    <div>
                        <dt class="text-ink-2">Status</dt>
                        <dd class="mt-1">
                            @if ($item->{$config['activeField']})
                                <x-ui.badge tone="income">Ativo</x-ui.badge>
                            @else
                                <x-ui.badge tone="warning">Inativo</x-ui.badge>
                            @endif
                        </dd>
                    </div>
                @endif
                <div>
                    <dt class="text-ink-2">Código</dt>
                    <dd class="mt-1 font-mono text-ink">#{{ $item->id }}</dd>
                </div>
                <div>
                    <dt class="text-ink-2">Criado em</dt>
                    <dd class="mt-1 font-mono text-ink">{{ $item->created_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-ink-2">Atualizado em</dt>
                    <dd class="mt-1 font-mono text-ink">{{ $item->updated_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                </div>
            </dl>

            <div class="flex justify-end pt-6 mt-6 border-t border-line">
                <x-ui.confirm-delete :action="route($config['route'].'.destroy', $item)" size="md" />
            </div>
        </x-ui.card>
    </div>
</x-finance-layout>
