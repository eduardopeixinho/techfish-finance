{{-- Ação destrutiva final (ex.: confirmar exclusão da conta): vermelho sólido. --}}
<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium rounded-sm border border-red-700 bg-red-700 text-white hover:bg-red-800 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2 focus-visible:ring-offset-paper']) }}>
    {{ $slot }}
</button>
