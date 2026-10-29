<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'TechFish') }} — {{ $title ?? 'Entrar' }}</title>

        {{-- Mesma identidade editorial da landing: Fraunces (títulos) + Figtree (corpo) --}}
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&family=fraunces:400,400i,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-paper text-ink selection:bg-ink selection:text-paper">
        <div class="min-h-screen flex flex-col">

            <header class="border-b border-line/60">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center">
                    {{-- Logo: TF em serifa dentro de um quadrado fino (igual à landing) --}}
                    <a href="{{ url('/') }}" class="flex items-center gap-3 group" aria-label="{{ config('app.name', 'TechFish') }} — Início">
                        <span class="w-9 h-9 border border-ink/80 rounded-sm flex items-center justify-center font-serif text-base font-semibold tracking-tight transition-colors group-hover:bg-ink group-hover:text-paper">
                            T<span class="opacity-60">F</span>
                        </span>
                        <span class="font-serif text-lg font-semibold tracking-tight">{{ config('app.name', 'TechFish') }}</span>
                    </a>
                </div>
            </header>

            <main class="flex-1 flex items-center justify-center px-4 py-16">
                <div class="w-full max-w-md">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </body>
</html>
