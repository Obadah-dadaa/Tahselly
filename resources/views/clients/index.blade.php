<x-layouts.app :title="__('العملاء')">
    <x-page-header :title="__('العملاء')" :subtitle="__(':count عميل', ['count' => $clients->total()])">
        <x-slot:actions>
            <a href="{{ route('clients.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> {{ __('عميل جديد') }}</a>
        </x-slot:actions>
    </x-page-header>

    <div class="card overflow-hidden">
        <form class="flex gap-2 border-b border-slate-100 p-4">
            <input name="q" value="{{ request('q') }}" placeholder="{{ __('بحث بالاسم، البريد أو الهاتف…') }}" class="input max-w-sm">
            <button class="btn-secondary">{{ __('بحث') }}</button>
        </form>
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead><tr><th>{{ __('العميل') }}</th><th>{{ __('التواصل') }}</th><th>{{ __('فواتير مفتوحة') }}</th><th>{{ __('المستحق') }}</th><th>{{ __('التذكيرات') }}</th></tr></thead>
                <tbody>
                @forelse($clients as $client)
                    <tr>
                        <td>
                            <a href="{{ route('clients.show', $client) }}" class="font-semibold text-ink hover:text-brand-700">{{ $client->name }}</a>
                            <p class="text-xs text-slate-500">{{ $client->contact_name }}</p>
                        </td>
                        <td class="text-xs"><p dir="ltr" class="text-end">{{ $client->email }}</p><p dir="ltr" class="text-end text-slate-500">{{ $client->phone }}</p></td>
                        <td class="num">{{ $client->open_invoices_count }}</td>
                        <td class="num font-semibold">@money(($client->open_amount ?? 0) - ($client->open_paid ?? 0))</td>
                        <td>
                            @if($client->reminders_enabled)
                                <span class="text-xs font-semibold text-emerald-600">{{ __('● مفعّلة') }}</span>
                            @else
                                <span class="text-xs font-semibold text-slate-400">{{ __('○ موقوفة') }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-10 text-center text-slate-400">{{ __('لا يوجد عملاء') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $clients->links() }}</div>
    </div>
</x-layouts.app>
