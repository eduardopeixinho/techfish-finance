{{--
    Campos compartilhados de criar/editar permissão.
    Espera $modules e $actions (valores já usados no banco) e $permission (nova no create).
    O identificador (slug) é gerado no controller como "modulo.acao".
--}}
@php
    use App\Models\Permission;

    $permission ??= new Permission;
    $invalid = fn (string $field): string => $errors->has($field) ? 'aria-invalid=true aria-describedby='.$field.'-error' : '';
    // Sugestões conhecidas + valores que já existem no banco
    $moduleOptions = collect(Permission::MODULES)->union($modules->mapWithKeys(fn ($value) => [$value => $value]));
    $actionOptions = collect(Permission::ACTIONS)->union($actions->mapWithKeys(fn ($value) => [$value => $value]));
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
    <x-ui.field label="Nome" for="name" required class="sm:col-span-2" hint="Nome legível, ex.: Gerenciar transações.">
        <input type="text" id="name" name="name" value="{{ old('name', $permission->name) }}" required maxlength="255"
               class="ui-input" {!! $invalid('name') !!}>
    </x-ui.field>

    <x-ui.field label="Módulo" for="module" required>
        <select id="module" name="module" required class="ui-input" {!! $invalid('module') !!}>
            <option value="">Selecione</option>
            @foreach ($moduleOptions as $value => $label)
                <option value="{{ $value }}" @selected(old('module', $permission->module) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </x-ui.field>

    <x-ui.field label="Ação" for="action" required>
        <select id="action" name="action" required class="ui-input" {!! $invalid('action') !!}>
            <option value="">Selecione</option>
            @foreach ($actionOptions as $value => $label)
                <option value="{{ $value }}" @selected(old('action', $permission->action) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        {{-- O slug é derivado de módulo + ação; a combinação repetida gera erro nele --}}
        @error('slug') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
    </x-ui.field>

    <x-ui.field label="Descrição" for="description" hint="Opcional, até 500 caracteres." class="sm:col-span-2">
        <textarea id="description" name="description" rows="2" maxlength="500" class="ui-input" {!! $invalid('description') !!}>{{ old('description', $permission->description) }}</textarea>
    </x-ui.field>

    @if ($permission->exists)
        <label class="sm:col-span-2 flex items-center gap-2 text-sm text-ink">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" class="rounded-sm border-line text-finance-base focus:ring-finance-base"
                   @checked(old('is_active', $permission->is_active))>
            Permissão ativa
        </label>
    @endif
</div>
