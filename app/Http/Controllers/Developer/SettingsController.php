<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Plan;
use App\Models\ReminderTemplate;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        return view('developer.settings', [
            'groups' => SystemSetting::query()->orderBy('group')->orderBy('id')->get()->groupBy('group'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $values = $request->input('settings', []);
        SystemSetting::query()->each(function (SystemSetting $setting) use ($values): void {
            $setting->value = $setting->type === 'bool'
                ? (isset($values[$setting->key]) ? '1' : '0')
                : ($values[$setting->key] ?? $setting->value);
            $setting->save();
        });
        SystemSetting::flush();
        ActivityLog::record('system.settings', 'تحديث إعدادات النظام');

        return back()->with('success', __('تم حفظ إعدادات النظام.'));
    }

    public function plans(): View
    {
        return view('developer.plans', ['plans' => Plan::query()->withCount('companies')->ordered()->get()]);
    }

    public function updatePlans(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'plans' => ['required', 'array'],
            'plans.*.name' => ['required', 'string', 'max:60'],
            'plans.*.price' => ['required', 'numeric', 'min:0'],
            'plans.*.max_users' => ['required', 'integer', 'min:1'],
            'plans.*.max_invoices' => ['required', 'integer', 'min:1'],
            'plans.*.features' => ['nullable', 'string', 'max:2000'],
            'plans.*.name_en' => ['nullable', 'string', 'max:60'],
            'plans.*.features_en' => ['nullable', 'string', 'max:2000'],
        ]);

        foreach ($data['plans'] as $id => $values) {
            Plan::query()->whereKey($id)->update([
                'name' => $values['name'],
                'price' => $values['price'],
                'max_users' => $values['max_users'],
                'max_invoices' => $values['max_invoices'],
                'features' => json_encode(array_values(array_filter(array_map('trim', explode("\n", $values['features'] ?? ''))))),
                'name_en' => $values['name_en'] ?? null,
                'features_en' => json_encode(array_values(array_filter(array_map('trim', explode("\n", $values['features_en'] ?? ''))))),
                'is_active' => $request->boolean("plans.{$id}.is_active"),
            ]);
        }
        ActivityLog::record('system.plans', 'تحديث الباقات والأسعار');

        return back()->with('success', __('تم حفظ الباقات.'));
    }

    public function templates(): View
    {
        return view('developer.templates', [
            'templates' => ReminderTemplate::query()->whereNull('company_id')->orderBy('locale')->orderBy('stage')->orderBy('channel')->get(),
            'placeholders' => ReminderTemplate::PLACEHOLDERS,
        ]);
    }

    public function updateTemplates(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'templates' => ['required', 'array'],
            'templates.*.subject' => ['nullable', 'string', 'max:190'],
            'templates.*.body' => ['required', 'string', 'max:3000'],
        ]);
        foreach ($data['templates'] as $id => $values) {
            ReminderTemplate::query()->whereNull('company_id')->whereKey($id)->update($values);
        }
        ActivityLog::record('system.templates', 'تحديث القوالب الافتراضية');

        return back()->with('success', __('تم حفظ القوالب الافتراضية.'));
    }
}
