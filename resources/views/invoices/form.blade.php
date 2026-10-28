<x-layouts.app :title="$invoice->exists ? __('تعديل فاتورة') : __('فاتورة جديدة')">
    <x-page-header :title="$invoice->exists ? __('تعديل الفاتورة').' '.$invoice->number : __('فاتورة جديدة')" />

    @if($clients->isEmpty())
        <div class="card p-8 text-center">
            <p class="text-slate-500">{{ __('أضف عميلاً أولاً قبل إنشاء فاتورة.') }}</p>
            <a href="{{ route('clients.create') }}" class="btn-primary mt-4">{{ __('إضافة عميل') }}</a>
        </div>
    @else
        <form method="POST" action="{{ $invoice->exists ? route('invoices.update', $invoice) : route('invoices.store') }}" class="card max-w-3xl space-y-5 p-6">
            @csrf
            @if($invoice->exists) @method('PUT') @endif
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="label">{{ __('العميل *') }}</label>
                    <select name="client_id" required class="input">
                        <option value="">{{ __('اختر العميل') }}</option>
                        @foreach($clients as $c)<option value="{{ $c->id }}" @selected(old('client_id', $invoice->client_id) == $c->id)>{{ $c->name }}</option>@endforeach
                    </select>
                </div>
                <div><label class="label">{{ __('رقم الفاتورة *') }}</label><input name="number" value="{{ old('number', $invoice->number) }}" required class="input" dir="ltr"></div>
                <div class="grid grid-cols-3 gap-2">
                    <div class="col-span-2"><label class="label">{{ __('المبلغ *') }}</label><input type="number" step="0.01" name="amount" value="{{ old('amount', $invoice->amount) }}" required class="input num"></div>
                    <div><label class="label">{{ __('العملة') }}</label><input name="currency" value="{{ old('currency', $invoice->currency) }}" maxlength="3" class="input uppercase" dir="ltr"></div>
                </div>
                <div><label class="label">{{ __('تاريخ الإصدار *') }}</label><input type="date" name="issue_date" value="{{ old('issue_date', $invoice->issue_date?->format('Y-m-d')) }}" required class="input"></div>
                <div><label class="label">{{ __('تاريخ الاستحقاق *') }}</label><input type="date" name="due_date" value="{{ old('due_date', $invoice->due_date?->format('Y-m-d')) }}" required class="input"></div>
                <div class="sm:col-span-2"><label class="label">{{ __('الوصف') }}</label><textarea name="description" rows="3" class="input">{{ old('description', $invoice->description) }}</textarea></div>
            </div>
            <div class="flex gap-2 border-t border-slate-100 pt-5">
                <button class="btn-primary">{{ __('حفظ الفاتورة') }}</button>
                <a href="{{ $invoice->exists ? route('invoices.show', $invoice) : route('invoices.index') }}" class="btn-secondary">{{ __('إلغاء') }}</a>
            </div>
        </form>
    @endif
</x-layouts.app>
