<x-layouts.app :title="__('نتيجة الاستيراد')">
    <x-page-header :title="__('نتيجة استيراد:').' '.$batch->filename" :subtitle="$batch->created_at->format('Y-m-d H:i').' · '.$batch->user?->name">
        <x-slot:actions><a href="{{ route('import.index') }}" class="btn-secondary">{{ __('استيراد ملف آخر') }}</a></x-slot:actions>
    </x-page-header>

    @php
        $totals = $batch->invoices->groupBy(fn ($invoice) => $invoice->currency ?: 'AED')->map(fn ($group) => $group->sum(fn ($invoice) => (float) $invoice->amount))->sortDesc();
        $mainCurrency = $totals->keys()->first() ?? 'AED';
    @endphp

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-kpi :label="__('مجموع الفواتير')" :value="number_format($totals->first() ?? 0, 2)" :suffix="$mainCurrency" icon="cash" tone="sky"
            :hint="$totals->count() > 1 ? $totals->skip(1)->map(fn ($sum, $currency) => '+ '.number_format($sum, 2).' '.$currency)->implode(' · ') : __(':count فاتورة', ['count' => $batch->invoices->count()])" />
        <x-kpi :label="__('إجمالي الصفوف')" :value="$batch->rows_total" icon="list" tone="slate" />
        <x-kpi :label="__('تم استيرادها')" :value="$batch->rows_imported" icon="check" />
        <x-kpi :label="__('أخطاء')" :value="$batch->rows_failed" icon="alert" tone="rose" />
    </div>

    @if($batch->errors)
        <div class="card mt-6 overflow-hidden">
            <h2 class="border-b border-slate-100 px-5 py-4 font-semibold text-rose-700">{{ __('صفوف لم يتم استيرادها') }}</h2>
            <table class="table-base">
                <thead><tr><th>{{ __('الصف') }}</th><th>{{ __('السبب') }}</th></tr></thead>
                <tbody>@foreach($batch->errors as $error)<tr><td class="num">{{ $error['row'] }}</td><td>{{ $error['message'] }}</td></tr>@endforeach</tbody>
            </table>
        </div>
    @endif

    <div class="card mt-6 overflow-hidden">
        <h2 class="border-b border-slate-100 px-5 py-4 font-semibold text-ink">{{ __('الفواتير المستوردة') }}</h2>
        <table class="table-base">
            <thead><tr><th>{{ __('الرقم') }}</th><th>{{ __('العميل') }}</th><th>{{ __('الاستحقاق') }}</th><th>{{ __('المبلغ') }}</th><th>{{ __('الحالة') }}</th></tr></thead>
            <tbody>
            @foreach($batch->invoices as $invoice)
                <tr>
                    <td><a href="{{ route('invoices.show', $invoice) }}" class="font-semibold text-brand-700">{{ $invoice->number }}</a></td>
                    <td>{{ $invoice->client->name }}</td>
                    <td class="num">{{ $invoice->due_date->format('Y-m-d') }}</td>
                    <td class="num">@money($invoice->amount)</td>
                    <td><x-status :invoice="$invoice" /></td>
                </tr>
            @endforeach
            </tbody>
            @if($batch->invoices->isNotEmpty())
                <tfoot>
                @foreach($totals as $currency => $sum)
                    <tr class="bg-slate-50 font-semibold"><td colspan="3">{{ __('المجموع') }}@if($totals->count() > 1) ({{ $currency }})@endif</td><td class="num">@money($sum) <span class="text-xs text-slate-400">{{ $currency }}</span></td><td></td></tr>
                @endforeach
                </tfoot>
            @endif
        </table>
    </div>
</x-layouts.app>
