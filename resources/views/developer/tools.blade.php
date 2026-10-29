<x-layouts.app :title="__('أدوات الصيانة')">
    <x-page-header :title="__('أدوات الصيانة')" :subtitle="__('أوامر آمنة بنقرة واحدة + خريطة قاعدة البيانات والمسارات لتسهيل التعديل.')" />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @foreach($actions as $key => $action)
            <form method="POST" action="{{ route('developer.tools.run', $key) }}" class="card flex flex-col justify-between p-5" x-data @submit.prevent="if (confirm(@js(__('تنفيذ:').' '.__($action['label']).'؟'))) $el.submit()">
                @csrf
                <div><p class="font-semibold text-ink">{{ __($action['label']) }}</p><p class="mt-1 text-xs text-slate-500" dir="ltr">{{ __($action['description']) }}</p></div>
                <button class="btn-secondary mt-4 w-full"><x-icon name="terminal" class="size-4" /> {{ __('تشغيل') }}</button>
            </form>
        @endforeach
    </div>

    @php
        $modeStyles = ['live' => 'bg-emerald-50 text-emerald-700', 'test' => 'bg-sky-50 text-sky-700', 'sandbox' => 'bg-amber-50 text-amber-700', 'off' => 'bg-slate-100 text-slate-600'];
        $modeLabels = ['live' => 'Stripe — دفع حقيقي', 'test' => 'Stripe — وضع الاختبار', 'sandbox' => 'بوابة تجريبية محلية', 'off' => 'التحويل البنكي فقط'];
    @endphp
    <div class="card mt-6 grid gap-4 p-5 md:grid-cols-3">
        <div>
            <p class="text-sm text-slate-500">{{ __('بوابة الدفع') }}</p>
            <span class="mt-2 inline-flex rounded-full px-3 py-1 text-sm font-semibold {{ $modeStyles[$gateway['mode']] }}">{{ __($modeLabels[$gateway['mode']]) }}</span>
        </div>
        <div>
            <p class="text-sm text-slate-500">Webhook</p>
            <p class="mt-2 text-sm font-semibold {{ $gateway['webhook'] ? 'text-emerald-600' : 'text-amber-600' }}">{{ $gateway['webhook'] ? __('مُعَدّ (STRIPE_WEBHOOK_SECRET)') : __('غير مُعَدّ بعد') }}</p>
        </div>
        <div>
            <p class="text-sm text-slate-500">{{ __('رابط الـ Webhook في Stripe') }}</p>
            <code class="mt-2 block break-all rounded-lg bg-slate-100 px-2 py-1 text-xs" dir="ltr">{{ $gateway['webhook_url'] }}</code>
            <p class="mt-1 text-xs text-slate-500" dir="ltr">checkout.session.completed · checkout.session.async_payment_succeeded</p>
        </div>
    </div>

    @if($output)
        <pre class="card mt-6 overflow-x-auto bg-night p-5 font-mono text-xs leading-relaxed text-emerald-300" dir="ltr">{{ $output }}</pre>
    @endif

    <div class="mt-6 grid gap-6 xl:grid-cols-5">
        <div class="card overflow-hidden xl:col-span-2">
            <h2 class="border-b border-slate-100 px-5 py-4 font-semibold text-ink">{{ __('جداول قاعدة البيانات') }}</h2>
            <table class="table-base font-mono text-xs" dir="ltr">
                <thead><tr><th>table</th><th>rows</th><th>KB</th></tr></thead>
                <tbody>@foreach($tables as $t)<tr><td>{{ $t['name'] }}</td><td>{{ number_format($t['rows']) }}</td><td>{{ $t['size'] }}</td></tr>@endforeach</tbody>
            </table>
        </div>
        <div class="card overflow-hidden xl:col-span-3">
            <h2 class="border-b border-slate-100 px-5 py-4 font-semibold text-ink">{{ __('خريطة المسارات') }} ({{ $routes->count() }})</h2>
            <div class="max-h-[32rem] overflow-auto">
                <table class="table-base font-mono text-xs" dir="ltr">
                    <thead><tr><th>method</th><th>uri</th><th>name</th><th>middleware</th></tr></thead>
                    <tbody>@foreach($routes as $r)<tr><td class="text-brand-700">{{ $r['method'] }}</td><td>/{{ ltrim($r['uri'], '/') }}</td><td>{{ $r['name'] }}</td><td class="text-slate-400">{{ $r['middleware'] }}</td></tr>@endforeach</tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mt-6 p-5 text-sm">
        <h2 class="font-semibold text-ink">{{ __('أين أعدّل؟ — دليل سريع للكود') }}</h2>
        <ul class="mt-3 grid gap-2 font-mono text-xs sm:grid-cols-2" dir="ltr">
            <li><b>app/Services/ReminderService.php</b> — reminder schedule & sending logic</li>
            <li><b>app/Services/InvoiceImportService.php</b> — Excel columns & parsing</li>
            <li><b>app/Services/ReceivablesReport.php</b> — KPIs, aging buckets, trends</li>
            <li><b>routes/console.php</b> — daily schedule time (09:00 Asia/Dubai)</li>
            <li><b>routes/web.php</b> — routes per role (manager / owner / developer)</li>
            <li><b>resources/views/components/layouts/app.blade.php</b> — sidebar & layout</li>
            <li><b>resources/css/app.css</b> — brand colors (Tailwind @theme)</li>
            <li><b>database/seeders/PlatformSeeder.php</b> — plans, settings, default templates</li>
        </ul>
    </div>
</x-layouts.app>
