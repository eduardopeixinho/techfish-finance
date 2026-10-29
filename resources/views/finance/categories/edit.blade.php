@php $transactionsCount = $category->transactions()->count(); @endphp

<x-finance-layout>
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-ui.page-header title="Editar categoria" kicker="Categorias">
            <x-slot:description>{{ $category->name }}</x-slot:description>
            <x-slot:actions>
                <x-ui.button variant="secondary" :href="route('finance.categories.show', $category)">Voltar</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.card>
            <form action="{{ route('finance.categories.update', $category) }}" method="POST">
                @csrf
                @method('PUT')
                @include('finance.categories._form')

                <div class="flex flex-wrap gap-3 pt-6 mt-6 border-t border-line">
                    <x-ui.button type="submit">Salvar alterações</x-ui.button>
                    <x-ui.button variant="secondary" :href="route('finance.categories.show', $category)">Cancelar</x-ui.button>
                </div>
            </form>

            {{-- Exclusão fora do form de edição (forms não podem ser aninhados) --}}
            <div class="flex flex-wrap items-center justify-between gap-3 pt-6 mt-6 border-t border-line">
                @if ($transactionsCount === 0)
                    <p class="text-sm text-ink-2">Sem transações vinculadas: pode ser excluída.</p>
                    <x-ui.confirm-delete :action="route('finance.categories.destroy', $category)" label="Excluir categoria" size="md" />
                @else
                    <p class="text-sm text-ink-2">
                        Tem {{ $transactionsCount }} {{ $transactionsCount === 1 ? 'transação vinculada' : 'transações vinculadas' }} e não pode ser excluída.
                        Desmarque "ativa" para escondê-la dos lançamentos.
                    </p>
                @endif
            </div>
        </x-ui.card>
    </div>
</x-finance-layout>
