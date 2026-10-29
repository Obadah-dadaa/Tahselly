{{-- One upgrade dialog for the whole app: any locked control dispatches "upgrade" with a feature key
     (or "_next" for the next plan) and this opens in place instead of sending the user to another page. --}}
@php
    $user = auth()->user();
    $company = $user?->company;
    $describe = fn (?\App\Models\Plan $plan) => $plan ? [
        'plan' => $plan->localizedName(),
        'price' => number_format((float) $plan->price),
        'audience' => $plan->audience(),
        'adds' => $plan->localizedFeatures(),
        'users' => $plan->max_users == 1 ? __('مستخدم واحد') : __('حتى :count مستخدمين', ['count' => $plan->max_users]),
        'invoices' => __('حتى :count فاتورة مفتوحة', ['count' => number_format($plan->max_invoices)]),
    ] : null;
    $data = [];
    if ($company) {
        foreach (array_keys(\App\Models\Plan::FEATURES) as $feature) {
            $data[$feature] = ['title' => \App\Models\Plan::featureLabel($feature)] + ($describe(\App\Models\Plan::minimumFor($feature)) ?? []);
        }
        $next = $company->plan
            ? \App\Models\Plan::query()->where('is_active', true)->ordered()->get()->first(fn ($p) => [(float) $p->price, $p->max_users] > [(float) $company->plan->price, $company->plan->max_users])
            : null;
        if ($next) {
            $data['_next'] = ['title' => __('رقِّ إلى باقة :plan', ['plan' => $next->localizedName()])] + $describe($next);
        }
    }
@endphp
@if($company)
    <div x-data="{ open: false, f: {}, data: @js($data) }"
         @upgrade.window="if (data[$event.detail]) { f = data[$event.detail]; open = true }"
         @keydown.escape.window="open = false"
         x-show="open" x-cloak x-transition.opacity
         class="fixed inset-0 z-50 grid place-items-center bg-night/60 p-4 backdrop-blur-sm" role="dialog" aria-modal="true">
        <div @click.outside="open = false" x-show="open" x-transition class="card w-full max-w-md overflow-hidden">
            <div class="bg-night p-6 text-white">
                <div class="flex items-start justify-between gap-4">
                    <span class="grid size-12 place-items-center rounded-2xl bg-brand-500/20 text-brand-300"><x-icon name="lock" class="size-6" /></span>
                    <button @click="open = false" class="text-slate-400 hover:text-white" aria-label="{{ __('إغلاق') }}">✕</button>
                </div>
                <h2 class="mt-4 text-xl font-bold" x-text="f.title"></h2>
                <p class="mt-1 text-sm text-slate-300">
                    {{ __('متاحة من باقة') }} <b class="text-white" x-text="f.plan"></b>
                    — <span class="num" x-text="f.price"></span> {{ __('درهم / شهرياً') }}
                </p>
            </div>
            <div class="p-6">
                <p class="text-sm text-slate-500" x-text="f.audience"></p>
                <p class="mt-4 text-sm font-semibold text-ink">{{ __('ما تضيفه هذه الباقة:') }}</p>
                <ul class="mt-2 space-y-2 text-sm">
                    <li class="flex items-center gap-2 font-semibold"><x-icon name="check" class="size-4 text-brand-600" /> <span x-text="f.users"></span></li>
                    <li class="flex items-center gap-2 font-semibold"><x-icon name="check" class="size-4 text-brand-600" /> <span x-text="f.invoices"></span></li>
                    <template x-for="item in (f.adds || [])"><li class="flex items-center gap-2"><x-icon name="check" class="size-4 text-brand-600" /> <span x-text="item"></span></li></template>
                </ul>
                <div class="mt-6 flex gap-3">
                    @if($user->isOwner())
                        <a href="{{ route('owner.billing') }}" class="btn-primary flex-1">{{ __('ترقية الباقة') }}</a>
                    @else
                        <p class="flex-1 text-sm text-slate-500">{{ __('اطلب من مالك الحساب ترقية الباقة.') }}</p>
                    @endif
                    <button @click="open = false" class="btn-secondary">{{ __('لاحقاً') }}</button>
                </div>
            </div>
        </div>
    </div>
@endif
