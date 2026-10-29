{{--
    Saudação + dropdown do usuário (Perfil · [Configurações, só admin] · Sair) no header dos layouts.
--}}
@php
    $styles = [
        'greeting' => 'text-ink-2', 'name' => 'text-ink',
        'button' => 'text-ink-2 hover:text-ink',
        'panel' => 'bg-paper border border-line shadow-lg',
        'item' => 'text-ink hover:bg-finance-base/5',
    ];
    $user = auth()->user();
@endphp

<div class="flex items-center gap-3">
    <span class="hidden md:inline text-sm {{ $styles['greeting'] }}">
        Olá, <span class="font-medium {{ $styles['name'] }}">{{ $user->nickname ?? $user->name }}</span>
    </span>
    <div x-data="{ open: false }" @click.outside="open = false" @close.stop="open = false" @keydown.escape.window="open = false" class="relative">
        <button type="button" @click="open = ! open" :aria-expanded="open.toString()" aria-haspopup="menu" aria-label="Menu do usuário"
                class="inline-flex items-center text-sm transition-colors {{ $styles['button'] }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
            </svg>
        </button>
        <div x-show="open" x-transition.opacity x-cloak role="menu"
             class="absolute right-0 mt-2 w-48 rounded-sm z-50 {{ $styles['panel'] }}">
            <a href="{{ route('profile.view') }}" role="menuitem" class="block px-4 py-2 text-sm {{ $styles['item'] }}">Perfil</a>
            @can('admin.super')
                <a href="{{ route('settings.index') }}" role="menuitem" class="block px-4 py-2 text-sm {{ $styles['item'] }}">Configurações</a>
            @endcan
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" role="menuitem" class="w-full text-left block px-4 py-2 text-sm {{ $styles['item'] }}">Sair</button>
            </form>
        </div>
    </div>
</div>
