<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\ReminderTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(Request $request): View
    {
        $company = $request->user()->company;
        $templates = [];
        foreach (ReminderTemplate::LOCALES as $locale) {
            foreach (['before_due', 'on_due', 'overdue', 'final'] as $stage) {
                foreach (['email', 'whatsapp'] as $channel) {
                    $templates[$locale][$stage][$channel] = ReminderTemplate::resolve($company->id, $stage, $channel, $locale);
                }
            }
        }

        return view('owner.settings', [
            'company' => $company,
            'settings' => $company->reminderSettings(),
            'templates' => $templates,
            'placeholders' => ReminderTemplate::PLACEHOLDERS,
        ]);
    }

    public function updateCompany(Request $request): RedirectResponse
    {
        $request->user()->company->update($request->validate([
            'name' => ['required', 'string', 'max:190'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'trn' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'currency' => ['required', 'string', 'size:3'],
        ]));
        ActivityLog::record('settings.company', 'تحديث بيانات الشركة');

        return back()->with('success', __('تم حفظ بيانات الشركة.'));
    }

    public function updateReminders(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'before_days' => ['nullable', 'string', 'max:60'],
            'after_days' => ['nullable', 'string', 'max:60'],
            'final_after' => ['nullable', 'integer', 'min:0', 'max:365'],
            'channels' => ['array'],
            'channels.*' => ['in:email,whatsapp'],
            'locale' => ['required', 'in:ar,en'],
        ]);

        $company = $request->user()->company;
        $channels = $data['channels'] ?? ['email'];
        if (! $company->canUse('whatsapp')) {
            $channels = array_values(array_diff($channels, ['whatsapp'])) ?: ['email'];
        }
        // Without the flexible schedule the reminder days stay on the standard schedule
        $schedule = $company->canUse('flexible_schedule')
            ? ['before_days' => $this->days($data['before_days'] ?? ''), 'after_days' => $this->days($data['after_days'] ?? ''), 'final_after' => (int) ($data['final_after'] ?? 0)]
            : array_intersect_key(Company::DEFAULT_REMINDER_SETTINGS, array_flip(['before_days', 'after_days', 'final_after']));

        $company->update(['reminder_settings' => [
            'enabled' => $request->boolean('enabled'),
            'on_due' => $request->boolean('on_due'),
            ...$schedule,
            'channels' => $channels,
            'locale' => $data['locale'],
        ]]);
        ActivityLog::record('settings.reminders', 'تحديث جدول التذكيرات');

        return back()->with('success', __('تم حفظ جدول التذكيرات.'));
    }

    public function updateTemplates(Request $request): RedirectResponse
    {
        $companyId = $request->user()->company_id;
        $data = $request->validate([
            'templates' => ['required', 'array'],
            'templates.*.*.*.subject' => ['nullable', 'string', 'max:190'],
            'templates.*.*.*.body' => ['required', 'string', 'max:3000'],
        ]);

        foreach ($data['templates'] as $locale => $stages) {
            if (! in_array($locale, ReminderTemplate::LOCALES, true)) {
                continue;
            }
            foreach ($stages as $stage => $channels) {
                foreach ($channels as $channel => $template) {
                    if (! in_array($stage, ['before_due', 'on_due', 'overdue', 'final'], true) || ! in_array($channel, ['email', 'whatsapp'], true)) {
                        continue;
                    }
                    ReminderTemplate::updateOrCreate(
                        ['company_id' => $companyId, 'stage' => $stage, 'channel' => $channel, 'locale' => $locale],
                        ['subject' => $template['subject'] ?? null, 'body' => $template['body']],
                    );
                }
            }
        }
        ActivityLog::record('settings.templates', 'تحديث قوالب الرسائل');

        return back()->with('success', __('تم حفظ قوالب الرسائل.'));
    }

    /**
     * Parse "3, 7, 14" into a sorted unique list of positive ints.
     *
     * @return list<int>
     */
    private function days(string $value): array
    {
        $days = array_filter(array_map('intval', preg_split('/[\s,،]+/u', $value)), fn (int $d) => $d > 0 && $d <= 365);
        sort($days);

        return array_values(array_unique($days));
    }
}
