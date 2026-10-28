@props(['label', 'value', 'hint' => null, 'icon' => 'cash', 'tone' => 'brand', 'suffix' => null])
@php
    $tones = [
        'brand' => 'bg-brand-50 text-brand-700',
        'rose' => 'bg-rose-50 text-rose-600',
        'amber' => 'bg-amber-50 text-amber-600',
        'sky' => 'bg-sky-50 text-sky-600',
        'violet' => 'bg-violet-50 text-violet-600',
        'slate' => 'bg-slate-100 text-slate-600',
    ];
@endphp
<div {{ $attributes->merge(['class' => 'card p-5']) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="text-sm font-medium text-slate-500">{{ __($label) }}</p>
            <p class="num mt-2 truncate text-2xl font-bold tracking-tight text-ink">{{ $value }} @if($suffix)<span class="text-sm font-medium text-slate-400">{{ $suffix }}</span>@endif</p>
        </div>
        <span class="grid size-10 shrink-0 place-items-center rounded-xl {{ $tones[$tone] }}"><x-icon :name="$icon" /></span>
    </div>
    @if($hint)
        <p class="mt-3 text-xs text-slate-500">{{ $hint }}</p>
    @endif
</div>
