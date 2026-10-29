<x-layouts.app :title="\App\Models\Plan::featureLabel($feature)">
    <div class="mx-auto max-w-xl py-12 text-center">
        <span class="mx-auto grid size-16 place-items-center rounded-2xl bg-brand-50 text-brand-700"><x-icon name="lock" class="size-8" /></span>
        <h1 class="mt-6 text-2xl font-bold text-ink">{{ \App\Models\Plan::featureLabel($feature) }}</h1>
        <p class="mt-3 text-slate-500">{{ $message }}</p>
        @if($plan)
            <div class="card mt-8 p-6 text-start">
                <p class="text-sm text-slate-500">{{ __('أقل باقة تشمل هذه الميزة') }}</p>
                <div class="mt-1 flex items-baseline justify-between gap-4">
                    <h2 class="text-xl font-bold text-ink">{{ $plan->localizedName() }}</h2>
                    <p><span class="num text-2xl font-bold">{{ number_format((float) $plan->price) }}</span> <span class="text-sm text-slate-500">{{ __('درهم / شهرياً') }}</span></p>
                </div>
                <p class="mt-1 text-sm text-slate-500">{{ $plan->audience() }}</p>
            </div>
        @endif
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            @if(auth()->user()->isOwner())
                <a href="{{ route('owner.billing') }}" class="btn-primary">{{ __('ترقية الباقة') }}</a>
            @else
                <p class="text-sm text-slate-500">{{ __('اطلب من مالك الحساب ترقية الباقة.') }}</p>
            @endif
            <a href="{{ route('home') }}" class="btn-secondary">{{ __('العودة للرئيسية') }}</a>
        </div>
    </div>
</x-layouts.app>
