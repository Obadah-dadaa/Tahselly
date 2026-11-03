{{-- Invisible reCAPTCHA v3 for the form it sits in: a token for this action is fetched on submit. Renders nothing until the keys are set. --}}
@props(['action'])
@if(\App\Support\Recaptcha::enabled())
    <div {{ $attributes->merge(['class' => 'text-[11px] leading-5 text-slate-400']) }}>
        <input type="hidden" name="g-recaptcha-response" data-recaptcha="{{ $action }}">
        @error('recaptcha')<p class="mb-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        {{ __('هذا الموقع محمي بـ reCAPTCHA وتنطبق عليه') }}
        <a href="https://policies.google.com/privacy" target="_blank" rel="noopener" class="underline">{{ __('سياسة الخصوصية') }}</a>
        {{ __('و') }}
        <a href="https://policies.google.com/terms" target="_blank" rel="noopener" class="underline">{{ __('شروط الخدمة') }}</a>
        {{ __('من Google.') }}
    </div>
    @once
        <style>.grecaptcha-badge { visibility: hidden !important; }</style>
        <script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.site_key') }}&hl={{ app()->getLocale() }}" async defer></script>
        <script>
            document.addEventListener('submit', function (event) {
                const form = event.target;
                const field = form.querySelector('input[data-recaptcha]');
                if (!field || form.dataset.recaptchaReady === '1') return;
                event.preventDefault();
                const send = () => { form.dataset.recaptchaReady = '1'; HTMLFormElement.prototype.submit.call(form); };
                if (!window.grecaptcha) { send(); return; } // script blocked: the server decides
                grecaptcha.ready(() => grecaptcha.execute(@json(config('services.recaptcha.site_key')), { action: field.dataset.recaptcha })
                    .then(token => { field.value = token; send(); }, send));
            }, true);
        </script>
    @endonce
@endif
