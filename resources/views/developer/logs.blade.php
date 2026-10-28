<x-layouts.app title="{{ __('سجل الأخطاء') }}">
    <x-page-header title="{{ __('سجل الأخطاء') }}" :subtitle="'storage/logs/laravel.log — '.$size.' KB'">
        <x-slot:actions>
            @foreach(['' => __('الكل'), 'error' => 'Errors', 'warning' => 'Warnings', 'info' => 'Info'] as $k => $l)
                <a href="{{ route('developer.logs', $k ? ['level' => $k] : []) }}" class="rounded-full px-3 py-1 text-xs font-semibold {{ request('level', '') === $k ? 'bg-night text-white' : 'bg-surface ring-1 ring-slate-200' }}">{{ $l }}</a>
            @endforeach
        </x-slot:actions>
    </x-page-header>
    <div class="space-y-3" dir="ltr">
        @forelse($entries as $e)
            <details class="card overflow-hidden">
                <summary class="flex cursor-pointer items-center gap-3 px-4 py-3 text-xs">
                    <span class="rounded px-2 py-0.5 font-bold uppercase {{ in_array($e['level'], ['error', 'critical', 'emergency', 'alert']) ? 'bg-rose-100 text-rose-700' : ($e['level'] === 'warning' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600') }}">{{ $e['level'] }}</span>
                    <span class="font-mono text-slate-400">{{ $e['time'] }}</span>
                    <span class="truncate font-mono">{{ \Illuminate\Support\Str::limit(strtok($e['message'], "\n"), 160) }}</span>
                </summary>
                <pre class="max-h-96 overflow-auto bg-night p-4 font-mono text-[11px] text-slate-300">{{ $e['message'] }}</pre>
            </details>
        @empty
            <div class="card p-10 text-center text-slate-400" dir="rtl">{{ __('السجل نظيف ✨ لا توجد أخطاء.') }}</div>
        @endforelse
    </div>
</x-layouts.app>
