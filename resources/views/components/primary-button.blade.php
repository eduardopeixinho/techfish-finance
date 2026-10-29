{{-- Botão principal do Breeze → mesma aparência de .btn-primary. --}}
<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn-primary']) }}>
    {{ $slot }}
</button>
