<x-layouts.guest :title="__('التحويل البنكي')">
    <div class="mx-auto max-w-2xl">
        <div class="card p-8 text-center">
            <span class="mx-auto grid size-16 place-items-center rounded-2xl bg-amber-50 text-amber-600"><x-icon name="clock" class="size-8" /></span>
            <h1 class="mt-5 text-2xl font-bold text-ink">{{ __('بانتظار التحويل البنكي') }}</h1>
            <p class="mt-2 text-slate-500">{{ __('حوّل المبلغ إلى الحساب التالي واكتب رقم المرجع في خانة الملاحظات. سنفعّل حسابك فور تأكيد التحويل.') }}</p>

            <dl class="mt-8 divide-y divide-slate-100 rounded-2xl bg-slate-50 text-start text-sm">
                @foreach([
                    __('المبلغ') => number_format((float) $subscription->amount, 2).' AED',
                    __('رقم المرجع') => 'TAH-'.str_pad((string) $subscription->id, 5, '0', STR_PAD_LEFT),
                    __('البنك') => $bank['name'],
                    __('اسم الحساب') => $bank['account'],
                    'IBAN' => $bank['iban'],
                    __('الباقة') => $subscription->plan->localizedName().' · '.\App\Models\Plan::cycleLabel($subscription->cycle),
                ] as $label => $value)
                    <div class="flex items-center justify-between px-5 py-3.5"><dt class="text-slate-500">{{ __($label) }}</dt><dd class="num font-semibold text-ink" dir="ltr">{{ $value }}</dd></div>
                @endforeach
            </dl>
            <p class="mt-6 text-sm text-slate-500">{{ __('ستصلك رسالة على :email عند التفعيل.', ['email' => $subscription->company->email]) }}</p>
            <a href="{{ route('landing') }}" class="btn-secondary mt-6">{{ __('العودة للرئيسية') }}</a>
        </div>
    </div>
</x-layouts.guest>
