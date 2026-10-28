<x-layouts.app title="{{ __('الفواتير') }}">
    <x-page-header title="{{ __('الفواتير') }}" :subtitle="__(':count فاتورة', ['count' => $invoices->total()])">
        <x-slot:actions>
            <a href="{{ route('import.index') }}" class="btn-secondary"><x-icon name="upload" class="size-4" /> {{ __('استيراد Excel') }}</a>
            <a href="{{ route('invoices.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> {{ __('فاتورة جديدة') }}</a>
        </x-slot:actions>
    </x-page-header>

    @php
        $tabs = ['' => __('الكل'), 'open' => __('مفتوحة'), 'overdue' => __('متأخرة'), 'partial' => __('جزئية'), 'paid' => __('مدفوعة'), 'cancelled' => __('ملغاة')];
    @endphp
    <div class="mb-4 flex flex-wrap gap-2">
        @foreach($tabs as $key => $label)
            <a href="{{ route('invoices.index', array_merge(request()->except('status', 'page'), $key ? ['status' => $key] : [])) }}"
               class="rounded-full px-4 py-1.5 text-sm font-semibold {{ request('status', '') === $key ? 'bg-night text-white' : 'bg-surface text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' }}">{{ __($label) }}</a>
        @endforeach
    </div>

    <div class="card overflow-hidden">
        <form class="grid gap-3 border-b border-slate-100 p-4 sm:grid-cols-5">
            <input type="hidden" name="status" value="{{ request('status') }}">
            <input name="q" value="{{ request('q') }}" placeholder="{{ __('رقم الفاتورة أو العميل') }}" class="input sm:col-span-2">
            <select name="client_id" class="input">
                <option value="">{{ __('كل العملاء') }}</option>
                @foreach($clients as $c)<option value="{{ $c->id }}" @selected(request('client_id') == $c->id)>{{ $c->name }}</option>@endforeach
            </select>
            <input type="date" name="from" value="{{ request('from') }}" class="input" title="{{ __('استحقاق من') }}">
            <div class="flex gap-2"><input type="date" name="to" value="{{ request('to') }}" class="input" title="{{ __('استحقاق إلى') }}"><button class="btn-secondary">{{ __('تصفية') }}</button></div>
        </form>
        <form action="{{ route('reminders.create') }}" x-data="{ picked: [] }">
        <div x-show="picked.length" x-cloak class="flex items-center justify-between gap-3 border-b border-brand-100 bg-brand-50 px-4 py-3 text-sm">
            <span class="font-semibold text-brand-800"><span x-text="picked.length"></span> {{ __('فاتورة محددة') }}</span>
            <button class="btn-primary py-1.5"><x-icon name="bell" class="size-4" /> {{ __('أرسل تذكيراً للمحدد') }}</button>
        </div>
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                <tr>
                    <th class="w-10"></th><th>{{ __('الرقم') }}</th><th>{{ __('العميل') }}</th><th>{{ __('الإصدار') }}</th>
                    <th><a href="{{ route('invoices.index', array_merge(request()->query(), ['sort' => 'due_date', 'dir' => request('dir') === 'asc' ? 'desc' : 'asc'])) }}">{{ __('الاستحقاق ↕') }}</a></th>
                    <th><a href="{{ route('invoices.index', array_merge(request()->query(), ['sort' => 'amount', 'dir' => request('dir') === 'desc' ? 'asc' : 'desc'])) }}">{{ __('المبلغ ↕') }}</a></th>
                    <th>{{ __('المتبقي') }}</th><th>{{ __('الحالة') }}</th><th>{{ __('تذكيرات') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse($invoices as $invoice)
                    <tr>
                        <td>@if(in_array($invoice->status, ['unpaid', 'partial'], true))<input type="checkbox" name="invoices[]" value="{{ $invoice->id }}" x-model="picked" class="rounded text-brand-600">@endif</td>
                        <td><a href="{{ route('invoices.show', $invoice) }}" class="font-semibold text-brand-700 hover:underline">{{ $invoice->number }}</a></td>
                        <td><a href="{{ route('clients.show', $invoice->client_id) }}" class="hover:text-brand-700">{{ $invoice->client->name }}</a></td>
                        <td class="num text-slate-500">{{ $invoice->issue_date->format('Y-m-d') }}</td>
                        <td class="num">{{ $invoice->due_date->format('Y-m-d') }}</td>
                        <td class="num">@money($invoice->amount)</td>
                        <td class="num font-semibold">@money($invoice->balance())</td>
                        <td><x-status :invoice="$invoice" /></td>
                        <td class="num text-center text-slate-500">{{ $invoice->reminders_count }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="py-12 text-center text-slate-400">{{ __('لا توجد فواتير مطابقة') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        </form>
        <div class="p-4">{{ $invoices->links() }}</div>
    </div>
</x-layouts.app>
