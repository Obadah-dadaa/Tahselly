<x-layouts.app :title="__('فاتورة').' '.$invoice->number">
    <x-page-header :title="__('فاتورة').' '.$invoice->number">
        <x-slot:actions>
            @if(in_array($invoice->status, ['unpaid', 'partial'], true))
                <form method="POST" action="{{ route('invoices.remind', $invoice) }}">@csrf<button class="btn-primary"><x-icon name="mail" class="size-4" /> {{ __('إرسال تذكير بالبريد') }}</button></form>
                @if($whatsappLink && \App\Models\SystemSetting::get('feature_whatsapp', true))
                    <a href="{{ route('invoices.whatsapp', $invoice) }}" target="_blank" class="btn-whatsapp"><x-icon name="whatsapp" class="size-4" /> {{ __('تذكير واتساب') }}</a>
                @endif
                <a href="{{ route('reminders.create', ['invoices' => [$invoice->id]]) }}" class="btn-secondary"><x-icon name="clock" class="size-4" /> {{ __('رسالة مخصصة أو مجدولة') }}</a>
            @endif
            <a href="{{ route('invoices.edit', $invoice) }}" class="btn-secondary">{{ __('تعديل') }}</a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="card p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-sm text-slate-500">{{ __('العميل') }}</p>
                        <a href="{{ route('clients.show', $invoice->client) }}" class="text-lg font-bold text-ink hover:text-brand-700">{{ $invoice->client->name }}</a>
                        <p class="text-sm text-slate-500" dir="ltr">{{ $invoice->client->email }}</p>
                    </div>
                    <x-status :invoice="$invoice" class="text-sm" />
                </div>
                <dl class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
                    @foreach([[__('تاريخ الإصدار'), $invoice->issue_date->format('Y-m-d')], [__('تاريخ الاستحقاق'), $invoice->due_date->format('Y-m-d')], [__('عدد التذكيرات'), $invoice->reminders_count], [__('آخر تذكير'), $invoice->last_reminded_at?->format('Y-m-d') ?? '—']] as [$k, $v])
                        <div class="rounded-xl bg-slate-50 p-3"><dt class="text-xs text-slate-500">{{ __($k) }}</dt><dd class="num mt-1 font-semibold">{{ $v }}</dd></div>
                    @endforeach
                </dl>
                @php $pct = (float) $invoice->amount > 0 ? min(100, (float) $invoice->paid_amount / (float) $invoice->amount * 100) : 0; @endphp
                <div class="mt-6">
                    <div class="flex justify-between text-sm"><span class="text-slate-500">{{ __('المدفوع :paid من :total', ['paid' => number_format((float) $invoice->paid_amount, 2), 'total' => number_format((float) $invoice->amount, 2)]) }} {{ $invoice->currency }}</span><span class="num font-semibold">{{ round($pct) }}%</span></div>
                    <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-brand-500" style="width: {{ $pct }}%"></div></div>
                    <p class="mt-3 text-2xl font-bold">{{ __('المتبقي:') }} <span class="num">@money($invoice->balance())</span> <span class="text-base font-medium text-slate-400">{{ $invoice->currency }}</span></p>
                </div>
                @if($invoice->description)<p class="mt-4 rounded-xl bg-slate-50 p-3 text-sm text-slate-600">{{ $invoice->description }}</p>@endif
            </div>

            <div class="card">
                <h2 class="border-b border-slate-100 px-5 py-4 font-semibold text-ink">{{ __('سجل التذكيرات') }}</h2>
                <ol class="relative space-y-0 px-5 py-4">
                    @forelse($invoice->reminders->sortByDesc('created_at') as $reminder)
                        <li class="relative border-s-2 border-slate-100 pb-5 ps-6 last:pb-0" x-data="{ open: false }">
                            <span class="absolute -start-[9px] top-0.5 size-4 rounded-full ring-4 ring-white {{ ['sent' => 'bg-brand-500', 'scheduled' => 'bg-violet-500', 'cancelled' => 'bg-slate-300'][$reminder->status] ?? 'bg-rose-500' }}"></span>
                            <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
                                <p class="font-semibold">{{ $reminder->stageLabel() }} <span class="font-normal text-slate-500">· {{ $reminder->channel === 'email' ? __('بريد إلكتروني') : __('واتساب') }} · {{ $reminder->automatic ? __('آلي') : __('يدوي').($reminder->user ? __(' بواسطة ').$reminder->user->name : '') }}</span></p>
                                <span class="flex items-center gap-2 text-xs text-slate-500">{{ ($reminder->scheduled_for && $reminder->status === 'scheduled' ? $reminder->scheduled_for : $reminder->created_at)->format('Y-m-d H:i') }} <x-status :value="$reminder->status" /></span>
                            </div>
                            @if($reminder->error)<p class="mt-1 text-xs text-rose-600">{{ $reminder->error }}</p>@endif
                            <button @click="open=!open" class="mt-1 text-xs text-brand-700 hover:underline">{{ __('عرض الرسالة') }}</button>
                            <div x-show="open" x-cloak class="mt-2 whitespace-pre-line rounded-xl bg-slate-50 p-3 text-xs text-slate-600">@if($reminder->subject)<strong>{{ $reminder->subject }}</strong>&#10;@endif{{ $reminder->body }}</div>
                        </li>
                    @empty
                        <li class="py-6 text-center text-sm text-slate-400">{{ __('لم يتم إرسال تذكيرات بعد') }}</li>
                    @endforelse
                </ol>
            </div>
        </div>

        <div class="space-y-6">
            @if(in_array($invoice->status, ['unpaid', 'partial'], true))
                <form method="POST" action="{{ route('payments.store', $invoice) }}" class="card space-y-4 p-5">
                    @csrf
                    <h2 class="font-semibold text-ink">{{ __('تسجيل دفعة') }}</h2>
                    <div><label class="label">{{ __('المبلغ') }}</label><input type="number" step="0.01" name="amount" value="{{ $invoice->balance() }}" max="{{ $invoice->balance() }}" required class="input num"></div>
                    <div><label class="label">{{ __('تاريخ الدفع') }}</label><input type="date" name="paid_on" value="{{ today()->format('Y-m-d') }}" required class="input"></div>
                    <div><label class="label">{{ __('طريقة الدفع') }}</label>
                        <select name="method" class="input">@foreach($methods as $k => $l)<option value="{{ $k }}">{{ __($l) }}</option>@endforeach</select>
                    </div>
                    <div><label class="label">{{ __('مرجع العملية') }}</label><input name="reference" class="input" dir="ltr"></div>
                    <button class="btn-primary w-full"><x-icon name="check" class="size-4" /> {{ __('تسجيل الدفعة') }}</button>
                </form>
            @endif

            <div class="card">
                <h2 class="border-b border-slate-100 px-5 py-4 font-semibold text-ink">{{ __('الدفعات') }}</h2>
                <ul class="divide-y divide-slate-100 text-sm">
                    @forelse($invoice->payments as $payment)
                        <li class="flex items-center justify-between px-5 py-3">
                            <div>
                                <p class="num font-semibold">@money($payment->amount)</p>
                                <p class="text-xs text-slate-500">{{ $payment->paid_on->format('Y-m-d') }} · {{ __($methods[$payment->method] ?? $payment->method) }} {{ $payment->reference ? '· '.$payment->reference : '' }}</p>
                            </div>
                            <form method="POST" action="{{ route('payments.destroy', $payment) }}" x-data @submit.prevent="if (confirm(@js(__('حذف الدفعة؟')))) $el.submit()">@csrf @method('DELETE')<button class="text-xs text-rose-500 hover:underline">{{ __('حذف') }}</button></form>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-slate-400">{{ __('لا توجد دفعات') }}</li>
                    @endforelse
                </ul>
            </div>

            <div class="card space-y-2 p-5">
                @if($invoice->status !== 'cancelled' && $invoice->status !== 'paid')
                    <form method="POST" action="{{ route('invoices.cancel', $invoice) }}" x-data @submit.prevent="if (confirm(@js(__('إلغاء الفاتورة؟')))) $el.submit()">@csrf<button class="btn-secondary w-full">{{ __('إلغاء الفاتورة') }}</button></form>
                @endif
                @if(in_array($invoice->status, ['unpaid', 'partial'], true))
                    <form method="POST" action="{{ route('invoices.pause', $invoice) }}">@csrf
                        <button class="btn-secondary w-full">{{ $invoice->reminders_paused ? __('تفعيل التذكيرات الآلية') : __('إيقاف التذكيرات الآلية لهذه الفاتورة') }}</button>
                    </form>
                    @if($invoice->reminders_paused)<p class="text-center text-xs text-amber-700">{{ __('التذكيرات الآلية موقوفة لهذه الفاتورة') }}</p>@endif
                @endif
                <form method="POST" action="{{ route('invoices.destroy', $invoice) }}" x-data @submit.prevent="if (confirm(@js(__('حذف الفاتورة نهائياً؟')))) $el.submit()">@csrf @method('DELETE')<button class="btn-secondary w-full text-rose-600">{{ __('حذف الفاتورة') }}</button></form>
            </div>
        </div>
    </div>
</x-layouts.app>
