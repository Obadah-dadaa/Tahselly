<x-layouts.app title="{{ __('نتيجة الاستيراد') }}">
    <x-page-header :title="__('نتيجة استيراد:').' '.$batch->filename" :subtitle="$batch->created_at->format('Y-m-d H:i').' · '.$batch->user?->name">
        <x-slot:actions><a href="{{ route('import.index') }}" class="btn-secondary">{{ __('استيراد ملف آخر') }}</a></x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-3">
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
        </table>
    </div>
</x-layouts.app>
