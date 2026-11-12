<x-layouts.app :title="__('ربط نظام المحاسبة')">
    <x-page-header :title="__('ربط نظام المحاسبة')" :subtitle="__('اجعل نظامكم (ERP أو برنامج المحاسبة) يرسل تقرير الفواتير المفتوحة يومياً إلى عنوانكم الخاص، ويتولى تحصيلي الباقي: يضيف الجديد، ويحدّث المدفوع جزئياً، ويغلق ما دُفع — دون إدخال أي شيء مرتين.')" />

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card p-6 lg:col-span-2" x-data="{ copied: false }">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="font-semibold text-ink">{{ __('عنوانكم الخاص لاستقبال التقرير') }}</h2>
                @if(! $feed->enabled)
                    <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">{{ __('متوقف') }}</span>
                @elseif($feed->last_received_at)
                    <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">{{ __('آخر تقرير: :time', ['time' => $feed->last_received_at->diffForHumans()]) }}</span>
                @else
                    <span class="rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20">{{ __('بانتظار أول تقرير') }}</span>
                @endif
            </div>
            <div class="mt-3 flex flex-wrap gap-2">
                <input readonly value="{{ $feed->address() }}" dir="ltr" class="input min-w-0 flex-1 font-mono text-sm" @focus="$event.target.select()">
                <button type="button" class="btn-primary" @click="navigator.clipboard.writeText(@js($feed->address())).catch(() => {}); copied = true; setTimeout(() => copied = false, 1500)">
                    <span x-text="copied ? @js(__('تم النسخ ✓')) : @js(__('نسخ العنوان'))"></span>
                </button>
            </div>
            <p class="mt-2 text-xs text-slate-500">{{ __('العنوان سري وخاص بشركتكم. لا نقبل عليه إلا التقارير القادمة من المرسلين المسموحين أدناه.') }}</p>

            <h3 class="mt-8 font-semibold text-ink">{{ __('التفعيل في نظامكم — مرة واحدة') }}</h3>
            <ol class="mt-3 space-y-3 text-sm text-slate-600">
                <li class="flex gap-3"><span class="grid size-7 shrink-0 place-items-center rounded-full bg-brand-50 font-bold text-brand-700">1</span> <span>{{ __('في نظامكم، افتح تقرير «الفواتير غير المدفوعة» (Open Invoices / Receivables / Aging) بصيغة Excel أو CSV. يكفي أن يحتوي: رقم الفاتورة، العميل، تاريخ الاستحقاق، والمبلغ أو المتبقي.') }}</span></li>
                <li class="flex gap-3"><span class="grid size-7 shrink-0 place-items-center rounded-full bg-brand-50 font-bold text-brand-700">2</span> <span>{{ __('اجعله تقريراً مجدولاً يُرسَل يومياً (مثلاً 7 صباحاً) إلى العنوان أعلاه. هذه الميزة موجودة في أغلب الأنظمة: SAP وOracle وDynamics وOdoo والأنظمة الخاصة.') }}</span></li>
                <li class="flex gap-3"><span class="grid size-7 shrink-0 place-items-center rounded-full bg-brand-50 font-bold text-brand-700">3</span> <span>{{ __('أضف البريد الذي يرسل منه نظامكم إلى «المرسلون المسموحون». وإذا وصل التقرير من بريد آخر، سنسألك قبل تطبيقه.') }}</span></li>
            </ol>

            <div class="mt-6 rounded-xl bg-slate-50 p-4 text-sm text-slate-600 dark:bg-white/5">
                <p class="font-semibold text-ink">{{ __('ماذا يحدث مع كل تقرير؟') }}</p>
                <ul class="mt-2 list-disc space-y-1 ps-5">
                    <li>{{ __('فاتورة جديدة: تُضاف مع عميلها، وتبدأ تذكيراتها في مواعيدها.') }}</li>
                    <li>{{ __('المتبقي أقل من قبل: تُسجَّل دفعة جزئية.') }}</li>
                    <li>{{ __('فاتورة اختفت من التقرير أو أصبح رصيدها صفراً: تُغلق كمدفوعة وتتوقف تذكيراتها.') }}</li>
                    <li>{{ __('تقرير فارغ أو سيغلق معظم الفواتير دفعة واحدة: نوقفه ونطلب مراجعتك.') }}</li>
                </ul>
            </div>
        </div>

        <div class="space-y-6">
            <form method="POST" action="{{ route('owner.sync.upload') }}" enctype="multipart/form-data" class="card p-6" x-data="{ name: '' }">
                @csrf
                <h2 class="font-semibold text-ink">{{ __('جرّبها الآن') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('ارفع نسخة من التقرير لترى النتيجة فوراً، أو للمزامنة يدوياً متى شئت.') }}</p>
                <label class="relative mt-4 flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center dark:bg-white/5">
                    <x-icon name="upload" class="size-6 text-brand-700" />
                    <span class="mt-2 text-sm font-semibold text-ink" x-text="name || @js(__('اختر ملف التقرير'))"></span>
                    <span class="text-xs text-slate-500">XLSX, XLS, CSV</span>
                    <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="absolute inset-0 cursor-pointer opacity-0" @change="name = $event.target.files[0]?.name">
                </label>
                <button class="btn-primary mt-4 w-full justify-center">{{ __('مزامنة') }}</button>
            </form>

            <form method="POST" action="{{ route('owner.sync.update') }}" class="card p-6">
                @csrf @method('PUT')
                <label class="label" for="senders">{{ __('المرسلون المسموحون') }}</label>
                <textarea id="senders" name="senders" rows="3" dir="ltr" class="input font-mono text-sm" placeholder="erp@company.ae&#10;@company.ae">{{ old('senders', implode("\n", $feed->senderList())) }}</textarea>
                <p class="mt-1 text-xs text-slate-500">{{ __('بريد في كل سطر، أو @النطاق للسماح لكل بريد الشركة.') }}</p>
                <label class="mt-4 flex items-center gap-2 text-sm">
                    <input type="hidden" name="enabled" value="0">
                    <input type="checkbox" name="enabled" value="1" @checked($feed->enabled) class="rounded border-slate-300 text-brand-600">
                    {{ __('المزامنة مفعّلة') }}
                </label>
                <button class="btn-primary mt-4">{{ __('حفظ') }}</button>
            </form>

            <form method="POST" action="{{ route('owner.sync.token') }}" class="card p-6" onsubmit="return confirm(@js(__('سيتوقف العنوان الحالي عن العمل. متابعة؟')))">
                @csrf
                <p class="text-sm text-slate-500">{{ __('هل تسرّب العنوان؟ أنشئ عنواناً جديداً.') }}</p>
                <button class="btn-secondary mt-3">{{ __('إنشاء عنوان جديد') }}</button>
            </form>
        </div>
    </div>

    @if($headers)
        <form method="POST" action="{{ route('owner.sync.mapping') }}" class="card mt-6 p-6">
            @csrf @method('PUT')
            <h2 class="font-semibold text-ink">{{ __('ربط الأعمدة') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('نتعرف على أغلب العناوين تلقائياً. إذا كانت أسماء الأعمدة في تقريركم مختلفة، حدّدها هنا مرة واحدة.') }}</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                @foreach($fields as $field => $label)
                    <div>
                        <label class="label" for="map-{{ $field }}">{{ __($label) }} @if(in_array($field, ['number', 'client_name', 'due_date'], true))<b class="text-rose-500">*</b>@endif</label>
                        <select id="map-{{ $field }}" name="mapping[{{ $field }}]" class="input">
                            <option value="">{{ __('تلقائي') }}</option>
                            @foreach($headers as $header)
                                <option value="{{ $header }}" @selected(($feed->mapping[$field] ?? null) === $header)>{{ $header }}</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach
            </div>
            <p class="mt-3 text-xs text-slate-500">{{ __('المطلوب: رقم الفاتورة والعميل وتاريخ الاستحقاق، ومعها المبلغ الإجمالي أو المتبقي.') }}</p>
            <button class="btn-primary mt-4">{{ __('حفظ ربط الأعمدة') }}</button>
        </form>
    @endif

    <div class="card mt-6 overflow-hidden">
        <h2 class="border-b border-slate-100 px-5 py-4 font-semibold text-ink">{{ __('التقارير المستلمة') }}</h2>
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead><tr><th>{{ __('التاريخ') }}</th><th>{{ __('المصدر') }}</th><th>{{ __('الملف') }}</th><th>{{ __('الحالة') }}</th><th>{{ __('جديدة') }}</th><th>{{ __('محدّثة') }}</th><th>{{ __('أُغلقت') }}</th><th>{{ __('أخطاء') }}</th></tr></thead>
                <tbody>
                @forelse($runs as $run)
                    <tr>
                        <td class="num text-slate-500">{{ $run->created_at->format('Y-m-d H:i') }}</td>
                        <td class="text-sm">{{ $run->source === 'email' ? __('بريد') : __('رفع يدوي') }}<div class="text-xs text-slate-400" dir="ltr">{{ $run->sender }}</div></td>
                        <td class="text-sm">{{ $run->filename }}</td>
                        <td>
                            @php($badge = ['applied' => ['bg-emerald-50 text-emerald-700 ring-emerald-600/20', __('طُبّق')], 'rejected' => ['bg-amber-50 text-amber-700 ring-amber-600/20', __('بانتظار مراجعتك')], 'released' => ['bg-slate-100 text-slate-600 ring-slate-500/20', __('رُوجع')], 'failed' => ['bg-rose-50 text-rose-700 ring-rose-600/20', __('تعذّر')]][$run->status] ?? ['bg-slate-100 text-slate-600 ring-slate-500/20', $run->status])
                            <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $badge[0] }}">{{ $badge[1] }}</span>
                            @if($run->message && $run->status !== 'applied')
                                <p class="mt-1 max-w-sm text-xs text-slate-500">{{ $run->message }}</p>
                            @endif
                            @if($run->isHeldBack())
                                <form method="POST" action="{{ route('owner.sync.apply', $run) }}" class="mt-2">
                                    @csrf
                                    <button class="btn-secondary !py-1 text-xs">{{ $run->source === 'email' && $run->sender && ! $feed->allows($run->sender) ? __('اسمح بهذا المرسل وطبّق') : __('طبّق على أي حال') }}</button>
                                </form>
                            @endif
                            @if($run->errors)
                                <details class="mt-1 text-xs text-rose-600"><summary class="cursor-pointer">{{ __('عرض الأخطاء') }}</summary>
                                    <ul class="mt-1 space-y-0.5">@foreach(array_slice($run->errors, 0, 20) as $error)<li>{{ __('صف :row', ['row' => $error['row']]) }}: {{ $error['message'] }}</li>@endforeach</ul>
                                </details>
                            @endif
                        </td>
                        <td class="num text-emerald-600">{{ $run->added }}</td>
                        <td class="num">{{ $run->updated }}</td>
                        <td class="num text-brand-700">{{ $run->closed }}</td>
                        <td class="num {{ $run->failed ? 'text-rose-600' : '' }}">{{ $run->failed }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="py-10 text-center text-slate-400">{{ __('لم يصل أي تقرير بعد') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $runs->links() }}</div>
    </div>
</x-layouts.app>
