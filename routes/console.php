<?php

use App\Mail\TrialEndingMail;
use App\Models\Company;
use App\Models\Subscription;
use App\Services\ReminderService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schedule;

Artisan::command('reminders:send', function (ReminderService $reminders) {
    $result = $reminders->runAll();
    $this->info("Companies: {$result['companies']} | Sent: {$result['sent']} | Failed: {$result['failed']}");
})->purpose('Send automatic invoice payment reminders for all companies');

Artisan::command('subscriptions:expire', function () {
    $expired = Subscription::query()->where('status', 'active')->where('ends_at', '<=', now())->update(['status' => 'expired']);
    $companies = Company::query()->where('status', 'active')
        ->whereDoesntHave('subscriptions', fn ($q) => $q->current())
        ->update(['status' => 'expired']);
    $trials = Company::query()->where('status', 'trial')
        ->whereDate('trial_ends_at', '<', today())
        ->update(['status' => 'expired']);
    $this->info("Expired subscriptions: {$expired} | Companies locked: {$companies} | Trials ended: {$trials}");
})->purpose('Expire finished subscription periods and lock companies without a paid period');

Artisan::command('reminders:dispatch', function (ReminderService $reminders) {
    $this->info('Scheduled reminders sent: '.$reminders->dispatchScheduled());
})->purpose('Send manual reminders whose scheduled time has arrived');

Artisan::command('trials:notify', function () {
    // Warn owners 3 days before their free trial ends and on its last day
    $sent = 0;
    Company::query()->where('status', 'trial')
        ->where(fn ($q) => $q->whereDate('trial_ends_at', today()->addDays(3))->orWhereDate('trial_ends_at', today()))
        ->with(['users' => fn ($q) => $q->where('role', 'owner')->where('is_active', true)])
        ->each(function (Company $company) use (&$sent) {
            foreach ($company->users as $owner) {
                Mail::to($owner->email)->locale($owner->locale ?? 'ar')->send(new TrialEndingMail($company));
                $sent++;
            }
        });
    $this->info("Trial notices sent: {$sent}");
})->purpose('Email owners whose free trial ends in 3 days or today');

Schedule::command('subscriptions:expire')->hourly();
Schedule::command('trials:notify')->dailyAt('10:00')->timezone('Asia/Dubai');
Schedule::command('reminders:dispatch')->everyMinute();
Schedule::command('reminders:send')->dailyAt('09:00')->timezone('Asia/Dubai');
