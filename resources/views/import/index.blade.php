<x-layouts.app :title="__('استيراد Excel')">
    <x-page-header :title="__('استيراد الفواتير من Excel')" :subtitle="__('ارفع ملف xlsx أو csv — يتم إنشاء العملاء تلقائياً وتحديث الفواتير الموجودة بنفس الرقم.')" />

    @if(! \App\Models\SystemSetting::get('feature_excel_import', true))
        <div class="card p-8 text-center text-slate-500">{{ __('ميزة الاستيراد معطلة حالياً من قبل مدير النظام.') }}</div>
    @else
    <div class="grid gap-6 lg:grid-cols-5">
        <form method="POST" action="{{ route('import.store') }}" enctype="multipart/form-data" class="card p-6 lg:col-span-3"
              x-data="{ name: '', drag: false }">
            @csrf
            <label @dragover.prevent="drag=true" @dragleave="drag=false" @drop="drag=false"
                   :class="drag ? 'border-brand-500 bg-brand-50' : 'border-slate-300 bg-slate-50'"
                   class="relative flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed px-6 py-14 text-center transition">
                <span class="grid size-14 place-items-center rounded-2xl bg-surface text-brand-700 shadow-sm"><x-icon name="upload" class="size-7" /></span>
                <p class="mt-4 font-semibold text-ink" x-text="name || @js(__('اسحب الملف هنا أو اضغط للاختيار'))"></p>
                <p class="mt-1 text-sm text-slate-500">{{ __('XLSX, XLS, CSV — حتى 10 ميجابايت') }}</p>
                @if(! auth()->user()->company->canUse('bulk_import') || auth()->user()->company->featureState('bulk_import') === 'trial')
                    <p class="mt-1 flex items-center justify-center gap-2 text-xs text-slate-500">{{ __('حتى :limit صف في الملف الواحد', ['limit' => \App\Support\Edition::isBeta() ? \App\Support\Edition::limit('import_rows') : \App\Models\Plan::IMPORT_ROWS_LIMIT]) }} <x-plan-badge feature="bulk_import" /></p>
                @endif
                <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="absolute inset-0 cursor-pointer opacity-0" @change="name = $event.target.files[0]?.name">
            </label>
            <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
                <a href="{{ route('import.template') }}" download class="btn-secondary"><x-icon name="download" class="size-4" /> {{ __('تحميل نموذج Excel') }}</a>
                <button class="btn-primary"><x-icon name="upload" class="size-4" /> {{ __('بدء الاستيراد') }}</button>
            </div>
        </form>

        <div class="card p-6 lg:col-span-2">
            <h2 class="font-semibold text-ink">{{ __('الأعمدة المدعومة') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('يتعرف النظام على العناوين بالعربية أو الإنجليزية.') }}</p>
            <ul class="mt-4 space-y-2 text-sm">
                @foreach($columns as $field => $aliases)
                    <li class="flex items-start justify-between gap-3 rounded-lg bg-slate-50 px-3 py-2">
                        <code class="text-xs font-semibold text-brand-800" dir="ltr">{{ $field }}</code>
                        <span class="text-end text-xs text-slate-500">{{ implode(__('، '), array_slice($aliases, -2)) }} @if(in_array($field, ['number', 'client_name', 'due_date', 'amount']))<b class="text-rose-500">*</b>@endif</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    <div class="card mt-6 overflow-hidden">
        <h2 class="border-b border-slate-100 px-5 py-4 font-semibold text-ink">{{ __('عمليات الاستيراد السابقة') }}</h2>
        <table class="table-base">
            <thead><tr><th>{{ __('الملف') }}</th><th>{{ __('بواسطة') }}</th><th>{{ __('الصفوف') }}</th><th>{{ __('تم') }}</th><th>{{ __('أخطاء') }}</th><th>{{ __('التاريخ') }}</th></tr></thead>
            <tbody>
            @forelse($batches as $batch)
                <tr>
                    <td><a href="{{ route('import.show', $batch) }}" class="font-semibold text-brand-700 hover:underline">{{ $batch->filename }}</a></td>
                    <td>{{ $batch->user?->name }}</td>
                    <td class="num">{{ $batch->rows_total }}</td>
                    <td class="num text-emerald-600">{{ $batch->rows_imported }}</td>
                    <td class="num {{ $batch->rows_failed ? 'text-rose-600' : '' }}">{{ $batch->rows_failed }}</td>
                    <td class="num text-slate-500">{{ $batch->created_at->format('Y-m-d H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="py-10 text-center text-slate-400">{{ __('لم يتم استيراد أي ملف بعد') }}</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $batches->links() }}</div>
    </div>
</x-layouts.app>
