<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function store(Request $request, Invoice $invoice): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.max($invoice->balance(), 0.01)],
            'paid_on' => ['required', 'date', 'before_or_equal:today'],
            'method' => ['required', Rule::in(array_keys(Payment::METHODS))],
            'reference' => ['nullable', 'string', 'max:120'],
        ]);

        $invoice->payments()->create($data + ['company_id' => $invoice->company_id, 'user_id' => $request->user()->id]);
        $invoice->refreshPaymentStatus();
        ActivityLog::record('payment.created', 'تسجيل دفعة '.number_format((float) $data['amount'], 2).' للفاتورة '.$invoice->number);

        return back()->with('success', $invoice->status === 'paid' ? 'تم سداد الفاتورة بالكامل.' : 'تم تسجيل الدفعة.');
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        $invoice = $payment->invoice;
        $payment->delete();
        $invoice->refreshPaymentStatus();
        ActivityLog::record('payment.deleted', 'حذف دفعة من الفاتورة '.$invoice->number);

        return back()->with('success', __('تم حذف الدفعة.'));
    }
}
