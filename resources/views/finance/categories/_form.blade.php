{{--
    Campos compartilhados de criar/editar categoria.
    Espera $category (instância nova no create). "Ativa" só aparece na edição.
--}}
@php $category ??= new \App\Models\Finance\FinancialCategory(['color' => '#0B3D3A']); @endphp

<div class="space-y-6">
    <fieldset>
        <legend class="ui-label">Tipo<span class="text-red-700" aria-hidden="true"> *</span></legend>
        <div class="grid grid-cols-2 gap-3">
            @foreach (['income' => ['Receita', 'text-emerald-700'], 'expense' => ['Despesa', 'text-red-700']] as $value => [$label, $color])
                <label class="flex items-center gap-2 p-3 border border-line rounded-sm bg-white cursor-pointer hover:border-finance-base/40 has-[:checked]:border-finance-base has-[:checked]:bg-finance-base/5">
                    <input type="radio" name="type" value="{{ $value }}" required
                           class="text-finance-base focus:ring-finance-base"
                           @checked(old('type', $category->type) === $value)>
                    <span class="font-medium {{ $color }}">{{ $label }}</span>
                </label>
            @endforeach
        </div>
        @error('type') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
    </fieldset>

    <x-ui.field label="Nome" for="name" required>
        <input type="text" id="name" name="name" value="{{ old('name', $category->name) }}" required maxlength="255"
               placeholder="Ex.: Salário, Mercado, Transporte"
               class="ui-input" @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
    </x-ui.field>

    <x-ui.field label="Cor" for="color" hint="Ajuda a identificar a categoria em listas e gráficos.">
        <input type="color" id="color" name="color" value="{{ old('color', $category->color ?? '#0B3D3A') }}"
               class="h-10 w-20 rounded-sm border border-line bg-white cursor-pointer">
    </x-ui.field>

    <x-ui.field label="Descrição" for="description" hint="Opcional.">
        <textarea id="description" name="description" rows="3" class="ui-input"
                  placeholder="Informações adicionais sobre esta categoria"
                  @error('description') aria-invalid="true" aria-describedby="description-error" @enderror>{{ old('description', $category->description) }}</textarea>
    </x-ui.field>

    @if ($category->exists)
        <label class="flex items-center gap-2 text-sm text-ink">
            {{-- hidden antes do checkbox: desmarcado envia 0 (senão o campo some e não dá para desativar) --}}
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" class="rounded-sm border-line text-finance-base focus:ring-finance-base"
                   @checked(old('is_active', $category->is_active))>
            Categoria ativa (aparece ao lançar transações)
        </label>
    @endif
</div>
