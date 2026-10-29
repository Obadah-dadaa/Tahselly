<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Reminder;
use App\Models\ReminderTemplate;
use App\Services\ReminderService;
use App\Services\WhatsappCloud;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Reminders center: what goes out automatically, what is scheduled, what was sent,
 * and a simple form to send or schedule a reminder for one or many invoices.
 */
class ReminderController extends Controller
{
    public function index(Request $request, ReminderService $service): View
    {
        $company = $request->user()->company;
        $tab = in_array($request->tab, ['whatsapp', 'upcoming', 'scheduled', 'log'], true) ? $request->tab : 'upcoming';

        $reminders = Reminder::query()->with(['client', 'invoice', 'user'])
            ->where('status', '!=', 'scheduled')
            ->when($request->channel, fn ($q, $c) => $q->where('channel', $c))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->stage, fn ($q, $s) => $q->where('stage', $s))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $stats = Reminder::query()
            ->selectRaw('COUNT(*) as total, SUM(status = ?) as sent, SUM(status = ?) as failed, SUM(automatic = 1) as automatic', ['sent', 'failed'])
            ->whereDate('created_at', '>=', today()->subDays(30))
            ->where('status', '!=', 'scheduled')
            ->first();

        return view('reminders.index', [
            'tab' => $tab,
            'reminders' => $reminders,
            'stats' => $stats,
            'settings' => $company->reminderSettings(),
            'upcoming' => $service->upcoming($company, 14)->groupBy(fn (array $r) => $r['date']->toDateString()),
            'scheduled' => Reminder::query()->with(['client', 'invoice', 'user'])->where('status', 'scheduled')->orderBy('scheduled_for')->get(),
            'whatsappQueue' => $service->whatsappQueue($company),
            'whatsappOn' => in_array('whatsapp', $company->reminderSettings()['channels'], true) && $company->canUse('whatsapp'),
            'whatsappApi' => app(WhatsappCloud::class)->allowedFor($company),
        ]);
    }

    /**
     * Send the whole of today's WhatsApp list automatically (Cloud API).
     */
    public function sendAllWhatsapp(Request $request, ReminderService $service, WhatsappCloud $cloud): RedirectResponse
    {
        abort_unless($cloud->allowedFor($request->user()->company), 404);
        $result = $service->sendAllWhatsapp($request->user()->company, $request->user());
        ActivityLog::record('reminder.whatsapp', "إرسال واتساب للكل: {$result['sent']} مرسلة");

        $message = __('واتساب: :sent رسالة أُرسلت، :failed فشلت (التفاصيل في السجل).', $result);
        if ($result['skipped']) {
            $message .= ' '.__(':count تبقّت لأنك وصلت للحد اليومي، أرسلها غداً أو بالنقر من القائمة.', ['count' => $result['skipped']]);
        }

        return back()->with($result['failed'] || $result['skipped'] ? 'error' : 'success', $message);
    }

    /**
     * One item of today's WhatsApp list: log it as sent and open WhatsApp with the message ready.
     */
    public function sendWhatsapp(Request $request, Invoice $invoice, ReminderService $service): RedirectResponse
    {
        $url = $service->sendQueuedWhatsapp($invoice, $request->user());
        ReminderService::forgetWhatsappCount($invoice->company_id);
        if (! $url) {
            return back()->with('error', __('هذا التذكير أُرسل مسبقاً أو لم يعد مستحقاً اليوم.'));
        }
        ActivityLog::record('reminder.whatsapp', 'تذكير واتساب للفاتورة '.$invoice->number);

        return redirect()->away($url);
    }

    public function create(Request $request): View
    {
        $invoices = Invoice::query()->open()->with('client')->orderBy('due_date')->get();
        $template = ReminderTemplate::resolve($request->user()->company_id, 'overdue', 'email', $request->user()->company->reminderSettings()['locale'] ?? 'ar');
        $waTemplate = ReminderTemplate::resolve($request->user()->company_id, 'overdue', 'whatsapp', $request->user()->company->reminderSettings()['locale'] ?? 'ar');

        return view('reminders.create', [
            'invoices' => $invoices,
            'selected' => array_map('intval', (array) $request->input('invoices', [])),
            'emailTemplate' => $template,
            'whatsappTemplate' => $waTemplate,
            'placeholders' => ReminderTemplate::PLACEHOLDERS,
        ]);
    }

    public function store(Request $request, ReminderService $service): RedirectResponse|View
    {
        $data = $request->validate([
            'invoices' => ['required', 'array', 'min:1', 'max:200'],
            'invoices.*' => ['integer'],
            'channel' => ['required', Rule::in(['email', 'whatsapp'])],
            'subject' => ['nullable', 'string', 'max:190'],
            'body' => ['required', 'string', 'max:3000'],
            'when' => ['required', Rule::in(['now', 'later'])],
            'scheduled_for' => ['required_if:when,later', 'nullable', 'date', 'after:now'],
        ]);

        if ($data['channel'] === 'whatsapp' && ! $request->user()->company->canUse('whatsapp')) {
            return back()->withInput()->with('error', __('«:feature» متاحة من باقة :plan. قم بترقية باقتك لاستخدامها.', [
                'feature' => Plan::featureLabel('whatsapp'), 'plan' => Plan::minimumFor('whatsapp')?->localizedName(),
            ]));
        }

        // Tenant scope guarantees only this company's invoices are found
        $invoices = Invoice::query()->open()->with('client')->whereIn('id', $data['invoices'])->get();
        abort_if($invoices->isEmpty(), 422, __('لم يتم اختيار فواتير مستحقة.'));
        $user = $request->user();

        // The form is pre-filled with the "overdue" template. Sent unedited, each invoice should get
        // the template that fits its own state (before due / due today / overdue) instead.
        $prefill = ReminderTemplate::resolve($user->company_id, 'overdue', $data['channel'], $user->company->reminderSettings()['locale'] ?? 'ar');
        if ($prefill && $this->sameText($data['body'], $prefill->body) && $this->sameText((string) ($data['subject'] ?? ''), (string) $prefill->subject)) {
            $data['body'] = null;
            $data['subject'] = null;
        }

        if ($data['when'] === 'later') {
            $when = Carbon::parse($data['scheduled_for']);
            foreach ($invoices as $invoice) {
                $service->schedule($invoice, $data['channel'], $when, $user, $data['subject'] ?? null, $data['body'] ?? '');
            }
            ActivityLog::record('reminders.scheduled', __('جدولة :count تذكير', ['count' => $invoices->count()]));

            return redirect()->route('reminders.index', ['tab' => 'scheduled'])
                ->with('success', __('تمت جدولة :count تذكير في :date.', ['count' => $invoices->count(), 'date' => $when->format('Y-m-d H:i')]));
        }

        if ($data['channel'] === 'whatsapp') {
            // WhatsApp opens on the user's phone/desktop: hand back one link per invoice
            $links = $invoices->map(function (Invoice $invoice) use ($service, $user, $data) {
                $service->send($invoice, 'manual', 'whatsapp', null, false, $user, ['body' => $data['body']]);

                return ['invoice' => $invoice, 'url' => $service->whatsappLink($invoice, 'manual', $data['body'])];
            });
            ActivityLog::record('reminders.whatsapp', __('تجهيز :count رسالة واتساب', ['count' => $invoices->count()]));

            return view('reminders.whatsapp', ['links' => $links]);
        }

        $sent = $failed = 0;
        foreach ($invoices as $invoice) {
            $reminder = $service->send($invoice, 'manual', 'email', null, false, $user, ['subject' => $data['subject'] ?? null, 'body' => $data['body']]);
            $reminder->status === 'sent' ? $sent++ : $failed++;
        }
        ActivityLog::record('reminders.manual', __('إرسال :count تذكير يدوي', ['count' => $sent]));

        return redirect()->route('reminders.index', ['tab' => 'log'])
            ->with($failed ? 'error' : 'success', __('تم إرسال :sent تذكير', ['sent' => $sent]).($failed ? ' — '.__(':failed فشل (بريد العميل مفقود)', ['failed' => $failed]) : ''));
    }

    /**
     * Compare message texts ignoring line-ending and surrounding-space differences from the textarea.
     */
    private function sameText(string $a, string $b): bool
    {
        $normalize = fn (string $s) => trim(preg_replace('/\s+/u', ' ', $s));

        return $normalize($a) === $normalize($b);
    }

    public function cancel(Reminder $reminder): RedirectResponse
    {
        abort_unless($reminder->status === 'scheduled', 422);
        $reminder->update(['status' => 'cancelled']);

        return back()->with('success', __('تم إلغاء التذكير المجدول.'));
    }

    public function run(Request $request, ReminderService $service): RedirectResponse
    {
        $result = $service->runForCompany($request->user()->company);
        ActivityLog::record('reminders.run', "تشغيل التذكيرات يدوياً: {$result['sent']} مرسلة");

        return back()->with('success', __('تم تشغيل جدول التذكيرات: :sent مرسلة، :failed فاشلة.', $result));
    }

    public function togglePause(Invoice $invoice): RedirectResponse
    {
        $invoice->update(['reminders_paused' => ! $invoice->reminders_paused]);

        return back()->with('success', $invoice->reminders_paused ? __('تم إيقاف التذكيرات الآلية لهذه الفاتورة.') : __('تم تفعيل التذكيرات الآلية لهذه الفاتورة.'));
    }
}
