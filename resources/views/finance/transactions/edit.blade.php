<x-finance-layout>
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-ui.page-header title="Editar transação" kicker="Transações">
            <x-slot:description>{{ $transaction->description }}</x-slot:description>
            <x-slot:actions>
                <x-ui.button variant="secondary" :href="route('finance.transactions.show', $transaction)">Voltar</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.card>
            <form action="{{ route('finance.transactions.update', $transaction) }}" method="POST">
                @csrf
                @method('PUT')
                @include('finance.transactions._form')

                <div class="flex flex-wrap gap-3 pt-6 mt-6 border-t border-line">
                    <x-ui.button type="submit">Salvar alterações</x-ui.button>
                    <x-ui.button variant="secondary" :href="route('finance.transactions.show', $transaction)">Cancelar</x-ui.button>
                </div>
            </form>

            <div class="flex justify-end pt-6 mt-6 border-t border-line">
                <x-ui.confirm-delete :action="route('finance.transactions.destroy', $transaction)" label="Excluir transação" size="md" />
            </div>
        </x-ui.card>
    </div>
</x-finance-layout>
