@php
    $title = __('مولّد رسائل تذكير بالدفع — مجاني');
    $description = __('اكتب اسم العميل والمبلغ وتاريخ الاستحقاق، واحصل فوراً على رسالة تذكير بالدفع مهذّبة بالعربية أو الإنجليزية، جاهزة للإرسال بالبريد أو واتساب.');
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'WebApplication',
        'name' => $title,
        'description' => $description,
        'url' => route('tools.reminder'),
        'applicationCategory' => 'BusinessApplication',
        'operatingSystem' => 'Any',
        'inLanguage' => ['ar', 'en'],
        'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'AED'],
        'publisher' => ['@type' => 'Organization', 'name' => 'Tahselly', 'url' => url('/')],
    ];
    // Message wording per language and stage. Placeholders are filled in the browser; nothing is sent to the server.
    $templates = [
        'ar' => [
            'before' => ['subject' => 'تذكير ودّي: الفاتورة {number} تستحق في {due}', 'body' => "مرحباً {client}،\n\nنأمل أن تكونوا بخير. نودّ تذكيركم بأن الفاتورة رقم {number} بمبلغ {amount} تستحق الدفع في {due}.\n\n{pay}إذا كنتم قد رتّبتم الدفع بالفعل فشكراً لكم، ويمكنكم تجاهل هذه الرسالة.\n\nمع خالص التحية،\n{company}"],
            'due' => ['subject' => 'الفاتورة {number} تستحق اليوم', 'body' => "مرحباً {client}،\n\nنذكّركم بلطف بأن الفاتورة رقم {number} بمبلغ {amount} تستحق الدفع اليوم ({due}).\n\n{pay}نشكر لكم تعاونكم الدائم، ولا تتردّدوا في التواصل معنا إذا كان لديكم أي استفسار.\n\nمع التحية،\n{company}"],
            'late' => ['subject' => 'الفاتورة {number} متأخرة {days} يوماً', 'body' => "مرحباً {client}،\n\nلاحظنا أن الفاتورة رقم {number} بمبلغ {amount}، والتي استحقت في {due}، ما زالت غير مدفوعة منذ {days} يوماً.\n\nقد يكون الأمر سهواً، لذا نرجو التكرّم بترتيب الدفع في أقرب وقت ممكن. {pay}وإذا كان هناك ما يؤخر الدفع، أخبرونا لنجد حلاً مناسباً معاً.\n\nمع التقدير،\n{company}"],
            'firm' => ['subject' => 'متابعة: الفاتورة {number} متأخرة {days} يوماً', 'body' => "مرحباً {client}،\n\nنتابع معكم بخصوص الفاتورة رقم {number} بمبلغ {amount}، المستحقة منذ {due}، والتي مضى على تأخرها {days} يوماً دون أن يصلنا الدفع أو ردّ منكم.\n\nنرجو تسديد المبلغ خلال 7 أيام من تاريخ هذه الرسالة، أو تزويدنا بموعد دفع واضح. {pay}\nنحرص على استمرار تعاوننا، ونقدّر استجابتكم السريعة.\n\nمع التحية،\n{company}"],
            'final' => ['subject' => 'إشعار أخير: الفاتورة {number}', 'body' => "السادة {client}،\n\nهذا إشعار أخير بخصوص الفاتورة رقم {number} بمبلغ {amount}، المستحقة منذ {due} (متأخرة {days} يوماً)، رغم تذكيراتنا السابقة.\n\nنرجو تسديد كامل المبلغ خلال 5 أيام عمل. {pay}وفي حال عدم السداد أو التواصل خلال هذه المدة، سنضطر آسفين لإيقاف الخدمات واتخاذ الإجراءات اللازمة لتحصيل المبلغ.\n\nنفضّل دائماً حل الأمر ودياً، ويسعدنا التواصل معكم.\n\n{company}"],
            'pay' => 'يمكنكم الدفع عبر: {details}' . "\n\n",
            'currency' => 'درهم',
        ],
        'en' => [
            'before' => ['subject' => 'Friendly reminder: invoice {number} is due on {due}', 'body' => "Hi {client},\n\nI hope you're well. Just a friendly reminder that invoice {number} for {amount} is due on {due}.\n\n{pay}If you've already arranged payment, thank you, and please ignore this message.\n\nBest regards,\n{company}"],
            'due' => ['subject' => 'Invoice {number} is due today', 'body' => "Hi {client},\n\nA quick reminder that invoice {number} for {amount} is due today ({due}).\n\n{pay}Thank you for your business, and let us know if you have any questions.\n\nKind regards,\n{company}"],
            'late' => ['subject' => 'Invoice {number} is {days} days overdue', 'body' => "Hi {client},\n\nOur records show that invoice {number} for {amount}, due on {due}, is now {days} days overdue.\n\nThis may simply have been missed, so we'd appreciate it if you could arrange payment at your earliest convenience. {pay}If something is holding the payment up, just let us know and we'll work it out together.\n\nBest regards,\n{company}"],
            'firm' => ['subject' => 'Follow-up: invoice {number} is {days} days overdue', 'body' => "Hi {client},\n\nI'm following up on invoice {number} for {amount}, due on {due}. It is now {days} days overdue and we haven't received payment or a reply.\n\nPlease settle the balance within 7 days of this message, or share a firm payment date. {pay}\nWe value working with you and appreciate a quick response.\n\nRegards,\n{company}"],
            'final' => ['subject' => 'Final notice: invoice {number}', 'body' => "Dear {client},\n\nThis is a final notice regarding invoice {number} for {amount}, due on {due} and now {days} days overdue despite our previous reminders.\n\nPlease pay the full amount within 5 business days. {pay}If we don't receive payment or hear from you by then, we will have to suspend services and take the necessary steps to recover the amount.\n\nWe would much rather settle this amicably and are happy to talk.\n\n{company}"],
            'pay' => 'You can pay via: {details}' . "\n\n",
            'currency' => 'AED',
        ],
    ];
