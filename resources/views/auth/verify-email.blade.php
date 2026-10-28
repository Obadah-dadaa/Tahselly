<x-layouts.guest :title="__('أكّد بريدك الإلكتروني')">
    <div class="mx-auto max-w-md">
        <div class="card p-8 text-center">
            <span class="mx-auto grid size-16 place-items-center rounded-2xl bg-brand-50 text-brand-700"><x-icon name="mail" class="size-8" /></span>
            <h1 class="mt-5 text-2xl font-bold text-ink">{{ __('أكّد بريدك الإلكتروني') }}</h1>
            <p class="mt-3 text-slate-500">{{ __('أرسلنا رابط تأكيد إلى :email. افتح الرسالة واضغط الزر لتفعيل حسابك.', ['email' => auth()->user()->email]) }}</p>
            <p class="mt-2 text-xs text-slate-400">{{ __('لم تجدها؟ تحقق من مجلد الرسائل غير المرغوب فيها.') }}</p>
            <form method="POST" action="{{ route('verification.send') }}" class="mt-6">
                @csrf
                <button class="btn-primary w-full py-3">{{ __('أعد إرسال رابط التأكيد') }}</button>
            </form>
            <form method="POST" action="{{ route('logout') }}" class="mt-3">
                @csrf
                <button class="text-sm text-slate-500 hover:text-rose-600">{{ __('تسجيل الخروج') }}</button>
            </form>
        </div>
    </div>
</x-layouts.guest>
