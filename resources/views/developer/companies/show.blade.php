<x-layouts.app :title="$company->name">
    <x-page-header :title="$company->name" :subtitle="__('مشترك منذ :date', ['date' => $company->created_at->format('Y-m-d')])" />

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
                <thead><tr><th>{{ __('الاسم') }}</th><th>{{ __('البريد') }}</th><th>{{ __('الدور') }}</th><th>{{ __('آخر دخول') }}</th><th>{{ __('الحالة') }}</th>@if(auth()->user()->hasRole('developer'))<th></th>@endif</tr></thead>
                <tbody>
                @foreach($company->users as $u)
                    <tr x-data="{ remove: false }">
                        <td class="font-medium">{{ $u->name }}</td>
                        <td dir="ltr" class="text-end text-xs">{{ $u->email }}</td>
                        <td>{{ $u->role->label() }}</td>
                        <td class="text-xs text-slate-500">{{ $u->last_login_at?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td><x-status :value="$u->is_active ? 'active' : 'suspended'" /></td>
                        @if(auth()->user()->hasRole('developer'))
                            <td class="whitespace-nowrap">
                                <div class="flex justify-end gap-2">
                                    <form method="POST" action="{{ route('developer.users.toggle', $u) }}">@csrf @method('PATCH')
                                        <button class="rounded-lg px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $u->is_active ? 'bg-amber-50 text-amber-700 ring-amber-600/20 hover:bg-amber-100' : 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 hover:bg-emerald-100' }}">{{ $u->is_active ? __('إيقاف الحساب') : __('تفعيل') }}</button>
                                    </form>
                                    <button type="button" @click="remove = true" class="rounded-lg bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700 ring-1 ring-inset ring-rose-600/20 hover:bg-rose-100">{{ __('حذف') }}</button>
                                </div>
                                <div x-show="remove" x-cloak x-transition.opacity class="fixed inset-0 z-50 grid place-items-center whitespace-normal bg-night/60 p-4 text-start backdrop-blur-sm" @keydown.escape.window="remove = false">
                                    <form method="POST" action="{{ route('developer.users.destroy', $u) }}" @click.outside="remove = false" class="card w-full max-w-md overflow-hidden">
                                        @csrf @method('DELETE')
                                        <div class="flex items-start gap-4 p-6">
                                            <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-rose-100 text-rose-600"><x-icon name="alert" class="size-6" /></span>
                                            <div class="min-w-0 flex-1 whitespace-normal">
                                                <h3 class="text-lg font-bold text-ink">{{ __('حذف :name نهائياً؟', ['name' => $u->name]) }}</h3>
                                                <p class="mt-1 text-sm text-slate-500">{{ __('يُحذف الحساب ولا يمكنه الدخول مجدداً. فواتير وعملاء الشركة تبقى كما هي.') }}</p>
                                            </div>
                                        </div>
                                        <div class="flex justify-end gap-2 border-t border-slate-100 bg-slate-50 px-6 py-4">
                                            <button type="button" @click="remove = false" class="btn-secondary">{{ __('تراجع') }}</button>
                                            <button class="btn-primary bg-rose-600 hover:bg-rose-700">{{ __('حذف المستخدم') }}</button>
                                        </div>
                                    </form>
                                </div>
                            </td>
                        @endif
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if(auth()->user()->hasRole('developer'))
        {{-- Danger zone: suspend or permanently delete the company --}}
        <div class="card mt-6 border-rose-200" x-data="{ remove: false }">
            <h2 class="border-b border-rose-100 px-5 py-4 font-semibold text-rose-700">{{ __('منطقة الخطر') }}</h2>
            <div class="divide-y divide-slate-100">
                <div class="flex flex-wrap items-center justify-between gap-4 px-5 py-4">
                    <div>
                        <p class="font-semibold text-ink">{{ $company->status === 'suspended' ? __('إعادة تفعيل الشركة') : __('إيقاف الشركة') }}</p>
                        <p class="text-sm text-slate-500">{{ $company->status === 'suspended' ? __('يستعيد مستخدموها الدخول حسب اشتراكهم أو تجربتهم.') : __('يُسجَّل خروج كل مستخدميها ولا يمكنهم الدخول حتى تعيد تفعيلها. البيانات تبقى محفوظة.') }}</p>
                    </div>
                    <form method="POST" action="{{ route('developer.companies.toggle', $company) }}">@csrf @method('PATCH')
                        <button class="{{ $company->status === 'suspended' ? 'btn-primary' : 'btn-secondary text-amber-700' }}">{{ $company->status === 'suspended' ? __('إعادة التفعيل') : __('إيقاف الشركة') }}</button>
                    </form>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-4 px-5 py-4">
                    <div>
                        <p class="font-semibold text-ink">{{ __('حذف الشركة نهائياً') }}</p>
                        <p class="text-sm text-slate-500">{{ __('يحذف الشركة وكل مستخدميها وعملائها وفواتيرها ودفعاتها واشتراكاتها. لا يمكن التراجع.') }}</p>
                    </div>
                    <button type="button" @click="remove = true" class="btn-primary bg-rose-600 hover:bg-rose-700">{{ __('حذف الشركة') }}</button>
                </div>
            </div>
            <div x-show="remove" x-cloak x-transition.opacity class="fixed inset-0 z-50 grid place-items-center bg-night/60 p-4 backdrop-blur-sm" @keydown.escape.window="remove = false">
                <form method="POST" action="{{ route('developer.companies.destroy', $company) }}" @click.outside="remove = false" class="card w-full max-w-md overflow-hidden" x-data="{ typed: '' }">
                    @csrf @method('DELETE')
                    <div class="flex items-start gap-4 p-6">
                        <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-rose-100 text-rose-600"><x-icon name="alert" class="size-6" /></span>
                        <div class="min-w-0 flex-1">
                            <h3 class="text-lg font-bold text-ink">{{ __('حذف :company نهائياً؟', ['company' => $company->name]) }}</h3>
                            <p class="mt-1 text-sm text-slate-500">{{ __('سيُحذف :users مستخدم و:invoices فاتورة وكل البيانات المرتبطة. لا يمكن استرجاعها.', ['users' => $company->users->count(), 'invoices' => $stats['invoices']]) }}</p>
                            <label class="label mt-4">{{ __('للتأكيد اكتب اسم الشركة:') }} <b class="text-ink">{{ $company->name }}</b></label>
                            <input name="confirm" x-model="typed" class="input" autocomplete="off">
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 border-t border-slate-100 bg-slate-50 px-6 py-4">
                        <button type="button" @click="remove = false" class="btn-secondary">{{ __('تراجع') }}</button>
                        <button :disabled="typed !== @js($company->name)" class="btn-primary bg-rose-600 hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-40">{{ __('حذف نهائياً') }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

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
