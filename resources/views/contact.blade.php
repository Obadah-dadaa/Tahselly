<x-layouts.guest :title="__('تواصل معنا')">
    <div class="mx-auto max-w-5xl">
        <div class="text-center">
            <h1 class="text-3xl font-bold text-ink sm:text-4xl">{{ __('تواصل معنا') }}</h1>
            <p class="mx-auto mt-3 max-w-2xl text-slate-600">{{ __('عندك سؤال عن الباقات أو تحتاج مساعدة في حسابك؟ تكلّم معي مباشرة، أرد عادةً خلال يوم عمل.') }}</p>
        </div>

        <div class="mt-10 grid gap-6 lg:grid-cols-5">
            <div class="space-y-4 lg:col-span-2">
                <div class="card flex items-center gap-4 p-6">
                    <span class="grid size-14 shrink-0 place-items-center rounded-2xl bg-gradient-to-br from-brand-500 to-brand-700 text-xl font-bold text-white">{{ app()->getLocale() === 'ar' ? 'ع' : 'O' }}</span>
                    <div>
                        <p class="text-lg font-bold text-ink">{{ __('عبادة دعدع') }}</p>
                        <p class="text-sm text-slate-500">{{ __('مؤسس ومطوّر :app', ['app' => __(\App\Models\SystemSetting::get('app_name', 'تحصيلي'))]) }}</p>
                    </div>
                </div>

                @if($whatsapp)
                    <a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener" class="card flex items-center gap-4 p-5 transition hover:ring-2 hover:ring-green-500/40">
                        <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-[#25D366] text-white"><x-icon name="whatsapp" class="size-5" /></span>
                        <div class="min-w-0">
                            <p class="text-sm text-slate-500">{{ __('واتساب') }}</p>
                            <p class="font-semibold text-ink" dir="ltr">+{{ $whatsapp }}</p>
                        </div>
                    </a>
                @endif

                @if($email)
                    <a href="mailto:{{ $email }}" class="card flex items-center gap-4 p-5 transition hover:ring-2 hover:ring-brand-500/40">
                        <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-700"><x-icon name="mail" class="size-5" /></span>
                        <div class="min-w-0">
                            <p class="text-sm text-slate-500">{{ __('البريد الإلكتروني') }}</p>
                            <p class="truncate font-semibold text-ink" dir="ltr">{{ $email }}</p>
                        </div>
                    </a>
                @endif

                <div class="card flex items-center gap-4 p-5">
                    <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-slate-100 text-slate-600"><x-icon name="building" class="size-5" /></span>
                    <div>
                        <p class="text-sm text-slate-500">{{ __('الموقع') }}</p>
                        <p class="font-semibold text-ink">{{ __('دبي، الإمارات العربية المتحدة') }}</p>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('contact.store') }}" class="card space-y-5 p-6 lg:col-span-3 lg:p-8">
                @csrf
                <h2 class="text-xl font-bold text-ink">{{ __('أرسل رسالة') }}</h2>
                {{-- Hidden from people; bots that fill it are ignored --}}
                <input type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="name" class="label">{{ __('الاسم') }}</label>
                        <input id="name" name="name" value="{{ old('name', auth()->user()?->name) }}" required maxlength="100" class="input">
                    </div>
                    <div>
                        <label for="email" class="label">{{ __('البريد الإلكتروني') }}</label>
                        <input id="email" type="email" name="email" value="{{ old('email', auth()->user()?->email) }}" required maxlength="150" class="input" dir="ltr">
                    </div>
                </div>
                <div>
                    <label for="company" class="label">{{ __('الشركة (اختياري)') }}</label>
                    <input id="company" name="company" value="{{ old('company', auth()->user()?->company?->name) }}" maxlength="150" class="input">
                </div>
                <div>
                    <label for="message" class="label">{{ __('رسالتك') }}</label>
                    <textarea id="message" name="message" rows="6" required minlength="10" maxlength="3000" class="input">{{ old('message') }}</textarea>
                </div>
                <button class="btn-primary w-full sm:w-auto"><x-icon name="mail" class="size-4" /> {{ __('إرسال الرسالة') }}</button>
            </form>
        </div>
    </div>
</x-layouts.guest>
