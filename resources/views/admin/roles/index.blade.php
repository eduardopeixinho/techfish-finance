{{-- Perfis (roles). Perfis do sistema e perfis com usuários não podem ser excluídos (regra no controller). --}}
<x-admin-layout>
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-ui.page-header title="Perfis" kicker="Admin">
            <x-slot:description>Agrupam permissões e são atribuídos aos usuários.</x-slot:description>
            <x-slot:actions>
                <x-ui.button :href="route('admin.roles.create')">Novo perfil</x-ui.button>
                <x-ui.button variant="secondary" :href="route('admin.permissions.index')">Permissões</x-ui.button>
                <x-ui.button variant="secondary" :href="route('admin.user-roles.index')">Usuários</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.flash />

        <x-ui.card class="!p-0 overflow-hidden">
            @if ($roles->isEmpty())
                <div class="px-6 py-12 text-center">
                    <p class="text-ink-2 mb-4">Nenhum perfil cadastrado.</p>
                    <x-ui.button :href="route('admin.roles.create')">Novo perfil</x-ui.button>
                </div>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($roles as $role)
                        @php $usersCount = $role->users->count(); @endphp
                        <li class="flex flex-wrap items-center justify-between gap-3 px-6 py-4 {{ $role->is_active ? '' : 'opacity-70' }}">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="w-3 h-3 rounded-full shrink-0 ring-1 ring-ink/10" style="background-color: {{ $role->color }}" aria-hidden="true"></span>
                                <div class="min-w-0">
                                    <a href="{{ route('admin.roles.show', $role) }}" class="font-medium text-ink hover:text-finance-base">{{ $role->name }}</a>
                                    @if ($role->is_system) <x-ui.badge class="ml-1">Sistema</x-ui.badge> @endif
                                    @unless ($role->is_active) <x-ui.badge tone="warning" class="ml-1">Inativo</x-ui.badge> @endunless
                                    @if ($role->description)
                                        <p class="text-sm text-ink-2 truncate">{{ $role->description }}</p>
                                    @endif
                                    <p class="text-xs text-ink-2/80 font-mono">
                                        {{ $role->permissions->count() }} {{ $role->permissions->count() === 1 ? 'permissão' : 'permissões' }}
                                        · {{ $usersCount }} {{ $usersCount === 1 ? 'usuário' : 'usuários' }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <x-ui.button variant="accent" size="sm" :href="route('admin.roles.edit', $role)">Editar</x-ui.button>
                                <form method="POST" action="{{ route('admin.roles.toggle', $role) }}">
                                    @csrf
                                    @method('PATCH')
                                    <x-ui.button type="submit" variant="secondary" size="sm">{{ $role->is_active ? 'Desativar' : 'Ativar' }}</x-ui.button>
                                </form>
                                @if (! $role->is_system && $usersCount === 0)
                                    <x-ui.confirm-delete :action="route('admin.roles.destroy', $role)" />
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>

                @if ($roles->hasPages())
                    <div class="px-6 py-3 border-t border-line">{{ $roles->links() }}</div>
                @endif
            @endif
        </x-ui.card>
    </div>
</x-admin-layout>
