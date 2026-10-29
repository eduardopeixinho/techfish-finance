{{-- Raramente exibida: o login via Google já marca o e-mail como verificado. --}}
<x-guest-layout>
    <div class="bg-white/60 border border-line rounded-sm p-8 sm:p-10">
        <h1 class="font-serif text-3xl tracking-tight">Confirme seu e-mail</h1>

        <p class="mt-3 text-ink-2 leading-relaxed">
            Enviamos um link de confirmação para o seu e-mail. Se não recebeu, podemos enviar outro.
        </p>

        @if (session('status') == 'verification-link-sent')
            <p class="mt-6 border border-green-300 bg-green-50 text-green-800 text-sm px-4 py-3 rounded-sm" role="status">
                Um novo link de confirmação foi enviado.
            </p>
        @endif

        <div class="mt-8 flex items-center justify-between gap-4">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="px-5 py-2.5 bg-ink text-paper text-sm font-medium rounded-sm hover:bg-ink-2 transition-colors">
                    Reenviar link
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm text-ink-2 underline underline-offset-4 hover:text-ink">Sair</button>
            </form>
        </div>
    </div>
</x-guest-layout>
