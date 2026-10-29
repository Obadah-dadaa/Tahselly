<x-layouts.app :title="__('المشتركون والاشتراكات')">
    <x-page-header :title="__('المشتركون والاشتراكات')" :subtitle="__('كل شركة مشتركة: الباقة، المدة، تاريخ الانتهاء، والدفع.')" />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <x-kpi :label="__('اشتراكات فعّالة')" :value="$stats['active']" icon="check" />
        <x-kpi :label="__('الإيراد الشهري المتكرر MRR')" :value="number_format($stats['mrr'], 0)" suffix="AED" icon="trend" tone="sky" :hint="__('السنوي ÷ 12، الربعي ÷ 3')" />
        <x-kpi :label="__('محصّل هذا الشهر')" :value="number_format($stats['revenue_month'], 0)" suffix="AED" icon="cash" tone="violet" />
        <x-kpi :label="__('بانتظار الاعتماد')" :value="$stats['pending']" icon="clock" tone="amber" :hint="__('تحويلات بنكية')" />
        <x-kpi :label="__('تنتهي خلال 14 يوم')" :value="$stats['expiring']" icon="alert" tone="rose" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-4">
        <div class="card p-5 xl:col-span-1">
            <h2 class="font-semibold text-ink">{{ __('التوزيع حسب الباقة') }}</h2>
            <ul class="mt-4 space-y-3 text-sm">
                @foreach($plans as $plan)
                    @php $n = $byPlan[$plan->id] ?? 0; $max = max(1, $byPlan->max() ?? 1); @endphp
                    <li>
                        <div class="flex justify-between"><span>{{ $plan->localizedName() }}</span><span class="num font-semibold">{{ $n }}</span></div>
                        <div class="mt-1 h-1.5 rounded-full bg-slate-100"><div class="h-full rounded-full bg-brand-500" style="width: {{ $n / $max * 100 }}%"></div></div>
                    </li>
                @endforeach
            </ul>
            <h2 class="mt-6 font-semibold text-ink">{{ __('حسب المدة') }}</h2>
            <div class="mt-3 flex flex-wrap gap-2 text-sm">
                @foreach(['monthly', 'quarterly', 'yearly'] as $c)
                    <span class="rounded-full bg-slate-100 px-3 py-1">{{ \App\Models\Plan::cycleLabel($c) }}: <b class="num">{{ $byCycle[$c] ?? 0 }}</b></span>
                @endforeach
            </div>
        </div>

        <div class="card overflow-hidden xl:col-span-3">
            <form class="grid gap-2 border-b border-slate-100 p-4 sm:grid-cols-6">
                <input name="q" value="{{ request('q') }}" placeholder="{{ __('اسم الشركة') }}" class="input sm:col-span-2">
                <select name="category" class="input"><option value="">{{ __('كل الفئات') }}</option>@foreach(\App\Models\Plan::CATEGORIES as $c)<option value="{{ $c }}" @selected(request('category') === $c)>{{ \App\Models\Plan::categoryLabel($c) }}</option>@endforeach</select>
                <select name="plan_id" class="input"><option value="">{{ __('كل الباقات') }}</option>@foreach($plans as $p)<option value="{{ $p->id }}" @selected(request('plan_id') == $p->id)>{{ $p->localizedName() }}</option>@endforeach</select>
                <select name="status" class="input"><option value="">{{ __('كل الحالات') }}</option>@foreach(['active' => __('فعّال'), 'pending' => __('بانتظار الدفع'), 'expired' => __('منتهي'), 'cancelled' => __('ملغى')] as $k => $l)<option value="{{ $k }}" @selected(request('status') === $k)>{{ __($l) }}</option>@endforeach</select>
                <div class="flex gap-2"><label class="flex items-center gap-1 text-xs"><input type="checkbox" name="expiring" value="1" @checked(request('expiring')) class="rounded"> {{ __('قريبة الانتهاء') }}</label><button class="btn-secondary">{{ __('تصفية') }}</button></div>
            </form>
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead><tr><th>{{ __('الشركة') }}</th><th>{{ __('الفئة') }}</th><th>{{ __('الباقة') }}</th><th>{{ __('المدة') }}</th><th>{{ __('من → إلى') }}</th><th>{{ __('المتبقي') }}</th><th>{{ __('المبلغ') }}</th><th>{{ __('الحالة') }}</th><th></th></tr></thead>
                    <tbody>
                    @forelse($subscriptions as $sub)
                        <tr x-data="{ more: false }">
                            <td><a href="{{ route('developer.companies.show', $sub->company) }}" class="font-semibold hover:text-brand-700">{{ $sub->company->name }}</a>
                                <p class="text-xs text-slate-400">{{ __(['card' => __('بطاقة'), 'bank_transfer' => __('تحويل بنكي'), 'sandbox' => __('بطاقة (تجريبي)'), 'manual' => __('يدوي')][$sub->payment_method]) }}</p></td>
                            <td class="text-xs">{{ \App\Models\Plan::categoryLabel($sub->plan->category) }}</td>
                            <td class="font-medium">{{ $sub->plan->localizedName() }}</td>
                            <td>{{ \App\Models\Plan::cycleLabel($sub->cycle) }}</td>
                            <td class="num whitespace-nowrap text-xs text-slate-500">{{ $sub->starts_at?->format('Y-m-d') ?? '—' }} → {{ $sub->ends_at?->format('Y-m-d') ?? '—' }}</td>
                            <td class="num {{ $sub->status === 'active' && $sub->daysLeft() <= 14 ? 'font-bold text-rose-600' : '' }}">{{ $sub->status === 'active' ? __(':days يوم', ['days' => $sub->daysLeft()]) : '—' }}</td>
                            <td class="num font-semibold">{{ number_format((float) $sub->amount, 0) }}</td>
                            <td><x-status :value="'sub_'.$sub->status" /></td>
                            <td class="whitespace-nowrap">
                                @if($sub->status === 'pending')
                                    <form method="POST" action="{{ route('developer.subscriptions.approve', $sub) }}">@csrf<button class="rounded-lg bg-emerald-600 px-3 py-1 text-xs font-semibold text-white hover:bg-emerald-700">{{ __('اعتماد الدفع') }}</button></form>
                                @elseif($sub->status === 'active')
                                    <div class="relative flex items-center justify-end gap-2" x-data="{ extend: false, cancel: false }">
                                        <button type="button" @click="extend = !extend" class="inline-flex items-center gap-1 rounded-lg bg-brand-50 px-3 py-1.5 text-xs font-semibold text-brand-700 ring-1 ring-inset ring-brand-600/20 hover:bg-brand-100">
                                            <x-icon name="clock" class="size-3.5" /> {{ __('تمديد') }}
                                        </button>
                                        <button type="button" @click="cancel = true" class="inline-flex items-center gap-1 rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 ring-1 ring-inset ring-rose-600/20 hover:bg-rose-100">
                                            ✕ {{ __('إلغاء') }}
                                        </button>

                                        {{-- Extend: pick a period --}}
                                        <form x-show="extend" x-cloak @click.outside="extend = false" method="POST" action="{{ route('developer.subscriptions.update', $sub) }}"
                                              class="card absolute end-0 top-10 z-30 w-56 space-y-2 p-3 text-start">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="action" value="extend">
                                            <p class="text-xs font-semibold text-slate-500">{{ __('تمديد الاشتراك لمدة') }}</p>
                                            <div class="grid grid-cols-3 gap-2">
                                                @foreach([1 => __('شهر'), 3 => __('3 أشهر'), 12 => __('سنة')] as $m => $l)
                                                    <button name="months" value="{{ $m }}" class="rounded-lg border border-slate-200 py-2 text-xs font-semibold hover:border-brand-500 hover:bg-brand-50">{{ $l }}</button>
                                                @endforeach
                                            </div>
                                        </form>

                                        {{-- Cancel: confirm with an optional reason --}}
                                        <div x-show="cancel" x-cloak x-transition.opacity class="fixed inset-0 z-50 grid place-items-center bg-night/60 p-4 text-start whitespace-normal backdrop-blur-sm" @keydown.escape.window="cancel = false">
                                            <form method="POST" action="{{ route('developer.subscriptions.update', $sub) }}" @click.outside="cancel = false" class="card w-full max-w-md overflow-hidden">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="action" value="cancel">
                                                <div class="flex items-start gap-4 p-6">
                                                    <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-rose-100 text-rose-600"><x-icon name="alert" class="size-6" /></span>
                                                    <div class="min-w-0 flex-1 whitespace-normal">
                                                        <h3 class="text-lg font-bold text-ink">{{ __('إلغاء اشتراك :company؟', ['company' => $sub->company->name]) }}</h3>
                                                        <p class="mt-1 text-sm text-slate-500">{{ __('ينتهي الاشتراك فوراً ويتوقف وصول الشركة للنظام، ويُطلب من المالك اختيار باقة للعودة. بياناتهم تبقى محفوظة.') }}</p>
                                                        <p class="mt-3 rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-600">{{ $sub->plan->localizedName() }} · {{ \App\Models\Plan::cycleLabel($sub->cycle) }} · {{ __(':days يوم متبقٍ', ['days' => $sub->daysLeft()]) }}</p>
                                                        <label class="label mt-4">{{ __('سبب الإلغاء (اختياري)') }}</label>
                                                        <input name="reason" maxlength="255" class="input" placeholder="{{ __('مثال: طلب العميل، عدم الدفع…') }}">
                                                    </div>
                                                </div>
                                                <div class="flex justify-end gap-2 border-t border-slate-100 bg-slate-50 px-6 py-4">
                                                    <button type="button" @click="cancel = false" class="btn-secondary">{{ __('تراجع') }}</button>
                                                    <button class="btn-primary bg-rose-600 hover:bg-rose-700">{{ __('نعم، ألغِ الاشتراك') }}</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="py-10 text-center text-slate-400">{{ __('لا توجد اشتراكات') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $subscriptions->links() }}</div>
        </div>
    </div>

    <form method="POST" action="{{ route('developer.subscriptions.store') }}" class="card mt-6 flex flex-wrap items-end gap-3 p-5">
        @csrf
        <h2 class="w-full font-semibold text-ink">{{ __('إضافة اشتراك يدوي (دفع خارج النظام)') }}</h2>
        <div class="min-w-48 flex-1"><label class="label">{{ __('الشركة') }}</label><select name="company_id" class="input">@foreach($companies as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
        <div class="min-w-48 flex-1"><label class="label">{{ __('الباقة') }}</label><select name="plan_id" class="input">@foreach($plans as $p)<option value="{{ $p->id }}">{{ $p->localizedName() }}</option>@endforeach</select></div>
        <div><label class="label">{{ __('المدة') }}</label><select name="cycle" class="input">@foreach(['monthly', 'quarterly', 'yearly'] as $c)<option value="{{ $c }}">{{ \App\Models\Plan::cycleLabel($c) }}</option>@endforeach</select></div>
        <button class="btn-primary">{{ __('إضافة وتفعيل') }}</button>
    </form>
</x-layouts.app>
