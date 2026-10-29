{{--
    Botão/link na paleta de Finanças.

    Props:
      $variant: 'primary' (verde-petróleo sólido) | 'secondary' (contorno neutro)
                | 'accent' (contorno âmbar, ex.: Editar) | 'danger' (contorno vermelho)
      $size:    'md' | 'sm'
      $href:    se presente, renderiza <a>; senão <button> (type padrão "button")
--}}
@props(['variant' => 'primary', 'size' => 'md', 'href' => null])

@php
    $variants = [
        'primary' => 'bg-finance-base text-paper border-finance-base hover:bg-finance-base/90',
        'secondary' => 'bg-transparent text-ink border-line hover:border-ink/60 hover:bg-ink/[0.03]',
        'accent' => 'bg-transparent text-[#7A5C33] border-finance-accent/50 hover:bg-finance-accent/10 hover:border-finance-accent',
        'danger' => 'bg-transparent text-red-700 border-red-200 hover:bg-red-50 hover:border-red-400',
    ];
    $sizes = [
        'md' => 'px-4 py-2 text-sm',
        'sm' => 'px-3 py-1.5 text-xs',
    ];

    $classes = 'inline-flex items-center justify-center gap-2 font-medium rounded-sm border transition-colors '
        .'focus:outline-none focus-visible:ring-2 focus-visible:ring-finance-accent focus-visible:ring-offset-2 focus-visible:ring-offset-paper '
        .'disabled:opacity-50 disabled:cursor-not-allowed '
        .($variants[$variant] ?? $variants['primary']).' '.($sizes[$size] ?? $sizes['md']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => 'button', 'class' => $classes]) }}>{{ $slot }}</button>
@endif
