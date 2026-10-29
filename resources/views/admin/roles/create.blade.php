<x-admin-layout>
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-ui.page-header title="Novo perfil" kicker="Admin · Perfis" />

        <x-ui.card>
            <form action="{{ route('admin.roles.store') }}" method="POST">
                @csrf
                @include('admin.roles._form')

                <div class="flex flex-wrap gap-3 pt-6 mt-6 border-t border-line">
                    <x-ui.button type="submit">Criar perfil</x-ui.button>
                    <x-ui.button variant="secondary" :href="route('admin.roles.index')">Cancelar</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-admin-layout>
