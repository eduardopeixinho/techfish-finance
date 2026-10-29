{{-- Permissões. Só podem ser excluídas quando não estão em nenhum perfil (regra no controller). --}}
@php use App\Models\Permission; @endphp

<x-admin-layout>
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-ui.page-header title="Permissões" kicker="Admin">
            <x-slot:actions>
                <x-ui.button :href="route('admin.permissions.create')">Nova permissão</x-ui.button>
                <x-ui.button variant="secondary" :href="route('admin.roles.index')">Perfis</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.flash />

        <x-ui.card class="!p-0 overflow-hidden">
            @if ($permissions->isEmpty())
                <div class="px-6 py-12 text-center">
                    <p class="text-ink-2 mb-4">Nenhuma permissão cadastrada.</p>
                    <x-ui.button :href="route('admin.permissions.create')">Nova permissão</x-ui.button>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="border-b border-line bg-paper/60 text-left text-xs uppercase tracking-wider text-ink-2">
                            <tr>
                                <th class="px-6 py-3 font-medium">Permissão</th>
                                <th class="px-6 py-3 font-medium">Módulo · ação</th>
                                <th class="px-6 py-3 font-medium text-right">Perfis</th>
                                <th class="px-6 py-3"><span class="sr-only">Ações</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($permissions as $permission)
                                @php $rolesCount = $permission->roles->count(); @endphp
                                <tr class="hover:bg-white/60 {{ $permission->is_active ? '' : 'opacity-70' }}">
                                    <td class="px-6 py-3">
                                        <a href="{{ route('admin.permissions.show', $permission) }}" class="font-medium text-ink hover:text-finance-base">{{ $permission->name }}</a>
                                        @unless ($permission->is_active) <x-ui.badge tone="warning" class="ml-1">Inativa</x-ui.badge> @endunless
                                        <span class="block font-mono text-xs text-ink-2">{{ $permission->slug }}</span>
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-ink">
                                        {{ Permission::MODULES[$permission->module] ?? ($permission->module ?: '—') }}
                                        <span class="text-ink-2">· {{ Permission::ACTIONS[$permission->action] ?? ($permission->action ?: '—') }}</span>
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-right font-mono">{{ $rolesCount }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-right">
                                        <div class="inline-flex items-center gap-2">
                                            <x-ui.button variant="accent" size="sm" :href="route('admin.permissions.edit', $permission)">Editar</x-ui.button>
                                            <form method="POST" action="{{ route('admin.permissions.toggle', $permission) }}">
                                                @csrf
                                                @method('PATCH')
                                                <x-ui.button type="submit" variant="secondary" size="sm">{{ $permission->is_active ? 'Desativar' : 'Ativar' }}</x-ui.button>
                                            </form>
                                            @if ($rolesCount === 0)
                                                <x-ui.confirm-delete :action="route('admin.permissions.destroy', $permission)" />
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($permissions->hasPages())
                    <div class="px-6 py-3 border-t border-line">{{ $permissions->links() }}</div>
                @endif
            @endif
        </x-ui.card>
    </div>
</x-admin-layout>
