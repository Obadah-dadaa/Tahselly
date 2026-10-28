@props(['invoice' => null, 'value' => null])
@php
    $status = $invoice ? $invoice->displayStatus() : $value;
    $green = 'bg-emerald-50 text-emerald-700 ring-emerald-600/20';
    $red = 'bg-rose-50 text-rose-700 ring-rose-600/20';
    $amber = 'bg-amber-50 text-amber-700 ring-amber-600/20';
    $grey = 'bg-slate-100 text-slate-500 ring-slate-500/20';
    $styles = [
        'paid' => $green, 'partial' => 'bg-sky-50 text-sky-700 ring-sky-600/20', 'unpaid' => $amber, 'overdue' => $red, 'cancelled' => $grey,
        'sent' => $green, 'failed' => $red, 'scheduled' => 'bg-violet-50 text-violet-700 ring-violet-600/20',
        'active' => $green, 'trial' => 'bg-violet-50 text-violet-700 ring-violet-600/20', 'suspended' => $red, 'expired' => $grey,
        'sub_active' => $green, 'sub_pending' => $amber, 'sub_expired' => $grey, 'sub_cancelled' => $red,
    ];
    $labels = [
        'unpaid' => __('غير مدفوعة'), 'partial' => __('مدفوعة جزئياً'), 'paid' => __('مدفوعة'), 'cancelled' => __('ملغاة'), 'overdue' => __('متأخرة'),
        'sent' => __('مُرسل'), 'failed' => __('فشل'), 'scheduled' => __('مجدول'),
        'active' => __('نشط'), 'trial' => __('تجريبي'), 'suspended' => __('موقوف'), 'expired' => __('منتهي'),
        'sub_active' => __('فعّال'), 'sub_pending' => __('بانتظار الدفع'), 'sub_expired' => __('منتهي'), 'sub_cancelled' => __('ملغى'),
    ];
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset '.($styles[$status] ?? $grey)]) }}>
    {{ __($labels[$status] ?? $status) }}
    @if($invoice && $status === 'overdue')<span class="num opacity-75">· {{ __(':count يوم', ['count' => $invoice->daysOverdue()]) }}</span>@endif
</span>
