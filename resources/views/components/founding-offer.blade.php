{{-- Founding customers banner: shown while the offer has seats left (live site, not for companies already in it) --}}
@php
    $company = auth()->user()?->company;
    $show = \App\Support\FoundingOffer::open() && ! $company?->founding_discount && ! $company?->isDemo();
@endphp
@if($show)
    <div {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-x-4 gap-y-2 rounded-2xl bg-gradient-to-r from-amber-50 to-amber-100 px-5 py-4 text-amber-900 ring-1 ring-amber-300/60 dark:from-amber-500/10 dark:to-amber-500/5 dark:text-amber-200']) }}>
        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-amber-500 text-lg font-bold text-white">%</span>
        <div class="min-w-0 flex-1">
            <p class="font-bold">{{ __('عرض المؤسسين: خصم :percent% مدى الحياة', ['percent' => \App\Support\FoundingOffer::percent()]) }}</p>
            <p class="text-sm">{{ __('لأول :seats شركة تشترك فقط، والخصم يبقى معك في كل تجديد. يُطبَّق تلقائياً عند الدفع.', ['seats' => (int) \App\Models\SystemSetting::get('founding_offer_seats', 20)]) }}</p>
        </div>
        <span class="shrink-0 rounded-full bg-white px-3 py-1 text-sm font-bold shadow-sm ring-1 ring-amber-300 dark:bg-white/10">{{ __('باقي :count مكاناً', ['count' => \App\Support\FoundingOffer::seatsLeft()]) }}</span>
    </div>
@endif
