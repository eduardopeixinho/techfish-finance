<x-finance-layout>
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-ui.page-header :title="'Editar '.$investment->ticker" kicker="Carteira">
            <x-slot:description>
                Investido {{ $money->format((float) $investment->total_invested) }} · atual {{ $money->format((float) $investment->current_value) }}
            </x-slot:description>
            <x-slot:actions>
                <x-ui.button variant="secondary" :href="route('investment.investments.show', $investment)">Voltar</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.card>
            <form action="{{ route('investment.investments.update', $investment) }}" method="POST">
                @csrf
                @method('PUT')
                @include('investment.investments._form')

                <div class="flex flex-wrap gap-3 pt-6 mt-6 border-t border-line">
                    <x-ui.button type="submit">Salvar alterações</x-ui.button>
                    <x-ui.button variant="secondary" :href="route('investment.investments.show', $investment)">Cancelar</x-ui.button>
                </div>
            </form>

            <div class="flex flex-wrap items-center justify-between gap-3 pt-6 mt-6 border-t border-line">
                <p class="text-sm text-ink-2">O ativo sai da carteira; o histórico de compras, vendas e proventos continua registrado.</p>
                <x-ui.confirm-delete :action="route('investment.investments.destroy', $investment)" label="Excluir investimento" size="md" />
            </div>
        </x-ui.card>
    </div>
</x-finance-layout>
