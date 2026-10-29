@props(['tone' => 'default'])
@php
    $isDark = ($theme ?? 'light') === 'dark';
    $base = $tone === 'onDark'
        ? 'text-slate-200 ring-white/20 hover:bg-white/10'
        : 'text-slate-600 ring-slate-200 hover:bg-slate-100';
@endphp
<div {{ $attributes->merge(['class' => 'flex items-center gap-1.5']) }}>
    <form method="POST" action="{{ route('locale', app()->getLocale() === 'ar' ? 'en' : 'ar') }}">
        @csrf
        <button class="grid h-9 min-w-9 place-items-center rounded-xl px-2.5 text-sm font-bold ring-1 {{ $base }}" title="{{ __('تغيير اللغة') }}">
            {{ app()->getLocale() === 'ar' ? 'EN' : 'AR' }}
        </button>
    </form>
    <form method="POST" action="{{ route('theme', $isDark ? 'light' : 'dark') }}">
        @csrf
        <button class="grid size-9 place-items-center rounded-xl ring-1 {{ $base }}" title="{{ $isDark ? __('الوضع الفاتح') : __('الوضع الداكن') }}">
            @if($isDark)
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/></svg>
            @else
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/></svg>
            @endif
        </button>
    </form>
</div>
