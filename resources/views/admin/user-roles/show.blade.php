{{--
    Perfis e permissões efetivas de um usuário.
    Isolamento: nenhum dado financeiro do usuário é exibido aqui, só acesso.
--}}
@php
    use App\Models\Permission;

    $effectivePermissions = $user->roles->flatMap->permissions->unique('id')->sortBy('module')->groupBy('module');
@endphp

<x-admin-layout>
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-ui.page-header :title="$user->name" kicker="Admin · Usuários">
            <x-slot:description>{{ $user->email }} · desde {{ $user->created_at?->format('d/m/Y') }}</x-slot:description>
            <x-slot:actions>
                @can('admin.users.deactivate', $user)
                    @if ($user->isDeactivated())
                        <form method="POST" action="{{ route('admin.user-roles.activate', $user) }}" class="inline">
                            @csrf
                            @method('PATCH')
                            <x-ui.button type="submit" variant="secondary">Reativar</x-ui.button>
                        </form>
                    @else
                        <x-ui.confirm-delete :action="route('admin.user-roles.deactivate', $user)" method="PATCH" size="md"
                                             label="Desativar" confirm-label="Confirmar desativação" />
                    @endif
                @endcan
                <x-ui.button variant="accent" :href="route('admin.user-roles.edit', $user)">Editar perfis</x-ui.button>
                <x-ui.button variant="secondary" :href="route('admin.user-roles.index')">Usuários</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.flash />

        @if ($user->isDeactivated())
            <p class="mb-6 border border-red-200 bg-red-50 text-red-800 text-sm px-4 py-3 rounded-sm" role="status">
                Conta desativada em {{ $user->deactivated_at->format('d/m/Y H:i') }}: não consegue entrar no app. Os dados dela foram mantidos.
            </p>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <x-ui.card :title="'Perfis ('.$user->roles->count().')'">
                @forelse ($user->roles as $role)
                    <div class="flex items-center justify-between gap-3 py-2 border-b border-line last:border-0">
                        <span class="inline-flex items-center gap-2 text-ink">
                            <span class="w-2.5 h-2.5 rounded-full" style="background-color: {{ $role->color }}" aria-hidden="true"></span>
                            {{ $role->name }}
                            @unless ($role->is_active) <x-ui.badge tone="warning">Inativo</x-ui.badge> @endunless
                        </span>
                        <div class="flex items-center gap-2">
                            <x-ui.button variant="secondary" size="sm" :href="route('admin.roles.show', $role)">Ver</x-ui.button>
                            <x-ui.confirm-delete :action="route('admin.user-roles.remove', $user)" label="Remover" confirm-label="Confirmar remoção">
                                <input type="hidden" name="role_id" value="{{ $role->id }}">
                            </x-ui.confirm-delete>
                        </div>
                    </div>
                @empty
                    <p class="text-ink-2 mb-4">Sem perfis atribuídos.</p>
                    <x-ui.button size="sm" :href="route('admin.user-roles.edit', $user)">Atribuir perfis</x-ui.button>
                @endforelse
            </x-ui.card>

            <x-ui.card title="Permissões efetivas">
                @forelse ($effectivePermissions as $module => $modulePermissions)
                    <div class="mb-4 last:mb-0">
                        <p class="text-[10px] font-semibold tracking-[0.2em] uppercase text-finance-base/70 mb-2">{{ Permission::MODULES[$module] ?? ($module ?: 'Geral') }}</p>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($modulePermissions as $permission)
                                <x-ui.badge tone="transfer">{{ $permission->name }}</x-ui.badge>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <p class="text-ink-2">Nenhuma permissão (sem perfis ou perfis vazios).</p>
                @endforelse
            </x-ui.card>
        </div>
    </div>
</x-admin-layout>
