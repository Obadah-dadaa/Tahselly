<x-layouts.app :title="$client->exists ? __('تعديل عميل') : __('عميل جديد')">
    <x-page-header :title="$client->exists ? __('تعديل:').' '.$client->name : __('عميل جديد')" />

    <form method="POST" action="{{ $client->exists ? route('clients.update', $client) : route('clients.store') }}" class="card max-w-3xl space-y-5 p-6">
        @csrf
        @if($client->exists) @method('PUT') @endif
        <div class="grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2"><label class="label">{{ __('اسم العميل / الشركة *') }}</label><input name="name" value="{{ old('name', $client->name) }}" required class="input"></div>
            <div><label class="label">{{ __('اسم المسؤول') }}</label><input name="contact_name" value="{{ old('contact_name', $client->contact_name) }}" class="input"></div>
            <div><label class="label">{{ __('الرقم الضريبي (TRN)') }}</label><input name="trn" value="{{ old('trn', $client->trn) }}" class="input" dir="ltr"></div>
            <div><label class="label">{{ __('البريد الإلكتروني (لإرسال التذكيرات)') }}</label><input type="email" name="email" value="{{ old('email', $client->email) }}" class="input" dir="ltr"></div>
            <x-phone-input :label="__('الهاتف / واتساب')" :required="false" :value="$client->phone" />
            <div class="sm:col-span-2"><label class="label">{{ __('العنوان') }}</label><input name="address" value="{{ old('address', $client->address) }}" class="input"></div>
            <div class="sm:col-span-2"><label class="label">{{ __('ملاحظات') }}</label><textarea name="notes" rows="3" class="input">{{ old('notes', $client->notes) }}</textarea></div>
        </div>
        <div class="max-w-xs">
            <label class="label">{{ __('لغة رسائل التذكير لهذا العميل') }}</label>
            <select name="locale" class="input">
                <option value="">{{ __('الافتراضية للشركة') }}</option>
                <option value="ar" @selected(old('locale', $client->locale) === 'ar')>العربية</option>
                <option value="en" @selected(old('locale', $client->locale) === 'en')>English</option>
            </select>
        </div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="reminders_enabled" value="1" @checked(old('reminders_enabled', $client->reminders_enabled)) class="rounded border-slate-300 text-brand-600"> {{ __('إرسال تذكيرات آلية لهذا العميل') }}</label>
        <div class="flex gap-2 border-t border-slate-100 pt-5">
            <button class="btn-primary">{{ __('حفظ') }}</button>
            <a href="{{ $client->exists ? route('clients.show', $client) : route('clients.index') }}" class="btn-secondary">{{ __('إلغاء') }}</a>
        </div>
    </form>
</x-layouts.app>
