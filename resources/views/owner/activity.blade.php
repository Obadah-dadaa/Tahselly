<x-layouts.app title="{{ __('سجل النشاط') }}">
    <x-page-header title="{{ __('سجل النشاط') }}" :subtitle="__('كل ما قام به فريقك: الفواتير، الدفعات، التذكيرات والعملاء.')" />
    @php
        $types = ['' => __('الكل'), 'invoices' => __('الفواتير'), 'payments' => __('الدفعات'), 'reminders' => __('التذكيرات'), 'clients' => __('العملاء والاستيراد'), 'team' => __('الفريق والإعدادات')];
        $tones = ['invoice' => 'bg-sky-50 text-sky-700', 'payment' => 'bg-emerald-50 text-emerald-700', 'reminder' => 'bg-violet-50 text-violet-700', 'reminders' => 'bg-violet-50 text-violet-700', 'client' => 'bg-amber-50 text-amber-700', 'import' => 'bg-amber-50 text-amber-700'];
    @endphp
    <form class="mb-4 flex flex-wrap items-center gap-2">
        @foreach($types as $key => $label)
            <a href="{{ route('owner.activity', array_filter(['type' => $key, 'user' => request('user')])) }}"
               class="rounded-full px-4 py-1.5 text-sm font-semibold transition {{ (string) $type === (string) $key ? 'bg-night text-white' : 'bg-surface text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' }}">{{ $label }}</a>
        @endforeach
        <input type="hidden" name="type" value="{{ $type }}">
        <select name="user" onchange="this.form.submit()" class="input ms-auto w-auto">
            <option value="">{{ __('كل الفريق') }}</option>
            @foreach($team as $member)<option value="{{ $member->id }}" @selected(request('user') == $member->id)>{{ $member->name }}</option>@endforeach
        </select>
    </form>
    <div class="card overflow-hidden">
        <table class="table-base">
            <thead><tr><th>{{ __('الوقت') }}</th><th>{{ __('المستخدم') }}</th><th>{{ __('الإجراء') }}</th><th>IP</th></tr></thead>
            <tbody>
            @forelse($logs as $log)
                <tr>
                    <td class="num whitespace-nowrap text-slate-500">{{ $log->created_at->format('Y-m-d H:i') }}</td>
                    <td>{{ $log->user?->name ?? __('النظام') }}</td>
                    <td><span class="me-2 rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $tones[explode('.', $log->action)[0]] ?? 'bg-slate-100 text-slate-600' }}">{{ $types[collect(\App\Http\Controllers\Owner\DashboardController::ACTIVITY_TYPES)->search(fn ($ps) => collect($ps)->contains(fn ($p) => \Illuminate\Support\Str::is(str_replace('%', '*', $p), $log->action))) ?: ''] ?? '' }}</span>{{ $log->description }}</td>
                    <td class="num text-xs text-slate-400">{{ $log->ip }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="py-10 text-center text-slate-400">{{ __('لا يوجد نشاط بعد') }}</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $logs->links() }}</div>
    </div>
</x-layouts.app>
