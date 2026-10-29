<x-finance-layout>
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-ui.page-header title="Editar provento" kicker="Investimentos">
            <x-slot:description>{{ $dividend->investment?->ticker }} · {{ $dividend->type_name }} · pago em {{ $dividend->payment_date->format('d/m/Y') }}</x-slot:description>
            <x-slot:actions>
                <x-ui.button variant="secondary" :href="route('investment.dividends.show', $dividend)">Voltar</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.card>
            <form action="{{ route('investment.dividends.update', $dividend) }}" method="POST">
                @csrf
                @method('PUT')
                @include('investment.dividends._form')

                <div class="flex flex-wrap gap-3 pt-6 mt-6 border-t border-line">
                    <x-ui.button type="submit">Salvar alterações</x-ui.button>
                    <x-ui.button variant="secondary" :href="route('investment.dividends.show', $dividend)">Cancelar</x-ui.button>
                </div>
            </form>

            <div class="flex justify-end pt-6 mt-6 border-t border-line">
                <x-ui.confirm-delete :action="route('investment.dividends.destroy', $dividend)" label="Excluir provento" size="md" />
            </div>
        </x-ui.card>
    </div>
</x-finance-layout>
