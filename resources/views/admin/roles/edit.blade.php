<x-admin-layout>
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-ui.page-header title="Editar perfil" kicker="Admin · Perfis">
            <x-slot:description>{{ $role->name }}@if ($role->is_system) · perfil do sistema @endif</x-slot:description>
            <x-slot:actions>
                <x-ui.button variant="secondary" :href="route('admin.roles.show', $role)">Voltar</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.card>
            <form action="{{ route('admin.roles.update', $role) }}" method="POST">
                @csrf
                @method('PUT')
                @include('admin.roles._form')

                <div class="flex flex-wrap gap-3 pt-6 mt-6 border-t border-line">
                    <x-ui.button type="submit">Salvar alterações</x-ui.button>
                    <x-ui.button variant="secondary" :href="route('admin.roles.show', $role)">Cancelar</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-admin-layout>