@endphp
<x-layouts.guest :title="$title" :description="$description">
    <x-slot:head>
        <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    </x-slot:head>

    <div class="mx-auto max-w-5xl"
         x-data="{
            t: @js($templates),
            lang: @js(app()->getLocale() === 'en' ? 'en' : 'ar'),
            stage: 'late',
            client: '', company: '', number: '', amount: '', due: '', details: '',
            copied: '',
            days() {
                if (!this.due) return this.stage === 'final' ? 45 : (this.stage === 'firm' ? 30 : 7);
                const d = Math.floor((new Date().setHours(0,0,0,0) - new Date(this.due + 'T00:00:00')) / 86400000);
                return Math.max(d, 1);
            },
            dueText() {
                if (!this.due) return this.lang === 'ar' ? '[تاريخ الاستحقاق]' : '[due date]';
                return new Date(this.due + 'T00:00:00').toLocaleDateString(this.lang === 'ar' ? 'ar-AE' : 'en-GB', { day: 'numeric', month: 'long', year: 'numeric' });
            },
            fill(text) {
                const L = this.t[this.lang];
                const ph = (v, ar, en) => v || (this.lang === 'ar' ? ar : en);
                const amount = this.amount ? Number(this.amount.toString().replace(/,/g, '')).toLocaleString('en-US') + ' ' + L.currency : ph('', '[المبلغ]', '[amount]');
                return text
                    .replaceAll('{client}', ph(this.client, '[اسم العميل]', '[client name]'))
                    .replaceAll('{company}', ph(this.company, '[اسم شركتك]', '[your company]'))
                    .replaceAll('{number}', ph(this.number, '[رقم الفاتورة]', '[invoice no.]'))
                    .replaceAll('{amount}', amount)
                    .replaceAll('{due}', this.dueText())
                    .replaceAll('{days}', this.days())
                    .replaceAll('{pay}', this.details ? L.pay.replace('{details}', this.details) : '');
            },
            get subject() { return this.fill(this.t[this.lang][this.stage].subject) },
            get body() { return this.fill(this.t[this.lang][this.stage].body) },
            async copy(what) {
                const text = what === 'all' ? this.subject + '\n\n' + this.body : this.body;
                try { await navigator.clipboard.writeText(text); } catch (e) {}
                this.copied = what; setTimeout(() => this.copied = '', 1500);
            },
         }">
        <header class="text-center">
            <span class="inline-flex rounded-full bg-brand-50 px-3 py-1 text-sm font-semibold text-brand-700 ring-1 ring-brand-600/20">{{ __('أداة مجانية · بدون تسجيل') }}</span>
            <h1 class="mt-4 text-3xl font-bold text-ink sm:text-4xl">{{ __('مولّد رسائل تذكير بالدفع') }}</h1>
            <p class="mx-auto mt-3 max-w-2xl text-slate-600">{{ __('املأ التفاصيل واختر مرحلة التأخير، وانسخ رسالة مهذّبة وواضحة لعميلك بالعربية أو الإنجليزية. كل شيء يتم في متصفحك ولا نحفظ أي بيانات.') }}</p>
        </header>

        <div class="mt-10 grid gap-6 lg:grid-cols-5">
            {{-- Inputs --}}
            <div class="card space-y-4 p-6 lg:col-span-2">
                <div>
                    <label class="label">{{ __('لغة الرسالة') }}</label>
                    <div class="mt-1 grid grid-cols-2 gap-1 rounded-xl bg-slate-100 p-1 text-sm font-semibold">
                        <button type="button" @click="lang='ar'" :class="lang==='ar' ? 'bg-surface text-ink shadow' : 'text-slate-500'" class="rounded-lg py-2">العربية</button>
                        <button type="button" @click="lang='en'" :class="lang==='en' ? 'bg-surface text-ink shadow' : 'text-slate-500'" class="rounded-lg py-2">English</button>
                    </div>
                </div>
                <div>
                    <label class="label" for="stage">{{ __('مرحلة التذكير') }}</label>
                    <select id="stage" x-model="stage" class="input">
                        <option value="before">{{ __('قبل الاستحقاق (تذكير ودّي)') }}</option>
                        <option value="due">{{ __('يوم الاستحقاق') }}</option>
                        <option value="late">{{ __('متأخرة أسبوع تقريباً') }}</option>
                        <option value="firm">{{ __('متأخرة شهر (متابعة حازمة)') }}</option>
                        <option value="final">{{ __('إشعار أخير') }}</option>
                    </select>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                    <div><label class="label" for="client">{{ __('اسم العميل') }}</label><input id="client" x-model="client" class="input" placeholder="Blue Wave Real Estate"></div>
                    <div><label class="label" for="company">{{ __('اسم شركتك') }}</label><input id="company" x-model="company" class="input"></div>
                    <div><label class="label" for="number">{{ __('رقم الفاتورة') }}</label><input id="number" x-model="number" class="input" dir="ltr" placeholder="INV-1024"></div>
                    <div><label class="label" for="amount">{{ __('المبلغ (درهم)') }}</label><input id="amount" x-model="amount" class="input" dir="ltr" inputmode="decimal" placeholder="12,500"></div>
                </div>
                <div><label class="label" for="due">{{ __('تاريخ الاستحقاق') }}</label><input id="due" type="date" x-model="due" class="input" dir="ltr"></div>
                <div><label class="label" for="details">{{ __('طريقة الدفع (اختياري)') }}</label><input id="details" x-model="details" class="input" :placeholder="lang==='ar' ? 'تحويل بنكي — IBAN AE00…' : 'Bank transfer — IBAN AE00…'"></div>
            </div>

            {{-- Result --}}
            <div class="lg:col-span-3">
                <div class="card overflow-hidden">
                    <div class="border-b border-slate-100 px-5 py-4">
                        <p class="text-xs font-semibold text-slate-500">{{ __('الموضوع') }}</p>
                        <p class="mt-1 font-semibold text-ink" :dir="lang==='ar' ? 'rtl' : 'ltr'" x-text="subject"></p>
                    </div>
                    <pre class="min-h-72 whitespace-pre-wrap px-5 py-4 font-sans text-[15px] leading-7 text-slate-700" :dir="lang==='ar' ? 'rtl' : 'ltr'" x-text="body"></pre>
                    <div class="flex flex-wrap gap-2 border-t border-slate-100 bg-slate-50 px-5 py-4">
                        <button type="button" @click="copy('body')" class="btn-primary"><span x-text="copied==='body' ? @js(__('تم النسخ ✓')) : @js(__('نسخ الرسالة'))"></span></button>
                        <button type="button" @click="copy('all')" class="btn-secondary"><span x-text="copied==='all' ? @js(__('تم النسخ ✓')) : @js(__('نسخ مع الموضوع'))"></span></button>
                        <a :href="'https://wa.me/?text=' + encodeURIComponent(body)" target="_blank" rel="noopener" class="btn-whatsapp"><x-icon name="whatsapp" class="size-4" /> {{ __('إرسال بواتساب') }}</a>
                        <a :href="'mailto:?subject=' + encodeURIComponent(subject) + '&body=' + encodeURIComponent(body)" class="btn-secondary">{{ __('فتح في البريد') }}</a>
                    </div>
                </div>

                {{-- The pitch: stop writing these by hand --}}
                <div class="mt-6 rounded-3xl bg-night p-6 text-white sm:p-8">
                    <h2 class="text-xl font-bold">{{ __('تعبت من كتابة هذه الرسائل كل أسبوع؟') }}</h2>
                    <p class="mt-2 text-sm leading-relaxed text-slate-300">{{ __('تحصيلي يرسل هذه التذكيرات تلقائياً لكل فاتورة: قبل الاستحقاق، ويومه، وبعده، عبر البريد وواتساب — وأنت تتابع المتأخرات من لوحة واحدة.') }}</p>
                    <div class="mt-5 flex flex-wrap items-center gap-3">
                        @guest
                            <form method="POST" action="{{ route('demo.start') }}" class="contents">
                                @csrf
                                <button class="btn bg-brand-500 px-5 py-2.5 font-semibold text-white hover:bg-brand-600">{{ __('جرّب النظام الآن — بدون تسجيل') }}</button>
                                <x-recaptcha action="demo" class="order-last w-full" />
                            </form>
                        @endguest
                        <a href="{{ route('landing') }}" class="btn px-5 py-2.5 text-white ring-1 ring-white/20 hover:bg-white/10">{{ __('تعرّف على تحصيلي') }}</a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Short guide under the tool, for readers and for search --}}
        <section class="article mx-auto mt-16 max-w-3xl">
            <h2>{{ __('أي رسالة أرسل ومتى؟') }}</h2>
            <ul>
                <li><strong>{{ __('قبل الاستحقاق بـ 3 أيام:') }}</strong> {{ __('تذكير ودّي يمنع النسيان، وهو الأكثر فعالية لأن العميل لم يتأخر بعد.') }}</li>
                <li><strong>{{ __('يوم الاستحقاق:') }}</strong> {{ __('رسالة قصيرة ومباشرة مع طريقة الدفع.') }}</li>
                <li><strong>{{ __('بعد أسبوع:') }}</strong> {{ __('افترض حسن النية واسأل إن كان هناك ما يؤخر الدفع.') }}</li>
                <li><strong>{{ __('بعد شهر:') }}</strong> {{ __('كن حازماً: حدّد مهلة واضحة واطلب موعد دفع.') }}</li>
                <li><strong>{{ __('إشعار أخير:') }}</strong> {{ __('اذكر ما سيحدث بعد المهلة، مع إبقاء باب الحل الودي مفتوحاً.') }}</li>
            </ul>
            <p>{{ __('للمزيد من الأمثلة، اقرأ') }} <a href="{{ route('blog.show', 'payment-reminder-templates') }}">{{ __('قوالب رسائل التذكير بالدفع') }}</a>.</p>
        </section>
    </div>
</x-layouts.guest>
