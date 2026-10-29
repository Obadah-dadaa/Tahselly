<x-layouts.app :title="__('اختر باقتك')">
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-ink">{{ __('أهلاً بك! أي باقة تناسبك؟') }}</h1>
            <p class="mt-1 text-slate-500">{{ __('كل المزايا مفتوحة لك طوال التجربة. اختيار الباقة الآن يسهّل الاشتراك لاحقاً، ويمكنك تغييرها متى شئت.') }}</p>
        </div>
        <a href="{{ route('dashboard') }}" class="btn-secondary">{{ __('تخطَّ الآن وجرّب') }} →</a>
    </div>

    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
        @foreach($plans as $plan)
            <form method="POST" action="{{ route('owner.plan.choose') }}"
                  class="relative flex flex-col rounded-3xl p-6 {{ $plan->is_featured ? 'bg-night text-white shadow-2xl ring-4 ring-brand-500/40' : 'card' }}">
                @csrf
                <input type="hidden" name="plan" value="{{ $plan->code }}">
                @if($plan->is_featured)
                    <span class="absolute inset-x-0 -top-3 mx-auto w-fit rounded-full bg-brand-500 px-4 py-1 text-xs font-bold text-white shadow">{{ __('الأكثر طلباً') }}</span>
                @endif
                <h2 class="text-xl font-bold">{{ $plan->localizedName() }}</h2>
                <p class="mt-1 min-h-10 text-sm {{ $plan->is_featured ? 'text-slate-300' : 'text-slate-500' }}">{{ $plan->audience() }}</p>
                <p class="mt-4"><span class="num text-4xl font-bold">{{ number_format((float) $plan->price) }}</span> <span class="text-sm {{ $plan->is_featured ? 'text-slate-400' : 'text-slate-500' }}">{{ __('درهم / شهرياً') }}</span></p>
                <ul class="mt-5 flex-1 space-y-2 text-sm">
                    <li class="flex items-start gap-2 font-semibold"><x-icon name="check" class="mt-0.5 size-4 shrink-0 text-brand-500" /> {{ $plan->max_users == 1 ? __('مستخدم واحد') : __('حتى :count مستخدمين', ['count' => $plan->max_users]) }}</li>
                    <li class="flex items-start gap-2 font-semibold"><x-icon name="check" class="mt-0.5 size-4 shrink-0 text-brand-500" /> {{ __('حتى :count فاتورة مفتوحة', ['count' => number_format($plan->max_invoices)]) }}</li>
                    @foreach($plan->localizedFeatures() as $feature)
                        <li class="flex items-start gap-2"><x-icon name="check" class="mt-0.5 size-4 shrink-0 text-brand-500" /> {{ $feature }}</li>
                    @endforeach
                </ul>
                <button class="mt-6 w-full {{ $plan->is_featured ? 'btn-primary bg-brand-500' : 'btn-secondary' }}">{{ __('اختر :plan', ['plan' => $plan->localizedName()]) }}</button>
            </form>
        @endforeach
    </div>
    <p class="mt-6 text-center text-sm text-slate-500">{{ __('كل باقة تشمل مزايا الباقات الأرخص منها.') }}</p>
</x-layouts.app>
