{{-- Detalhe da permissão. $usersWithAccess = usuários que a recebem por algum perfil (controller). --}}
@php use App\Models\Permission; @endphp

<x-admin-layout>
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-ui.page-header :title="$permission->name" kicker="Admin · Permissões">
            @if ($permission->description)
                <x-slot:description>{{ $permission->description }}</x-slot:description>
            @endif
            <x-slot:actions>
                <x-ui.button variant="accent" :href="route('admin.permissions.edit', $permission)">Editar</x-ui.button>
                <x-ui.button variant="secondary" :href="route('admin.permissions.index')">Permissões</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.flash />

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <x-ui.card>
                <p class="text-sm text-ink-2">Identificador</p>
                <p class="mt-2 font-mono text-sm text-ink">{{ $permission->slug }}</p>
            </x-ui.card>
            <x-ui.card>
                <p class="text-sm text-ink-2">Módulo · ação</p>
                <p class="mt-2 text-ink">
                    {{ Permission::MODULES[$permission->module] ?? $permission->module }}
                    <span class="text-ink-2">· {{ Permission::ACTIONS[$permission->action] ?? $permission->action }}</span>
                </p>
            </x-ui.card>
            <x-ui.card>
                <p class="text-sm text-ink-2">Status</p>
                <p class="mt-2">@if ($permission->is_active) <x-ui.badge tone="income">Ativa</x-ui.badge> @else <x-ui.badge tone="warning">Inativa</x-ui.badge> @endif</p>
            </x-ui.card>
            <x-ui.card>
                <p class="text-sm text-ink-2">Usuários com acesso</p>
                <p class="mt-1 font-serif text-2xl text-ink">{{ $usersWithAccess->count() }}</p>
            </x-ui.card>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <x-ui.card title="Perfis com esta permissão">
                @forelse ($permission->roles as $role)
                    <div class="flex items-center justify-between gap-3 py-2 border-b border-line last:border-0">
                        <span class="inline-flex items-center gap-2 text-ink">
                            <span class="w-2.5 h-2.5 rounded-full" style="background-color: {{ $role->color }}" aria-hidden="true"></span>
                            {{ $role->name }}
                            <span class="text-xs text-ink-2">{{ $role->users_count }} {{ $role->users_count === 1 ? 'usuário' : 'usuários' }}</span>
                        </span>
                        <x-ui.button variant="secondary" size="sm" :href="route('admin.roles.show', $role)">Ver</x-ui.button>
                    </div>
                @empty
                    <p class="text-ink-2">Não está em nenhum perfil (pode ser excluída).</p>
                @endforelse
            </x-ui.card>

            <x-ui.card title="Usuários com acesso">
                @forelse ($usersWithAccess as $user)
                    <div class="py-2 border-b border-line last:border-0">
                        <a href="{{ route('admin.user-roles.show', $user) }}" class="font-medium text-ink hover:text-finance-base">{{ $user->name }}</a>
                        <p class="text-xs text-ink-2">{{ $user->email }} · via {{ $user->roles->whereIn('id', $permission->roles->pluck('id'))->pluck('name')->join(', ') }}</p>
                    </div>
                @empty
                    <p class="text-ink-2">Nenhum usuário recebe esta permissão.</p>
                @endforelse
            </x-ui.card>
        </div>
    </div>
</x-admin-layout>
