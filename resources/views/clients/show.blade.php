<x-layouts.app :title="$client->name">
    <x-page-header :title="$client->name" :subtitle="$client->contact_name">
        <x-slot:actions>
            <a href="{{ route('invoices.create', ['client_id' => $client->id]) }}" class="btn-primary"><x-icon name="plus" class="size-4" /> {{ __('فاتورة لهذا العميل') }}</a>
            <a href="{{ route('clients.edit', $client) }}" class="btn-secondary">{{ __('تعديل') }}</a>
            <form method="POST" action="{{ route('clients.destroy', $client) }}" x-data @submit.prevent="if (confirm(@js(__('حذف العميل وكل فواتيره؟')))) $el.submit()">@csrf @method('DELETE')<button class="btn-secondary text-rose-600">{{ __('حذف') }}</button></form>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card space-y-3 p-5 text-sm">
            <h2 class="font-semibold text-ink">{{ __('بيانات العميل') }}</h2>
            <p class="flex justify-between"><span class="text-slate-500">{{ __('البريد') }}</span><span dir="ltr">{{ $client->email ?? '—' }}</span></p>
            <p class="flex justify-between"><span class="text-slate-500">{{ __('الهاتف') }}</span><span dir="ltr">{{ $client->phone ?? '—' }}</span></p>
            <p class="flex justify-between"><span class="text-slate-500">TRN</span><span dir="ltr">{{ $client->trn ?? '—' }}</span></p>
            <p class="flex justify-between"><span class="text-slate-500">{{ __('التذكيرات الآلية') }}</span><span>{{ $client->reminders_enabled ? __('مفعّلة') : __('موقوفة') }}</span></p>
            @if($client->notes)<p class="rounded-xl bg-slate-50 p-3 text-slate-600">{{ $client->notes }}</p>@endif
        </div>
        @if($aging)
        <div class="card p-5 lg:col-span-2">
            <div class="flex items-center justify-between">
                <h2 class="flex items-center gap-2 font-semibold text-ink">{{ __('أعمار ديون العميل') }} <x-plan-badge feature="client_aging" /></h2>
                <p class="num text-xl font-bold">@money($aging['grand']) <span class="text-sm font-medium text-slate-400">{{ auth()->user()->company->currency }}</span></p>
            </div>
            <div class="mt-4 grid grid-cols-5 gap-2 text-center">
                @foreach(\App\Services\ReceivablesReport::BUCKETS as $key => $label)
                    <div class="rounded-xl p-3 {{ $key === 'current' ? 'bg-brand-50' : ($key === 'd90_plus' ? 'bg-rose-50' : 'bg-slate-50') }}">
                        <p class="text-[11px] text-slate-500">{{ __($label) }}</p>
                        <p class="num mt-1 text-sm font-bold">@short($aging['totals'][$key])</p>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <div class="card mt-6 overflow-hidden">
        <h2 class="border-b border-slate-100 px-5 py-4 font-semibold text-ink">{{ __('الفواتير') }} ({{ $client->invoices->count() }})</h2>
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead><tr><th>{{ __('الرقم') }}</th><th>{{ __('الإصدار') }}</th><th>{{ __('الاستحقاق') }}</th><th>{{ __('المبلغ') }}</th><th>{{ __('المتبقي') }}</th><th>{{ __('الحالة') }}</th></tr></thead>
                <tbody>
                @foreach($client->invoices as $invoice)
                    <tr>
                        <td><a href="{{ route('invoices.show', $invoice) }}" class="font-semibold text-brand-700 hover:underline">{{ $invoice->number }}</a></td>
                        <td class="num">{{ $invoice->issue_date->format('Y-m-d') }}</td>
                        <td class="num">{{ $invoice->due_date->format('Y-m-d') }}</td>
                        <td class="num">@money($invoice->amount)</td>
                        <td class="num font-semibold">@money($invoice->balance())</td>
                        <td><x-status :invoice="$invoice" /></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mt-6">
        <h2 class="border-b border-slate-100 px-5 py-4 font-semibold text-ink">{{ __('آخر التذكيرات') }}</h2>
        <ul class="divide-y divide-slate-100 text-sm">
            @forelse($client->reminders as $reminder)
                <li class="flex items-center justify-between px-5 py-3">
                    <span>{{ $reminder->invoice->number }} · {{ $reminder->stageLabel() }} · {{ $reminder->channel === 'email' ? __('بريد') : __('واتساب') }}</span>
                    <span class="flex items-center gap-3 text-xs text-slate-500">{{ $reminder->created_at->format('Y-m-d H:i') }} <x-status :value="$reminder->status" /></span>
                </li>
            @empty
                <li class="px-5 py-8 text-center text-slate-400">{{ __('لا توجد تذكيرات') }}</li>
            @endforelse
        </ul>
    </div>
</x-layouts.app>
