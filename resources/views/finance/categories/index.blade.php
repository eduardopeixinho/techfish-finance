{{-- Categorias financeiras do usuário, separadas por tipo. Usa componentes <x-ui.*>. --}}
<x-finance-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-ui.page-header title="Categorias" kicker="Finanças">
            <x-slot:description>Organize receitas e despesas. Categorias com transações não podem ser excluídas.</x-slot:description>
            <x-slot:actions>
                <x-ui.button :href="route('finance.categories.create')">Nova categoria</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.flash />

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @foreach ([
                ['title' => 'Receitas', 'tone' => 'income', 'items' => $incomeCategories, 'empty' => 'Nenhuma categoria de receita ainda.'],
                ['title' => 'Despesas', 'tone' => 'expense', 'items' => $expenseCategories, 'empty' => 'Nenhuma categoria de despesa ainda.'],
            ] as $group)
                <x-ui.card :title="$group['title'].' ('.$group['items']->count().')'" :tone="$group['tone']">
                    @if ($group['items']->isEmpty())
                        <div class="py-8 text-center">
                            <p class="text-ink-2 mb-4">{{ $group['empty'] }}</p>
                            <x-ui.button variant="secondary" :href="route('finance.categories.create')">Criar categoria</x-ui.button>
                        </div>
                    @else
                        <ul class="divide-y divide-line">
                            @foreach ($group['items'] as $category)
                                <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <span class="w-3 h-3 rounded-full shrink-0 ring-1 ring-ink/10" style="background-color: {{ $category->color }}" aria-hidden="true"></span>
                                        <div class="min-w-0">
                                            <a href="{{ route('finance.categories.show', $category) }}" class="font-medium text-ink hover:text-finance-base">{{ $category->name }}</a>
                                            @if ($category->description)
                                                <p class="text-sm text-ink-2 truncate">{{ $category->description }}</p>
                                            @endif
                                            <p class="text-xs text-ink-2/80 font-mono">
                                                {{ $category->transactions_count }} {{ $category->transactions_count === 1 ? 'transação' : 'transações' }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <x-ui.button variant="secondary" size="sm" :href="route('finance.categories.show', $category)">Ver</x-ui.button>
                                        <x-ui.button variant="accent" size="sm" :href="route('finance.categories.edit', $category)">Editar</x-ui.button>
                                        @if ($category->transactions_count === 0)
                                            <x-ui.confirm-delete :action="route('finance.categories.destroy', $category)" />
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-ui.card>
            @endforeach
        </div>
    </div>
</x-finance-layout>
