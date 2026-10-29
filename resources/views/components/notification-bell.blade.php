{{-- Bell in the top bar: unread count and the latest notices (subscription news for owners and staff). --}}
@php
    $user = auth()->user();
    $unread = $user->unreadNotifications()->count();
    $latest = $user->notifications()->latest()->limit(8)->get();
@endphp
<div class="relative" x-data="{ bell: false }">
    <button type="button" @click="bell = !bell" class="relative grid size-10 place-items-center rounded-xl hover:bg-slate-100" aria-label="{{ __('الإشعارات') }}">
        <x-icon name="bell" class="size-5 text-slate-600" />
        @if($unread)
            <span class="num absolute -top-0.5 -end-0.5 grid min-w-5 place-items-center rounded-full bg-rose-600 px-1 text-[11px] font-bold leading-5 text-white">{{ $unread > 9 ? '9+' : $unread }}</span>
        @endif
    </button>
    <div x-show="bell" x-cloak @click.outside="bell = false" x-transition
         class="card absolute end-0 top-12 z-40 w-[22rem] max-w-[calc(100vw-2rem)] overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <p class="font-semibold text-ink">{{ __('الإشعارات') }}</p>
            @if($unread)
                <form method="POST" action="{{ route('notifications.read') }}">@csrf<button class="text-xs font-semibold text-brand-700 hover:underline">{{ __('تعليم الكل كمقروء') }}</button></form>
            @endif
        </div>
        <ul class="max-h-96 divide-y divide-slate-100 overflow-y-auto">
            @forelse($latest as $n)
                @php $kind = $n->data['kind'] ?? ''; @endphp
                <li>
                    <a href="{{ route('notifications.open', $n->id) }}" class="flex gap-3 px-4 py-3 hover:bg-slate-50 {{ $n->read_at ? '' : 'bg-brand-50/50' }}">
                        <span class="grid size-9 shrink-0 place-items-center rounded-xl {{ $kind === 'subscription_cancelled' ? 'bg-rose-50 text-rose-600' : ($kind === 'subscription_requested' ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600') }}">
                            <x-icon :name="\App\Notifications\AccountNotice::icon($kind)" class="size-4" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold text-ink">{{ \App\Notifications\AccountNotice::title($kind) }}</span>
                            <span class="mt-0.5 block text-xs leading-5 text-slate-500">{{ \App\Notifications\AccountNotice::body($kind, $n->data['params'] ?? []) }}</span>
                            <span class="mt-1 block text-[11px] text-slate-400">{{ $n->created_at->locale(app()->getLocale())->diffForHumans() }}</span>
                        </span>
                        @unless($n->read_at)<span class="mt-1.5 size-2 shrink-0 rounded-full bg-brand-500"></span>@endunless
                    </a>
                </li>
            @empty
                <li class="px-4 py-10 text-center text-sm text-slate-400">{{ __('لا توجد إشعارات') }}</li>
            @endforelse
        </ul>
    </div>
</div>
