{{-- What a beta account may use, and how much of it this company has used. --}}
@props(['company'])
@php
    use App\Support\Edition;
    $rows = [
        ['invoice', __('الفواتير'), \App\Models\Invoice::withoutGlobalScopes()->where('company_id', $company->id)->count(), Edition::limit('max_invoices')],
        ['building', __('العملاء'), \App\Models\Client::withoutGlobalScopes()->where('company_id', $company->id)->count(), Edition::limit('max_clients')],
        ['users', __('المستخدمون'), $company->users()->count(), Edition::limit('max_users')],
        ['bell', __('التذكيرات اليوم'), \App\Models\Reminder::withoutGlobalScopes()->where('company_id', $company->id)->where('status', 'sent')->whereDate('created_at', today())->count(), Edition::limit('daily_reminders')],
    ];
@endphp
<div {{ $attributes->merge(['class' => 'grid gap-3 sm:grid-cols-2']) }}>
    @foreach($rows as [$icon, $label, $used, $max])
        @php $pct = $max > 0 ? min(100, (int) round($used / $max * 100)) : 0; @endphp
        <div class="rounded-2xl border border-slate-200 bg-surface p-4">
            <div class="flex items-center justify-between text-sm">
                <span class="flex items-center gap-2 font-semibold text-ink"><x-icon :name="$icon" class="size-4 text-brand-700" /> {{ $label }}</span>
                <span class="num text-slate-500">{{ $used }} / {{ $max }}</span>
            </div>
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
                <div class="h-full rounded-full {{ $pct >= 90 ? 'bg-rose-500' : 'bg-brand-500' }}" style="width: {{ $pct }}%"></div>
            </div>
        </div>
    @endforeach
    <p class="text-xs text-slate-500 sm:col-span-2">{{ __('ملف الاستيراد: حتى :rows صف · المدة: :days يوماً من التسجيل', ['rows' => Edition::limit('import_rows'), 'days' => Edition::limit('trial_days')]) }}</p>
</div>
