<x-layouts.app :title="__('لوحة المالك')">
    @php $currency = $company->currency; @endphp
    <x-page-header :title="__('أهلاً').' '.explode(' ', auth()->user()->name)[0].' 👋'" :subtitle="__('صورة كاملة عن التدفق النقدي وأداء التحصيل لشركتك.')">
        <x-slot:actions>
            <a href="{{ route('reports.aging.export') }}" download class="btn-secondary"><x-icon name="download" class="size-4" /> {{ __('تصدير التقرير') }}</a>
            <a href="{{ route('dashboard') }}" class="btn-primary">{{ __('لوحة المدير') }}</a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-kpi :label="__('الذمم المدينة')" :value="number_format($kpis['outstanding'], 0)" :suffix="$currency" icon="cash" :hint="__(':count فاتورة مفتوحة', ['count' => $kpis['open_count']])" />
        <x-kpi :label="__('نسبة التحصيل (90 يوم)')" :value="$kpis['collection_rate'].'%'" icon="trend" tone="sky" :hint="__('المحصّل ÷ المفوتر')" />
        <x-kpi :label="__('متوسط أيام التحصيل DSO')" :value="$kpis['dso']" :suffix="__('يوم')" icon="clock" tone="amber" :hint="__('كلما قلّ كان أفضل')" />
        <x-kpi :label="__('حُصّل بعد التذكير (30 يوم)')" :value="number_format($recovered, 0)" :suffix="$currency" icon="bolt" tone="violet" :hint="__(':count تذكير هذا الشهر', ['count' => $remindersMonth])" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="card p-5 xl:col-span-2">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-ink">{{ __('المفوتر مقابل المحصّل') }}</h2>
                <span class="text-xs text-slate-500">{{ __('آخر 6 أشهر') }} · {{ $currency }}</span>
            </div>
            <div class="skeleton mt-4 h-72">
                @php
                    $trendChart = [
                        'type' => 'bar',
                        'data' => [
                            'labels' => $trend['labels'],
                            'datasets' => [
                                ['label' => __('المفوتر'), 'data' => $trend['invoiced'], 'backgroundColor' => '#cbd5e1', 'borderRadius' => 6, 'maxBarThickness' => 28],
                                ['label' => __('المحصّل'), 'data' => $trend['collected'], 'backgroundColor' => '#0f766e', 'borderRadius' => 6, 'maxBarThickness' => 28],
                            ],
                        ],
                        'options' => ['plugins' => ['legend' => ['position' => 'top', 'align' => 'end']], 'scales' => ['x' => ['grid' => ['display' => false]], 'y' => ['grid' => ['color' => '#f1f5f9'], 'border' => ['display' => false]]]],
                    ];
                @endphp
                <canvas data-chart="{{ json_encode($trendChart) }}"></canvas>
            </div>
        </div>
        <div class="card p-5">
            <h2 class="font-semibold text-ink">{{ __('أعمار الديون') }}</h2>
            <div class="skeleton mt-2 h-52">
                @php
                    $agingChart = [
                        'type' => 'doughnut',
                        'data' => ['labels' => array_map(fn ($l) => __($l), array_values($buckets)), 'datasets' => [['data' => array_map(fn ($v) => round($v), array_values($aging['totals'])), 'backgroundColor' => ['#14b8a6', '#fbbf24', '#fb923c', '#f87171', '#be123c'], 'borderWidth' => 0]]],
                        'options' => ['cutout' => '70%', 'plugins' => ['legend' => ['display' => false]]],
                    ];
                @endphp
                <canvas data-chart="{{ json_encode($agingChart) }}"></canvas>
            </div>
            <ul class="mt-4 space-y-2 text-sm">
                @foreach($buckets as $key => $label)
                    <li class="flex justify-between"><span class="text-slate-500">{{ __($label) }}</span><span class="num font-semibold">@money($aging['totals'][$key])</span></li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="card overflow-hidden xl:col-span-2">
            <h2 class="border-b border-slate-100 px-5 py-4 font-semibold text-ink">{{ __('أكبر العملاء المدينين') }}</h2>
            <table class="table-base">
                <thead><tr><th>{{ __('العميل') }}</th><th>{{ __('فواتير') }}</th><th>{{ __('الرصيد') }}</th><th>{{ __('منه متأخر') }}</th><th class="w-40">{{ __('نسبة التأخير') }}</th></tr></thead>
                <tbody>
                @foreach($topDebtors as $row)
                    @php $ratio = $row['balance'] > 0 ? $row['overdue'] / $row['balance'] * 100 : 0; @endphp
                    <tr>
                        <td><a href="{{ route('clients.show', $row['client']) }}" class="font-semibold hover:text-brand-700">{{ $row['client']->name }}</a></td>
                        <td class="num">{{ $row['invoices'] }}</td>
                        <td class="num font-semibold">@money($row['balance'])</td>
                        <td class="num text-rose-600">@money($row['overdue'])</td>
                        <td><div class="h-2 rounded-full bg-slate-100"><div class="h-full rounded-full {{ $ratio > 60 ? 'bg-rose-500' : ($ratio > 25 ? 'bg-amber-400' : 'bg-brand-500') }}" style="width: {{ $ratio }}%"></div></div></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="space-y-6">
            <div class="card p-5">
                <div class="flex items-center justify-between">
                    <h2 class="font-semibold text-ink">{{ __('الاشتراك') }}</h2>
                    <x-status :value="$company->status" />
                </div>
                @beta
                    <p class="mt-3 text-2xl font-bold">{{ __('نسخة تجريبية') }}</p>
                    <p class="text-sm text-slate-500">{{ __('حتى :date', ['date' => $company->trial_ends_at?->format('Y-m-d') ?? '—']) }}</p>
                    <a href="{{ route('owner.billing') }}" class="mt-3 inline-block text-sm font-semibold text-brand-700 hover:underline">{{ __('الحدود وطلب الاشتراك') }} ←</a>
                @else
                    <p class="mt-3 text-2xl font-bold">{{ $company->plan?->localizedName() }}</p>
                    <p class="num text-sm text-slate-500">{{ number_format((float) $company->plan?->price, 0) }} {{ __('درهم / شهرياً') }}</p>
                    <p class="mt-3 text-sm text-slate-500">{{ __('المستخدمون:') }} <b class="num text-ink">{{ $team->count() }} / {{ $company->plan?->max_users }}</b></p>
                @endbeta
            </div>
            <div class="card">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <h2 class="font-semibold text-ink">{{ __('نشاط الفريق') }}</h2>
                    <a href="{{ route('owner.activity') }}" class="text-xs font-semibold text-brand-700">{{ __('الكل') }}</a>
                </div>
                <ul class="divide-y divide-slate-100 text-sm">
                    @foreach($activity as $log)
                        <li class="px-5 py-3"><p>{{ $log->description }}</p><p class="text-xs text-slate-500">{{ $log->user?->name ?? __('النظام') }} · {{ $log->created_at->locale(app()->getLocale())->diffForHumans() }}</p></li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</x-layouts.app>
