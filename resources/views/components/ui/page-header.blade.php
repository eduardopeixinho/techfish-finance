{{--
    Cabeçalho de página do contexto Finanças/Investimentos.

    Props:
      $title:  título (Fraunces)
      $kicker: rótulo pequeno acima do título (opcional)
    Slots:
      $actions:     botões à direita (opcional)
      $description: texto abaixo do título (opcional)
--}}
@props(['title', 'kicker' => null])

<div {{ $attributes->merge(['class' => 'flex flex-wrap items-end justify-between gap-4 mb-8']) }}>
    <div class="min-w-0">
        @if ($kicker)
            <p class="text-[10px] font-semibold tracking-[0.2em] uppercase text-finance-base/70 mb-2">{{ $kicker }}</p>
        @endif
        <h1 class="font-serif text-3xl sm:text-4xl tracking-tight text-finance-base">{{ $title }}</h1>
        @isset($description)
            <p class="mt-2 text-ink-2 max-w-2xl">{{ $description }}</p>
        @endisset
    </div>

    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
