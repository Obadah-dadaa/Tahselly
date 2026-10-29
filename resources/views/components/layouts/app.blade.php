<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $dir }}" class="{{ $theme === 'dark' ? 'dark' : '' }}">
<head>
    <meta charset="utf-8">
    <x-favicons />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? __('لوحة التحكم') }} — {{ __(\App\Models\SystemSetting::get('app_name', __('تحصيلي'))) }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $user = auth()->user();
    $role = $user->role->value;
    $appName = __(\App\Models\SystemSetting::get('app_name', __('تحصيلي')));
    $announcement = \App\Models\SystemSetting::get('announcement');
    $nav = [];
    if (in_array($role, ['manager', 'owner'], true)) {
        if ($role === 'owner') {
            $nav[__('المالك')] = [
                ['owner.dashboard', __('لوحة المالك'), 'trend', 'owner.dashboard', 'owner_analytics'],
                ['owner.billing', __('الاشتراك والفواتير'), 'tag', 'owner.billing'],
                ['owner.team.index', __('فريق العمل'), 'users', 'owner.team.*'],
                ['owner.settings.edit', __('الإعدادات والقوالب'), 'cog', 'owner.settings.*'],
                ['owner.activity', __('سجل النشاط'), 'list', 'owner.activity', 'activity_log'],
            ];
        }
        $nav[__('التحصيل')] = [
            ['dashboard', __('لوحة المدير'), 'home', 'dashboard'],
            ['invoices.index', __('الفواتير'), 'invoice', 'invoices.*'],
            ['clients.index', __('العملاء'), 'building', 'clients.*'],
            ['reminders.index', __('مركز التذكيرات'), 'bell', 'reminders.*'],
            ['reports.aging', __('أعمار الديون'), 'chart', 'reports.*'],
            ['import.index', __('استيراد Excel'), 'upload', 'import.*'],
        ];
    }
    if (in_array($role, ['developer', 'platform'], true)) {
        $nav[__('المنصة')] = [
            ['developer.dashboard', __('نظرة عامة'), 'trend', 'developer.dashboard'],
            ['developer.subscriptions.index', __('المشتركون والاشتراكات'), 'tag', 'developer.subscriptions.*', null, \App\Models\Subscription::query()->where('status', 'pending')->count()],
            ['developer.companies.index', __('الشركات'), 'building', 'developer.companies.*'],
            ['developer.plans', __('الباقات والأسعار'), 'cash', 'developer.plans*'],
            ['developer.activity', __('نشاط المنصة'), 'list', 'developer.activity'],
        ];
    }
    if ($role === 'developer') {
        $nav[__('المبرمج')] = [
            ['developer.settings', __('إعدادات النظام'), 'cog', 'developer.settings*'],
            ['developer.templates', __('القوالب الافتراضية'), 'mail', 'developer.templates*'],
            ['developer.tools', __('أدوات الصيانة'), 'terminal', 'developer.tools*'],
            ['developer.logs', __('سجل الأخطاء'), 'alert', 'developer.logs'],
        ];
    }
    $subscription = $user->company?->currentSubscription();
@endphp
<body class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased" x-data="{ menu: false }">
<div class="flex min-h-screen">
    {{-- Sidebar --}}
    <aside class="fixed inset-y-0 start-0 z-40 w-68 overflow-y-auto bg-night px-4 py-6 transition ltr:-translate-x-full rtl:translate-x-full lg:static lg:!translate-x-0"
           :class="menu && '!translate-x-0'">
        <a href="{{ route('home') }}" class="mb-8 flex items-center gap-3 px-2">
            <span class="grid size-10 place-items-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 text-lg font-bold text-white shadow-lg shadow-brand-900/40">{{ __('ت') }}</span>
            <span>
                <span class="block text-lg font-bold text-white">{{ $appName }}</span>
                <span class="block text-xs text-slate-400">Invoice & Payment Reminder</span>
            </span>
        </a>
        @foreach($nav as $section => $items)
            <p class="mb-2 mt-6 px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">{{ $section }}</p>
            <nav class="space-y-1">
                @foreach($items as $item)
                    @php [$route, $label, $icon, $pattern] = $item; $locked = isset($item[4]) && $user->company?->featureState($item[4]) === 'locked'; @endphp
                    <a href="{{ route($route) }}" class="nav-link {{ request()->routeIs($pattern) ? 'active' : '' }} {{ $locked ? 'opacity-70' : '' }}"
                       @if($locked) @click.prevent="$dispatch('upgrade', '{{ $item[4] }}')" @endif>
                        <x-icon :name="$icon" class="size-5 opacity-80" /> {{ __($label) }}
                        @isset($item[4])<x-plan-badge :feature="$item[4]" class="ms-auto" />@endisset
                        @if(! empty($item[5]))<span class="num ms-auto grid min-w-5 place-items-center rounded-full bg-amber-500 px-1.5 text-[11px] font-bold leading-5 text-white" title="{{ __('طلبات بانتظار الموافقة') }}">{{ $item[5] }}</span>@endif
                    </a>
                @endforeach
            </nav>
        @endforeach
        @if($user->company)
            <a href="{{ $user->isOwner() ? route('owner.billing') : '#' }}" class="mt-10 block rounded-2xl bg-white/5 p-4 text-sm">
                <p class="text-slate-400">{{ __('الشركة') }}</p>
                <p class="mt-1 font-semibold text-white">{{ $user->company->name }}</p>
                <p class="mt-2 inline-flex rounded-full bg-brand-500/15 px-2 py-0.5 text-xs text-brand-200">{{ $user->company->plan?->localizedName() ?? ($user->company->onTrial() ? __('تجربة مجانية') : '—') }}</p>
                @if($subscription)
                    <p class="mt-2 text-xs text-slate-400">{{ __('ينتهي بعد :days يوم', ['days' => $subscription->daysLeft()]) }}</p>
                @elseif($user->company->status === 'trial')
                    <p class="mt-2 text-xs text-amber-300">{{ __('فترة تجريبية') }}</p>
                @endif
            </a>
        @endif
    </aside>
    <div x-show="menu" x-cloak @click="menu=false" class="fixed inset-0 z-30 bg-black/40 lg:hidden"></div>

    <div class="flex min-w-0 flex-1 flex-col">
        @if($user->company?->status === 'trial' && $user->company->trial_ends_at)
            @php
                $trialDays = max(0, (int) today()->diffInDays($user->company->trial_ends_at, false));
                $urgent = $trialDays <= 3;
                $trialPlan = $user->company->plan;
            @endphp
            {{-- Trial reminder bar: days left, what happens next, and the way to subscribe --}}
            <div class="border-b px-4 py-2.5 {{ $urgent ? 'border-rose-700 bg-rose-600 text-white' : 'border-amber-200 bg-gradient-to-r from-amber-50 to-amber-100 text-amber-900' }}">
                <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-x-6 gap-y-2">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="flex shrink-0 items-baseline gap-1 rounded-xl px-3 py-1 {{ $urgent ? 'bg-white/15' : 'bg-white shadow-sm ring-1 ring-amber-200' }}">
                            @if($trialDays === 0)
                                <span class="text-sm font-bold">{{ __('آخر يوم') }}</span>
                            @else
                                <span class="num text-lg font-bold leading-none">{{ $trialDays }}</span>
                                <span class="text-xs font-semibold">{{ __('يوم متبقٍ') }}</span>
                            @endif
                        </span>
                        <div class="min-w-0 text-sm">
                            <p class="font-semibold">{{ __('تجربة مجانية — كل المزايا مفتوحة لك') }}</p>
                            <p class="text-xs {{ $urgent ? 'text-rose-100' : 'text-amber-800' }}">
                                {{ __('بعد انتهائها يتطلب الاستمرار اشتراكاً مدفوعاً.') }}
                                @if($trialPlan)
                                    {{ __('باقتك المختارة: :plan؛ الميزات التي تحمل علامة صفراء ليست ضمنها.', ['plan' => $trialPlan->localizedName()]) }}
                                @endif
                            </p>
                        </div>
                    </div>
                    @if($user->isOwner())
                        <div class="flex shrink-0 items-center gap-2">
                            @if($trialPlan)
                                <a href="{{ route('owner.plan') }}" class="rounded-lg px-3 py-1.5 text-xs font-semibold {{ $urgent ? 'text-white/90 hover:bg-white/10' : 'text-amber-900 hover:bg-amber-200/60' }}">{{ __('تغيير الباقة') }}</a>
                                <a href="{{ route('subscribe.show', ['plan' => $trialPlan->code, 'cycle' => 'yearly']) }}" class="rounded-lg px-4 py-1.5 text-sm font-bold shadow-sm {{ $urgent ? 'bg-white text-rose-700' : 'bg-amber-900 text-white hover:bg-amber-950' }}">{{ __('اشترك في :plan', ['plan' => $trialPlan->localizedName()]) }}</a>
                            @else
                                <a href="{{ route('owner.plan') }}" class="rounded-lg px-4 py-1.5 text-sm font-bold shadow-sm {{ $urgent ? 'bg-white text-rose-700' : 'bg-amber-900 text-white hover:bg-amber-950' }}">{{ __('اختر باقتك') }}</a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @endif
        @if($announcement)
            <div class="bg-amber-50 px-4 py-2 text-center text-sm text-amber-800">{{ $announcement }}</div>
        @endif

        <header class="sticky top-0 z-20 flex h-16 items-center justify-between gap-4 border-b border-slate-200/70 bg-surface/80 px-4 backdrop-blur lg:px-8">
            <div class="flex items-center gap-3">
                <button class="rounded-lg p-2 hover:bg-slate-100 lg:hidden" @click="menu=true" aria-label="{{ __('القائمة') }}"><x-icon name="menu" /></button>
                @if(in_array($role, ['manager', 'owner'], true))
                    <form action="{{ route('invoices.index') }}" class="relative hidden sm:block">
                        <x-icon name="search" class="pointer-events-none absolute start-3 top-2.5 size-4 text-slate-400" />
                        <input name="q" placeholder="{{ __('ابحث برقم فاتورة أو اسم عميل…') }}" class="input w-80 bg-slate-50 ps-9">
                    </form>
                @endif
            </div>
            <div class="flex items-center gap-2" x-data="{ open: false }">
                @if($user->isOwner() && $user->company && ! $user->company->onTrial() && $user->company->plan && \App\Models\Plan::query()->where('is_active', true)->where('price', '>', $user->company->plan->price)->exists())
                    <button type="button" @click="$dispatch('upgrade', '_next')" class="hidden items-center gap-1.5 rounded-full bg-gradient-to-r from-brand-600 to-brand-500 px-3 py-1.5 text-xs font-bold text-white shadow-sm hover:from-brand-700 sm:flex">
                        <x-icon name="bolt" class="size-4" /> {{ __('ترقية الباقة') }}
                    </button>
                @endif
                <span class="hidden text-sm text-slate-500 xl:block">{{ now()->locale(app()->getLocale())->translatedFormat(__('l، j F Y')) }}</span>
                <x-notification-bell />
                <x-preference-switches />
                <div class="relative">
                    <button @click="open=!open" class="flex items-center gap-2 rounded-xl px-2 py-1.5 hover:bg-slate-100">
                        <span class="grid size-9 place-items-center rounded-full bg-brand-100 font-bold text-brand-800">{{ mb_substr($user->name, 0, 1) }}</span>
                        <span class="hidden text-start sm:block">
                            <span class="block text-sm font-semibold">{{ $user->name }}</span>
                            <span class="block text-xs text-slate-500">{{ $user->role->label() }}</span>
                        </span>
                    </button>
                    <div x-show="open" x-cloak @click.outside="open=false" class="card absolute end-0 top-14 w-52 p-2">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-rose-600 hover:bg-rose-50"><x-icon name="logout" class="size-4" /> {{ __('تسجيل الخروج') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 px-4 py-8 lg:px-8">
            @foreach(['success' => 'bg-emerald-50 text-emerald-800 ring-emerald-600/20', 'error' => 'bg-rose-50 text-rose-800 ring-rose-600/20'] as $key => $style)
                @if(session($key))
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 7000)" class="mb-6 flex items-center justify-between rounded-xl px-4 py-3 text-sm ring-1 ring-inset {{ $style }}">
                        <span>{{ session($key) }}</span>
                        <button @click="show=false" class="opacity-60 hover:opacity-100">✕</button>
                    </div>
                @endif
            @endforeach
            @if($errors->any())
                <div class="mb-6 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-800 ring-1 ring-inset ring-rose-600/20">
                    <ul class="list-inside list-disc space-y-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            {{ $slot }}
        </main>
    </div>
</div>
<x-upgrade-modal />
</body>
</html>
