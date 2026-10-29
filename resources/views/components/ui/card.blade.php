{{--
    Superfície de conteúdo (substitui "bg-white rounded-lg shadow-md p-6" do Breeze).

    Props:
      $title: título da seção (opcional)
      $tone:  cor do título — 'default' | 'income' (verde) | 'expense' (vermelho)
--}}
@props(['title' => null, 'tone' => 'default'])

@php
    $titleColor = [
        'default' => 'text-finance-base',
        'income' => 'text-emerald-700',
        'expense' => 'text-red-700',
    ][$tone] ?? 'text-finance-base';
@endphp

<section {{ $attributes->merge(['class' => 'bg-finance-paper border border-line rounded-sm p-6']) }}>
    @if ($title)
        <h2 class="font-serif text-xl tracking-tight mb-4 {{ $titleColor }}">{{ $title }}</h2>
    @endif
    {{ $slot }}
</section>
