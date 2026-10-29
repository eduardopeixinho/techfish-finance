{{--
    Página inicial pública: apresenta o app e leva ao login (Google) ou à demonstração.
    Quem já está logado vai direto para o painel (ver routes/web.php).
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name') }} — Finanças pessoais e investimentos</title>
    <meta name="description" content="Controle de gastos, contas, faturas de cartão por competência e carteira de investimentos. Projeto open source em Laravel.">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&family=fraunces:400,500,600,700&family=jetbrains-mono:400,500&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-paper text-ink-2 selection:bg-finance-base selection:text-paper">
    <header class="border-b border-line/60 bg-paper/80 backdrop-blur-sm sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="{{ url('/') }}" class="flex items-center gap-2" aria-label="{{ config('app.name') }} — Início">
                <span class="w-8 h-8 border border-finance-base/60 rounded-sm flex items-center justify-center font-serif text-sm font-semibold text-finance-base">
                    T<span class="opacity-60">F</span>
                </span>
                <span class="font-serif text-base font-semibold tracking-tight text-finance-base">{{ config('app.name') }}</span>
            </a>
            <a href="{{ route('login') }}" class="text-sm font-medium text-finance-base hover:underline underline-offset-4">Entrar</a>
        </div>
    </header>

    <main class="animate-fade-in">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-20">

        {{-- ===== Hero editorial ===== --}}
        <section class="mb-16 lg:mb-24">
            <div class="max-w-3xl">
                <p class="inline-flex items-center gap-2 text-xs font-medium tracking-widest uppercase text-finance-base mb-6">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Gestão financeira pessoal
                </p>

                <h1 class="font-serif text-4xl sm:text-5xl lg:text-6xl leading-[1.05] tracking-tight text-finance-base">
                    Suas finanças, com a <em class="italic font-medium">clareza</em> que merecem.
                </h1>

                <p class="mt-6 text-lg sm:text-xl text-ink-2 leading-relaxed max-w-2xl">
                    Gastos, investimentos e faturas de cartão — tudo num só painel.
                    Controle total, relatórios detalhados e exportação em CSV.
                </p>

                <div class="mt-10">
                    @include('partials.welcome-cta')
                </div>
            </div>
        </section>

        {{-- ===== Features principais ===== --}}
        <section id="features" class="mb-16 lg:mb-24">
            <h2 class="font-serif text-3xl sm:text-4xl tracking-tight text-finance-base mb-12">
                Tudo que você precisa, <em class="italic">nada que não precisa</em>.
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div class="bg-finance-paper border border-finance-base/10 rounded-sm p-6">
                    <div class="w-10 h-10 bg-finance-base/10 rounded-sm flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-finance-base" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="font-serif text-xl font-semibold text-finance-base mb-2">Controle de Gastos</h3>
                    <p class="text-sm text-ink-2 leading-relaxed">
                        Receitas, despesas e categorias. Cada transação no lugar certo, com a data de competência.
                    </p>
                </div>

                <div class="bg-finance-paper border border-finance-base/10 rounded-sm p-6">
                    <div class="w-10 h-10 bg-finance-base/10 rounded-sm flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-finance-base" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                        </svg>
                    </div>
                    <h3 class="font-serif text-xl font-semibold text-finance-base mb-2">Contas Bancárias</h3>
                    <p class="text-sm text-ink-2 leading-relaxed">
                        Múltiplas contas, saldos em tempo real, transferências entre contas. Tudo integrado.
                    </p>
                </div>

                <div class="bg-finance-paper border border-finance-base/10 rounded-sm p-6">
                    <div class="w-10 h-10 bg-finance-base/10 rounded-sm flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-finance-base" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                        </svg>
                    </div>
                    <h3 class="font-serif text-xl font-semibold text-finance-base mb-2">Faturas de Cartão</h3>
                    <p class="text-sm text-ink-2 leading-relaxed">
                        Faturas por competência, controle de limites, parcelas e vencimentos. Sem surpresas.
                    </p>
                </div>

                <div class="bg-finance-paper border border-finance-base/10 rounded-sm p-6">
                    <div class="w-10 h-10 bg-finance-base/10 rounded-sm flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-finance-base" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                        </svg>
                    </div>
                    <h3 class="font-serif text-xl font-semibold text-finance-base mb-2">Investimentos</h3>
                    <p class="text-sm text-ink-2 leading-relaxed">
                        Ações, FIIs, BDRs. Carteira diversificada, dividendos e relatórios de performance.
                    </p>
                </div>

                <div class="bg-finance-paper border border-finance-base/10 rounded-sm p-6">
                    <div class="w-10 h-10 bg-finance-base/10 rounded-sm flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-finance-base" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                        </svg>
                    </div>
                    <h3 class="font-serif text-xl font-semibold text-finance-base mb-2">Relatórios</h3>
                    <p class="text-sm text-ink-2 leading-relaxed">
                        Exportação em CSV, gráficos e dashboards. Para Excel, Sheets ou outra ferramenta.
                    </p>
                </div>

                <div class="bg-finance-paper border border-finance-base/10 rounded-sm p-6">
                    <div class="w-10 h-10 bg-finance-base/10 rounded-sm flex items-center justify-center mb-4">
                        <svg class="w-5 h-5 text-finance-base" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" />
                        </svg>
                    </div>
                    <h3 class="font-serif text-xl font-semibold text-finance-base mb-2">Configurações</h3>
                    <p class="text-sm text-ink-2 leading-relaxed">
                        Tipos de conta, métodos de pagamento, categorias personalizadas. Tudo ajustável.
                    </p>
                </div>
            </div>
        </section>

        {{-- ===== Stack técnica ===== --}}
        <section class="border-t border-line/40 pt-16 lg:pt-24">
            <div class="max-w-3xl">
                <h2 class="font-serif text-3xl sm:text-4xl tracking-tight text-finance-base mb-8">
                    Construído com <em class="italic">tecnologia moderna</em>.
                </h2>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-6">
                    <div class="text-center">
                        <p class="font-serif text-2xl font-semibold text-finance-base">Laravel 13</p>
                        <p class="text-xs text-ink-2 mt-1">Framework PHP</p>
                    </div>
                    <div class="text-center">
                        <p class="font-serif text-2xl font-semibold text-finance-base">PHP 8.4</p>
                        <p class="text-xs text-ink-2 mt-1">Linguagem</p>
                    </div>
                    <div class="text-center">
                        <p class="font-serif text-2xl font-semibold text-finance-base">Tailwind 3</p>
                        <p class="text-xs text-ink-2 mt-1">CSS Framework</p>
                    </div>
                    <div class="text-center">
                        <p class="font-serif text-2xl font-semibold text-finance-base">MySQL 8</p>
                        <p class="text-xs text-ink-2 mt-1">Banco de dados</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- ===== CTA Final ===== --}}
        <section class="mt-16 lg:mt-24 text-center py-12 px-6 rounded-sm" style="background-color: rgba(11, 61, 58, 0.05);">
            <h2 class="font-serif text-3xl sm:text-4xl tracking-tight text-finance-base">
                Pronto para <em class="italic">começar</em>?
            </h2>
            <p class="mt-4 text-lg text-ink-2 max-w-xl mx-auto">
                Entre com sua conta Google ou explore a demonstração com dados fictícios.
            </p>
            <div class="mt-8 flex justify-center">
                @include('partials.welcome-cta')
            </div>
        </section>

    </div>
    </main>

    <footer class="border-t border-line/60 py-8 text-center text-xs text-ink-2">
        Projeto open source · Laravel {{ app()->version() }}
    </footer>
</body>
</html>
