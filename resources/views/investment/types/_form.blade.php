{{--
    Campos compartilhados de criar/editar tipo de investimento (catálogo global, admin).
    Espera $type (instância nova no create).
--}}
@php
    $type ??= new \App\Models\Investment\InvestmentType(['color' => '#0B3D3A', 'has_dividends' => true]);
    $invalid = fn (string $field): string => $errors->has($field) ? 'aria-invalid=true aria-describedby='.$field.'-error' : '';
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
    <x-ui.field label="Nome" for="name" required>
        <input type="text" id="name" name="name" value="{{ old('name', $type->name) }}" required maxlength="255"
               placeholder="Ex.: FII, Ação, BDR" class="ui-input" {!! $invalid('name') !!}>
    </x-ui.field>

    <x-ui.field label="Código" for="code" required hint="Identificador curto e único. Salvo em minúsculas.">
        <input type="text" id="code" name="code" value="{{ old('code', $type->code) }}" required maxlength="50"
               placeholder="Ex.: fii" class="ui-input font-mono" {!! $invalid('code') !!}>
    </x-ui.field>

    <x-ui.field label="Cor" for="color" required>
        <input type="color" id="color" name="color" required value="{{ old('color', $type->color ?? '#0B3D3A') }}"
               class="h-10 w-20 rounded-sm border border-line bg-white cursor-pointer">
    </x-ui.field>

    <div class="flex items-end pb-2">
        <label class="flex items-center gap-2 text-sm text-ink">
            <input type="hidden" name="has_dividends" value="0">
            <input type="checkbox" name="has_dividends" value="1" class="rounded-sm border-line text-finance-base focus:ring-finance-base"
                   @checked(old('has_dividends', $type->has_dividends))>
            Paga proventos (libera "Registrar provento" para ativos deste tipo)
        </label>
    </div>

    <x-ui.field label="Descrição" for="description" hint="Opcional." class="sm:col-span-2">
        <textarea id="description" name="description" rows="3" class="ui-input" {!! $invalid('description') !!}>{{ old('description', $type->description) }}</textarea>
    </x-ui.field>
</div>
