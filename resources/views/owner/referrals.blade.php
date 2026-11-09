@php
    $share = __('أستخدم تحصيلي لمتابعة الفواتير المتأخرة وتذكير العملاء بالدفع تلقائياً. جرّبه مجاناً :days يوماً من رابطي:', ['days' => $trialDays]).' '.$link;
@endphp
<x-layouts.app :title="__('ادعُ شركة واربح شهراً')">
    <x-page-header :title="__('ادعُ شركة واربح شهراً')" :subtitle="__('شارك رابطك مع شركات تعرفها. لكل شركة تشترك عن طريقه، تحصل على :days يوماً مجاناً.', ['days' => $rewardDays])" />

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card p-6 lg:col-span-2" x-data="{ copied: false }">
            <h2 class="font-semibold text-ink">{{ __('رابطك الخاص') }}</h2>
            <div class="mt-3 flex flex-wrap gap-2">
                <input readonly value="{{ $link }}" dir="ltr" class="input min-w-0 flex-1 font-mono text-sm" @focus="$event.target.select()">
                <button type="button" class="btn-primary" @click="navigator.clipboard.writeText(@js($link)).catch(() => {}); copied = true; setTimeout(() => copied = false, 1500)">
                    <span x-text="copied ? @js(__('تم النسخ ✓')) : @js(__('نسخ الرابط'))"></span>
                </button>
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
                <a href="https://wa.me/?text={{ rawurlencode($share) }}" target="_blank" rel="noopener" class="btn-whatsapp"><x-icon name="whatsapp" class="size-4" /> {{ __('مشاركة على واتساب') }}</a>
                <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ rawurlencode($link) }}" target="_blank" rel="noopener" class="btn-secondary">{{ __('مشاركة على لينكد إن') }}</a>
                <a href="mailto:?subject={{ rawurlencode(__('جرّب تحصيلي لمتابعة الفواتير')) }}&body={{ rawurlencode($share) }}" class="btn-secondary">{{ __('إرسال بالبريد') }}</a>
            </div>

            <h3 class="mt-8 font-semibold text-ink">{{ __('كيف يعمل؟') }}</h3>
            <ol class="mt-3 space-y-3 text-sm text-slate-600">
                <li class="flex gap-3"><span class="grid size-7 shrink-0 place-items-center rounded-full bg-brand-50 font-bold text-brand-700">1</span> {{ __('ترسل رابطك لصاحب شركة أو محاسب تعرفه.') }}</li>
                <li class="flex gap-3"><span class="grid size-7 shrink-0 place-items-center rounded-full bg-brand-50 font-bold text-brand-700">2</span> {{ __('يحصل على تجربة مجانية لمدة :days يوماً بدلاً من المدة العادية.', ['days' => $trialDays]) }}</li>
                <li class="flex gap-3"><span class="grid size-7 shrink-0 place-items-center rounded-full bg-brand-50 font-bold text-brand-700">3</span> {{ __('عندما يدفع أول اشتراك له، نضيف :days يوماً مجاناً لاشتراكك تلقائياً.', ['days' => $rewardDays]) }}</li>
            </ol>
        </div>

        <div class="space-y-4">
            <x-kpi :label="__('شركات سجّلت من رابطك')" :value="$referrals->count()" icon="users" tone="sky" />
            <x-kpi :label="__('أيام مجانية حصلت عليها')" :value="$referrals->whereNotNull('referral_rewarded_at')->count() * $rewardDays" icon="check" />
            @if($creditDays)
                <p class="rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800 ring-1 ring-amber-600/20">{{ __('لديك :days يوماً محفوظة، تُضاف تلقائياً عند دفع اشتراكك القادم.', ['days' => $creditDays]) }}</p>
            @endif
        </div>
    </div>

    @if($referrals->isNotEmpty())
        <div class="card mt-6 overflow-hidden">
            <h2 class="border-b border-slate-100 px-5 py-4 font-semibold text-ink">{{ __('الشركات التي سجّلت من رابطك') }}</h2>
            <table class="table-base">
                <thead><tr><th>{{ __('الشركة') }}</th><th>{{ __('تاريخ التسجيل') }}</th><th>{{ __('الحالة') }}</th></tr></thead>
                <tbody>
                @foreach($referrals as $referral)
                    <tr>
                        <td class="font-semibold">{{ $referral->name }}</td>
                        <td class="num">{{ $referral->created_at->format('Y-m-d') }}</td>
                        <td>
                            @if($referral->referral_rewarded_at)
                                <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">{{ __('اشتركت — حصلت على مكافأتك') }}</span>
                            @else
                                <span class="rounded-full bg-violet-50 px-2.5 py-0.5 text-xs font-semibold text-violet-700 ring-1 ring-inset ring-violet-600/20">{{ __('في التجربة') }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-layouts.app>
