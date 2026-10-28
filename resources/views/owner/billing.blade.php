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

        <div class="card p-6">
            <h3 class="font-semibold text-ink">{{ $current ? __('جدّد أو غيّر باقتك') : __('اختر باقة') }}</h3>
            <div class="mt-4 space-y-3">
                @foreach($plans as $plan)
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
    </div>
</x-layouts.app>
