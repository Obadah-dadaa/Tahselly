{{-- Phone with a country dial code (UAE first, then the Gulf and the region). Posts phone_country + phone. --}}
@props(['label' => null, 'required' => true, 'value' => null])
@php $locale = app()->getLocale(); @endphp
<div {{ $attributes->merge(['class' => 'sm:col-span-2']) }}>
    <label class="label" for="phone">{{ $label ?? __('رقم الجوال') }}</label>
    <div class="flex gap-2" dir="ltr">
        <select name="phone_country" aria-label="{{ __('رمز الدولة') }}" class="input w-32 shrink-0 pe-7 sm:w-52">
            @foreach(\App\Support\Phone::COUNTRIES as $code => [, $ar, $en])
                <option value="{{ $code }}" @selected(old('phone_country', '971') == $code)>+{{ $code }}  {{ $locale === 'ar' ? $ar : $en }}</option>
            @endforeach
        </select>
        <input id="phone" name="phone" value="{{ old('phone', $value) }}" @if($required) required @endif inputmode="tel" autocomplete="tel-national" class="input min-w-0 flex-1" placeholder="50 123 4567">
    </div>
    <p class="mt-1 text-xs text-slate-500">{{ __('اختر الدولة ثم اكتب الرقم، أو اكتبه كاملاً بـ +') }}</p>
    @error('phone')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
</div>
