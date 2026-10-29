{{--
    Cabeçalho do app (todas as telas logadas): marca, menu principal,
    ocultar valores e menu do usuário.
--}}
<header class="bg-paper/80 backdrop-blur-sm border-b border-line/60 sticky top-0 z-30">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center gap-3 h-16">
            <a href="{{ route('finance.dashboard') }}" class="flex items-center gap-2 group" aria-label="{{ config('app.name') }} — Painel">
                <span class="w-8 h-8 border border-finance-base/60 rounded-sm flex items-center justify-center font-serif text-sm font-semibold text-finance-base group-hover:bg-finance-base group-hover:text-paper transition-colors">
                    T<span class="opacity-60">F</span>
                </span>
                <span class="hidden sm:inline font-serif text-base font-semibold tracking-tight text-finance-base">
                    {{ config('app.name') }}
                </span>
            </a>

            <x-main-nav />

            <div class="flex items-center gap-3">
                <x-value-toggle />
                <x-user-menu />
            </div>
        </div>
    </div>
</header>
