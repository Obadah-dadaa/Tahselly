{{-- "Interested in subscribing?" request form (the beta's replacement for plans and checkout). Sends to the developer inbox. --}}
@props(['dark' => false])
@php $user = auth()->user(); @endphp
<form method="POST" action="{{ route('interest.store') }}#interest" {{ $attributes->merge(['class' => 'space-y-4']) }}>
    @csrf
    <x-recaptcha action="interest" />
    {{-- Its own messages, since it also sits on pages without a flash area (the landing page) --}}
    @if(session('success') && request()->routeIs('landing'))
        <div class="rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-inset ring-emerald-600/20">{{ session('success') }}</div>
    @endif
    @if($errors->any() && request()->routeIs('landing'))
        <div class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-800 ring-1 ring-inset ring-rose-600/20">{{ $errors->first() }}</div>
    @endif
    <input type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="label {{ $dark ? 'text-slate-200' : '' }}" for="interest-name">{{ __('الاسم') }}</label>
            <input id="interest-name" name="name" value="{{ old('name', $user?->name) }}" required maxlength="120" class="input">
        </div>
        <div>
            <label class="label {{ $dark ? 'text-slate-200' : '' }}" for="interest-company">{{ __('الشركة') }}</label>
            <input id="interest-company" name="company_name" value="{{ old('company_name', $user?->company?->name) }}" maxlength="190" class="input">
        </div>
        <div>
            <label class="label {{ $dark ? 'text-slate-200' : '' }}" for="interest-email">{{ __('البريد الإلكتروني') }}</label>
            <input id="interest-email" type="email" name="email" value="{{ old('email', $user?->email) }}" required maxlength="190" class="input" dir="ltr">
        </div>
        <div>
            <label class="label {{ $dark ? 'text-slate-200' : '' }}" for="interest-phone">{{ __('رقم الجوال / واتساب') }}</label>
            <input id="interest-phone" name="phone" value="{{ old('phone', $user?->phone ?? $user?->company?->phone) }}" required maxlength="30" class="input" dir="ltr">
        </div>
    </div>
    <div>
        <label class="label {{ $dark ? 'text-slate-200' : '' }}" for="interest-message">{{ __('ملاحظات (اختياري)') }}</label>
        <textarea id="interest-message" name="message" rows="3" maxlength="2000" class="input" placeholder="{{ __('مثلاً: عدد الفواتير الشهرية، عدد المستخدمين، أو أي سؤال') }}">{{ old('message') }}</textarea>
    </div>
    <button class="btn-primary w-full sm:w-auto"><x-icon name="mail" class="size-4" /> {{ __('أرسل طلب الاشتراك') }}</button>
</form>
