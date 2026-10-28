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
                                    <form method="POST" action="{{ route('developer.subscriptions.update', $sub) }}" class="flex items-center gap-1">
                                        @csrf @method('PATCH')
                                        <select name="months" class="input w-16 px-1 py-1 text-xs"><option>1</option><option>3</option><option>12</option></select>
                                        <button name="action" value="extend" class="text-xs font-semibold text-brand-700 hover:underline">{{ __('تمديد') }}</button>
                                        <button name="action" value="cancel" class="text-xs text-rose-600 hover:underline" onclick="return confirm(@js(__('إلغاء الاشتراك؟')))">{{ __('إلغاء') }}</button>
                                    </form>
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
