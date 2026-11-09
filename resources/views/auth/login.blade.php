<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $dir }}" class="{{ $theme === 'dark' ? 'dark' : '' }}">
<head>
    <meta charset="utf-8">
    <x-favicons />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('تسجيل الدخول') }} — {{ __(\App\Models\SystemSetting::get('app_name', 'تحصيلي')) }}</title>
    <meta name="robots" content="noindex, follow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <x-google-tag />
</head>
<body class="min-h-screen bg-slate-50 font-sans antialiased" x-data="{ email: '{{ old('email') }}', password: '' }">
<div class="grid min-h-screen lg:grid-cols-2">
    <div class="flex items-center justify-center px-6 py-12">
        <div class="w-full max-w-sm">
            <a href="{{ route('landing') }}" class="mb-10 flex items-center gap-3">
                <x-brand-mark class="size-11" />
                <span class="text-2xl font-bold text-ink">{{ __(\App\Models\SystemSetting::get('app_name', 'تحصيلي')) }}</span> <x-beta-badge />
            </a>
            <x-preference-switches class="-mt-6 mb-8" />
            <h1 class="text-2xl font-bold text-ink">{{ __('مرحباً بعودتك 👋') }}</h1>
            <p class="mt-2 text-sm text-slate-500">{{ __('سجّل دخولك لمتابعة فواتيرك ومستحقاتك.') }}</p>

            <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
                @csrf
                <div>
                    <label class="label" for="email">{{ __('البريد الإلكتروني') }}</label>
                    <input id="email" name="email" type="email" x-model="email" required autofocus class="input py-2.5" dir="ltr">
                    @error('email')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <div class="mb-1 flex items-center justify-between"><label class="label mb-0" for="password">{{ __('كلمة المرور') }}</label><a href="{{ route('password.request') }}" class="text-xs font-semibold text-brand-700 hover:underline">{{ __('نسيت كلمة المرور؟') }}</a></div>
                    <input id="password" name="password" type="password" x-model="password" required class="input py-2.5" dir="ltr">
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="remember" class="rounded border-slate-300 text-brand-600"> {{ __('تذكرني') }}</label>
                <x-recaptcha action="login" />
                <button class="btn-primary w-full py-3">{{ __('تسجيل الدخول') }}</button>
            </form>
            @if(\App\Models\SystemSetting::get('allow_signups', true))
                <p class="mt-5 text-center text-sm text-slate-500">{{ __('ليس لديك حساب؟') }} <a href="{{ route('trial.create') }}" class="font-semibold text-brand-700">{{ __('ابدأ تجربتك المجانية') }}</a></p>
            @endif

            @if(app()->environment('local'))
                <div class="mt-8 rounded-2xl border border-dashed border-slate-300 p-4">
                    <p class="mb-3 text-xs font-semibold text-slate-500">{{ __('حسابات تجريبية (بيئة التطوير فقط)') }}</p>
                    <div class="grid gap-2 text-sm">
                        @foreach(['owner@demo.test' => __('المالك'), 'manager@demo.test' => __('المدير'), 'developer@tahselly.test' => __('المبرمج')] as $demoEmail => $demoRole)
                            <button type="button" @click="email='{{ $demoEmail }}'; password='password'" class="flex items-center justify-between rounded-lg bg-slate-100 px-3 py-2 hover:bg-slate-200">
                                <span class="font-semibold">{{ $demoRole }}</span><span dir="ltr" class="text-slate-500">{{ $demoEmail }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
    <div class="relative hidden overflow-hidden bg-night lg:block">
        <div class="absolute -start-20 -top-20 size-96 rounded-full bg-brand-600/30 blur-3xl"></div>
        <div class="absolute -bottom-32 end-0 size-[28rem] rounded-full bg-sky-500/20 blur-3xl"></div>
        <div class="relative flex h-full flex-col justify-center px-16 text-white">
            <p class="text-sm font-semibold text-brand-200">Invoice & Payment Reminder</p>
            <h2 class="mt-4 text-4xl font-bold leading-tight">{{ __('لا مزيد من') }}<br>{{ __('الفواتير المنسية.') }}</h2>
            <p class="mt-4 max-w-md text-lg text-slate-300">{{ __(\App\Models\SystemSetting::get('app_tagline')) }}</p>
            <div class="mt-12 grid max-w-md grid-cols-3 gap-4">
                @foreach([['9:00', __('تذكير آلي يومي')], ['Excel', __('استيراد بنقرة')], ['WhatsApp', __('تذكير بنقرة')]] as [$n, $l])
                    <div class="rounded-2xl bg-white/5 p-4 ring-1 ring-white/10"><p class="text-2xl font-bold">{{ $n }}</p><p class="mt-1 text-xs text-slate-400">{{ $l }}</p></div>
                @endforeach
            </div>
        </div>
    </div>
</div>
</body>
</html>
