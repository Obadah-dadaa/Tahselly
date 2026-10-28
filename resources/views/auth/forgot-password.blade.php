<x-layouts.guest :title="__('نسيت كلمة المرور')">
    <div class="mx-auto max-w-md">
        <div class="card p-8">
            <span class="grid size-12 place-items-center rounded-2xl bg-brand-50 text-brand-700"><x-icon name="shield" class="size-6" /></span>
            <h1 class="mt-5 text-2xl font-bold text-ink">{{ __('نسيت كلمة المرور؟') }}</h1>
            <p class="mt-2 text-sm text-slate-500">{{ __('اكتب بريدك الإلكتروني وسنرسل لك رابطاً لتعيين كلمة مرور جديدة.') }}</p>
            <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
                @csrf
                <div><label class="label" for="email">{{ __('البريد الإلكتروني') }}</label><input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus class="input py-2.5" dir="ltr"></div>
                <button class="btn-primary w-full py-3">{{ __('أرسل رابط إعادة التعيين') }}</button>
            </form>
            <p class="mt-6 text-center text-sm"><a href="{{ route('login') }}" class="font-semibold text-brand-700">← {{ __('العودة لتسجيل الدخول') }}</a></p>
        </div>
    </div>
</x-layouts.guest>
