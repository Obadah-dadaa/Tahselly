<x-layouts.app title="{{ __('الباقات والأسعار') }}">
    <x-page-header title="{{ __('الباقات والأسعار') }}" :subtitle="__('عدّل الأسعار والحدود والمزايا — تنعكس فوراً على الصفحة التسويقية وحدود الشركات.')" />
    <form method="POST" action="{{ route('developer.plans.update') }}">
        @csrf @method('PUT')
        <div class="grid gap-6 lg:grid-cols-2">
            @foreach($plans as $plan)
                <div class="card space-y-4 p-6">
                    <div class="flex items-center justify-between">
                        <code class="rounded bg-slate-100 px-2 py-0.5 text-xs" dir="ltr">{{ $plan->code }}</code>
                        <span class="text-xs text-slate-500">{{ $plan->companies_count }} {{ __('شركة مشتركة') }}</span>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="label">{{ __('اسم الباقة') }} (AR)</label><input name="plans[{{ $plan->id }}][name]" value="{{ $plan->name }}" class="input"></div>
                        <div><label class="label">{{ __('اسم الباقة') }} (EN)</label><input name="plans[{{ $plan->id }}][name_en]" value="{{ $plan->name_en }}" class="input" dir="ltr"></div>
                        <div><label class="label">{{ __('السعر الشهري (درهم)') }}</label><input type="number" step="1" name="plans[{{ $plan->id }}][price]" value="{{ (int) $plan->price }}" class="input num"></div>
                        <div><label class="label">{{ __('أقصى مستخدمين') }}</label><input type="number" name="plans[{{ $plan->id }}][max_users]" value="{{ $plan->max_users }}" class="input num"></div>
                        <div><label class="label">{{ __('أقصى فواتير') }}</label><input type="number" name="plans[{{ $plan->id }}][max_invoices]" value="{{ $plan->max_invoices }}" class="input num"></div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label class="label">{{ __('المزايا (سطر لكل ميزة)') }} (AR)</label><textarea name="plans[{{ $plan->id }}][features]" rows="5" class="input">{{ implode("\n", $plan->features ?? []) }}</textarea></div>
                        <div><label class="label">{{ __('المزايا (سطر لكل ميزة)') }} (EN)</label><textarea name="plans[{{ $plan->id }}][features_en]" rows="5" class="input" dir="ltr">{{ implode("\n", $plan->features_en ?? []) }}</textarea></div>
                    </div>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="plans[{{ $plan->id }}][is_active]" value="1" @checked($plan->is_active) class="rounded text-brand-600"> {{ __('متاحة للبيع') }}</label>
                </div>
            @endforeach
        </div>
        <button class="btn-primary mt-6">{{ __('حفظ الباقات') }}</button>
    </form>
</x-layouts.app>
