{{--
    Botão de toggle "ocultar valores" (módulo Finanças).

    Comportamento:
      - POST /preferences/toggle-values
      - Volta para a página anterior (`back()`)
      - Estado persiste em sessão (`session('hide_values')`)

    Renderiza:
      - Olho aberto: valores estão VISÍVEIS (estado padrão)
      - Olho fechado: valores estão OCULTOS (R$ •••••)
--}}

@php
    $isHidden = (bool) session('hide_values', false);
    $baseClasses = 'border-line text-ink-2 hover:border-ink hover:text-ink';
    $activeClasses = 'bg-ink/5 text-ink border-ink';
@endphp

<form method="POST" action="{{ route('preferences.toggle-values') }}" class="inline">
    @csrf
    <button type="submit"
            title="{{ $isHidden ? 'Mostrar valores' : 'Ocultar valores' }}"
            aria-label="{{ $isHidden ? 'Mostrar valores monetários' : 'Ocultar valores monetários' }}"
            aria-pressed="{{ $isHidden ? 'true' : 'false' }}"
            class="inline-flex items-center justify-center w-9 h-9 rounded-sm border transition-colors
                   {{ $isHidden ? $activeClasses : $baseClasses }}">
        @if($isHidden)
            {{-- Olho FECHADO (valores ocultos) --}}
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                 stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.244 7.244L21 21m-3.878-3.878l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
            </svg>
        @else
            {{-- Olho ABERTO (valores visíveis) --}}
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                 stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
        @endif
    </button>
</form>
