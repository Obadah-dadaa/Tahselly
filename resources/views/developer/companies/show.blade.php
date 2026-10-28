<x-layouts.app :title="$company->name">
    <x-page-header :title="$company->name" :subtitle="__('مشترك منذ :date', ['date' => $company->created_at->format('Y-m-d')])">
        <x-slot:actions>
            <form method="POST" action="{{ route('developer.companies.impersonate', $company) }}">@csrf<button class="btn-secondary text-violet-700">{{ __('دخول كمالك الشركة') }}</button></form>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-4">
        <x-kpi :label="__('العملاء')" :value="$stats['clients']" icon="building" tone="slate" />
        <x-kpi :label="__('الفواتير')" :value="$stats['invoices']" icon="invoice" tone="sky" />
        <x-kpi :label="__('مستحقات مفتوحة')" :value="number_format($stats['outstanding'], 0)" suffix="AED" icon="cash" />
        <x-kpi :label="__('تذكيرات')" :value="$stats['reminders']" icon="bell" tone="violet" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <form method="POST" action="{{ route('developer.companies.update', $company) }}" class="card space-y-4 p-5">
            @csrf @method('PATCH')
            <h2 class="font-semibold text-ink">{{ __('الاشتراك') }}</h2>
            <div><label class="label">{{ __('الباقة') }}</label><select name="plan_id" class="input">@foreach($plans as $p)<option value="{{ $p->id }}" @selected($company->plan_id === $p->id)>{{ $p->localizedName() }} — {{ (int) $p->price }}</option>@endforeach</select></div>
            <div><label class="label">{{ __('الحالة') }}</label><select name="status" class="input">@foreach(['active' => __('نشطة'), 'trial' => __('تجريبية'), 'suspended' => __('موقوفة')] as $k => $l)<option value="{{ $k }}" @selected($company->status === $k)>{{ __($l) }}</option>@endforeach</select></div>
            <div><label class="label">{{ __('نهاية التجربة') }}</label><input type="date" name="trial_ends_at" value="{{ $company->trial_ends_at?->format('Y-m-d') }}" class="input"></div>
            <button class="btn-primary w-full">{{ __('حفظ') }}</button>
        </form>
        <div class="card overflow-hidden lg:col-span-2">
            <h2 class="border-b border-slate-100 px-5 py-4 font-semibold text-ink">{{ __('المستخدمون') }}</h2>
            <table class="table-base">
                <thead><tr><th>{{ __('الاسم') }}</th><th>{{ __('البريد') }}</th><th>{{ __('الدور') }}</th><th>{{ __('آخر دخول') }}</th></tr></thead>
                <tbody>
                @foreach($company->users as $u)
                    <tr><td>{{ $u->name }}</td><td dir="ltr" class="text-end text-xs">{{ $u->email }}</td><td>{{ $u->role->label() }}</td><td class="text-xs text-slate-500">{{ $u->last_login_at?->format('Y-m-d H:i') ?? '—' }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mt-6">
        <h2 class="border-b border-slate-100 px-5 py-4 font-semibold text-ink">{{ __('النشاط') }}</h2>
        <ul class="divide-y divide-slate-100 text-sm">
            @forelse($activity as $log)
                <li class="flex justify-between px-5 py-3"><span>{{ $log->description }}</span><span class="text-xs text-slate-500">{{ $log->user?->name }} · {{ $log->created_at->format('Y-m-d H:i') }}</span></li>
            @empty
                <li class="px-5 py-6 text-center text-slate-400">{{ __('لا يوجد نشاط') }}</li>
            @endforelse
        </ul>
    </div>
</x-layouts.app>
