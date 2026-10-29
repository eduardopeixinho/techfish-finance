<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? \App\Support\PageTitle::forRoute(request()->route()?->getName()) ?? 'Administração' }} — {{ config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&family=jetbrains-mono:400,500&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-paper text-ink-2 selection:bg-ink selection:text-paper">

        <x-app-header />

        {{-- Page Heading (slot opcional) --}}
        @isset($header)
            <div class="bg-paper/60 border-b border-line/40">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </div>
        @endisset

        {{-- Page Content --}}
        <main>
            {{ $slot ?? '' }}
        </main>

    </body>
</html>
