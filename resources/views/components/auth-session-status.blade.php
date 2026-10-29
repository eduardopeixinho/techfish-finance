@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'border border-emerald-300 bg-emerald-50 text-emerald-800 text-sm px-4 py-3 rounded-sm']) }}>
        {{ $status }}
    </div>
@endif
