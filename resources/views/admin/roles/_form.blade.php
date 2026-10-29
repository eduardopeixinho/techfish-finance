{{--
    Campos compartilhados de criar/editar perfil (role).
    Espera $permissions (ativas) e $role (instância nova no create). "Ativo" só na edição.
--}}
@php
    use App\Models\Permission;

    $role ??= new \App\Models\Role(['color' => '#0B3D3A']);
    $invalid = fn (string $field): string => $errors->has($field) ? 'aria-invalid=true aria-describedby='.$field.'-error' : '';
    $selected = collect(old('permissions', $role->exists ? $role->permissions->pluck('id')->all() : []))->map(fn ($id) => (int) $id);
    $groupedPermissions = $permissions->groupBy('module');
@endphp

<div class="space-y-6">
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <x-ui.field label="Nome" for="name" required class="sm:col-span-2">
            <input type="text" id="name" name="name" value="{{ old('name', $role->name) }}" required maxlength="255"
                   placeholder="Ex.: Gestor financeiro" class="ui-input" {!! $invalid('name') !!}>
            {{-- O slug é derivado do nome; o erro de "nome equivalente" vem nele --}}
            @error('slug') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        </x-ui.field>

        <x-ui.field label="Cor" for="color" required>
            <input type="color" id="color" name="color" required value="{{ old('color', $role->color ?? '#0B3D3A') }}"
                   class="h-10 w-20 rounded-sm border border-line bg-white cursor-pointer">
        </x-ui.field>
    </div>

    <x-ui.field label="Descrição" for="description" hint="Opcional, até 500 caracteres.">
        <textarea id="description" name="description" rows="2" maxlength="500" class="ui-input"
                  placeholder="Responsabilidades deste perfil" {!! $invalid('description') !!}>{{ old('description', $role->description) }}</textarea>
    </x-ui.field>

    @if ($role->exists)
        <label class="flex items-center gap-2 text-sm text-ink">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" class="rounded-sm border-line text-finance-base focus:ring-finance-base"
                   @checked(old('is_active', $role->is_active))>
            Perfil ativo
        </label>
    @endif

    <fieldset x-data>
        <div class="flex flex-wrap items-baseline justify-between gap-2 mb-3">
            <legend class="ui-label !mb-0">Permissões</legend>
            <div class="flex gap-3 text-xs">
                <button type="button" class="text-finance-base underline underline-offset-2"
                        @click="$root.querySelectorAll('input[name=\'permissions[]\']').forEach(i => i.checked = true)">Marcar todas</button>
                <button type="button" class="text-finance-base underline underline-offset-2"
                        @click="$root.querySelectorAll('input[name=\'permissions[]\']').forEach(i => i.checked = false)">Desmarcar todas</button>
            </div>
        </div>

        @if ($permissions->isEmpty())
            <p class="text-sm text-ink-2">Nenhuma permissão ativa cadastrada.</p>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach ($groupedPermissions as $module => $modulePermissions)
                    <div class="border border-line rounded-sm bg-white p-4">
                        <p class="text-[10px] font-semibold tracking-[0.2em] uppercase text-finance-base/70 mb-2">
                            {{ Permission::MODULES[$module] ?? ($module ?: 'Geral') }}
                        </p>
                        <div class="space-y-1.5">
                            @foreach ($modulePermissions as $permission)
                                <label class="flex items-start gap-2 text-sm text-ink">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                                           class="mt-0.5 rounded-sm border-line text-finance-base focus:ring-finance-base"
                                           @checked($selected->contains($permission->id))>
                                    <span>
                                        {{ $permission->name }}
                                        <span class="block font-mono text-xs text-ink-2">{{ $permission->slug }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
        @error('permissions') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
    </fieldset>
</div>
