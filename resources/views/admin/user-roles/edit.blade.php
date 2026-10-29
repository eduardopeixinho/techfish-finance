{{-- Atribuir perfis a um usuário. Desmarcar todos remove os perfis (o controller usa `roles ?? []`). --}}
@php $assigned = collect(old('roles', $user->roles->pluck('id')->all()))->map(fn ($id) => (int) $id); @endphp

<x-admin-layout>
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-ui.page-header :title="'Perfis de '.$user->name" kicker="Admin · Usuários">
            <x-slot:description>{{ $user->email }}</x-slot:description>
            <x-slot:actions>
                <x-ui.button variant="secondary" :href="route('admin.user-roles.show', $user)">Voltar</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.card>
            <form action="{{ route('admin.user-roles.update', $user) }}" method="POST">
                @csrf
                @method('PATCH')

                <fieldset>
                    <legend class="ui-label">Perfis ativos</legend>
                    @if ($roles->isEmpty())
                        <p class="text-sm text-ink-2">Nenhum perfil ativo cadastrado.</p>
                    @else
                        <div class="space-y-2">
                            @foreach ($roles as $role)
                                <label class="flex items-start gap-3 p-3 border border-line rounded-sm bg-white cursor-pointer hover:border-finance-base/40 has-[:checked]:border-finance-base has-[:checked]:bg-finance-base/5">
                                    <input type="checkbox" name="roles[]" value="{{ $role->id }}" class="mt-0.5 rounded-sm border-line text-finance-base focus:ring-finance-base"
                                           @checked($assigned->contains($role->id))>
                                    <span class="min-w-0">
                                        <span class="inline-flex items-center gap-2 font-medium text-ink">
                                            <span class="w-2.5 h-2.5 rounded-full" style="background-color: {{ $role->color }}" aria-hidden="true"></span>
                                            {{ $role->name }}
                                            @if ($role->is_system) <x-ui.badge>Sistema</x-ui.badge> @endif
                                        </span>
                                        @if ($role->description)
                                            <span class="block text-sm text-ink-2">{{ $role->description }}</span>
                                        @endif
                                        <span class="block text-xs text-ink-2 font-mono">{{ $role->permissions->count() }} {{ $role->permissions->count() === 1 ? 'permissão' : 'permissões' }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                    @error('roles') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </fieldset>

                @if (auth()->id() === $user->id)
                    <p class="mt-4 text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-sm px-4 py-3">
                        Você está editando os próprios perfis: remover "admin" tira o seu acesso a esta área.
                    </p>
                @endif

                <div class="flex flex-wrap gap-3 pt-6 mt-6 border-t border-line">
                    <x-ui.button type="submit">Salvar perfis</x-ui.button>
                    <x-ui.button variant="secondary" :href="route('admin.user-roles.show', $user)">Cancelar</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-admin-layout>
