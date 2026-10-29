@php use App\Models\Permission; @endphp

<x-admin-layout>
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-ui.page-header :title="$role->name" kicker="Admin · Perfis">
            @if ($role->description)
                <x-slot:description>{{ $role->description }}</x-slot:description>
            @endif
            <x-slot:actions>
                <x-ui.button variant="accent" :href="route('admin.roles.edit', $role)">Editar</x-ui.button>
                <x-ui.button variant="secondary" :href="route('admin.roles.index')">Perfis</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.flash />

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <x-ui.card>
                <p class="text-sm text-ink-2">Status</p>
                <p class="mt-2">
                    @if ($role->is_active) <x-ui.badge tone="income">Ativo</x-ui.badge> @else <x-ui.badge tone="warning">Inativo</x-ui.badge> @endif
                    @if ($role->is_system) <x-ui.badge class="ml-1">Sistema</x-ui.badge> @endif
                </p>
            </x-ui.card>
            <x-ui.card>
                <p class="text-sm text-ink-2">Identificador</p>
                <p class="mt-2 flex items-center gap-2 font-mono text-sm text-ink">
                    <span class="w-3 h-3 rounded-full ring-1 ring-ink/10" style="background-color: {{ $role->color }}" aria-hidden="true"></span>
                    {{ $role->slug }}
                </p>
            </x-ui.card>
            <x-ui.card>
                <p class="text-sm text-ink-2">Usuários</p>
                <p class="mt-1 font-serif text-2xl text-ink">{{ $role->users->count() }}</p>
            </x-ui.card>
            <x-ui.card>
                <p class="text-sm text-ink-2">Permissões</p>
                <p class="mt-1 font-serif text-2xl text-ink">{{ $role->permissions->count() }}</p>
            </x-ui.card>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <x-ui.card title="Permissões">
                @forelse ($role->permissions->groupBy('module') as $module => $modulePermissions)
                    <div class="mb-4 last:mb-0">
                        <p class="text-[10px] font-semibold tracking-[0.2em] uppercase text-finance-base/70 mb-2">{{ Permission::MODULES[$module] ?? ($module ?: 'Geral') }}</p>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($modulePermissions as $permission)
                                <a href="{{ route('admin.permissions.show', $permission) }}"><x-ui.badge tone="transfer">{{ $permission->name }}</x-ui.badge></a>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <p class="text-ink-2">Nenhuma permissão atribuída.</p>
                @endforelse
            </x-ui.card>

            <x-ui.card title="Usuários com este perfil">
                @forelse ($role->users as $user)
                    <div class="flex items-center justify-between gap-3 py-2 border-b border-line last:border-0">
                        <div class="min-w-0">
                            <p class="font-medium text-ink truncate">{{ $user->name }}</p>
                            <p class="text-xs text-ink-2 truncate">{{ $user->email }}</p>
                        </div>
                        <x-ui.button variant="secondary" size="sm" :href="route('admin.user-roles.show', $user)">Ver</x-ui.button>
                    </div>
                @empty
                    <p class="text-ink-2">Nenhum usuário com este perfil.</p>
                @endforelse
            </x-ui.card>
        </div>

        <p class="mt-6 text-xs text-ink-2 font-mono">Criado em {{ $role->created_at?->format('d/m/Y H:i') }} · atualizado em {{ $role->updated_at?->format('d/m/Y H:i') }}</p>
    </div>
</x-admin-layout>
