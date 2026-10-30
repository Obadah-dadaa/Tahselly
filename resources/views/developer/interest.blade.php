<x-layouts.app :title="__('طلبات الاشتراك')">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-ink">{{ __('طلبات الاشتراك') }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ __('من أرسل «مهتم بالاشتراك». تواصل معه، ثم فعّل حسابه لمدة تختارها أو علّم الطلب كمُعالَج.') }}</p>
    </div>
    <div class="card overflow-hidden">
        <ul class="divide-y divide-slate-100">
            @forelse($requests as $item)
                <li class="flex flex-wrap items-start justify-between gap-4 px-6 py-5 {{ $item->status === 'new' ? '' : 'opacity-60' }}">
                    <div class="min-w-0">
                        <p class="font-semibold text-ink">{{ $item->name }}
                            @if($item->company_name || $item->company)<span class="font-normal text-slate-500">· {{ $item->company_name ?: $item->company->name }}</span>@endif
                            @if($item->status === 'new')<span class="ms-2 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">{{ __('جديد') }}</span>@endif
                        </p>
                        <p class="mt-1 text-sm text-slate-600" dir="ltr">
                            <a href="mailto:{{ $item->email }}" class="text-brand-700 hover:underline">{{ $item->email }}</a>
                            @if($item->phone) · <a href="https://wa.me/{{ preg_replace('/\D+/', '', $item->phone) }}" target="_blank" rel="noopener" class="text-brand-700 hover:underline">{{ $item->phone }}</a>@endif
                        </p>
                        @if($item->message)<p class="mt-2 max-w-2xl whitespace-pre-line text-sm text-slate-600">{{ $item->message }}</p>@endif
                        <p class="mt-2 text-xs text-slate-400">{{ $item->created_at->format('Y-m-d H:i') }}
                            @if($item->company) · <a href="{{ route('developer.companies.show', $item->company) }}" class="hover:underline">{{ __('ينتهي الوصول :date', ['date' => $item->company->trial_ends_at?->format('Y-m-d') ?? '—']) }}</a>@endif
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        @if($item->company)
                            <form method="POST" action="{{ route('developer.interest.extend', $item) }}" class="flex items-center gap-2">
                                @csrf
                                <select name="days" class="input w-auto py-2 text-sm">
                                    @foreach([30, 90, 180, 365] as $days)<option value="{{ $days }}">{{ __(':count يوماً', ['count' => $days]) }}</option>@endforeach
                                </select>
                                <button class="btn-primary py-2 text-sm">{{ __('فعّل الحساب') }}</button>
                            </form>
                        @endif
                        @if($item->status === 'new')
                            <form method="POST" action="{{ route('developer.interest.handle', $item) }}">@csrf<button class="btn-secondary py-2 text-sm">{{ __('تمت المعالجة') }}</button></form>
                        @endif
                    </div>
                </li>
            @empty
                <li class="px-6 py-12 text-center text-slate-400">{{ __('لا توجد طلبات بعد.') }}</li>
            @endforelse
        </ul>
    </div>
    <div class="mt-4">{{ $requests->links() }}</div>
</x-layouts.app>
