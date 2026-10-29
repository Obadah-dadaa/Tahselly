{{-- What a free trial gives access to (sign-up page and the trial welcome page). --}}
@props(['columns' => false])
@php
    $included = [
        ['upload', __('استيراد فواتيرك من Excel كما هي')],
        ['bell', __('تذكيرات آلية بالبريد قبل الاستحقاق وبعده')],
        ['whatsapp', __('تذكيرات واتساب برسالة جاهزة بنقرة')],
        ['clock', __('جدولة التذكيرات وتعديل أيامها ونصوصها')],
        ['cash', __('تسجيل الدفعات وإغلاق الفواتير تلقائياً')],
        ['chart', __('تقارير أعمار الديون وتصديرها إلى Excel')],
        ['trend', __('لوحة المالك: نسبة التحصيل والتدفق النقدي')],
        ['users', __('إضافة فريقك وتحديد صلاحياتهم')],
    ];
@endphp
<ul {{ $attributes->class(['grid gap-3 text-sm', 'sm:grid-cols-2 lg:grid-cols-4' => $columns]) }}>
    @foreach($included as [$icon, $text])
        <li class="flex items-center gap-3 {{ $columns ? 'rounded-2xl bg-surface p-4 ring-1 ring-slate-200' : '' }}">
            <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-700"><x-icon :name="$icon" class="size-4" /></span>
            <span class="text-ink">{{ $text }}</span>
        </li>
    @endforeach
</ul>
