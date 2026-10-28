<x-layouts.app title="{{ __('الإعدادات') }}">
    <x-page-header title="{{ __('إعدادات الشركة والتذكيرات') }}" />

    <div x-data="{ tab: 'reminders' }">
        <div class="mb-6 flex gap-2 border-b border-slate-200">
            @foreach(['reminders' => __('جدول التذكيرات'), 'templates' => __('قوالب الرسائل'), 'company' => __('بيانات الشركة')] as $key => $label)
                <button @click="tab='{{ $key }}'" :class="tab==='{{ $key }}' ? 'border-brand-600 text-brand-700' : 'border-transparent text-slate-500'" class="-mb-px border-b-2 px-4 py-2 text-sm font-semibold">{{ __($label) }}</button>
            @endforeach
        </div>

        <form x-show="tab==='reminders'" method="POST" action="{{ route('owner.settings.reminders') }}" class="card max-w-3xl space-y-6 p-6">
            @csrf @method('PUT')
            <label class="flex items-center justify-between rounded-xl bg-slate-50 p-4">
                <span><span class="block font-semibold">{{ __('تفعيل التذكيرات الآلية') }}</span><span class="text-sm text-slate-500">{{ __('يرسل النظام التذكيرات يومياً الساعة 9 صباحاً') }}</span></span>
                <input type="checkbox" name="enabled" value="1" @checked($settings['enabled']) class="size-5 rounded text-brand-600">
            </label>
            <div class="grid gap-5 sm:grid-cols-2">
                <div><label class="label">{{ __('قبل الاستحقاق بـ (أيام)') }}</label><input name="before_days" value="{{ implode(', ', $settings['before_days']) }}" class="input num" placeholder="3"><p class="mt-1 text-xs text-slate-400">{{ __('افصل بفاصلة، مثال: 7, 3') }}</p></div>
                <div><label class="label">{{ __('بعد الاستحقاق بـ (أيام)') }}</label><input name="after_days" value="{{ implode(', ', $settings['after_days']) }}" class="input num" placeholder="7, 14, 30"></div>
                <div><label class="label">{{ __('إنذار نهائي بعد (يوم) — 0 للإيقاف') }}</label><input type="number" name="final_after" value="{{ $settings['final_after'] }}" class="input num"></div>
                <div class="flex items-end"><label class="flex items-center gap-2 text-sm"><input type="checkbox" name="on_due" value="1" @checked($settings['on_due']) class="rounded text-brand-600"> {{ __('تذكير يوم الاستحقاق نفسه') }}</label></div>
            </div>
            <div>
                <p class="label">{{ __('القنوات الآلية') }}</p>
                <div class="flex gap-4 text-sm">
                    <label class="flex items-center gap-2"><input type="checkbox" name="channels[]" value="email" @checked(in_array('email', $settings['channels'])) class="rounded text-brand-600"> {{ __('البريد الإلكتروني') }}</label>
                    <label class="flex items-center gap-2"><input type="checkbox" name="channels[]" value="whatsapp" @checked(in_array('whatsapp', $settings['channels'])) class="rounded text-brand-600"> {{ __('واتساب (إرسال بنقرة من صفحة الفاتورة)') }}</label>
                </div>
            </div>
            <div>
                <label class="label">{{ __('لغة رسائل التذكير الافتراضية') }}</label>
                <select name="locale" class="input max-w-xs">
                    <option value="ar" @selected(($settings['locale'] ?? 'ar') === 'ar')>العربية</option>
                    <option value="en" @selected(($settings['locale'] ?? 'ar') === 'en')>English</option>
                </select>
                <p class="mt-1 text-xs text-slate-500">{{ __('تُستخدم لكل عميل لم تُحدَّد له لغة. يمكنك اختيار لغة مختلفة لأي عميل من صفحة تعديله.') }}</p>
            </div>
            <button class="btn-primary">{{ __('حفظ الجدول') }}</button>
        </form>

        <form x-show="tab==='templates'" x-cloak method="POST" action="{{ route('owner.settings.templates') }}" class="space-y-6" x-data="{ lang: '{{ $settings['locale'] ?? 'ar' }}' }">
            @csrf @method('PUT')
            <div class="card flex flex-wrap items-center justify-between gap-4 p-4 text-sm text-slate-600">
                <div>
                    {{ __('المتغيرات المتاحة:') }}
                    @foreach($placeholders as $p)<code class="mx-1 rounded bg-slate-100 px-1.5 py-0.5 text-xs" dir="ltr">{{ $p }}</code>@endforeach
                </div>
                <div class="inline-flex rounded-xl bg-slate-100 p-1 font-semibold">
                    <button type="button" @click="lang='ar'" :class="lang==='ar' ? 'bg-surface text-ink shadow' : 'text-slate-500'" class="rounded-lg px-4 py-1.5">{{ __('رسائل عربية') }}</button>
                    <button type="button" @click="lang='en'" :class="lang==='en' ? 'bg-surface text-ink shadow' : 'text-slate-500'" class="rounded-lg px-4 py-1.5">{{ __('رسائل إنجليزية') }}</button>
                </div>
            </div>
            @foreach($templates as $locale => $stages)
                <div x-show="lang==='{{ $locale }}'" @if($locale !== ($settings['locale'] ?? 'ar')) x-cloak @endif class="space-y-6" dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}">
                    @foreach($stages as $stage => $channels)
                        <div class="card p-5">
                            <h3 class="font-semibold text-ink">{{ __(\App\Models\Reminder::STAGE_LABELS[$stage]) }}</h3>
                            <div class="mt-4 grid gap-5 lg:grid-cols-2">
                                <div class="space-y-2">
                                    <p class="text-sm font-semibold text-sky-700">{{ __('البريد الإلكتروني') }}</p>
                                    <input name="templates[{{ $locale }}][{{ $stage }}][email][subject]" value="{{ $channels['email']?->subject }}" class="input" placeholder="{{ __('عنوان الرسالة') }}">
                                    <textarea name="templates[{{ $locale }}][{{ $stage }}][email][body]" rows="6" class="input text-sm">{{ $channels['email']?->body }}</textarea>
                                </div>
                                <div class="space-y-2">
                                    <p class="text-sm font-semibold text-green-700">{{ __('واتساب') }}</p>
                                    <textarea name="templates[{{ $locale }}][{{ $stage }}][whatsapp][body]" rows="8" class="input text-sm">{{ $channels['whatsapp']?->body }}</textarea>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
            <button class="btn-primary">{{ __('حفظ القوالب') }}</button>
        </form>

        <form x-show="tab==='company'" x-cloak method="POST" action="{{ route('owner.settings.company') }}" class="card max-w-3xl space-y-5 p-6">
            @csrf @method('PUT')
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2"><label class="label">{{ __('اسم الشركة') }}</label><input name="name" value="{{ $company->name }}" required class="input"></div>
                <div><label class="label">{{ __('البريد') }}</label><input name="email" value="{{ $company->email }}" class="input" dir="ltr"></div>
                <div><label class="label">{{ __('الهاتف (يظهر في الرسائل)') }}</label><input name="phone" value="{{ $company->phone }}" class="input" dir="ltr"></div>
                <div><label class="label">{{ __('الرقم الضريبي') }}</label><input name="trn" value="{{ $company->trn }}" class="input" dir="ltr"></div>
                <div><label class="label">{{ __('العملة الافتراضية') }}</label><input name="currency" value="{{ $company->currency }}" maxlength="3" class="input uppercase" dir="ltr"></div>
                <div class="sm:col-span-2"><label class="label">{{ __('العنوان') }}</label><input name="address" value="{{ $company->address }}" class="input"></div>
            </div>
            <button class="btn-primary">{{ __('حفظ') }}</button>
        </form>
    </div>
</x-layouts.app>
