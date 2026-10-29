<x-layouts.app :title="__('مركز التذكيرات')">
    <x-page-header :title="__('مركز التذكيرات')" :subtitle="__('أرسل تذكيراً الآن، جدوله لوقت لاحق، أو اترك النظام يذكّر عملاءك تلقائياً.')">
        <x-slot:actions>
            <form method="POST" action="{{ route('reminders.run') }}">@csrf<button class="btn-secondary"><x-icon name="bolt" class="size-4" /> {{ __('شغّل التذكيرات الآلية الآن') }}</button></form>
            <a href="{{ route('reminders.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> {{ __('تذكير جديد') }}</a>
        </x-slot:actions>
    </x-page-header>

    {{-- How it works --}}
    <div class="card mb-6 overflow-hidden" x-data="{ open: {{ $stats->total ? 'false' : 'true' }} }">
        <button @click="open=!open" class="flex w-full items-center justify-between px-5 py-4 text-start">
            <span class="flex items-center gap-3"><span class="grid size-9 place-items-center rounded-xl bg-brand-50 text-brand-700"><x-icon name="bell" class="size-5" /></span>
                <span><span class="block font-semibold text-ink">{{ __('كيف تعمل التذكيرات؟') }}</span><span class="text-sm text-slate-500">{{ __('دليل سريع في 3 خطوات') }}</span></span></span>
            <span class="text-slate-400" x-text="open ? '−' : '+'"></span>
        </button>
        <div x-show="open" x-transition class="grid gap-4 border-t border-slate-100 p-5 md:grid-cols-3">
            <div class="rounded-2xl bg-slate-50 p-4">
                <p class="font-semibold text-ink">{{ __('١. التذكيرات الآلية') }}</p>
                <p class="mt-1 text-sm text-slate-600">{{ __('كل يوم الساعة 9:00 صباحاً يفحص النظام الفواتير المفتوحة ويرسل التذكير المناسب حسب الجدول بالأسفل — بدون أي تدخل منك.') }}</p>
            </div>
            <div class="rounded-2xl bg-slate-50 p-4">
                <p class="font-semibold text-ink">{{ __('٢. تذكير يدوي أو مجدول') }}</p>
                <p class="mt-1 text-sm text-slate-600">{{ __('اضغط «تذكير جديد»، اختر فاتورة أو أكثر، عدّل الرسالة إن أردت، ثم أرسلها فوراً أو حدد موعداً لإرسالها.') }}</p>
            </div>
            <div class="rounded-2xl bg-slate-50 p-4">
                <p class="font-semibold text-ink">{{ __('٣. تابع كل شيء هنا') }}</p>
                <p class="mt-1 text-sm text-slate-600">{{ __('«القادمة» تعرض ما سيُرسل آلياً خلال 14 يوماً، «المجدولة» ما حددته أنت، و«السجل» كل ما أُرسل فعلاً.') }}</p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2 border-t border-slate-100 px-5 py-4 text-sm">
            <span class="font-semibold text-slate-500">{{ __('الجدول الآلي:') }}</span>
            @if(! $settings['enabled'])<span class="rounded-full bg-rose-50 px-3 py-1 font-semibold text-rose-700">{{ __('متوقف') }}</span>@endif
            @foreach($settings['before_days'] as $d)<span class="rounded-full bg-brand-50 px-3 py-1 text-brand-800">{{ __('قبل :d أيام', ['d' => $d]) }}</span>@endforeach
            @if($settings['on_due'])<span class="rounded-full bg-amber-50 px-3 py-1 text-amber-800">{{ __('يوم الاستحقاق') }}</span>@endif
            @foreach($settings['after_days'] as $d)<span class="rounded-full bg-orange-50 px-3 py-1 text-orange-800">{{ __('بعد :d يوم', ['d' => $d]) }}</span>@endforeach
            @if($settings['final_after'])<span class="rounded-full bg-rose-50 px-3 py-1 text-rose-800">{{ __('إنذار نهائي بعد :d يوم', ['d' => $settings['final_after']]) }}</span>@endif
            @if(auth()->user()->isOwner())<a href="{{ route('owner.settings.edit') }}" class="ms-auto font-semibold text-brand-700 hover:underline">{{ __('تعديل الجدول والرسائل') }} ←</a>@endif
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-kpi :label="__('تذكيرات آخر 30 يوم')" :value="(int) $stats->total" icon="bell" />
        <x-kpi :label="__('تم الإرسال')" :value="(int) $stats->sent" icon="check" tone="sky" />
        <x-kpi :label="__('فشل')" :value="(int) $stats->failed" icon="alert" tone="rose" :hint="__('غالباً بسبب بريد مفقود للعميل')" />
        <x-kpi :label="__('مجدولة بانتظار الإرسال')" :value="$scheduled->count()" icon="clock" tone="violet" />
    </div>

    <div class="mt-6 flex gap-2 border-b border-slate-200">
        @foreach(array_filter(['whatsapp' => $whatsappOn ? __('واتساب اليوم') : null, 'upcoming' => __('القادمة (آلية)'), 'scheduled' => __('المجدولة'), 'log' => __('السجل')]) as $key => $label)
            <a href="{{ route('reminders.index', ['tab' => $key]) }}" class="-mb-px border-b-2 px-4 py-2 text-sm font-semibold {{ $tab === $key ? 'border-brand-600 text-brand-700' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                {{ __($label) }}
                @if($key === 'whatsapp' && $whatsappQueue->count())<span class="ms-1 rounded-full bg-green-50 px-2 text-xs text-green-700">{{ $whatsappQueue->count() }}</span>@endif
                @if($key === 'scheduled' && $scheduled->count())<span class="ms-1 rounded-full bg-violet-50 px-2 text-xs text-violet-700">{{ $scheduled->count() }}</span>@endif
            </a>
        @endforeach
    </div>

    @if($tab === 'whatsapp')
        @php $stageLabels = ['before_due' => __('قبل الاستحقاق'), 'on_due' => __('يوم الاستحقاق'), 'overdue' => __('متأخرة'), 'final' => __('إنذار نهائي')]; @endphp
        <div class="card overflow-hidden" x-data="{ done: [] }">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 bg-green-50 px-5 py-4">
                <div class="flex items-center gap-3">
                    <span class="grid size-10 place-items-center rounded-xl bg-[#25D366] text-white"><x-icon name="whatsapp" class="size-5" /></span>
                    <div>
                        <p class="font-semibold text-ink">{{ __('تذكيرات واتساب المستحقة اليوم') }}</p>
                        <p class="text-xs text-slate-500">{{ __('اضغط «إرسال» لكل عميل: يفتح واتساب والرسالة جاهزة، وتُسجَّل في السجل تلقائياً.') }}</p>
                    </div>
                </div>
                <p class="text-sm font-semibold text-slate-600"><span class="num" x-text="{{ $whatsappQueue->count() }} - done.length">{{ $whatsappQueue->count() }}</span> {{ __('متبقية') }}</p>
            </div>
            <ul class="divide-y divide-slate-100">
                @forelse($whatsappQueue as $row)
                    @php $inv = $row['invoice']; @endphp
                    <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 transition" :class="done.includes({{ $inv->id }}) && 'opacity-50'">
                        <div class="min-w-0">
                            <p class="font-semibold text-ink">{{ $inv->client->name }} <span class="text-xs font-normal text-slate-400" dir="ltr">+{{ $inv->client->whatsappNumber() }}</span></p>
                            <p class="text-sm text-slate-500">
                                <a href="{{ route('invoices.show', $inv) }}" class="font-medium text-brand-700 hover:underline">{{ $inv->number }}</a>
                                · <span class="num">@money($inv->balance())</span> {{ $inv->currency }}
                                · {{ __('الاستحقاق :date', ['date' => $inv->due_date->format('Y-m-d')]) }}
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ in_array($row['stage'], ['overdue', 'final'], true) ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700' }}">{{ $stageLabels[$row['stage']] ?? $row['stage'] }}</span>
                            <template x-if="! done.includes({{ $inv->id }})">
                                <a href="{{ route('reminders.whatsapp', $inv) }}" target="_blank" rel="noopener" @click="done.push({{ $inv->id }})" class="btn-whatsapp"><x-icon name="whatsapp" class="size-4" /> {{ __('إرسال') }}</a>
                            </template>
                            <template x-if="done.includes({{ $inv->id }})">
                                <span class="inline-flex items-center gap-1 text-sm font-semibold text-emerald-700"><x-icon name="check" class="size-4" /> {{ __('تم') }}</span>
                            </template>
                        </div>
                    </li>
                @empty
                    <li class="px-5 py-12 text-center text-slate-400">{{ __('لا توجد تذكيرات واتساب مستحقة اليوم 🎉') }}</li>
                @endforelse
            </ul>
        </div>
    @elseif($tab === 'upcoming')
        <div class="mt-6 space-y-4">
            @forelse($upcoming as $date => $rows)
                @php $day = \Carbon\Carbon::parse($date); @endphp
                <div class="card overflow-hidden">
                    <div class="flex items-center justify-between bg-slate-50 px-5 py-3">
                        <p class="font-semibold text-ink">{{ $day->isToday() ? __('اليوم') : ($day->isTomorrow() ? __('غداً') : $day->locale(app()->getLocale())->translatedFormat('l j F')) }}</p>
                        <span class="text-xs text-slate-500">{{ __(':count تذكير · الساعة 9:00', ['count' => $rows->count()]) }}</span>
                    </div>
                    <ul class="divide-y divide-slate-100">
                        @foreach($rows as $row)
                            <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-3 text-sm">
                                <span class="flex items-center gap-3">
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ ['before_due' => 'bg-brand-50 text-brand-800', 'on_due' => 'bg-amber-50 text-amber-800', 'overdue' => 'bg-orange-50 text-orange-800', 'final' => 'bg-rose-50 text-rose-800'][$row['stage']] }}">{{ __(\App\Models\Reminder::STAGE_LABELS[$row['stage']]) }}</span>
                                    <a href="{{ route('invoices.show', $row['invoice']) }}" class="font-semibold text-ink hover:text-brand-700">{{ $row['invoice']->client->name }}</a>
                                    <span class="text-slate-400">{{ $row['invoice']->number }}</span>
                                </span>
                                <span class="flex items-center gap-4">
                                    <span class="num font-semibold">@money($row['invoice']->balance())</span>
                                    <span class="text-xs text-slate-400" dir="ltr">{{ $row['invoice']->client->email ?? __('بدون بريد!') }}</span>
                                    <form method="POST" action="{{ route('invoices.pause', $row['invoice']) }}">@csrf<button class="text-xs text-slate-500 hover:text-rose-600" title="{{ __('إيقاف التذكيرات الآلية لهذه الفاتورة') }}">{{ __('إيقاف') }}</button></form>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @empty
                <div class="card p-10 text-center text-slate-400">{{ __('لا توجد تذكيرات آلية خلال الـ 14 يوماً القادمة.') }}</div>
            @endforelse
        </div>
    @elseif($tab === 'scheduled')
        <div class="card mt-6 overflow-hidden">
            <table class="table-base">
                <thead><tr><th>{{ __('موعد الإرسال') }}</th><th>{{ __('العميل') }}</th><th>{{ __('الفاتورة') }}</th><th>{{ __('القناة') }}</th><th>{{ __('بواسطة') }}</th><th></th></tr></thead>
                <tbody>
                @forelse($scheduled as $reminder)
                    <tr>
                        <td class="num whitespace-nowrap font-semibold">{{ $reminder->scheduled_for->format('Y-m-d H:i') }}</td>
                        <td>{{ $reminder->client->name }}</td>
                        <td><a href="{{ route('invoices.show', $reminder->invoice_id) }}" class="font-semibold text-brand-700 hover:underline">{{ $reminder->invoice->number }}</a></td>
                        <td>{{ $reminder->channel === 'email' ? __('بريد') : __('واتساب') }}</td>
                        <td class="text-xs">{{ $reminder->user?->name }}</td>
                        <td><form method="POST" action="{{ route('reminders.cancel', $reminder) }}">@csrf<button class="text-xs text-rose-600 hover:underline">{{ __('إلغاء') }}</button></form></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-12 text-center text-slate-400">{{ __('لا توجد تذكيرات مجدولة.') }} <a href="{{ route('reminders.create') }}" class="font-semibold text-brand-700">{{ __('جدول تذكيراً') }}</a></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    @else
        <div class="card mt-6 overflow-hidden">
            <form class="flex flex-wrap gap-2 border-b border-slate-100 p-4">
                <input type="hidden" name="tab" value="log">
                <select name="channel" class="input w-auto"><option value="">{{ __('كل القنوات') }}</option><option value="email" @selected(request('channel') === 'email')>{{ __('بريد') }}</option><option value="whatsapp" @selected(request('channel') === 'whatsapp')>{{ __('واتساب') }}</option></select>
                <select name="status" class="input w-auto"><option value="">{{ __('كل الحالات') }}</option><option value="sent" @selected(request('status') === 'sent')>{{ __('مُرسل') }}</option><option value="failed" @selected(request('status') === 'failed')>{{ __('فشل') }}</option></select>
                <select name="stage" class="input w-auto"><option value="">{{ __('كل المراحل') }}</option>@foreach(\App\Models\Reminder::STAGE_LABELS as $k => $l)<option value="{{ $k }}" @selected(request('stage') === $k)>{{ __($l) }}</option>@endforeach</select>
                <button class="btn-secondary">{{ __('تصفية') }}</button>
            </form>
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead><tr><th>{{ __('التاريخ') }}</th><th>{{ __('العميل') }}</th><th>{{ __('الفاتورة') }}</th><th>{{ __('المرحلة') }}</th><th>{{ __('القناة') }}</th><th>{{ __('المستلم') }}</th><th>{{ __('النوع') }}</th><th>{{ __('الحالة') }}</th></tr></thead>
                    <tbody>
                    @forelse($reminders as $reminder)
                        <tr>
                            <td class="num whitespace-nowrap text-slate-500">{{ $reminder->created_at->format('Y-m-d H:i') }}</td>
                            <td>{{ $reminder->client->name }}</td>
                            <td><a href="{{ route('invoices.show', $reminder->invoice_id) }}" class="font-semibold text-brand-700 hover:underline">{{ $reminder->invoice->number }}</a></td>
                            <td>{{ $reminder->stageLabel() }}</td>
                            <td>{{ $reminder->channel === 'email' ? __('بريد') : __('واتساب') }}</td>
                            <td class="text-xs text-slate-500" dir="ltr">{{ $reminder->recipient ?? '—' }}</td>
                            <td class="text-xs">{{ $reminder->automatic ? __('آلي') : __('يدوي') }}</td>
                            <td><x-status :value="$reminder->status" :title="$reminder->error" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-12 text-center text-slate-400">{{ __('لا توجد تذكيرات') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $reminders->links() }}</div>
        </div>
    @endif
</x-layouts.app>
