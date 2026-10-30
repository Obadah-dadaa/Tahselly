{{-- The Tahselly mark: a follow-up loop with an arrowhead around a coin (reminders go round until the money arrives).
     "tile" is the app icon (teal circle); "plain" draws the loop in the current text colour for dark grounds. --}}
@props(['variant' => 'tile'])
<svg {{ $attributes->merge(['class' => 'shrink-0']) }} viewBox="0 0 100 100" role="img" aria-label="{{ __(\App\Models\SystemSetting::get('app_name', 'تحصيلي')) }}" xmlns="http://www.w3.org/2000/svg">
    @if($variant === 'tile')
        <circle cx="50" cy="50" r="50" fill="#0F766E"/>
        <path d="M71 31 A28 28 0 1 0 78 50" fill="none" stroke="#fff" stroke-width="8.5" stroke-linecap="round"/>
        <path d="M78 22 L73 33 L62 29" fill="none" stroke="#fff" stroke-width="8.5" stroke-linecap="round" stroke-linejoin="round"/>
    @else
        <path d="M71 31 A28 28 0 1 0 78 50" fill="none" stroke="currentColor" stroke-width="8.5" stroke-linecap="round"/>
        <path d="M78 22 L73 33 L62 29" fill="none" stroke="currentColor" stroke-width="8.5" stroke-linecap="round" stroke-linejoin="round"/>
    @endif
    <circle cx="50" cy="50" r="11" fill="#2DD4BF"/>
</svg>
