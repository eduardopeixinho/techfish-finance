{{-- Login exclusivo via Google (não há cadastro nem senha). --}}
<x-guest-layout>
    <div class="bg-white/60 border border-line rounded-sm p-8 sm:p-10">
        <p class="text-[10px] font-semibold tracking-[0.2em] uppercase text-ink-2 mb-4">Acesso</p>

        <h1 class="font-serif text-3xl sm:text-4xl tracking-tight leading-tight">
            Entrar no <em class="italic">{{ config('app.name') }}</em>.
        </h1>

        <p class="mt-3 text-ink-2 leading-relaxed">
            Use a sua conta Google. Na primeira vez, a conta é criada automaticamente e os seus
            dados ficam visíveis só para você.
        </p>

        {{-- Erro vindo do callback do Google (domínio não autorizado, falha no OAuth...) --}}
        @if (session('error'))
            <p class="mt-6 border border-red-300 bg-red-50 text-red-800 text-sm px-4 py-3 rounded-sm" role="alert">
                {{ session('error') }}
            </p>
        @endif

        <x-auth-session-status class="mt-6" :status="session('status')" />

        @if (config('services.google.enabled'))
            <a href="{{ route('google.login') }}"
               class="mt-8 w-full inline-flex items-center justify-center gap-3 px-6 py-3 bg-ink text-paper font-medium rounded-sm hover:bg-ink-2 transition-colors">
                {{-- Logo "G" do Google (SVG inline, sem depender de biblioteca de ícones) --}}
                <svg class="w-5 h-5 bg-white rounded-full p-0.5" viewBox="0 0 48 48" aria-hidden="true">
                    <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                    <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                    <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                    <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                </svg>
                Entrar com Google
            </a>
        @endif

        @if (config('demo.enabled'))
            <div class="mt-8 pt-6 border-t border-line">
                <p class="text-sm text-ink-2 mb-3">Quer só conhecer o app? Entre numa conta com dados fictícios.</p>
                <form method="POST" action="{{ route('demo.login') }}">
                    @csrf
                    <button type="submit"
                            class="w-full inline-flex items-center justify-center px-6 py-3 border border-ink text-ink font-medium rounded-sm hover:bg-ink hover:text-paper transition-colors">
                        Ver demonstração
                    </button>
                </form>
            </div>
        @endif
    </div>
</x-guest-layout>
