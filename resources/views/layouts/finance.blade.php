<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? \App\Support\PageTitle::forRoute(request()->route()?->getName()) ?? 'Painel' }} — {{ config('app.name') }}</title>

        {{-- Figtree (corpo) + Fraunces (display) + JetBrains Mono (detalhes) --}}
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&family=fraunces:400,500,600,700&family=jetbrains-mono:400,500&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-paper text-ink-2 selection:bg-finance-base selection:text-paper">

        <x-app-header />

        {{-- Page Heading (slot opcional, mantém padrão Breeze) --}}
        @isset($header)
            <div class="bg-paper/60 border-b border-line/40">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </div>
        @endisset

        {{-- Breadcrumb (aparece apenas se não houver $header) --}}
        @empty($header)
            @php
                $routeName = request()->route()?->getName();
                $pageTitle = \App\Support\PageTitle::forRoute($routeName);
            @endphp
            @if($pageTitle && $routeName !== 'finance.dashboard')
                <div class="bg-paper/40 border-b border-line/30">
                    <div class="max-w-7xl mx-auto py-3 px-4 sm:px-6 lg:px-8">
                        <nav class="flex items-center gap-2 text-xs text-ink-2" aria-label="Breadcrumb">
                            <a href="{{ route('finance.dashboard') }}" class="hover:text-ink transition-colors">Painel</a>
                            <svg class="w-3 h-3 text-line" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
                            <span class="text-ink font-medium">{{ $pageTitle }}</span>
                        </nav>
                    </div>
                </div>
            @endif
        @endempty

        {{-- Page Content --}}
        <main class="animate-fade-in">
            {{ $slot ?? '' }}
        </main>

    </body>
</html>
