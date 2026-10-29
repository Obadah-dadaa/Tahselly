@php
    $invoiceData = $invoices->map(fn ($i) => [
        'id' => $i->id, 'number' => $i->number, 'client' => $i->client->name, 'balance' => number_format($i->balance(), 2),
        'overdue' => $i->daysOverdue(), 'email' => (bool) $i->client->email, 'phone' => (bool) $i->client->phone,
    ])->values();
@endphp
<x-layouts.app :title="__('تذكير جديد')">
    <x-page-header :title="__('تذكير جديد')" :subtitle="__('ثلاث خطوات: اختر الفواتير، اكتب الرسالة، وحدد وقت الإرسال.')">
        <x-slot:actions><a href="{{ route('reminders.index') }}" class="btn-secondary">{{ __('رجوع') }}</a></x-slot:actions>
    </x-page-header>

    <form method="POST" action="{{ route('reminders.store') }}" class="grid gap-6 xl:grid-cols-5"
          x-data="{
              invoices: @js($invoiceData), selected: @js($selected), q: '', onlyOverdue: false,
              channel: '{{ old('channel', 'email') }}', when: '{{ old('when', 'now') }}',
              templates: { email: { subject: @js($emailTemplate?->subject), body: @js($emailTemplate?->body) }, whatsapp: { subject: '', body: @js($whatsappTemplate?->body) } },
              subject: @js(old('subject', $emailTemplate?->subject)), body: @js(old('body', $emailTemplate?->body)),
              get filtered() { return this.invoices.filter(i => (!this.onlyOverdue || i.overdue > 0) && (i.client + ' ' + i.number).toLowerCase().includes(this.q.toLowerCase())) },
              toggleAll() { const ids = this.filtered.map(i => i.id); const all = ids.every(id => this.selected.includes(id)); this.selected = all ? this.selected.filter(id => !ids.includes(id)) : [...new Set([...this.selected, ...ids])] },
              useChannel(c) { this.channel = c; this.subject = this.templates[c].subject; this.body = this.templates[c].body },
              insert(p) { this.body = (this.body || '') + ' ' + p },
              get missing() { return this.invoices.filter(i => this.selected.includes(i.id) && !(this.channel === 'email' ? i.email : i.phone)).length },
          }">
        @csrf
        {{-- Step 1: invoices --}}
        <section class="card overflow-hidden xl:col-span-2">
            <div class="border-b border-slate-100 p-5">
                <h2 class="font-semibold text-ink">{{ __('١. اختر الفواتير') }} <span class="text-sm font-normal text-slate-500">(<span x-text="selected.length"></span> {{ __('محددة') }})</span></h2>
                <div class="mt-3 flex gap-2">
                    <input x-model="q" placeholder="{{ __('ابحث بالعميل أو رقم الفاتورة') }}" class="input">
                    <label class="flex shrink-0 items-center gap-1.5 text-xs"><input type="checkbox" x-model="onlyOverdue" class="rounded text-brand-600"> {{ __('المتأخرة فقط') }}</label>
                </div>
                <button type="button" @click="toggleAll()" class="mt-2 text-xs font-semibold text-brand-700 hover:underline">{{ __('تحديد / إلغاء تحديد الكل') }}</button>
            </div>
            <div class="max-h-[30rem] divide-y divide-slate-100 overflow-y-auto">
                <template x-for="inv in filtered" :key="inv.id">
                    <label class="flex cursor-pointer items-center gap-3 px-5 py-3 text-sm hover:bg-slate-50" :class="selected.includes(inv.id) && 'bg-brand-50'">
                        <input type="checkbox" name="invoices[]" :value="inv.id" x-model.number="selected" class="rounded text-brand-600">
                        <span class="min-w-0 flex-1">
                            <span class="block truncate font-semibold text-ink" x-text="inv.client"></span>
                            <span class="text-xs text-slate-500"><span x-text="inv.number"></span> · <span class="num" x-text="inv.balance"></span></span>
                        </span>
                        <span x-show="inv.overdue > 0" class="rounded-full bg-rose-50 px-2 py-0.5 text-xs font-semibold text-rose-700"><span x-text="inv.overdue"></span> {{ __('يوم') }}</span>
                    </label>
                </template>
                <p x-show="!filtered.length" class="p-8 text-center text-sm text-slate-400">{{ __('لا توجد فواتير مستحقة مطابقة') }}</p>
            </div>
        </section>

        <div class="space-y-6 xl:col-span-3">
            {{-- Step 2: message --}}
            <section class="card p-5">
                <h2 class="font-semibold text-ink">{{ __('٢. القناة والرسالة') }}</h2>
                <div class="mt-4 grid grid-cols-2 gap-3">
                    <button type="button" @click="useChannel('email')" class="flex items-center justify-center gap-2 rounded-2xl border-2 p-3 font-semibold" :class="channel==='email' ? 'border-brand-600 bg-brand-50 text-brand-800' : 'border-slate-200 text-slate-600'"><x-icon name="mail" class="size-5" /> {{ __('بريد إلكتروني') }}</button>
                    <button type="button" @click="useChannel('whatsapp')" class="flex items-center justify-center gap-2 rounded-2xl border-2 p-3 font-semibold" :class="channel==='whatsapp' ? 'border-[#25D366] bg-green-50 text-green-700' : 'border-slate-200 text-slate-600'"><x-icon name="whatsapp" class="size-5" /> {{ __('واتساب') }} <x-plan-badge feature="whatsapp" /></button>
                </div>
                <input type="hidden" name="channel" :value="channel">
                <div class="mt-4 space-y-3">
                    <div x-show="channel==='email'"><label class="label">{{ __('عنوان الرسالة') }}</label><input name="subject" x-model="subject" class="input"></div>
                    <div><label class="label">{{ __('نص الرسالة') }}</label><textarea name="body" x-model="body" rows="8" required class="input text-sm leading-relaxed"></textarea></div>
                    <div class="flex flex-wrap gap-1.5">
                        <span class="text-xs text-slate-500">{{ __('أدرج:') }}</span>
                        @foreach($placeholders as $p)<button type="button" @click="insert('{{ $p }}')" class="rounded-lg bg-slate-100 px-2 py-0.5 text-xs text-slate-600 hover:bg-brand-50 hover:text-brand-700" dir="ltr">{{ $p }}</button>@endforeach
                    </div>
                    <p class="text-xs text-slate-500">{{ __('المتغيرات تُستبدل تلقائياً ببيانات كل فاتورة (اسم العميل، المبلغ، التاريخ…).') }}</p>
                    <p x-show="missing" x-cloak class="rounded-xl bg-amber-50 px-3 py-2 text-xs text-amber-800"><span x-text="missing"></span> {{ __('من الفواتير المحددة ليس لعملائها بيانات تواصل لهذه القناة وسيفشل إرسالها.') }}</p>
                </div>
            </section>

            {{-- Step 3: when --}}
            <section class="card p-5">
                <h2 class="font-semibold text-ink">{{ __('٣. متى يُرسل؟') }}</h2>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <label class="cursor-pointer rounded-2xl border-2 p-4" :class="when==='now' ? 'border-brand-600 bg-brand-50' : 'border-slate-200'">
                        <input type="radio" name="when" value="now" x-model="when" class="sr-only">
                        <span class="font-semibold text-ink">{{ __('الآن') }}</span>
                        <span class="block text-xs text-slate-500" x-text="channel==='whatsapp' ? @js(__('ستظهر لك روابط واتساب جاهزة للإرسال')) : @js(__('يُرسل البريد فوراً'))"></span>
                    </label>
                    <label class="cursor-pointer rounded-2xl border-2 p-4" :class="when==='later' ? 'border-brand-600 bg-brand-50' : 'border-slate-200'">
                        <input type="radio" name="when" value="later" x-model="when" class="sr-only">
                        <span class="font-semibold text-ink">{{ __('في موعد محدد') }}</span>
                        <span class="block text-xs text-slate-500">{{ __('اختر اليوم والساعة') }}</span>
                    </label>
                </div>
                <div x-show="when==='later'" x-cloak class="mt-3">
                    <input type="datetime-local" name="scheduled_for" value="{{ old('scheduled_for', now()->addDay()->setTime(9, 0)->format('Y-m-d\TH:i')) }}" class="input">
                </div>
                <button class="btn-primary mt-5 w-full py-3 text-base" :disabled="!selected.length">
                    <span x-show="when==='now'">{{ __('أرسل التذكير') }} (<span x-text="selected.length"></span>)</span>
                    <span x-show="when==='later'" x-cloak>{{ __('جدولة التذكير') }} (<span x-text="selected.length"></span>)</span>
                </button>
            </section>
        </div>
    </form>
</x-layouts.app>
