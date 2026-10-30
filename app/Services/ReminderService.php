<?php

namespace App\Services;

use App\Mail\InvoiceReminderMail;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Reminder;
use App\Models\ReminderTemplate;
use App\Models\SystemSetting;
use App\Models\User;
use App\Support\Edition;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ReminderService
{
    /**
     * Run the automatic schedule for every company that has access, then deliver due scheduled reminders.
     *
     * @return array{companies: int, sent: int, failed: int}
     */
    public function runAll(): array
    {
        $totals = ['companies' => 0, 'sent' => 0, 'failed' => 0];

        if (! SystemSetting::get('reminders_engine_enabled', true)) {
            return $totals;
        }

        Company::query()->whereIn('status', ['active', 'trial'])->each(function (Company $company) use (&$totals): void {
            $result = $this->runForCompany($company);
            $totals['companies']++;
            $totals['sent'] += $result['sent'];
            $totals['failed'] += $result['failed'];
        });

        return $totals;
    }

    /**
     * @return array{sent: int, failed: int}
     */
    public function runForCompany(Company $company): array
    {
        $settings = $company->reminderSettings();
        $result = ['sent' => 0, 'failed' => 0];

        if (! $settings['enabled']) {
            return $result;
        }

        $this->eligibleInvoices($company)->each(function (Invoice $invoice) use ($settings, &$result): void {
            $due = $this->dueStage($invoice, $settings);
            if (! $due || ! in_array('email', $settings['channels'], true)) {
                return; // WhatsApp is sent from the invoice page via wa.me
            }
            $reminder = $this->send($invoice, $due['stage'], 'email', $due['offset'], true);
            $reminder->status === 'failed' ? $result['failed']++ : $result['sent']++;
        });

        return $result;
    }

    /**
     * Open invoices that automatic reminders apply to (client enabled, invoice not paused).
     *
     * @return Collection<int, Invoice>
     */
    private function eligibleInvoices(Company $company): Collection
    {
        return Invoice::withoutGlobalScopes()
            ->with(['client', 'company'])
            ->where('company_id', $company->id)
            ->where('reminders_paused', false)
            ->open()
            ->whereHas('client', fn ($q) => $q->withoutGlobalScopes()->where('reminders_enabled', true))
            ->get();
    }

    /**
     * Decide which stage (if any) is due today for an invoice, skipping stages already sent.
     *
     * @param  array{before_days: list<int>, on_due: bool, after_days: list<int>, final_after: int}  $settings
     * @return array{stage: string, offset: int}|null
     */
    public function dueStage(Invoice $invoice, array $settings, string $channel = 'email'): ?array
    {
        $offset = (int) $invoice->due_date->startOfDay()->diffInDays(today(), false);

        $candidate = null;
        if ($offset < 0) {
            $daysUntil = -$offset;
            $matching = array_filter($settings['before_days'], fn (int $d) => $d >= $daysUntil);
            if ($matching) {
                $candidate = ['stage' => 'before_due', 'offset' => -min($matching)];
            }
        } elseif ($offset === 0 && $settings['on_due']) {
            $candidate = ['stage' => 'on_due', 'offset' => 0];
        } elseif ($offset > 0) {
            if ($settings['final_after'] > 0 && $offset >= $settings['final_after']) {
                $candidate = ['stage' => 'final', 'offset' => (int) $settings['final_after']];
            } else {
                $matching = array_filter($settings['after_days'], fn (int $d) => $d <= $offset);
                if ($matching) {
                    $candidate = ['stage' => 'overdue', 'offset' => max($matching)];
                }
            }
        }

        if (! $candidate || $this->alreadySent($invoice, $candidate['stage'], $candidate['offset'], $channel)) {
            return null;
        }

        return $candidate;
    }

    private function alreadySent(Invoice $invoice, string $stage, int $offset, string $channel = 'email'): bool
    {
        return Reminder::withoutGlobalScopes()
            ->where('invoice_id', $invoice->id)
            ->where('channel', $channel)
            ->where('stage', $stage)
            ->where('day_offset', $offset)
            ->where('automatic', true)
            ->where('status', 'sent')
            ->exists();
    }

    /**
     * Today's WhatsApp reminders: the same schedule as email, prepared as one-click messages
     * that the team sends from the company's own WhatsApp.
     *
     * @return Collection<int, array{invoice: Invoice, stage: string, offset: int, url: string}>
     */
    public function whatsappQueue(Company $company): Collection
    {
        $settings = $company->reminderSettings();
        if (! $settings['enabled'] || ! in_array('whatsapp', $settings['channels'], true) || ! $company->canUse('whatsapp')) {
            return collect();
        }

        return $this->eligibleInvoices($company)
            ->map(function (Invoice $invoice) use ($settings): ?array {
                $due = $this->dueStage($invoice, $settings, 'whatsapp');
                $url = $due ? $this->whatsappLink($invoice, $due['stage']) : null;

                return $url ? ['invoice' => $invoice] + $due + ['url' => $url] : null;
            })
            ->filter()
            ->sortByDesc(fn (array $row) => $row['offset'])
            ->values();
    }

    /**
     * How many WhatsApp reminders are waiting today (cached briefly; shown in the sidebar and dashboard).
     */
    public static function whatsappCount(Company $company): int
    {
        return (int) Cache::remember('wa-queue:'.$company->id.':'.today()->toDateString(), now()->addMinutes(10),
            fn () => app(self::class)->whatsappQueue($company)->count());
    }

    public static function forgetWhatsappCount(int $companyId): void
    {
        Cache::forget('wa-queue:'.$companyId.':'.today()->toDateString());
    }

    /**
     * Log a WhatsApp reminder from today's list as sent (the user sends it from WhatsApp) and return its link.
     */
    public function sendQueuedWhatsapp(Invoice $invoice, User $user): ?string
    {
        $due = $this->dueStage($invoice, $invoice->company->reminderSettings(), 'whatsapp');
        if (! $due) {
            return null;
        }
        $this->send($invoice, $due['stage'], 'whatsapp', $due['offset'], true, $user);

        return $this->whatsappLink($invoice, $due['stage']);
    }

    /**
     * Send every WhatsApp reminder on today's list through the Cloud API in one go.
     *
     * @return array{sent: int, failed: int, skipped: int}
     */
    public function sendAllWhatsapp(Company $company, User $user): array
    {
        $result = ['sent' => 0, 'failed' => 0, 'skipped' => 0];
        $cloud = app(WhatsappCloud::class);
        // One run per company at a time, so a double click can't message a client twice
        $lock = Cache::lock('wa-send-all:'.$company->id, (int) config('services.whatsapp.daily_limit') * 20 + 60);
        if (! $cloud->allowedFor($company) || ! $lock->get()) {
            return $result;
        }

        try {
            $queue = $this->whatsappQueue($company);
            $batch = $queue->take($cloud->remainingToday($company));
            $result['skipped'] = $queue->count() - $batch->count();
            foreach ($batch as $row) {
                if (! $this->dueStage($row['invoice'], $company->reminderSettings(), 'whatsapp')) {
                    continue; // sent meanwhile from the click list
                }
                $reminder = $this->send($row['invoice'], $row['stage'], 'whatsapp', $row['offset'], true, $user, viaApi: true);
                $reminder->status === 'sent' ? $result['sent']++ : $result['failed']++;
            }
        } finally {
            $lock->release();
            self::forgetWhatsappCount($company->id);
        }

        return $result;
    }

    /**
     * The automatic reminders the engine will send over the next days, so users can see them in advance.
     *
     * @return Collection<int, array{date: CarbonInterface, invoice: Invoice, stage: string, offset: int}>
     */
    public function upcoming(Company $company, int $days = 14): Collection
    {
        $settings = $company->reminderSettings();
        if (! $settings['enabled'] || ! in_array('email', $settings['channels'], true)) {
            return collect();
        }

        $points = [];
        foreach ($settings['before_days'] as $d) {
            $points[] = ['stage' => 'before_due', 'offset' => -$d];
        }
        if ($settings['on_due']) {
            $points[] = ['stage' => 'on_due', 'offset' => 0];
        }
        foreach ($settings['after_days'] as $d) {
            if (! $settings['final_after'] || $d < $settings['final_after']) {
                $points[] = ['stage' => 'overdue', 'offset' => $d];
            }
        }
        if ($settings['final_after'] > 0) {
            $points[] = ['stage' => 'final', 'offset' => (int) $settings['final_after']];
        }

        $until = today()->addDays($days);
        $rows = collect();
        foreach ($this->eligibleInvoices($company) as $invoice) {
            $today = $this->dueStage($invoice, $settings);
            if ($today) {
                $rows->push(['date' => today(), 'invoice' => $invoice] + $today);
            }
            foreach ($points as $point) {
                $date = $invoice->due_date->copy()->startOfDay()->addDays($point['offset']);
                if ($date->gt(today()) && $date->lte($until) && ! $this->alreadySent($invoice, $point['stage'], $point['offset'])) {
                    $rows->push(['date' => $date, 'invoice' => $invoice] + $point);
                }
            }
        }

        return $rows->sortBy(fn (array $r) => $r['date']->timestamp)->values();
    }

    /**
     * Render, deliver and log a reminder. A custom subject/body (with placeholders) overrides the template.
     *
     * @param  array{subject?: string|null, body?: string|null}|null  $custom
     */
    public function send(Invoice $invoice, string $stage, string $channel, ?int $offset = null, bool $automatic = false, ?User $user = null, ?array $custom = null, bool $viaApi = false): Reminder
    {
        $reminder = new Reminder([
            'company_id' => $invoice->company_id,
            'invoice_id' => $invoice->id,
            'client_id' => $invoice->client_id,
            'user_id' => $user?->id,
            'channel' => $channel,
            'stage' => $automatic ? $stage : 'manual',
            'day_offset' => $offset,
            'automatic' => $automatic,
        ]);

        return $this->deliver($reminder, $invoice, $custom, $viaApi);
    }

    /**
     * Queue a manual reminder for a later moment; its text is rendered when it goes out.
     */
    public function schedule(Invoice $invoice, string $channel, CarbonInterface $when, User $user, ?string $subject, string $body = ''): Reminder
    {
        return Reminder::withoutGlobalScopes()->create([
            'company_id' => $invoice->company_id,
            'invoice_id' => $invoice->id,
            'client_id' => $invoice->client_id,
            'user_id' => $user->id,
            'channel' => $channel,
            'stage' => 'manual',
            'status' => 'scheduled',
            'scheduled_for' => $when,
            'automatic' => false,
            'subject' => $subject,
            'body' => $body,
        ]);
    }

    /**
     * Deliver scheduled reminders whose time has come.
     */
    public function dispatchScheduled(): int
    {
        $count = 0;
        Reminder::withoutGlobalScopes()
            ->with(['invoice' => fn ($q) => $q->withoutGlobalScopes()->with(['client' => fn ($c) => $c->withoutGlobalScopes(), 'company'])])
            ->where('status', 'scheduled')
            ->where('scheduled_for', '<=', now())
            ->each(function (Reminder $reminder) use (&$count): void {
                $invoice = $reminder->invoice;
                if (! $invoice || ! in_array($invoice->status, ['unpaid', 'partial'], true)) {
                    $reminder->update(['status' => 'cancelled', 'error' => __('أُلغي: الفاتورة لم تعد مستحقة')]);

                    return;
                }
                $this->deliver($reminder, $invoice, ['subject' => $reminder->subject, 'body' => $reminder->body]);
                $count++;
            });

        return $count;
    }

    /**
     * @param  array{subject?: string|null, body?: string|null}|null  $custom
     */
    private function deliver(Reminder $reminder, Invoice $invoice, ?array $custom, bool $viaApi = false): Reminder
    {
        $invoice->loadMissing(['client', 'company']);
        $stage = $reminder->stage;
        $channel = $reminder->channel;
        $locale = $invoice->client->messageLocale();

        if ($custom && filled($custom['body'] ?? null)) {
            $content = (new ReminderTemplate(['subject' => $custom['subject'] ?? null, 'body' => $custom['body']]))->render($invoice);
            if (blank($content['subject'])) {
                $content['subject'] = __('تذكير بالفاتورة :number', ['number' => $invoice->number], $locale);
            }
        } else {
            $template = ReminderTemplate::resolve($invoice->company_id, $this->templateStage($invoice, $stage), $channel, $locale);
            $content = $template?->render($invoice) ?? [
                'subject' => __('تذكير بالفاتورة :number', ['number' => $invoice->number], $locale),
                'body' => __('نود تذكيركم بفاتورة رقم :number بمبلغ :amount', ['number' => $invoice->number, 'amount' => number_format($invoice->balance(), 2).' '.$invoice->currency], $locale),
            ];
        }

        $recipient = $channel === 'email' ? $invoice->client->email : $invoice->client->whatsappNumber();
        $status = 'sent';
        $error = null;

        if (Edition::isBeta() && $this->sentToday($invoice->company_id) >= Edition::limit('daily_reminders')) {
            $status = 'failed';
            $error = __('وصلت للحد اليومي للتذكيرات في النسخة التجريبية (:limit). يُستأنف الإرسال غداً.', ['limit' => Edition::limit('daily_reminders')]);
        } elseif (! $recipient) {
            $status = 'failed';
            $error = $channel === 'email' ? __('لا يوجد بريد إلكتروني للعميل') : __('لا يوجد رقم هاتف للعميل');
        } elseif ($channel === 'email') {
            try {
                Mail::to($recipient)->locale($locale)->send(new InvoiceReminderMail($invoice, $content['subject'], $content['body']));
            } catch (Throwable $e) {
                $status = 'failed';
                $error = $e->getMessage();
            }
        } elseif ($viaApi) {
            try {
                app(WhatsappCloud::class)->sendReminder($invoice, $this->templateStage($invoice, $stage), $locale, $recipient);
            } catch (Throwable $e) {
                $status = 'failed';
                $error = $e->getMessage();
            }
        }

        $reminder->forceFill([
            'status' => $status,
            'recipient' => $recipient,
            'subject' => $content['subject'],
            'body' => $content['body'],
            'error' => $error,
        ]);
        $reminder->save();

        if ($status === 'sent') {
            $invoice->forceFill([
                'last_reminded_at' => now(),
                'reminders_count' => $invoice->reminders_count + 1,
            ])->saveQuietly();
        }

        return $reminder;
    }

    private function sentToday(int $companyId): int
    {
        return Reminder::withoutGlobalScopes()->where('company_id', $companyId)->where('status', 'sent')->whereDate('created_at', today())->count();
    }

    /**
     * Build a WhatsApp click-to-chat link with the rendered message (or a custom one).
     */
    public function whatsappLink(Invoice $invoice, string $stage = 'manual', ?string $customBody = null): ?string
    {
        $invoice->loadMissing(['client', 'company']);
        $number = $invoice->client->whatsappNumber();
        if (! $number) {
            return null;
        }
        $text = $customBody
            ? (new ReminderTemplate(['body' => $customBody]))->render($invoice)['body']
            : (ReminderTemplate::resolve($invoice->company_id, $this->templateStage($invoice, $stage), 'whatsapp', $invoice->client->messageLocale())?->render($invoice)['body']
                ?? __('تذكير بالفاتورة :number', ['number' => $invoice->number], $invoice->client->messageLocale()));

        return 'https://wa.me/'.$number.'?text='.rawurlencode($text);
    }

    /**
     * Manual reminders use the template that matches the invoice today: before due, due today, or overdue.
     */
    private function templateStage(Invoice $invoice, string $stage): string
    {
        if ($stage !== 'manual') {
            return $stage;
        }
        $offset = (int) $invoice->due_date->startOfDay()->diffInDays(today(), false);

        return match (true) {
            $offset < 0 => 'before_due',
            $offset === 0 => 'on_due',
            default => 'overdue',
        };
    }
}
