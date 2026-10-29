<x-layouts.app :title="__('الاشتراك والفواتير')">
    <x-page-header :title="__('الاشتراك والفواتير')" :subtitle="__('باقتك الحالية، مدة الاشتراك، وسجل المدفوعات.')" />

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card overflow-hidden lg:col-span-2">
            <div class="bg-night p-6 text-white">
                @if($current)
                    @php $total = max(1, $current->starts_at->diffInDays($current->ends_at)); $used = min($total, $current->starts_at->diffInDays(now())); @endphp
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="text-sm text-brand-200">{{ \App\Models\Plan::categoryLabel($current->plan->category) }}</p>
                            <h2 class="mt-1 text-2xl font-bold">{{ $current->plan->localizedName() }}</h2>
                            <p class="mt-1 text-sm text-slate-400">{{ \App\Models\Plan::cycleLabel($current->cycle) }} · {{ number_format((float) $current->amount, 2) }} AED</p>
                        </div>
                        <span class="rounded-full bg-emerald-500/15 px-3 py-1 text-sm font-semibold text-emerald-300">{{ __('فعّال') }}</span>
                    </div>
                    <div class="mt-6">
                        <div class="flex justify-between text-sm text-slate-300"><span>{{ $current->starts_at->format('Y-m-d') }}</span><span>{{ $current->ends_at->format('Y-m-d') }}</span></div>
                        <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-white/10"><div class="h-full rounded-full bg-brand-500" style="width: {{ $used / $total * 100 }}%"></div></div>
                        <p class="mt-3 text-lg font-semibold">{{ __('متبقٍ :days يوم', ['days' => $current->daysLeft()]) }}</p>
                    </div>
                @elseif($company->status === 'trial')
                    <h2 class="text-2xl font-bold">{{ __('فترة تجريبية') }}</h2>
                    <p class="mt-2 text-slate-300">{{ __('تنتهي في :date. اختر باقة للاستمرار بعدها.', ['date' => $company->trial_ends_at?->format('Y-m-d') ?? '—']) }}</p>
                @else
                    <h2 class="text-2xl font-bold">{{ __('لا يوجد اشتراك فعّال') }}</h2>
                    <p class="mt-2 text-slate-300">{{ __('اختر باقة أدناه لتفعيل حسابك.') }}</p>
                @endif
                @if(! $current && $company->plan)
                    <div class="mt-6 flex flex-wrap items-center justify-between gap-4 rounded-2xl bg-white/10 p-4">
                        <div>
                            <p class="text-sm text-brand-200">{{ __('الباقة التي اخترتها') }}</p>
                            <p class="text-lg font-bold">{{ $company->plan->localizedName() }} — <span class="num">{{ number_format((float) $company->plan->price) }}</span> {{ __('درهم / شهرياً') }}</p>
                        </div>
                        <a href="{{ route('subscribe.show', ['plan' => $company->plan->code, 'cycle' => 'yearly']) }}" class="btn-primary bg-brand-500">{{ __('اشترك بهذه الباقة الآن') }}</a>
                    </div>
                    <p class="mt-2 text-xs text-slate-400">{{ __('أو اختر باقة أخرى من القائمة أدناه.') }}</p>
                @endif
            </div>
            <div class="p-6">
                <h3 class="font-semibold text-ink">{{ __('سجل الاشتراكات والمدفوعات') }}</h3>
                <div class="mt-4 overflow-x-auto">
                    <table class="table-base">
                        <thead><tr><th>{{ __('الباقة') }}</th><th>{{ __('المدة') }}</th><th>{{ __('الفترة') }}</th><th>{{ __('المبلغ') }}</th><th>{{ __('الدفع') }}</th><th>{{ __('الحالة') }}</th></tr></thead>
                        <tbody>
                        @forelse($history as $sub)
                            <tr>
                                <td class="font-medium">{{ $sub->plan->localizedName() }}</td>
                                <td>{{ \App\Models\Plan::cycleLabel($sub->cycle) }}</td>
                                <td class="num text-xs text-slate-500">{{ $sub->starts_at?->format('Y-m-d') ?? '—' }} → {{ $sub->ends_at?->format('Y-m-d') ?? '—' }}</td>
                                <td class="num font-semibold">{{ number_format((float) $sub->amount, 2) }}</td>
                                <td class="text-xs">{{ __(['card' => __('بطاقة'), 'bank_transfer' => __('تحويل بنكي'), 'sandbox' => __('بطاقة (تجريبي)'), 'manual' => __('يدوي')][$sub->payment_method]) }}</td>
                                <td><x-status :value="'sub_'.$sub->status" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-8 text-center text-slate-400">{{ __('لا توجد اشتراكات بعد') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if($current)
            @php
                $higher = $plans->filter(fn ($p) => $p->isHigherThan($current->plan))->values();
                $longer = collect(\App\Models\Subscription::CYCLE_MONTHS)->filter(fn ($m) => $m > \App\Models\Subscription::CYCLE_MONTHS[$current->cycle])->keys();
            @endphp
            <div class="space-y-6" x-data="{ upgrade: false }">
                <div class="card p-6">
                    <h3 class="font-semibold text-ink">{{ __('إدارة اشتراكك') }}</h3>
                    @if($pending)
                        <p class="mt-3 rounded-xl bg-amber-50 p-3 text-sm text-amber-800">{{ __('لديك طلب :plan (:cycle) بانتظار تأكيد الدفع.', ['plan' => $pending->plan->localizedName(), 'cycle' => \App\Models\Plan::cycleLabel($pending->cycle)]) }}
                            <a href="{{ route('subscribe.pending', $pending) }}" class="font-semibold underline">{{ __('تعليمات التحويل') }}</a></p>
                    @endif
                    <div class="mt-4 space-y-3">
                        <a href="{{ route('subscribe.show', ['plan' => $current->plan->code, 'cycle' => $current->cycle, 'intent' => 'renew']) }}" class="btn-secondary w-full justify-center">
                            <x-icon name="clock" class="size-4" /> {{ __('تمديد الاشتراك') }}
                        </a>
                        @if($higher->isNotEmpty() || $longer->isNotEmpty())
                            <button type="button" @click="upgrade = !upgrade" class="btn-primary w-full justify-center"><x-icon name="bolt" class="size-4" /> {{ __('ترقية الباقة') }}</button>
                        @else
                            <p class="text-center text-sm text-slate-500">{{ __('أنت على أعلى باقة وأطول مدة.') }}</p>
                        @endif
                    </div>
                    <p class="mt-3 text-xs text-slate-500">{{ __('التمديد يضيف مدة جديدة بعد انتهاء اشتراكك الحالي بنفس باقتك.') }}</p>
                </div>

                <div x-show="upgrade" x-cloak x-transition class="card p-6">
                    <h3 class="font-semibold text-ink">{{ __('اختر الترقية') }}</h3>
                    <div class="mt-4 space-y-3">
                        @if($longer->isNotEmpty())
                            <a href="{{ route('subscribe.show', ['plan' => $current->plan->code, 'cycle' => $longer->last(), 'intent' => 'upgrade']) }}"
                               class="flex items-center justify-between rounded-2xl border border-brand-500 bg-brand-50 p-4 transition hover:bg-brand-100">
                                <span>
                                    <span class="block font-semibold text-ink">{{ $current->plan->localizedName() }} — {{ __('مدة أطول') }}</span>
                                    <span class="text-xs text-slate-500">{{ __('باقتك الحالية بمدة :cycles', ['cycles' => $longer->map(fn ($c) => \App\Models\Plan::cycleLabel($c))->join(' / ')]) }}</span>
                                </span>
                                <span class="text-xs font-semibold text-brand-700">{{ __('وفّر أكثر') }}</span>
                            </a>
                        @endif
                        @foreach($higher as $plan)
                            <a href="{{ route('subscribe.show', ['plan' => $plan->code, 'cycle' => $current->cycle]) }}"
                               class="flex items-center justify-between rounded-2xl border border-slate-200 p-4 transition hover:border-brand-500">
                                <span>
                                    <span class="block font-semibold text-ink">{{ $plan->localizedName() }}</span>
                                    <span class="text-xs text-slate-500">{{ $plan->audience() }}</span>
                                </span>
                                <span class="num text-end font-bold">{{ (int) $plan->price }}<span class="block text-[11px] font-normal text-slate-400">{{ __('درهم / شهر') }}</span></span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @else
        <div class="card p-6">
            <h3 class="font-semibold text-ink">{{ $company->onTrial() && $company->plan ? __('ترقية الباقة') : __('اختر باقة') }}</h3>
            @if($company->onTrial() && $company->trial_ends_at)
                <p class="mt-1 text-xs text-slate-500">{{ __('متبقٍ لك :days يوم من التجربة المجانية.', ['days' => max(0, (int) today()->diffInDays($company->trial_ends_at, false))]) }}</p>
            @endif
            <div class="mt-4 space-y-3">
                @foreach($plans->filter(fn ($p) => ! ($company->onTrial() && $company->plan) || $p->id === $company->plan_id || $p->isHigherThan($company->plan)) as $plan)
                    <a href="{{ route('subscribe.show', ['plan' => $plan->code, 'cycle' => $current?->cycle ?? 'monthly']) }}"
                       class="flex items-center justify-between rounded-2xl border p-4 transition hover:border-brand-500 {{ $current?->plan_id === $plan->id ? 'border-brand-500 bg-brand-50' : 'border-slate-200' }}">
                        <span>
                            <span class="block font-semibold text-ink">{{ $plan->localizedName() }}</span>
                            <span class="text-xs text-slate-500">{{ \App\Models\Plan::categoryLabel($plan->category) }}</span>
                        </span>
                        <span class="num text-end font-bold">{{ (int) $plan->price }}<span class="block text-[11px] font-normal text-slate-400">{{ __('درهم / شهر') }}</span></span>
                    </a>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</x-layouts.app>
