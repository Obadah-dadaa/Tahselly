{{-- A plan's monthly price; with the founding discount the list price is shown struck through --}}
@props(['plan', 'size' => 'text-4xl'])
@php
    $discount = \App\Support\FoundingOffer::discountFor(auth()->user()?->company);
    $price = \App\Support\FoundingOffer::price($plan, 'monthly', $discount);
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex flex-wrap items-baseline gap-x-2']) }}>
    @if($discount)<s class="num text-base font-semibold opacity-50">{{ number_format((float) $plan->price) }}</s>@endif
    <span class="num {{ $size }} font-bold">{{ number_format($price) }}</span>
</span>
