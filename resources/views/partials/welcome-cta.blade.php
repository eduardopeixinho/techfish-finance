{{-- Botões de entrada da página inicial: Google e, com DEMO_MODE ligado, a demonstração. --}}
<div class="flex flex-col sm:flex-row gap-3">
    <a href="{{ route('google.login') }}"
       class="inline-flex items-center justify-center px-6 py-3 font-medium rounded-sm bg-finance-base text-white hover:opacity-90 transition-opacity shadow-sm">
        Entrar com Google
    </a>
    @if (config('demo.enabled'))
        <form method="POST" action="{{ route('demo.login') }}">
            @csrf
            <button type="submit"
                    class="w-full inline-flex items-center justify-center px-6 py-3 border-2 border-finance-base text-finance-base font-medium rounded-sm hover:bg-finance-base hover:text-white transition-colors">
                Ver demonstração
            </button>
        </form>
    @endif
</div>
