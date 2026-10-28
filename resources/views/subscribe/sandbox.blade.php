<x-layouts.guest :title="__('الدفع')">
    <div class="mx-auto max-w-md">
        <div class="mb-4 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <b>{{ __('بوابة دفع تجريبية') }}</b> — {{ __('استخدم البطاقة 4242 4242 4242 4242 بأي تاريخ ورمز. البطاقة 4000 0000 0000 0002 تُرفض.') }}
        </div>
        <form method="POST" action="{{ route('subscribe.sandbox.pay', $subscription) }}" class="card space-y-5 p-6" x-data="{ busy: false }" @submit="busy = true">
            @csrf
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div>
                    <p class="text-sm text-slate-500">{{ $subscription->company->name }}</p>
                    <p class="font-semibold text-ink">{{ $subscription->plan->localizedName() }} · {{ \App\Models\Plan::cycleLabel($subscription->cycle) }}</p>
                </div>
                <p class="num text-2xl font-bold">{{ number_format((float) $subscription->amount, 2) }} <span class="text-sm text-slate-400">AED</span></p>
            </div>
            <div><label class="label">{{ __('رقم البطاقة') }}</label><input name="card_number" required inputmode="numeric" class="input num tracking-widest" dir="ltr" placeholder="4242 4242 4242 4242"></div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="label">{{ __('تاريخ الانتهاء') }}</label><input name="expiry" required class="input num" dir="ltr" placeholder="12/28"></div>
                <div><label class="label">CVC</label><input name="cvc" required inputmode="numeric" maxlength="4" class="input num" dir="ltr" placeholder="123"></div>
            </div>
            <button class="btn-primary w-full py-3 text-base" :disabled="busy">
                <span x-show="!busy">{{ __('ادفع') }} {{ number_format((float) $subscription->amount, 2) }} AED</span>
                <span x-show="busy" x-cloak>{{ __('جاري المعالجة…') }}</span>
            </button>
        </form>
    </div>
</x-layouts.guest>
