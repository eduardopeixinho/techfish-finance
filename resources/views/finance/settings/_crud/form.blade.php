{{--
    Criar/editar genérico dos catálogos de Configurações (admin).
    Espera $config (ver _crud/index) e $item (null no create).
--}}
@php
    $isEdit = $item !== null;
    $invalid = fn (string $field): string => $errors->has($field) ? 'aria-invalid=true aria-describedby='.$field.'-error' : '';
    $activeField = $config['activeField'];
@endphp

<x-finance-layout>
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <x-ui.page-header :title="$isEdit ? 'Editar '.$config['singular'] : ucfirst($config['new'])" :kicker="$config['title']">
            @if ($isEdit)
                <x-slot:description>{{ $item->name }}</x-slot:description>
            @endif
            <x-slot:actions>
                <x-ui.button variant="secondary" :href="$isEdit ? route($config['route'].'.show', $item) : route($config['route'].'.index')">Voltar</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.card>
            <form method="POST" action="{{ $isEdit ? route($config['route'].'.update', $item) : route($config['route'].'.store') }}">
                @csrf
                @if ($isEdit) @method('PUT') @endif

                <div class="space-y-6">
                    <x-ui.field label="Nome" for="name" required>
                        <input type="text" id="name" name="name" value="{{ old('name', $item?->name) }}" required maxlength="255"
                               class="ui-input" {!! $invalid('name') !!}>
                    </x-ui.field>

                    <x-ui.field label="Descrição" for="description" hint="Opcional, até 500 caracteres.">
                        <textarea id="description" name="description" rows="3" maxlength="500" class="ui-input" {!! $invalid('description') !!}>{{ old('description', $item?->description) }}</textarea>
                    </x-ui.field>

                    @if ($activeField)
                        <label class="flex items-center gap-2 text-sm text-ink">
                            {{-- hidden antes do checkbox: desmarcado envia 0 --}}
                            <input type="hidden" name="{{ $activeField }}" value="0">
                            <input type="checkbox" name="{{ $activeField }}" value="1" class="rounded-sm border-line text-finance-base focus:ring-finance-base"
                                   @checked(old($activeField, $item?->{$activeField} ?? true))>
                            Ativo (aparece ao lançar transações)
                        </label>
                    @endif
                </div>

                <div class="flex flex-wrap gap-3 pt-6 mt-6 border-t border-line">
                    <x-ui.button type="submit">{{ $isEdit ? 'Salvar alterações' : 'Salvar' }}</x-ui.button>
                    <x-ui.button variant="secondary" :href="route($config['route'].'.index')">Cancelar</x-ui.button>
                </div>
            </form>

            @if ($isEdit)
                <div class="flex justify-end pt-6 mt-6 border-t border-line">
                    <x-ui.confirm-delete :action="route($config['route'].'.destroy', $item)" size="md" />
                </div>
            @endif
        </x-ui.card>
    </div>
</x-finance-layout>
