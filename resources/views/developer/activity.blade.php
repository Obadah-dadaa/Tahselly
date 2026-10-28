<x-layouts.app title="{{ __('نشاط المنصة') }}">
    <x-page-header title="{{ __('نشاط المنصة') }}" :subtitle="__('كل الإجراءات عبر جميع الشركات.')" />
    <div class="card overflow-hidden">
        <table class="table-base">
            <thead><tr><th>{{ __('الوقت') }}</th><th>{{ __('الشركة') }}</th><th>{{ __('المستخدم') }}</th><th>{{ __('الإجراء') }}</th><th>{{ __('الكود') }}</th><th>IP</th></tr></thead>
            <tbody>
            @foreach($logs as $log)
                <tr>
                    <td class="num whitespace-nowrap text-slate-500">{{ $log->created_at->format('Y-m-d H:i') }}</td>
                    <td>{{ $log->company?->name ?? '—' }}</td>
                    <td>{{ $log->user?->name ?? __('النظام') }}</td>
                    <td>{{ $log->description }}</td>
                    <td><code class="text-xs text-slate-400" dir="ltr">{{ $log->action }}</code></td>
                    <td class="num text-xs text-slate-400">{{ $log->ip }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="p-4">{{ $logs->links() }}</div>
    </div>
</x-layouts.app>
