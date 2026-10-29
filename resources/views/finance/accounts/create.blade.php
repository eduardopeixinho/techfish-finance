<x-finance-layout>
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-ui.page-header title="Nova conta" kicker="Contas" />

        <x-ui.card>
            <form action="{{ route('finance.accounts.store') }}" method="POST">
                @csrf
                @include('finance.accounts._form')

                <div class="flex flex-wrap gap-3 pt-6 mt-6 border-t border-line">
                    <x-ui.button type="submit">Criar conta</x-ui.button>
                    <x-ui.button variant="secondary" :href="route('finance.accounts.index')">Cancelar</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-finance-layout>
