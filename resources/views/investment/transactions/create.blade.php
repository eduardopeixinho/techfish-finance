<x-finance-layout>
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-ui.page-header title="Nova compra ou venda" kicker="Investimentos" />

        <x-ui.card>
            @if ($investments->isEmpty())
                <p class="text-ink-2 mb-4">Cadastre primeiro o ativo na carteira.</p>
                <x-ui.button :href="route('investment.investments.create')">Novo investimento</x-ui.button>
            @else
                <form action="{{ route('investment.transactions.store') }}" method="POST">
                    @csrf
                    @include('investment.transactions._form')

                    <div class="flex flex-wrap gap-3 pt-6 mt-6 border-t border-line">
                        <x-ui.button type="submit">Salvar</x-ui.button>
                        <x-ui.button variant="secondary" :href="$selectedInvestment ? route('investment.investments.show', $selectedInvestment) : route('investment.transactions.index')">Cancelar</x-ui.button>
                    </div>
                </form>
            @endif
        </x-ui.card>
    </div>
</x-finance-layout>
