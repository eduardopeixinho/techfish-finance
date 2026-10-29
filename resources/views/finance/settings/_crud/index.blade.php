{{--
    Listagem genérica dos catálogos de Configurações (admin).
    Espera:
      $config = [
        'title' => 'Formas de pagamento', 'singular' => 'forma de pagamento', 'new' => 'Nova forma',
        'route' => 'settings.payment-methods', 'description' => '...', 'activeField' => 'active'|null,
      ]
      $items  = coleção ou paginator de modelos (name, description, [active])
--}}
<x-finance-layout>
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-ui.page-header :title="$config['title']" kicker="Configurações">
            <x-slot:description>{{ $config['description'] }}</x-slot:description>
            <x-slot:actions>
                <x-ui.button :href="route($config['route'].'.create')">{{ $config['new'] }}</x-ui.button>
                <x-ui.button variant="secondary" :href="route('settings.index')">Configurações</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.flash />

        <x-ui.card class="!p-0 overflow-hidden">
            @if ($items->isEmpty())
                <div class="px-6 py-12 text-center">
                    <p class="text-ink-2 mb-4">Nenhum item cadastrado.</p>
                    <x-ui.button :href="route($config['route'].'.create')">{{ $config['new'] }}</x-ui.button>
                </div>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($items as $item)
                        <li class="flex flex-wrap items-center justify-between gap-3 px-6 py-4">
                            <div class="min-w-0">
                                <a href="{{ route($config['route'].'.show', $item) }}" class="font-medium text-ink hover:text-finance-base">{{ $item->name }}</a>
                                @if ($config['activeField'] && ! $item->{$config['activeField']})
                                    <x-ui.badge tone="warning" class="ml-1">Inativo</x-ui.badge>
                                @endif
                                @if ($item->description)
                                    <p class="text-sm text-ink-2 truncate">{{ $item->description }}</p>
                                @endif
                            </div>
                            <div class="flex items-center gap-2">
                                <x-ui.button variant="accent" size="sm" :href="route($config['route'].'.edit', $item)">Editar</x-ui.button>
                                <x-ui.confirm-delete :action="route($config['route'].'.destroy', $item)" />
                            </div>
                        </li>
                    @endforeach
                </ul>

                @if (method_exists($items, 'hasPages') && $items->hasPages())
                    <div class="px-6 py-3 border-t border-line">{{ $items->links() }}</div>
                @endif
            @endif
        </x-ui.card>
    </div>
</x-finance-layout>
