{{-- Botão secundário do Breeze → mesma aparência de .btn-secondary. --}}
<button {{ $attributes->merge(['type' => 'button', 'class' => 'btn-secondary']) }}>
    {{ $slot }}
</button>
