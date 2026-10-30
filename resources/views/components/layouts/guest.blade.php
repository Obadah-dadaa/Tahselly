@props(['title' => null, 'description' => null])
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $dir }}" class="{{ $theme === 'dark' ? 'dark' : '' }}">
<head>
    <meta charset="utf-8">
    <x-favicons />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' — ' : '' }}{{ __(\App\Models\SystemSetting::get('app_name', __('تحصيلي'))) }}</title>
    <x-seo :title="($title ? $title.' — ' : '').__(\App\Models\SystemSetting::get('app_name', 'تحصيلي'))" :description="$description" />
    {{ $head ?? '' }}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased">
    <header class="border-b border-slate-200/70 bg-surface">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
            <a href="{{ route('landing') }}" class="flex items-center gap-2">
                <x-brand-mark class="size-9" />
                <span class="text-xl font-bold text-ink">{{ __(\App\Models\SystemSetting::get('app_name', __('تحصيلي'))) }}</span>
            </a>
            <div class="flex items-center gap-3">
                <x-preference-switches />
                @auth
                    <a href="{{ route('home') }}" class="btn-secondary">{{ __('لوحة التحكم') }}</a>
                @else
                    <a href="{{ route('login') }}" class="btn-secondary">{{ __('تسجيل الدخول') }}</a>
                @endauth
            </div>
        </div>
    </header>
    <main class="mx-auto max-w-6xl px-6 py-10">
        @foreach(['success' => 'bg-emerald-50 text-emerald-800 ring-emerald-600/20', 'info' => 'bg-amber-50 text-amber-800 ring-amber-600/20', 'error' => 'bg-rose-50 text-rose-800 ring-rose-600/20'] as $key => $style)
            @if(session($key))
                <div class="mb-6 rounded-xl px-4 py-3 text-sm ring-1 ring-inset {{ $style }}">{{ session($key) }}</div>
            @endif
        @endforeach
        @if($errors->any())
            <div class="mb-6 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-800 ring-1 ring-inset ring-rose-600/20">
                <ul class="list-inside list-disc space-y-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        {{ $slot }}
    </main>
</body>
</html>
