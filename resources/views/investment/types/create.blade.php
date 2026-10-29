<x-finance-layout>
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-ui.page-header title="Novo tipo de investimento" kicker="Investimentos · Admin" />

        <x-ui.card>
            <form action="{{ route('investment.types.store') }}" method="POST">
                @csrf
                @include('investment.types._form')

                <div class="flex flex-wrap gap-3 pt-6 mt-6 border-t border-line">
                    <x-ui.button type="submit">Salvar tipo</x-ui.button>
                    <x-ui.button variant="secondary" :href="route('investment.types.index')">Cancelar</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-finance-layout>
