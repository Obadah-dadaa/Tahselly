{{-- Marks a gated feature: amber "from plan X" during the free trial, a lock when the paid plan lacks it, nothing when included.
     A plain span, so it can sit inside links and buttons. --}}
@props(['feature'])
@php
    $company = auth()->user()?->company;
    $state = $company?->featureState($feature) ?? 'included';
    $plan = $state === 'included' ? null : \App\Models\Plan::minimumFor($feature);
@endphp
@if($plan)
    @if($state === 'trial')
        <span {{ $attributes->class('inline-flex shrink-0 items-center gap-1 whitespace-nowrap rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800') }}
              title="{{ __('مفتوحة لك خلال التجربة المجانية. بعد التجربة تحتاج باقة :plan أو أعلى.', ['plan' => $plan->localizedName()]) }}">
            {{ __('من باقة :plan', ['plan' => $plan->localizedName()]) }}
        </span>
    @else
        <span {{ $attributes->class('inline-flex shrink-0 cursor-pointer items-center gap-1 whitespace-nowrap rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600 hover:bg-brand-50 hover:text-brand-700') }}
              @click.prevent.stop="$dispatch('upgrade', '{{ $feature }}')" role="button"
              title="{{ __('متاحة من باقة :plan. قم بترقية باقتك لاستخدامها.', ['plan' => $plan->localizedName()]) }}">
            <x-icon name="lock" class="size-3" /> {{ $plan->localizedName() }}
        </span>
    @endif
@endif
