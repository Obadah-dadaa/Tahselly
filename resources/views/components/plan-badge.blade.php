{{-- Marks a gated feature during the free trial with an amber "from plan X" label. Paid plans never see features they lack.
     A plain span, so it can sit inside links and buttons. --}}
@props(['feature'])
@php
    $company = auth()->user()?->company;
    $plan = $company?->featureState($feature) === 'trial' ? \App\Models\Plan::minimumFor($feature) : null;
@endphp
@if($plan)
    <span {{ $attributes->class('inline-flex shrink-0 items-center gap-1 whitespace-nowrap rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800') }}
          title="{{ __('مفتوحة لك خلال التجربة المجانية. بعد التجربة تحتاج باقة :plan أو أعلى.', ['plan' => $plan->localizedName()]) }}">
        {{ __('من باقة :plan', ['plan' => $plan->localizedName()]) }}
    </span>
@endif
