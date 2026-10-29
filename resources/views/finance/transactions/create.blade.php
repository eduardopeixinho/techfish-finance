<x-finance-layout>
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-ui.page-header title="Nova transação" kicker="Transações" />

        <x-ui.card>
            @if ($accounts->isEmpty())
                <p class="text-ink-2 mb-4">Para lançar uma transação, crie antes uma conta.</p>
                <x-ui.button :href="route('finance.accounts.create')">Criar conta</x-ui.button>
            @else
                <form action="{{ route('finance.transactions.store') }}" method="POST">
                    @csrf
                    @include('finance.transactions._form')

                    <div class="flex flex-wrap gap-3 pt-6 mt-6 border-t border-line">
                        <x-ui.button type="submit">Salvar transação</x-ui.button>
                        <x-ui.button variant="secondary" :href="route('finance.transactions.index')">Cancelar</x-ui.button>
                    </div>
                </form>
            @endif
        </x-ui.card>
    </div>
</x-finance-layout>
