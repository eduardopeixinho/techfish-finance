<x-finance-layout>
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-ui.page-header title="Editar operação" kicker="Investimentos">
            <x-slot:description>{{ $transaction->investment?->ticker }} · {{ $transaction->transaction_date->format('d/m/Y') }}</x-slot:description>
            <x-slot:actions>
                <x-ui.button variant="secondary" :href="route('investment.transactions.show', $transaction)">Voltar</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.card>
            <form action="{{ route('investment.transactions.update', $transaction) }}" method="POST">
                @csrf
                @method('PUT')
                @include('investment.transactions._form')

                <div class="flex flex-wrap gap-3 pt-6 mt-6 border-t border-line">
                    <x-ui.button type="submit">Salvar alterações</x-ui.button>
                    <x-ui.button variant="secondary" :href="route('investment.transactions.show', $transaction)">Cancelar</x-ui.button>
                </div>
            </form>

            <div class="flex justify-end pt-6 mt-6 border-t border-line">
                <x-ui.confirm-delete :action="route('investment.transactions.destroy', $transaction)" label="Excluir operação" size="md" />
            </div>
        </x-ui.card>
    </div>
</x-finance-layout>
