{{--
    Etiqueta curta (tipo de transação, status, tipo de conta).

    Props:
      $tone: 'neutral' | 'income' | 'expense' | 'transfer' | 'warning'
--}}
@props(['tone' => 'neutral'])

@php
    $tones = [
        'neutral' => 'bg-ink/[0.05] text-ink-2 border-line',
        'income' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
        'expense' => 'bg-red-50 text-red-800 border-red-200',
        'transfer' => 'bg-finance-base/5 text-finance-base border-finance-base/20',
        'warning' => 'bg-amber-50 text-amber-800 border-amber-200',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded-sm border whitespace-nowrap '.($tones[$tone] ?? $tones['neutral'])]) }}>{{ $slot }}</span>
