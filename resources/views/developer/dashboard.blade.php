<x-layouts.app :title="__('لوحة المبرمج')">
    <x-page-header :title="__('لوحة المبرمج')" :subtitle="__('مراقبة المنصة، الاشتراكات وصحة النظام — كل ما تحتاجه لإدارة المنتج وتعديله.')">
        <x-slot:actions>
            <a href="{{ route('developer.tools') }}" class="btn-secondary"><x-icon name="terminal" class="size-4" /> {{ __('أدوات الصيانة') }}</a>
            <a href="{{ route('developer.companies.index') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> {{ __('شركة جديدة') }}</a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-kpi :label="__('الإيراد الشهري MRR')" :value="number_format($stats['mrr'], 0)" suffix="AED" icon="cash" :hint="'ARR: '.number_format($stats['arr'], 0).' AED'" />
        <x-kpi :label="__('الشركات المشتركة')" :value="$stats['companies']" icon="building" tone="sky" :hint="__(':active نشطة · :trial تجريبية · :suspended منتهية/موقوفة', ['active' => $stats['active'], 'trial' => $stats['trial'], 'suspended' => $stats['suspended']])" />
        <x-kpi :label="__('فواتير على المنصة')" :value="number_format($stats['invoices'])" icon="invoice" tone="violet" :hint="__('بقيمة :volume AED · :users مستخدم', ['volume' => number_format($stats['volume'], 0), 'users' => $stats['users']])" />
        <x-kpi :label="__('نجاح إرسال التذكيرات')" :value="$stats['delivery_rate'].'%'" icon="bell" :tone="$stats['delivery_rate'] < 90 ? 'rose' : 'brand'" :hint="__(':sent مرسلة · :failed فاشلة (30 يوم)', ['sent' => $stats['reminders_sent'], 'failed' => $stats['reminders_failed']])" />
    </div>

    {{-- Growth funnel: visitors → free trials → paying subscribers --}}
    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-kpi :label="__('زوار الموقع اليوم')" :value="number_format($funnel['visitors']['today'])" icon="users" tone="sky" :hint="__(':week آخر 7 أيام · :month آخر 30 يوماً', ['week' => number_format($funnel['visitors']['week']), 'month' => number_format($funnel['visitors']['month'])])" />
        <x-kpi :label="__('في التجربة المجانية الآن')" :value="$funnel['trials']" icon="clock" tone="amber" :hint="__(':count تنتهي تجربتهم خلال 3 أيام', ['count' => $funnel['trials_ending']])" />
        <x-kpi :label="__('مشتركون يدفعون')" :value="$funnel['paying']" icon="cash" :hint="__('اشتراك مدفوع فعّال حالياً')" />
        <x-kpi :label="__('تحويل الزوار لتسجيلات')" :value="$funnel['signup_rate'].'%'" icon="trend" tone="violet" :hint="__(':signups تسجيل جديد من :visitors زائر (30 يوماً)', ['signups' => $funnel['signups'], 'visitors' => number_format($funnel['visitors']['month'])])" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="card p-5 xl:col-span-2">
            <h2 class="font-semibold text-ink">{{ __('زوار الموقع — آخر 14 يوماً') }}</h2>
            <p class="text-xs text-slate-500">{{ __('زوار فريدون يومياً للصفحات العامة، بدون تخزين عناوين IP أو ملفات تعريف الارتباط.') }}</p>
            <div class="skeleton mt-3 h-48">
                @php
                    $visitChart = ['type' => 'bar', 'data' => ['labels' => $visitorChart['labels'], 'datasets' => [['data' => $visitorChart['data'], 'backgroundColor' => 'rgba(14,165,233,.55)', 'borderRadius' => 6]]], 'options' => ['plugins' => ['legend' => ['display' => false]], 'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]], 'x' => ['grid' => ['display' => false]]]]];
                @endphp
                <canvas data-chart="{{ json_encode($visitChart) }}"></canvas>
            </div>
        </div>
        <div class="card p-5">
            <h2 class="font-semibold text-ink">{{ __('من أين يأتي الزوار') }}</h2>
            <p class="text-xs text-slate-500">{{ __('أكثر المواقع إحالةً (30 يوماً)') }}</p>
            <ul class="mt-4 space-y-2 text-sm">
                @forelse($funnel['referrers'] as $host => $total)
                    <li class="flex items-center justify-between gap-3"><span class="truncate" dir="ltr">{{ $host }}</span><span class="num font-semibold">{{ $total }}</span></li>
                @empty
                    <li class="text-slate-400">{{ __('لا توجد إحالات بعد — أغلب الزوار وصلوا مباشرة.') }}</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="card overflow-hidden xl:col-span-2">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h2 class="font-semibold text-ink">{{ __('أحدث الشركات') }}</h2>
                <a href="{{ route('developer.companies.index') }}" class="text-sm font-semibold text-brand-700">{{ __('إدارة الشركات') }}</a>
            </div>
            <table class="table-base">
                <thead><tr><th>{{ __('الشركة') }}</th><th>{{ __('الباقة') }}</th><th>{{ __('مستخدمين') }}</th><th>{{ __('فواتير') }}</th><th>{{ __('الحالة') }}</th></tr></thead>
                <tbody>
                @foreach($companies as $c)
                    <tr>
                        <td><a href="{{ route('developer.companies.show', $c) }}" class="font-semibold hover:text-brand-700">{{ $c->name }}</a><p class="text-xs text-slate-400">{{ $c->created_at->locale(app()->getLocale())->diffForHumans() }}</p></td>
                        <td>{{ $c->plan?->localizedName() }}</td>
                        <td class="num">{{ $c->users_count }}</td>
                        <td class="num">{{ $c->invoices_count }}</td>
                        <td><x-status :value="$c->status" /></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="space-y-6">
            <div class="card p-5">
                <h2 class="font-semibold text-ink">{{ __('اشتراكات جديدة') }}</h2>
                <div class="skeleton mt-3 h-40">
                    @php
                        $signupChart = ['type' => 'line', 'data' => ['labels' => $signups['labels'], 'datasets' => [['data' => $signups['data'], 'borderColor' => '#0f766e', 'backgroundColor' => 'rgba(20,184,166,.12)', 'fill' => true, 'tension' => 0.35, 'pointRadius' => 3]]], 'options' => ['plugins' => ['legend' => ['display' => false]], 'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]], 'x' => ['grid' => ['display' => false]]]]];
                    @endphp
                    <canvas data-chart="{{ json_encode($signupChart) }}"></canvas>
                </div>
            </div>
            <div class="card bg-night p-5 text-sm text-slate-300">
                <h2 class="mb-3 flex items-center gap-2 font-semibold text-white"><x-icon name="shield" class="size-5 text-brand-500" /> {{ __('صحة النظام') }}</h2>
                <dl class="space-y-2 font-mono text-xs" dir="ltr">
                    @foreach($system as $k => $v)
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ __($k) }}</dt><dd class="{{ $k === 'debug' && $v ? 'text-amber-400' : 'text-slate-200' }}">{{ is_bool($v) ? ($v ? 'true' : 'false') : $v }}</dd></div>
                    @endforeach
                    <div class="flex justify-between"><dt class="text-slate-500">pending jobs</dt><dd>{{ $stats['pending_jobs'] }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">failed jobs</dt><dd class="{{ $stats['failed_jobs'] ? 'text-rose-400' : 'text-emerald-400' }}">{{ $stats['failed_jobs'] }}</dd></div>
                </dl>
            </div>
        </div>
    </div>

    <div class="card mt-6">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <h2 class="font-semibold text-ink">{{ __('نشاط المنصة') }}</h2>
            <a href="{{ route('developer.activity') }}" class="text-sm font-semibold text-brand-700">{{ __('السجل الكامل') }}</a>
        </div>
        <ul class="divide-y divide-slate-100 text-sm">
            @foreach($activity as $log)
                <li class="flex items-center justify-between gap-4 px-5 py-3">
                    <span><b class="font-semibold">{{ $log->company?->name ?? __('المنصة') }}</b> — {{ $log->description }}</span>
                    <span class="whitespace-nowrap text-xs text-slate-500">{{ $log->user?->name }} · {{ $log->created_at->locale(app()->getLocale())->diffForHumans() }}</span>
                </li>
            @endforeach
        </ul>
    </div>
</x-layouts.app>
