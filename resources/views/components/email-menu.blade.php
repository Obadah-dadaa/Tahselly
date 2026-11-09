{{-- "Send by email" as a menu: Gmail and Outlook open their web composer (a mailto: link alone does nothing
     on a device with no mail app set up), plus the device's own mail app. subject/body are JavaScript expressions. --}}
@props(['subject', 'body', 'label' => null])
<div x-data="{ open: false }" class="relative" @click.outside="open = false" @keydown.escape="open = false">
    <button type="button" @click="open = !open" :aria-expanded="open" {{ $attributes->merge(['class' => 'btn-secondary']) }}>
        <x-icon name="mail" class="size-4" /> {{ $label ?? __('فتح في البريد') }}
        <svg class="size-3.5 opacity-60" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.06l3.71-3.83a.75.75 0 111.08 1.04l-4.25 4.39a.75.75 0 01-1.08 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
    </button>
    <div x-show="open" x-cloak x-transition.opacity class="absolute bottom-full z-30 mb-2 w-56 overflow-hidden rounded-xl bg-surface py-1 text-sm shadow-xl ring-1 ring-slate-200 dark:ring-white/10">
        <a :href="'https://mail.google.com/mail/?view=cm&fs=1&su=' + encodeURIComponent({{ $subject }}) + '&body=' + encodeURIComponent({{ $body }})" target="_blank" rel="noopener" @click="open = false" class="block px-4 py-2.5 text-ink hover:bg-slate-50 dark:hover:bg-white/5">Gmail</a>
        <a :href="'https://outlook.office.com/mail/deeplink/compose?subject=' + encodeURIComponent({{ $subject }}) + '&body=' + encodeURIComponent({{ $body }})" target="_blank" rel="noopener" @click="open = false" class="block px-4 py-2.5 text-ink hover:bg-slate-50 dark:hover:bg-white/5">Outlook</a>
        <a :href="'mailto:?subject=' + encodeURIComponent({{ $subject }}) + '&body=' + encodeURIComponent({{ $body }})" @click="open = false" class="block px-4 py-2.5 text-ink hover:bg-slate-50 dark:hover:bg-white/5">{{ __('تطبيق البريد على جهازك') }}</a>
    </div>
</div>
