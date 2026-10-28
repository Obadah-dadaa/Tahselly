<x-layouts.app title="{{ __('الشركات') }}">
    <x-page-header title="{{ __('الشركات المشتركة') }}" :subtitle="__(':count شركة', ['count' => $companies->total()])" />

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="card overflow-hidden xl:col-span-2">
            <form class="flex gap-2 border-b border-slate-100 p-4">
                <input name="q" value="{{ request('q') }}" placeholder="{{ __('اسم الشركة') }}" class="input max-w-xs">
                <select name="status" class="input w-auto"><option value="">{{ __('كل الحالات') }}</option>@foreach(['active' => __('نشطة'), 'trial' => __('تجريبية'), 'suspended' => __('موقوفة')] as $k => $l)<option value="{{ $k }}" @selected(request('status') === $k)>{{ __($l) }}</option>@endforeach</select>
                <button class="btn-secondary">{{ __('بحث') }}</button>
            </form>
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead><tr><th>{{ __('الشركة') }}</th><th>{{ __('الباقة') }}</th><th>{{ __('عملاء') }}</th><th>{{ __('فواتير') }}</th><th>{{ __('الحالة') }}</th><th></th></tr></thead>
                    <tbody>
                    @foreach($companies as $c)
                        <tr>
                            <td><a href="{{ route('developer.companies.show', $c) }}" class="font-semibold hover:text-brand-700">{{ $c->name }}</a><p class="text-xs text-slate-400">{{ $c->created_at->format('Y-m-d') }}</p></td>
                            <td>{{ $c->plan?->localizedName() }} <span class="num text-xs text-slate-400">{{ (int) $c->plan?->price }}</span></td>
                            <td class="num">{{ $c->clients_count }}</td>
                            <td class="num">{{ $c->invoices_count }}</td>
                            <td><x-status :value="$c->status" /></td>
                            <td>
                                <form method="POST" action="{{ route('developer.companies.impersonate', $c) }}">@csrf<button class="text-xs font-semibold text-violet-700 hover:underline">{{ __('دخول كالمالك') }}</button></form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $companies->links() }}</div>
        </div>

        <form method="POST" action="{{ route('developer.companies.store') }}" class="card h-fit space-y-4 p-5">
            @csrf
            <h2 class="font-semibold text-ink">{{ __('إضافة شركة (عميل جديد)') }}</h2>
            <div><label class="label">{{ __('اسم الشركة') }}</label><input name="name" required class="input"></div>
            <div><label class="label">{{ __('الباقة') }}</label><select name="plan_id" class="input">@foreach($plans as $p)<option value="{{ $p->id }}">{{ $p->localizedName() }} — {{ (int) $p->price }} {{ __('درهم') }}</option>@endforeach</select></div>
            <div><label class="label">{{ __('أيام التجربة') }}</label><input type="number" name="trial_days" value="{{ \App\Models\SystemSetting::get('default_trial_days', 14) }}" class="input num"></div>
            <hr class="border-slate-100">
            <div><label class="label">{{ __('اسم المالك') }}</label><input name="owner_name" required class="input"></div>
            <div><label class="label">{{ __('بريد المالك') }}</label><input type="email" name="owner_email" required class="input" dir="ltr"></div>
            <div><label class="label">{{ __('كلمة مرور مؤقتة') }}</label><input type="password" name="owner_password" required minlength="8" class="input" dir="ltr"></div>
            <button class="btn-primary w-full">{{ __('إنشاء الشركة') }}</button>
        </form>
    </div>
</x-layouts.app>
