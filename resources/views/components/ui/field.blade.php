{{--
    Campo de formulário: rótulo + controle (slot) + dica + erro de validação.
    O controle vai no slot e deve usar class="ui-input" e o mesmo id de $for.

    Props:
      $label:    texto do rótulo
      $for:      id do controle (e nome do campo para @error, se $name não vier)
      $name:     nome do campo para buscar o erro (padrão = $for)
      $hint:     texto de ajuda (opcional)
      $required: mostra "*" no rótulo
--}}
@props(['label', 'for', 'name' => null, 'hint' => null, 'required' => false])

@php $errorKey = $name ?? $for; @endphp

<div {{ $attributes }}>
    <label for="{{ $for }}" class="ui-label">
        {{ $label }}@if ($required)<span class="text-red-700" aria-hidden="true"> *</span>@endif
    </label>

    {{ $slot }}

    @if ($hint)
        <p class="mt-1 text-xs text-ink-2">{{ $hint }}</p>
    @endif

    @error($errorKey)
        <p class="mt-1 text-sm text-red-700" id="{{ $for }}-error">{{ $message }}</p>
    @enderror
</div>
