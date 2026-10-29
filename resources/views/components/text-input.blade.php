{{-- Campo de texto do Breeze, na paleta do projeto (mesma aparência de .ui-input). --}}
@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'ui-input']) }}>
