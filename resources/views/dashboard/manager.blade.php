<x-layouts.app :title="__('لوحة المدير')">
    <x-page-header :title="__('لوحة المدير')" :subtitle="__('ملخص المستحقات اليوم وما يحتاج إلى متابعة.')">
        <x-slot:actions>
            <form method="POST" action="{{ route('reminders.run') }}">@csrf<button class="btn-secondary"><x-icon name="bolt" class="size-4" /> {{ __('تشغيل التذكيرات الآن') }}</button></form>
            <a href="{{ route('invoices.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> {{ __('فاتورة جديدة') }}</a>
        </x-slot:actions>
    </x-page-header>

    @php $currency = auth()->user()->company->currency; @endphp
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-kpi :label="__('إجمالي المستحق')" :value="number_format($kpis['outstanding'], 0)" :suffix="$currency" icon="cash" :hint="__(':count فاتورة مفتوحة', ['count' => $kpis['open_count']])" />
        <x-kpi :label="__('متأخرات')" :value="number_format($kpis['overdue'], 0)" :suffix="$currency" icon="alert" tone="rose" :hint="__(':count فاتورة تجاوزت تاريخ الاستحقاق', ['count' => $kpis['overdue_count']])" />
        <x-kpi :label="__('يستحق خلال 7 أيام')" :value="number_format($kpis['due_week'], 0)" :suffix="$currency" icon="clock" tone="amber" :hint="__(':count فاتورة قادمة', ['count' => $kpis['due_week_count']])" />
        <x-kpi :label="__('محصّل هذا الشهر')" :value="number_format($kpis['collected_month'], 0)" :suffix="$currency" icon="check" tone="sky" :hint="__('نسبة التحصيل (90 يوم): :rate%', ['rate' => $kpis['collection_rate']])" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="card overflow-hidden xl:col-span-2">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h2 class="font-semibold text-ink">{{ __('فواتير متأخرة تحتاج متابعة') }}</h2>
                <a href="{{ route('invoices.index', ['status' => 'overdue']) }}" class="text-sm font-semibold text-brand-700 hover:underline">{{ __('عرض الكل') }}</a>
            </div>
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead><tr><th>{{ __('الفاتورة') }}</th><th>{{ __('العميل') }}</th><th>{{ __('المتبقي') }}</th><th>{{ __('الحالة') }}</th><th>{{ __('آخر تذكير') }}</th><th></th></tr></thead>
                    <tbody>
                    @forelse($overdueInvoices as $invoice)
                        <tr>
                            <td><a href="{{ route('invoices.show', $invoice) }}" class="font-semibold text-brand-700 hover:underline">{{ $invoice->number }}</a></td>
                            <td>{{ $invoice->client->name }}</td>
                            <td class="num font-semibold">@money($invoice->balance())</td>
                            <td><x-status :invoice="$invoice" /></td>
                            <td class="text-xs text-slate-500">{{ $invoice->last_reminded_at?->locale(app()->getLocale())->diffForHumans() ?? '—' }}</td>
                            <td>
                                <form method="POST" action="{{ route('invoices.remind', $invoice) }}">@csrf
                                    <button class="rounded-lg p-1.5 text-slate-400 hover:bg-brand-50 hover:text-brand-700" title="{{ __('إرسال تذكير') }}"><x-icon name="mail" class="size-4" /></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-10 text-center text-slate-400">{{ __('لا توجد فواتير متأخرة 🎉') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card p-5">
            <h2 class="font-semibold text-ink">{{ __('توزيع أعمار الديون') }}</h2>
            <div class="skeleton mt-4 h-56">
                @php
                    $agingChart = [
                        'type' => 'doughnut',
                        'data' => [
                            'labels' => array_map(fn ($l) => __($l), array_values(\App\Services\ReceivablesReport::BUCKETS)),
                            'datasets' => [['data' => array_map(fn ($v) => round($v), array_values($aging)), 'backgroundColor' => ['#14b8a6', '#fbbf24', '#fb923c', '#f87171', '#be123c'], 'borderWidth' => 0]],
                        ],
                        'options' => ['cutout' => '68%', 'plugins' => ['legend' => ['position' => 'bottom', 'labels' => ['boxWidth' => 8, 'font' => ['size' => 11]]]]],
                    ];
                @endphp
                <canvas data-chart="{{ json_encode($agingChart) }}"></canvas>
            </div>
            <a href="{{ route('reports.aging') }}" class="btn-secondary mt-4 w-full">{{ __('تقرير أعمار الديون الكامل') }}</a>
        </div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <div class="card">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h2 class="font-semibold text-ink">{{ __('تستحق خلال الأسبوع') }}</h2>
                <span class="rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700">{{ $upcomingInvoices->count() }}</span>
            </div>
            <ul class="divide-y divide-slate-100">
                @forelse($upcomingInvoices as $invoice)
                    <li class="flex items-center justify-between px-5 py-3 text-sm">
                        <div>
                            <a href="{{ route('invoices.show', $invoice) }}" class="font-semibold text-ink hover:text-brand-700">{{ $invoice->client->name }}</a>
                            <p class="text-xs text-slate-500">{{ $invoice->number }} · {{ __('تستحق') }} {{ $invoice->due_date->locale(app()->getLocale())->diffForHumans() }}</p>
                        </div>
                        <span class="num font-semibold">@money($invoice->balance())</span>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-slate-400">{{ __('لا شيء يستحق هذا الأسبوع') }}</li>
                @endforelse
            </ul>
        </div>
        <div class="card">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h2 class="font-semibold text-ink">{{ __('آخر التذكيرات المرسلة') }}</h2>
                <span class="text-xs text-slate-500">{{ __('اليوم:') }} {{ $remindersToday }}</span>
            </div>
            <ul class="divide-y divide-slate-100">
                @forelse($recentReminders as $reminder)
                    <li class="flex items-center gap-3 px-5 py-3 text-sm">
                        <span class="grid size-8 shrink-0 place-items-center rounded-full {{ $reminder->channel === 'whatsapp' ? 'bg-green-50 text-green-600' : 'bg-sky-50 text-sky-600' }}"><x-icon :name="$reminder->channel === 'whatsapp' ? 'whatsapp' : 'mail'" class="size-4" /></span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium">{{ $reminder->client->name }} <span class="text-slate-400">· {{ $reminder->invoice->number }}</span></p>
                            <p class="text-xs text-slate-500">{{ $reminder->stageLabel() }} · {{ $reminder->created_at->locale(app()->getLocale())->diffForHumans() }}</p>
                        </div>
                        <x-status :value="$reminder->status" />
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-slate-400">{{ __('لا توجد تذكيرات بعد') }}</li>
                @endforelse
            </ul>
        </div>
    </div>
</x-layouts.app>
