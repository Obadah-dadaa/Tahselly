@php
    $prices = collect(array_keys(\App\Models\Subscription::CYCLE_MONTHS))->mapWithKeys(fn ($c) => [$c => $plan->priceFor($c)]);
@endphp
<x-layouts.guest :title="__('الاشتراك')">
    <div class="mb-8">
        <a href="{{ route('landing') }}#pricing" class="text-sm font-semibold text-brand-700 hover:underline">← {{ __('كل الباقات') }}</a>
        <h1 class="mt-3 text-3xl font-bold text-ink">{{ $renewing ? __('تجديد أو تغيير الاشتراك') : __('أنشئ حسابك وابدأ الآن') }}</h1>
        <p class="mt-2 text-slate-500">{{ __('ادفع بأمان، وسيتم تفعيل حسابك فوراً بعد الدفع.') }}</p>
    </div>

    <form method="POST" action="{{ route('subscribe.store', $plan) }}" class="grid gap-8 lg:grid-cols-5"
          x-data="{ cycle: '{{ old('cycle', $cycle) }}', method: '{{ old('payment_method', $methods[0]) }}', prices: @js($prices) }">
        @csrf
        <div class="space-y-6 lg:col-span-3">
            <section class="card p-6">
                <h2 class="font-semibold text-ink">{{ __('١. مدة الاشتراك') }}</h2>
                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                    @foreach(\App\Models\Subscription::CYCLE_MONTHS as $c => $months)
                        @php $available = in_array($c, $allowed, true); @endphp
                        <label class="relative rounded-2xl border-2 p-4 transition {{ $available ? 'cursor-pointer' : 'cursor-not-allowed opacity-40' }}" :class="cycle==='{{ $c }}' ? 'border-brand-600 bg-brand-50' : 'border-slate-200 {{ $available ? 'hover:border-slate-300' : '' }}'"
                               @unless($available) title="{{ __('غير متاح: اختر مدة أطول من اشتراكك الحالي') }}" @endunless>
                            <input type="radio" name="cycle" value="{{ $c }}" x-model="cycle" class="sr-only" @disabled(! $available)>
                            <span class="block font-semibold text-ink">{{ \App\Models\Plan::cycleLabel($c) }}</span>
                            <span class="num mt-1 block text-xl font-bold">{{ number_format($prices[$c]) }} <span class="text-sm font-medium text-slate-400">AED</span></span>
                            <span class="mt-1 block text-xs text-slate-500">
                                @if($c === 'yearly') {{ __('شهران مجاناً') }} @elseif($c === 'quarterly') {{ __('خصم 5%') }} @else {{ __('ادفع شهرياً') }} @endif
                            </span>
                            @if($c === 'yearly')<span class="absolute -top-2.5 end-3 rounded-full bg-brand-600 px-2 py-0.5 text-[11px] font-bold text-white">{{ __('الأوفر') }}</span>@endif
                        </label>
                    @endforeach
                </div>
            </section>

            @unless($renewing)
                <section class="card p-6">
                    <h2 class="font-semibold text-ink">{{ __('٢. بيانات الحساب') }}</h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2"><label class="label">{{ __('اسم الشركة / النشاط') }}</label><input name="company_name" value="{{ old('company_name') }}" required class="input"></div>
                        <div><label class="label">{{ __('اسمك') }}</label><input name="name" value="{{ old('name') }}" required class="input"></div>
                        <x-phone-input />
                        <div class="sm:col-span-2"><label class="label">{{ __('البريد الإلكتروني') }}</label><input type="email" name="email" value="{{ old('email') }}" required class="input" dir="ltr"></div>
                        <div><label class="label">{{ __('كلمة المرور') }}</label><input type="password" name="password" required minlength="8" class="input" dir="ltr"></div>
                        <div><label class="label">{{ __('تأكيد كلمة المرور') }}</label><input type="password" name="password_confirmation" required class="input" dir="ltr"></div>
                    </div>
                </section>
            @endunless

            <section class="card p-6">
                <h2 class="font-semibold text-ink">{{ $renewing ? __('٢. طريقة الدفع') : __('٣. طريقة الدفع') }}</h2>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    @if(in_array('card', $methods, true))
                        <label class="cursor-pointer rounded-2xl border-2 p-4 transition" :class="method==='card' ? 'border-brand-600 bg-brand-50' : 'border-slate-200'">
                            <input type="radio" name="payment_method" value="card" x-model="method" class="sr-only">
                            <span class="flex items-center gap-2 font-semibold text-ink"><x-icon name="cash" class="size-5 text-brand-700" /> {{ __('بطاقة ائتمان / مدى') }}</span>
                            <span class="mt-1 block text-xs text-slate-500">{{ __('Visa · Mastercard · Apple Pay — تفعيل فوري') }}</span>
                        </label>
                    @endif
                    <label class="cursor-pointer rounded-2xl border-2 p-4 transition" :class="method==='bank_transfer' ? 'border-brand-600 bg-brand-50' : 'border-slate-200'">
                        <input type="radio" name="payment_method" value="bank_transfer" x-model="method" class="sr-only">
                        <span class="flex items-center gap-2 font-semibold text-ink"><x-icon name="building" class="size-5 text-brand-700" /> {{ __('تحويل بنكي') }}</span>
                        <span class="mt-1 block text-xs text-slate-500">{{ __('يتم التفعيل بعد تأكيد التحويل خلال يوم عمل') }}</span>
                    </label>
                </div>
                @if($sandbox)
                    <p class="mt-4 rounded-xl bg-amber-50 px-4 py-3 text-xs text-amber-800">{{ __('وضع الاختبار: الدفع بالبطاقة هنا تجريبي ولا يتم خصم أي مبلغ. أضف مفتاح Stripe لتفعيل الدفع الحقيقي.') }}</p>
                @endif
            </section>
        </div>

        <aside class="lg:col-span-2">
            <div class="card sticky top-6 overflow-hidden">
                <div class="bg-night p-6 text-white">
                    <p class="text-sm text-brand-200">{{ \App\Models\Plan::categoryLabel($plan->category) }}</p>
                    <h3 class="mt-1 text-2xl font-bold">{{ $plan->localizedName() }}</h3>
                    <p class="num mt-4 text-4xl font-bold"><span x-text="Number(prices[cycle]).toLocaleString('en-US')"></span> <span class="text-base font-medium text-slate-400">AED</span></p>
                    <p class="text-sm text-slate-400" x-text="{ monthly: @js(__('لكل شهر')), quarterly: @js(__('لكل 3 أشهر')), yearly: @js(__('لكل سنة')) }[cycle]"></p>
                </div>
                <ul class="space-y-2.5 p-6 text-sm">
                    @foreach($plan->localizedFeatures() as $feature)
                        <li class="flex items-center gap-2"><x-icon name="check" class="size-4 text-brand-600" /> {{ $feature }}</li>
                    @endforeach
                </ul>
                <div class="border-t border-slate-100 p-6">
                    @if($trial && $trial->trial_ends_at)
                        <p class="mb-3 rounded-xl bg-amber-50 p-3 text-xs text-amber-900">{{ __('متبقٍ لك :days يوم من التجربة المجانية. عند الدفع تتحول تجربتك فوراً إلى باقة :plan.', ['days' => max(0, (int) today()->diffInDays($trial->trial_ends_at, false)), 'plan' => $plan->localizedName()]) }}</p>
                    @endif
                    @if($current && $upgrading)
                        <p class="mb-3 rounded-xl bg-brand-50 p-3 text-xs text-brand-800">{{ __('الترقية تبدأ فوراً بعد الدفع، والأيام المتبقية من باقتك الحالية تُضاف لمدتك الجديدة حسب قيمتها.') }}</p>
                    @elseif($current)
                        <p class="mb-3 rounded-xl bg-slate-50 p-3 text-xs text-slate-500">{{ __('اشتراكك الحالي ينتهي في :date — المدة الجديدة تبدأ بعده.', ['date' => $current->ends_at->format('Y-m-d')]) }}</p>
                    @endif
                    @unless($renewing)
                        <label class="mb-4 flex items-start gap-2 text-sm text-slate-600"><input type="checkbox" name="terms" value="1" required class="mt-1 rounded text-brand-600"> {!! __('أوافق على :terms و:privacy', ['terms' => '<a href="'.route('legal.terms').'" target="_blank" class="font-semibold text-brand-700 underline">'.e(__('شروط الاستخدام')).'</a>', 'privacy' => '<a href="'.route('legal.privacy').'" target="_blank" class="font-semibold text-brand-700 underline">'.e(__('سياسة الخصوصية')).'</a>']) !!}</label>
                    @endunless
                    <x-recaptcha action="checkout" />
                    <button class="btn-primary w-full py-3 text-base">
                        <span x-show="method==='card'">{{ __('ادفع الآن') }}</span>
                        <span x-show="method==='bank_transfer'" x-cloak>{{ __('متابعة — تعليمات التحويل') }}</span>
                    </button>
                    <p class="mt-3 text-center text-xs text-slate-400">🔒 {{ __('دفع آمن ومشفّر') }}</p>
                </div>
            </div>
        </aside>
    </form>
</x-layouts.guest>
