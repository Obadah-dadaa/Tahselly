@php
    $planData = $plans->map(fn ($p) => ['code' => $p->code, 'name' => $p->localizedName(), 'category' => \App\Models\Plan::categoryLabel($p->category), 'price' => (int) $p->price])->values();
@endphp
<x-layouts.guest :title="__('ابدأ تجربتك المجانية')">
    <form method="POST" action="{{ route('trial.store') }}" class="mx-auto grid max-w-5xl gap-8 lg:grid-cols-5"
          x-data="{ plan: '{{ old('plan', $selected) }}', plans: @js($planData), get current() { return this.plans.find(p => p.code === this.plan) } }">
        @csrf
        <div class="lg:col-span-3">
            <h1 class="text-3xl font-bold text-ink">{{ __('ابدأ تجربتك المجانية') }}</h1>
            <p class="mt-2 text-slate-500">{{ __('بدون بطاقة ائتمان. كل المزايا مفتوحة طوال فترة التجربة.') }}</p>

            {{-- The trial notice: free for a limited time, paid afterwards --}}
            <div class="mt-6 rounded-2xl border-2 border-amber-300 bg-amber-50 p-5 text-amber-900">
                <p class="flex items-center gap-2 text-lg font-bold"><x-icon name="clock" class="size-5" /> {{ __('مجاناً لمدة :days يوماً فقط', ['days' => $days]) }}</p>
                <p class="mt-2 text-sm leading-relaxed">
                    {{ __('بعد انتهاء التجربة يتوقف الوصول إلى حسابك حتى تختار باقة وتدفع اشتراكها. لن نخصم أي مبلغ تلقائياً — أنت من يقرر الاشتراك.') }}
                </p>
                <p class="mt-3 text-sm font-semibold" x-show="current">
                    {{ __('سعر الباقة المختارة بعد التجربة:') }} <span class="num" x-text="current?.price"></span> {{ __('درهم / شهرياً') }}
                </p>
                <p class="mt-2 text-xs">{{ __('سنذكّرك بالبريد قبل انتهاء التجربة بـ 3 أيام وفي يومها الأخير.') }}</p>
            </div>

            <div class="card mt-6 grid gap-4 p-6 sm:grid-cols-2">
                <div class="sm:col-span-2"><label class="label">{{ __('اسم الشركة / النشاط') }}</label><input name="company_name" value="{{ old('company_name') }}" required class="input"></div>
                <div><label class="label">{{ __('اسمك') }}</label><input name="name" value="{{ old('name') }}" required class="input"></div>
                <div><label class="label">{{ __('رقم الجوال') }}</label><input name="phone" value="{{ old('phone') }}" required class="input" dir="ltr" placeholder="05XXXXXXXX"></div>
                <div class="sm:col-span-2"><label class="label">{{ __('البريد الإلكتروني') }}</label><input type="email" name="email" value="{{ old('email') }}" required class="input" dir="ltr"></div>
                <div><label class="label">{{ __('كلمة المرور') }}</label><input type="password" name="password" required minlength="8" class="input" dir="ltr"></div>
                <div><label class="label">{{ __('تأكيد كلمة المرور') }}</label><input type="password" name="password_confirmation" required class="input" dir="ltr"></div>
            </div>
        </div>

        <aside class="lg:col-span-2">
            <div class="card sticky top-6 p-6">
                <h2 class="font-semibold text-ink">{{ __('اختر الباقة التي تناسبك') }}</h2>
                <p class="mt-1 text-xs text-slate-500">{{ __('تجرّبها مجاناً الآن، ويمكنك تغييرها لاحقاً.') }}</p>
                <div class="mt-4 space-y-2">
                    @foreach($plans as $plan)
                        <label class="flex cursor-pointer items-center justify-between rounded-2xl border-2 p-3 transition" :class="plan === '{{ $plan->code }}' ? 'border-brand-600 bg-brand-50' : 'border-slate-200'">
                            <input type="radio" name="plan" value="{{ $plan->code }}" x-model="plan" class="sr-only">
                            <span><span class="block font-semibold text-ink">{{ $plan->localizedName() }}</span><span class="text-xs text-slate-500">{{ \App\Models\Plan::categoryLabel($plan->category) }}</span></span>
                            <span class="num text-end text-sm font-bold">{{ (int) $plan->price }}<span class="block text-[11px] font-normal text-slate-400">{{ __('بعد التجربة') }}</span></span>
                        </label>
                    @endforeach
                </div>
                <label class="mt-5 flex items-start gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="terms" value="1" required class="mt-1 rounded text-brand-600">
                    <span>{!! __('أفهم أن التجربة مجانية لمدة :days يوماً فقط وأن الاستمرار بعدها يتطلب اشتراكاً مدفوعاً، وأوافق على :terms و:privacy.', ['days' => $days, 'terms' => '<a href="'.route('legal.terms').'" target="_blank" class="font-semibold text-brand-700 underline">'.e(__('شروط الاستخدام')).'</a>', 'privacy' => '<a href="'.route('legal.privacy').'" target="_blank" class="font-semibold text-brand-700 underline">'.e(__('سياسة الخصوصية')).'</a>']) !!}</span>
                </label>
                <button class="btn-primary mt-5 w-full py-3 text-base">{{ __('ابدأ التجربة المجانية') }}</button>
                <p class="mt-3 text-center text-xs text-slate-500">{{ __('تريد الاشتراك مباشرة؟') }} <a href="{{ route('landing') }}#pricing" class="font-semibold text-brand-700">{{ __('اختر باقة وادفع') }}</a></p>
            </div>
        </aside>
    </form>
</x-layouts.guest>
