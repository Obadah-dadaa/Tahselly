<x-layouts.guest :title="__('ابدأ تجربتك المجانية')">
    <form method="POST" action="{{ route('trial.store') }}" class="mx-auto grid max-w-5xl gap-8 lg:grid-cols-5">
        @csrf
        @if($selected)<input type="hidden" name="plan" value="{{ $selected->code }}">@endif

        <div class="lg:col-span-3">
            <h1 class="text-3xl font-bold text-ink">{{ __('ابدأ تجربتك المجانية') }}</h1>
            <p class="mt-2 text-slate-500">{{ __('أنشئ حسابك في دقيقة. بدون بطاقة ائتمان.') }}</p>

            <div class="card mt-6 grid gap-4 p-6 sm:grid-cols-2">
                <div class="sm:col-span-2"><label class="label">{{ __('اسم الشركة / النشاط') }}</label><input name="company_name" value="{{ old('company_name') }}" required class="input"></div>
                <div><label class="label">{{ __('اسمك') }}</label><input name="name" value="{{ old('name') }}" required class="input"></div>
                <x-phone-input />
                <div class="sm:col-span-2"><label class="label">{{ __('البريد الإلكتروني') }}</label><input type="email" name="email" value="{{ old('email') }}" required class="input" dir="ltr"></div>
                <div><label class="label">{{ __('كلمة المرور') }}</label><input type="password" name="password" required minlength="8" class="input" dir="ltr"></div>
                <div><label class="label">{{ __('تأكيد كلمة المرور') }}</label><input type="password" name="password_confirmation" required class="input" dir="ltr"></div>

                <label class="flex items-start gap-2 text-sm text-slate-600 sm:col-span-2">
                    <input type="checkbox" name="terms" value="1" required class="mt-1 rounded text-brand-600">
                    <span>{!! __(\App\Support\Edition::isBeta() ? 'أفهم أن هذه نسخة تجريبية مجانية لمدة :days يوماً بحدود استخدام، وأوافق على :terms و:privacy.' : 'أفهم أن التجربة مجانية لمدة :days يوماً فقط وأن الاستمرار بعدها يتطلب اشتراكاً مدفوعاً، وأوافق على :terms و:privacy.', ['days' => $days, 'terms' => '<a href="'.route('legal.terms').'" target="_blank" class="font-semibold text-brand-700 underline">'.e(__('شروط الاستخدام')).'</a>', 'privacy' => '<a href="'.route('legal.privacy').'" target="_blank" class="font-semibold text-brand-700 underline">'.e(__('سياسة الخصوصية')).'</a>']) !!}</span>
                </label>
                <x-recaptcha action="signup" class="sm:col-span-2" />
                <button class="btn-primary w-full py-3 text-base sm:col-span-2">{{ __('ابدأ التجربة المجانية') }}</button>
                <p class="text-center text-xs text-slate-500 sm:col-span-2">{{ __('لديك حساب؟') }} <a href="{{ route('login') }}" class="font-semibold text-brand-700">{{ __('سجّل الدخول') }}</a></p>
            </div>
        </div>

        <aside class="space-y-6 lg:col-span-2">
            {{-- The trial notice: free for a limited time, paid afterwards --}}
            <div class="rounded-2xl border-2 border-amber-300 bg-amber-50 p-5 text-amber-900">
                <p class="flex items-center gap-2 text-lg font-bold"><x-icon name="clock" class="size-5" /> {{ __('جرّبه مجاناً لمدة :days يوماً', ['days' => $days]) }}</p>
                @beta
                    <p class="mt-2 text-sm leading-relaxed">{{ __('هذه نسخة تجريبية مجانية بالكامل: كل المزايا مفتوحة بحدود استخدام للتجربة، ولا يوجد أي دفع. إذا أعجبك النظام، اطلب الاشتراك من داخل حسابك.') }}</p>
                @else
                    <p class="mt-2 text-sm leading-relaxed">{{ __('كل المزايا مفتوحة طوال التجربة. بعدها تختار الباقة التي تناسبك وتدفع للاستمرار — لن نخصم أي مبلغ تلقائياً.') }}</p>
                @endbeta
                <p class="mt-2 text-xs">{{ __('سنذكّرك بالبريد قبل انتهاء التجربة بـ 3 أيام وفي يومها الأخير.') }}</p>
                @if($selected)
                    <p class="mt-3 rounded-xl bg-white/60 px-3 py-2 text-sm">{{ __('الباقة المختارة: :plan — :price درهم / شهرياً بعد التجربة.', ['plan' => $selected->localizedName(), 'price' => number_format((float) $selected->price)]) }}</p>
                @endif
            </div>

            <div class="card p-6">
                <h2 class="font-semibold text-ink">{{ __('ماذا تستطيع أن تفعل خلال التجربة') }}</h2>
                <x-trial-includes class="mt-4" />
            </div>
        </aside>
    </form>
</x-layouts.guest>
