<x-layouts.app :title="__('فريق العمل')">
    <x-page-header :title="__('فريق العمل')" :subtitle="\App\Support\Edition::isBeta()
        ? __(':count من :max مستخدمين مسموحين في النسخة التجريبية', ['count' => $users->count(), 'max' => \App\Support\Edition::limit('max_users')])
        : __(':count من :max مستخدمين مسموحين في باقتك', ['count' => $users->count(), 'max' => $company->plan?->max_users ?? '—'])" />

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card overflow-hidden lg:col-span-2">
            <table class="table-base">
                <thead><tr><th>{{ __('المستخدم') }}</th><th>{{ __('الدور') }}</th><th>{{ __('آخر دخول') }}</th><th>{{ __('الحالة') }}</th><th></th></tr></thead>
                <tbody>
                @foreach($users as $member)
                    <tr x-data="{ edit: false }">
                        <td><p class="font-semibold">{{ $member->name }}</p><p class="text-xs text-slate-500" dir="ltr">{{ $member->email }}</p></td>
                        <td>{{ $member->role->label() }}</td>
                        <td class="text-xs text-slate-500">{{ $member->last_login_at?->locale(app()->getLocale())->diffForHumans() ?? __('لم يسجل بعد') }}</td>
                        <td><x-status :value="$member->is_active ? 'active' : 'suspended'" /></td>
                        <td>
                            @if($member->is(auth()->user()))
                            @elseif(! $company->canUse('team_permissions'))
                                <x-plan-badge feature="team_permissions" />
                            @else
                                <form method="POST" action="{{ route('owner.team.update', $member) }}" class="flex items-center gap-2">
                                    @csrf @method('PATCH')
                                    <select name="role" class="input w-auto py-1 text-xs"><option value="manager" @selected($member->role->value === 'manager')>{{ __('مدير') }}</option><option value="owner" @selected($member->role->value === 'owner')>{{ __('مالك') }}</option></select>
                                    <input type="hidden" name="is_active" value="{{ $member->is_active ? 0 : 1 }}">
                                    <button class="text-xs font-semibold {{ $member->is_active ? 'text-rose-600' : 'text-emerald-600' }}" onclick="this.form.is_active.value='{{ $member->is_active ? 0 : 1 }}'">{{ $member->is_active ? __('تعطيل') : __('تفعيل') }}</button>
                                    <button class="text-xs font-semibold text-brand-700" onclick="this.form.is_active.value='{{ $member->is_active ? 1 : 0 }}'">{{ __('حفظ الدور') }}</button>
                                </form>
                                <x-plan-badge feature="team_permissions" class="mt-1" />
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <form method="POST" action="{{ route('owner.team.store') }}" class="card space-y-4 p-5">
            @csrf
            <h2 class="font-semibold text-ink">{{ __('إضافة عضو') }}</h2>
            <div><label class="label">{{ __('الاسم') }}</label><input name="name" required class="input"></div>
            <div><label class="label">{{ __('البريد الإلكتروني') }}</label><input type="email" name="email" required class="input" dir="ltr"></div>
            <div><label class="label">{{ __('الدور') }}</label><select name="role" class="input"><option value="manager">{{ __('مدير (تشغيل وتحصيل)') }}</option>@if($company->canUse('team_permissions'))<option value="owner">{{ __('مالك (كل الصلاحيات)') }}</option>@endif</select><x-plan-badge feature="team_permissions" class="mt-1" /></div>
            <div><label class="label">{{ __('كلمة مرور مؤقتة') }}</label><input type="password" name="password" required minlength="8" class="input" dir="ltr"></div>
            <button class="btn-primary w-full">{{ __('إضافة') }}</button>
        </form>
    </div>
</x-layouts.app>
