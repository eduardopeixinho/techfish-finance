@php $investmentsCount = $type->investments()->count(); @endphp

<x-finance-layout>
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-ui.page-header title="Editar tipo" kicker="Investimentos · Admin">
            <x-slot:description>{{ $type->name }}</x-slot:description>
            <x-slot:actions>
                <x-ui.button variant="secondary" :href="route('investment.types.show', $type)">Voltar</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.card>
            <form action="{{ route('investment.types.update', $type) }}" method="POST">
                @csrf
                @method('PUT')
                @include('investment.types._form')

                <div class="flex flex-wrap gap-3 pt-6 mt-6 border-t border-line">
                    <x-ui.button type="submit">Salvar alterações</x-ui.button>
                    <x-ui.button variant="secondary" :href="route('investment.types.show', $type)">Cancelar</x-ui.button>
                </div>
            </form>

            <div class="flex flex-wrap items-center justify-between gap-3 pt-6 mt-6 border-t border-line">
                @if ($investmentsCount === 0)
                    <p class="text-sm text-ink-2">Nenhum ativo usa este tipo: pode ser excluído.</p>
                    <x-ui.confirm-delete :action="route('investment.types.destroy', $type)" label="Excluir tipo" size="md" />
                @else
                    <p class="text-sm text-ink-2">{{ $investmentsCount }} {{ $investmentsCount === 1 ? 'ativo usa' : 'ativos usam' }} este tipo (somando todos os usuários); não pode ser excluído.</p>
                @endif
            </div>
        </x-ui.card>
    </div>
</x-finance-layout>
