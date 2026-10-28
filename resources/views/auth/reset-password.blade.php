<x-layouts.guest :title="__('تعيين كلمة مرور جديدة')">
    <div class="mx-auto max-w-md">
        <div class="card p-8">
            <h1 class="text-2xl font-bold text-ink">{{ __('تعيين كلمة مرور جديدة') }}</h1>
            <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div><label class="label" for="email">{{ __('البريد الإلكتروني') }}</label><input id="email" type="email" name="email" value="{{ old('email', $email) }}" required class="input py-2.5" dir="ltr"></div>
                <div><label class="label" for="password">{{ __('كلمة المرور الجديدة') }}</label><input id="password" type="password" name="password" required minlength="8" autofocus class="input py-2.5" dir="ltr"></div>
                <div><label class="label" for="password_confirmation">{{ __('تأكيد كلمة المرور') }}</label><input id="password_confirmation" type="password" name="password_confirmation" required class="input py-2.5" dir="ltr"></div>
                <button class="btn-primary w-full py-3">{{ __('حفظ كلمة المرور') }}</button>
            </form>
        </div>
    </div>
</x-layouts.guest>
