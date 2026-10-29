<x-layouts.app :title="__('تقرير أعمار الديون')">
    <x-page-header :title="__('تقرير أعمار الديون')" :subtitle="__('حتى تاريخ :date — الأرصدة المفتوحة مصنفة حسب مدة التأخير', ['date' => today()->format('Y-m-d')])">
        <x-slot:actions>
            @if(auth()->user()->company->canUse('report_exports'))
                <a href="{{ route('reports.aging.export') }}" download class="btn-primary"><x-icon name="download" class="size-4" /> {{ __('تصدير Excel') }} <x-plan-badge feature="report_exports" /></a>
            @endif
        </x-slot:actions>
    </x-page-header>

    @php
        $colors = ['current' => 'bg-brand-500', 'd1_30' => 'bg-amber-400', 'd31_60' => 'bg-orange-400', 'd61_90' => 'bg-rose-400', 'd90_plus' => 'bg-rose-700'];
        $grand = max($aging['grand'], 1);
    @endphp

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @foreach($buckets as $key => $label)
            <div class="card p-5">
                <div class="flex items-center gap-2 text-sm text-slate-500"><span class="size-2.5 rounded-full {{ $colors[$key] }}"></span>{{ __($label) }}</div>
                <p class="num mt-2 text-xl font-bold">@money($aging['totals'][$key])</p>
                <p class="num mt-1 text-xs text-slate-400">{{ __(':pct% من الإجمالي', ['pct' => round($aging['totals'][$key] / $grand * 100, 1)]) }}</p>
            </div>
        @endforeach
    </div>

    <div class="card mt-6 p-5">
        <div class="flex h-4 overflow-hidden rounded-full bg-slate-100">
            @foreach($buckets as $key => $label)
                <div class="{{ $colors[$key] }}" style="width: {{ $aging['totals'][$key] / $grand * 100 }}%" title="{{ __($label) }}"></div>
            @endforeach
        </div>
        <p class="mt-3 text-sm text-slate-500">{{ __('إجمالي الذمم المدينة:') }} <b class="num text-ink">@money($aging['grand'])</b> {{ auth()->user()->company->currency }}</p>
    </div>

    <div class="card mt-6 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead><tr><th>{{ __('العميل') }}</th><th>{{ __('فواتير') }}</th>@foreach($buckets as $label)<th>{{ __($label) }}</th>@endforeach<th>{{ __('الإجمالي') }}</th></tr></thead>
                <tbody>
                @forelse($aging['clients'] as $row)
                    <tr>
                        <td><a href="{{ route('clients.show', $row['client']) }}" class="font-semibold hover:text-brand-700">{{ $row['client']->name }}</a></td>
                        <td class="num">{{ $row['invoices'] }}</td>
                        @foreach($buckets as $key => $label)
                            <td class="num {{ $row[$key] > 0 && $key !== 'current' ? 'text-rose-600' : 'text-slate-400' }}">{{ $row[$key] > 0 ? number_format($row[$key], 2) : '—' }}</td>
                        @endforeach
                        <td class="num font-bold">@money($row['total'])</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="py-12 text-center text-slate-400">{{ __('لا توجد أرصدة مفتوحة') }}</td></tr>
                @endforelse
                </tbody>
                @if($aging['clients']->isNotEmpty())
                    <tfoot class="bg-slate-50 font-bold">
                    <tr><td class="px-4 py-3">{{ __('الإجمالي') }}</td><td></td>@foreach($buckets as $key => $label)<td class="num px-4 py-3">@money($aging['totals'][$key])</td>@endforeach<td class="num px-4 py-3">@money($aging['grand'])</td></tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</x-layouts.app>
