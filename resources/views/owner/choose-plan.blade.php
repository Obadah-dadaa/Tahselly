{{-- Full-page trial welcome (no sidebar): the 14-day access, what it includes, and upgrade-only plans. --}}
@php
    $trialEnds = $company->trial_ends_at;
    $daysLeft = $trialEnds ? max(0, (int) today()->diffInDays($trialEnds, false)) : null;
@endphp
<x-layouts.guest :title="__('تجربتك المجانية')">
    <div class="mx-auto max-w-6xl space-y-10">
        {{-- Trial access --}}
        <section class="overflow-hidden rounded-3xl bg-night text-white shadow-xl">
            <div class="grid gap-8 p-8 md:grid-cols-3 md:p-10">
                <div class="md:col-span-2">
                    <span class="inline-flex items-center gap-2 rounded-full bg-amber-400/15 px-3 py-1 text-sm font-semibold text-amber-300"><x-icon name="clock" class="size-4" /> {{ __('تجربة مجانية') }}</span>
                    <h1 class="mt-4 text-3xl font-bold leading-tight md:text-4xl">{{ __('أهلاً :name! لديك وصول كامل لمدة :days يوماً', ['name' => auth()->user()->name, 'days' => $totalDays]) }}</h1>
                    <p class="mt-3 max-w-xl text-slate-300">{{ __('كل المزايا مفتوحة لك الآن بدون بطاقة. جرّب النظام بفواتيرك الحقيقية، واختر باقتك عندما تكون جاهزاً.') }}</p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ route('dashboard') }}" class="btn-primary bg-brand-500 px-6 py-3 text-base">{{ __('تخطَّ وجرّب أولاً') }} →</a>
                        <a href="#plans" class="rounded-xl px-5 py-3 font-semibold text-white ring-1 ring-white/20 hover:bg-white/10">{{ __('عرض الباقات') }}</a>
                    </div>
                </div>
                @if($trialEnds)
                    <div class="flex flex-col items-center justify-center rounded-2xl bg-white/5 p-6 text-center ring-1 ring-white/10">
                        <p class="num text-6xl font-bold text-brand-300">{{ $daysLeft }}</p>
                        <p class="mt-1 font-semibold">{{ __('يوم متبقٍ في تجربتك') }}</p>
                        <p class="mt-2 text-sm text-slate-400">{{ __('تنتهي في :date', ['date' => $trialEnds->format('Y-m-d')]) }}</p>
                    </div>
                @endif
            </div>
        </section>

        {{-- What the trial includes --}}
        <section>
            <h2 class="text-xl font-bold text-ink">{{ __('ماذا تستطيع أن تفعل خلال التجربة') }}</h2>
            <x-trial-includes :columns="true" class="mt-4" />
        </section>

        {{-- Plans: same or higher than the one already chosen --}}
        <x-founding-offer />

        <section id="plans" class="scroll-mt-6">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="text-xl font-bold text-ink">{{ __('اختر باقتك') }}</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        @if($daysLeft !== null)
                            {{ __('متبقٍ لك :days يوم من التجربة. عند الاشتراك تتحول تجربتك فوراً إلى الباقة التي تختارها.', ['days' => $daysLeft]) }}
                        @endif
                        @if($company->plan) {{ __('تظهر لك الترقيات فقط.') }} @endif
                    </p>
                </div>
            </div>

            <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 {{ $plans->count() >= 5 ? 'xl:grid-cols-5' : 'xl:grid-cols-'.max(1, $plans->count()) }}">
                @foreach($plans as $plan)
                    @php $isCurrent = $company->plan_id === $plan->id; @endphp
                    <div class="relative flex flex-col rounded-3xl p-6 {{ $plan->is_featured ? 'bg-night text-white shadow-2xl ring-4 ring-brand-500/40' : 'card' }}">
                        @if($isCurrent)
                            <span class="absolute inset-x-0 -top-3 mx-auto w-fit rounded-full bg-amber-500 px-4 py-1 text-xs font-bold text-white shadow">{{ __('باقتك المختارة') }}</span>
                        @elseif($plan->is_featured)
                            <span class="absolute inset-x-0 -top-3 mx-auto w-fit rounded-full bg-brand-500 px-4 py-1 text-xs font-bold text-white shadow">{{ __('الأكثر طلباً') }}</span>
                        @endif
                        <h3 class="text-xl font-bold">{{ $plan->localizedName() }}</h3>
                        <p class="mt-1 min-h-10 text-sm {{ $plan->is_featured ? 'text-slate-300' : 'text-slate-500' }}">{{ $plan->audience() }}</p>
                        <p class="mt-4"><x-plan-price :plan="$plan" /> <span class="text-sm {{ $plan->is_featured ? 'text-slate-400' : 'text-slate-500' }}">{{ __('درهم / شهرياً') }}</span></p>
                        <ul class="mt-5 flex-1 space-y-2 text-sm">
                            <li class="flex items-start gap-2 font-semibold"><x-icon name="check" class="mt-0.5 size-4 shrink-0 text-brand-500" /> {{ $plan->max_users == 1 ? __('مستخدم واحد') : __('حتى :count مستخدمين', ['count' => $plan->max_users]) }}</li>
                            <li class="flex items-start gap-2 font-semibold"><x-icon name="check" class="mt-0.5 size-4 shrink-0 text-brand-500" /> {{ __('حتى :count فاتورة مفتوحة', ['count' => number_format($plan->max_invoices)]) }}</li>
                            @foreach($plan->localizedFeatures() as $feature)
                                <li class="flex items-start gap-2"><x-icon name="check" class="mt-0.5 size-4 shrink-0 text-brand-500" /> {{ $feature }}</li>
                            @endforeach
                        </ul>
                        <a href="{{ route('subscribe.show', ['plan' => $plan->code, 'cycle' => 'monthly']) }}" class="mt-6 w-full {{ $plan->is_featured ? 'btn-primary bg-brand-500' : 'btn-primary' }}">{{ __('اشترك الآن') }}</a>
                        @unless($isCurrent)
                            <form method="POST" action="{{ route('owner.plan.choose') }}" class="mt-2 text-center">
                                @csrf
                                <input type="hidden" name="plan" value="{{ $plan->code }}">
                                <button class="text-xs font-semibold underline {{ $plan->is_featured ? 'text-brand-200' : 'text-brand-700' }}">{{ __('أو اخترها لما بعد التجربة') }}</button>
                            </form>
                        @endunless
                    </div>
                @endforeach
            </div>
            <p class="mt-6 text-center text-sm text-slate-500">{{ __('كل باقة تشمل مزايا الباقات الأرخص منها.') }}</p>
        </section>

        <div class="pb-6 text-center">
            <a href="{{ route('dashboard') }}" class="btn-secondary px-6">{{ __('تخطَّ وجرّب أولاً') }} →</a>
        </div>
    </div>
</x-layouts.guest>
