<x-layouts.app :title="__('إعدادات النظام')">
    <x-page-header :title="__('إعدادات النظام')" :subtitle="__('غيّر اسم المنتج، الألوان والميزات بدون لمس الكود. القيم محفوظة في جدول system_settings.')" />
    @php $groupLabels = ['general' => __('عام'), 'branding' => __('الهوية'), 'features' => __('الميزات (Feature Flags)'), 'billing' => __('الاشتراكات')]; @endphp
    <form method="POST" action="{{ route('developer.settings.update') }}" class="max-w-3xl space-y-6">
        @csrf @method('PUT')
        @foreach($groups as $group => $settings)
            <div class="card p-6">
                <h2 class="mb-4 font-semibold text-ink">{{ __($groupLabels[$group] ?? $group) }}</h2>
                <div class="space-y-4">
                    @foreach($settings as $setting)
                        @if($setting->type === 'bool')
                            <label class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3">
                                <span><span class="block text-sm font-medium">{{ __($setting->label) }}</span><code class="text-[11px] text-slate-400" dir="ltr">{{ $setting->key }}</code></span>
                                <input type="checkbox" name="settings[{{ $setting->key }}]" value="1" @checked($setting->value) class="size-5 rounded text-brand-600">
                            </label>
                        @else
                            <div>
                                <label class="label">{{ __($setting->label) }} <code class="text-[11px] text-slate-400" dir="ltr">{{ $setting->key }}</code></label>
                                @if($setting->type === 'text')
                                    <textarea name="settings[{{ $setting->key }}]" rows="2" class="input">{{ $setting->value }}</textarea>
                                @else
                                    <input name="settings[{{ $setting->key }}]" value="{{ $setting->value }}" type="{{ $setting->type === 'int' ? 'number' : 'text' }}" class="input">
                                @endif
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endforeach
        <button class="btn-primary">{{ __('حفظ الإعدادات') }}</button>
    </form>
</x-layouts.app>
