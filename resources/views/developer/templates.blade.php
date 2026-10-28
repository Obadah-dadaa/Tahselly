<x-layouts.app title="{{ __('القوالب الافتراضية') }}">
    <x-page-header title="{{ __('قوالب الرسائل الافتراضية') }}" :subtitle="__('تُستخدم لكل الشركات التي لم تخصص قوالبها الخاصة.')" />
    <div class="card mb-6 p-4 text-sm text-slate-600">
        {{ __('المتغيرات:') }} @foreach($placeholders as $p)<code class="mx-1 rounded bg-slate-100 px-1.5 py-0.5 text-xs" dir="ltr">{{ $p }}</code>@endforeach
    </div>
    <form method="POST" action="{{ route('developer.templates.update') }}" class="grid gap-6 lg:grid-cols-2">
        @csrf @method('PUT')
        @foreach($templates as $t)
            <div class="card space-y-3 p-5">
                <p class="font-semibold"><span class="me-2 rounded bg-slate-100 px-1.5 py-0.5 text-xs uppercase">{{ $t->locale }}</span>{{ __(\App\Models\Reminder::STAGE_LABELS[$t->stage]) }} <span class="text-sm font-normal {{ $t->channel === 'email' ? 'text-sky-700' : 'text-green-700' }}">· {{ $t->channel === 'email' ? __('بريد') : __('واتساب') }}</span></p>
                @if($t->channel === 'email')<input name="templates[{{ $t->id }}][subject]" value="{{ $t->subject }}" class="input">@endif
                <textarea dir="{{ $t->locale === 'ar' ? 'rtl' : 'ltr' }}" name="templates[{{ $t->id }}][body]" rows="6" class="input text-sm">{{ $t->body }}</textarea>
            </div>
        @endforeach
        <div class="lg:col-span-2"><button class="btn-primary">{{ __('حفظ القوالب') }}</button></div>
    </form>
</x-layouts.app>
