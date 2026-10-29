{{-- Usuários e seus perfis. Os dados financeiros de cada usuário NÃO aparecem aqui (isolamento). --}}
<x-admin-layout>
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-ui.page-header title="Usuários" kicker="Admin">
            <x-slot:description>Quem acessa o app e com quais perfis. Novos usuários entram pelo Google; desativados não entram mais, mas os dados ficam guardados.</x-slot:description>
            <x-slot:actions>
                <x-ui.button variant="secondary" :href="route('admin.roles.index')">Perfis</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.flash />

        <x-ui.card class="!p-0 overflow-hidden">
            @if ($users->isEmpty())
                <p class="px-6 py-12 text-center text-ink-2">Nenhum usuário.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="border-b border-line bg-paper/60 text-left text-xs uppercase tracking-wider text-ink-2">
                            <tr>
                                <th class="px-6 py-3 font-medium">Usuário</th>
                                <th class="px-6 py-3 font-medium">Perfis</th>
                                <th class="px-6 py-3 font-medium">Desde</th>
                                <th class="px-6 py-3"><span class="sr-only">Ações</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($users as $user)
                                <tr @class(['hover:bg-white/60', 'opacity-60' => $user->isDeactivated()])>
                                    <td class="px-6 py-3">
                                        <a href="{{ route('admin.user-roles.show', $user) }}" class="font-medium text-ink hover:text-finance-base">{{ $user->name }}</a>
                                        @if ($user->isDeactivated())
                                            <x-ui.badge tone="expense" class="ml-1">Desativado</x-ui.badge>
                                        @endif
                                        <span class="block text-xs text-ink-2">{{ $user->email }}</span>
                                    </td>
                                    <td class="px-6 py-3">
                                        <div class="flex flex-wrap gap-1.5">
                                            @forelse ($user->roles as $role)
                                                <x-ui.badge>
                                                    <span class="w-2 h-2 rounded-full" style="background-color: {{ $role->color }}" aria-hidden="true"></span>
                                                    {{ $role->name }}
                                                </x-ui.badge>
                                            @empty
                                                <span class="text-ink-2">Sem perfil</span>
                                            @endforelse
                                        </div>
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap font-mono text-ink-2">{{ $user->created_at?->format('d/m/Y') }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap text-right">
                                        <div class="inline-flex items-center gap-2">
                                            <x-ui.button variant="accent" size="sm" :href="route('admin.user-roles.edit', $user)">Perfis</x-ui.button>
                                        @can('admin.users.deactivate', $user)
                                            @if ($user->isDeactivated())
                                                <form method="POST" action="{{ route('admin.user-roles.activate', $user) }}" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <x-ui.button type="submit" variant="secondary" size="sm">Reativar</x-ui.button>
                                                </form>
                                            @else
                                                <x-ui.confirm-delete :action="route('admin.user-roles.deactivate', $user)" method="PATCH"
                                                                     label="Desativar" confirm-label="Confirmar desativação" />
                                            @endif
                                        @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($users->hasPages())
                    <div class="px-6 py-3 border-t border-line">{{ $users->links() }}</div>
                @endif
            @endif
        </x-ui.card>
    </div>
</x-admin-layout>
