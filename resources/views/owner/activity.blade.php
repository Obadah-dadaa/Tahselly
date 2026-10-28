<x-layouts.app title="{{ __('سجل النشاط') }}">
    <x-page-header title="{{ __('سجل النشاط') }}" :subtitle="__('كل إجراء قام به فريقك داخل النظام.')" />
    <div class="card overflow-hidden">
        <table class="table-base">
            <thead><tr><th>{{ __('الوقت') }}</th><th>{{ __('المستخدم') }}</th><th>{{ __('الإجراء') }}</th><th>IP</th></tr></thead>
            <tbody>
            @foreach($logs as $log)
                <tr>
                    <td class="num whitespace-nowrap text-slate-500">{{ $log->created_at->format('Y-m-d H:i') }}</td>
                    <td>{{ $log->user?->name ?? __('النظام') }}</td>
                    <td>{{ $log->description }}</td>
                    <td class="num text-xs text-slate-400">{{ $log->ip }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="p-4">{{ $logs->links() }}</div>
    </div>
</x-layouts.app>
