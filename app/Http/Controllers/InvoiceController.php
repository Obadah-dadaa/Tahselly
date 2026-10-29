<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\ReminderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->input('status');

        $invoices = Invoice::query()->with('client')
            ->when($request->q, fn ($q, $term) => $q->where(fn ($w) => $w->where('number', 'like', "%{$term}%")
                ->orWhereHas('client', fn ($c) => $c->where('name', 'like', "%{$term}%"))))
            ->when($request->client_id, fn ($q, $id) => $q->where('client_id', $id))
            ->when($status === 'overdue', fn ($q) => $q->overdue())
            ->when($status === 'open', fn ($q) => $q->open())
            ->when(in_array($status, ['paid', 'partial', 'unpaid', 'cancelled'], true), fn ($q) => $q->where('status', $status))
            ->when($request->from, fn ($q, $from) => $q->whereDate('due_date', '>=', $from))
            ->when($request->to, fn ($q, $to) => $q->whereDate('due_date', '<=', $to))
            ->orderBy($request->input('sort') === 'amount' ? 'amount' : 'due_date', $request->input('dir') === 'desc' ? 'desc' : 'asc')
            ->paginate(20)
            ->withQueryString();

        return view('invoices.index', [
            'invoices' => $invoices,
            'clients' => Client::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(Request $request): View
    {
        return view('invoices.form', [
            'invoice' => new Invoice([
                'client_id' => $request->client_id,
                'issue_date' => today(),
                'due_date' => today()->addDays(30),
                'currency' => $request->user()->company?->currency ?? 'AED',
            ]),
            'clients' => Client::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        if ($request->user()->company->openInvoiceRoom() === 0) {
            return back()->withInput()->with('error', __('وصلت للحد الأقصى للفواتير المفتوحة في باقتك. سجّل الدفعات أو قم بترقية باقتك لإضافة المزيد.'));
        }
        $invoice = Invoice::create($data + ['status' => 'unpaid']);
        ActivityLog::record('invoice.created', 'إنشاء فاتورة '.$invoice->number);

        return redirect()->route('invoices.show', $invoice)->with('success', __('تم إنشاء الفاتورة.'));
    }

    public function show(Invoice $invoice, ReminderService $reminders): View
    {
        $invoice->load(['client', 'payments.user', 'reminders.user']);

        return view('invoices.show', [
            'invoice' => $invoice,
            'whatsappLink' => $reminders->whatsappLink($invoice),
            'methods' => Payment::METHODS,
        ]);
    }

    public function edit(Invoice $invoice): View
    {
        return view('invoices.form', [
            'invoice' => $invoice,
            'clients' => Client::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        $invoice->update($this->validated($request, $invoice));
        $invoice->refreshPaymentStatus();
        ActivityLog::record('invoice.updated', 'تعديل فاتورة '.$invoice->number);

        return redirect()->route('invoices.show', $invoice)->with('success', __('تم حفظ التعديلات.'));
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        ActivityLog::record('invoice.deleted', 'حذف فاتورة '.$invoice->number);
        $invoice->delete();

        return redirect()->route('invoices.index')->with('success', __('تم حذف الفاتورة.'));
    }

    public function cancel(Invoice $invoice): RedirectResponse
    {
        $invoice->update(['status' => 'cancelled']);
        ActivityLog::record('invoice.cancelled', 'إلغاء فاتورة '.$invoice->number);

        return back()->with('success', __('تم إلغاء الفاتورة وإيقاف تذكيراتها.'));
    }

    public function remind(Request $request, Invoice $invoice, ReminderService $reminders): RedirectResponse
    {
        $reminder = $reminders->send($invoice, 'manual', 'email', null, false, $request->user());
        ActivityLog::record('reminder.manual', 'إرسال تذكير يدوي للفاتورة '.$invoice->number);

        return $reminder->status === 'sent'
            ? back()->with('success', __('تم إرسال التذكير إلى ').$reminder->recipient)
            : back()->with('error', __('فشل الإرسال: ').$reminder->error);
    }

    public function whatsapp(Request $request, Invoice $invoice, ReminderService $reminders): RedirectResponse
    {
        $link = $reminders->whatsappLink($invoice);
        if (! $link) {
            return back()->with('error', __('لا يوجد رقم هاتف للعميل.'));
        }
        $reminder = $reminders->send($invoice, 'manual', 'whatsapp', null, false, $request->user());
        ActivityLog::record('reminder.whatsapp', 'تذكير واتساب للفاتورة '.$invoice->number, ['reminder_id' => $reminder->id]);

        return redirect()->away($link);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Invoice $invoice = null): array
    {
        return $request->validate([
            'client_id' => ['required', Rule::exists('clients', 'id')->where('company_id', $request->user()->company_id)],
            'number' => ['required', 'string', 'max:60', Rule::unique('invoices')->where('company_id', $request->user()->company_id)->ignore($invoice?->id)],
            'issue_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:issue_date'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'currency' => ['required', 'string', 'size:3'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
