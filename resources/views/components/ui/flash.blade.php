{{-- Mensagens de sessão (success / error) no padrão visual de Finanças. --}}
@if (session('success'))
    <p {{ $attributes->merge(['class' => 'mb-6 border border-emerald-300 bg-emerald-50 text-emerald-800 text-sm px-4 py-3 rounded-sm']) }} role="status">
        {{ session('success') }}
    </p>
@endif

@if (session('error'))
    <p {{ $attributes->merge(['class' => 'mb-6 border border-red-300 bg-red-50 text-red-800 text-sm px-4 py-3 rounded-sm']) }} role="alert">
        {{ session('error') }}
    </p>
@endif
