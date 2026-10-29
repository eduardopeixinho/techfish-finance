<x-admin-layout>
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-ui.page-header title="Editar permissão" kicker="Admin · Permissões">
            <x-slot:description><span class="font-mono">{{ $permission->slug }}</span></x-slot:description>
            <x-slot:actions>
                <x-ui.button variant="secondary" :href="route('admin.permissions.show', $permission)">Voltar</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.card>
            <form action="{{ route('admin.permissions.update', $permission) }}" method="POST">
                @csrf
                @method('PUT')
                @include('admin.permissions._form')

                <div class="flex flex-wrap gap-3 pt-6 mt-6 border-t border-line">
                    <x-ui.button type="submit">Salvar alterações</x-ui.button>
                    <x-ui.button variant="secondary" :href="route('admin.permissions.show', $permission)">Cancelar</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-admin-layout>
