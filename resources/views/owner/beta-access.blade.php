{{-- Beta: the account's access, its limits, and the "interested in subscribing" request (no plans or prices here).
     Shown as the welcome page after sign-up ($welcome) and as the account's "Subscription" page. --}}
@php
    $daysLeft = $company->trial_ends_at ? max(0, (int) today()->diffInDays($company->trial_ends_at, false)) : null;
    $expired = ! $company->hasAccess();
    $requested = \App\Models\InterestRequest::query()->where('company_id', $company->id)->latest()->first();
@endphp
<x-layouts.guest :title="__('النسخة التجريبية')">
    <div class="mx-auto max-w-4xl">
        <div class="text-center">
            <span class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">BETA</span>
            <h1 class="mt-3 text-3xl font-bold text-ink">
                @if($welcome) {{ __('أهلاً بك في النسخة التجريبية من :app', ['app' => __(\App\Models\SystemSetting::get('app_name', 'تحصيلي'))]) }}
                @elseif($expired) {{ __('انتهت فترة النسخة التجريبية') }}
                @else {{ __('حسابك في النسخة التجريبية') }}
                @endif
            </h1>
            <p class="mx-auto mt-3 max-w-2xl text-slate-600">{{ __('هذه نسخة مجانية للتجربة فقط، بكل المزايا وبحدود استخدام محدودة. لا يوجد دفع داخل النسخة التجريبية.') }}</p>
            @if(! $expired && $daysLeft !== null)
                <p class="mt-4 inline-flex items-baseline gap-2 rounded-2xl bg-surface px-5 py-2 shadow-sm ring-1 ring-slate-200">
                    <span class="num text-2xl font-bold text-brand-700">{{ $daysLeft }}</span><span class="text-sm text-slate-600">{{ __('يوم متبقٍ · حتى :date', ['date' => $company->trial_ends_at->format('Y-m-d')]) }}</span>
                </p>
            @endif
        </div>

        <div class="mt-10 grid gap-6 lg:grid-cols-5">
            <div class="card p-6 lg:col-span-2">
                <h2 class="font-semibold text-ink">{{ __('حدود النسخة التجريبية') }}</h2>
                <x-beta-limits :company="$company" class="mt-4 !grid-cols-1" />
            </div>
            <div class="card p-6 lg:col-span-3">
                <h2 class="text-xl font-bold text-ink">{{ __('مهتم بالاشتراك؟') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('اترك بياناتك وسنتواصل معك لتفعيل حسابك دون حدود.') }}</p>
                @if($requested)
                    <p class="mt-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ __('أرسلت طلباً بتاريخ :date، وسنتواصل معك قريباً.', ['date' => $requested->created_at->format('Y-m-d')]) }}</p>
                @endif
                <x-interest-form class="mt-5" />
            </div>
        </div>

        @if($welcome && ! $expired)
            <div class="mt-8 text-center">
                <a href="{{ route('dashboard') }}" class="btn-primary px-8 py-3 text-base">{{ __('ابدأ التجربة') }} ←</a>
            </div>
        @endif
    </div>
</x-layouts.guest>
