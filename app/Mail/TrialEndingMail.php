<?php

namespace App\Mail;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells a company owner their free trial is about to end (or ends today) and what continuing costs.
 */
class TrialEndingMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Company $company) {}

    public function daysLeft(): int
    {
        return max(0, (int) today()->diffInDays($this->company->trial_ends_at, false));
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->daysLeft() === 0
            ? __('تنتهي تجربتك المجانية في تحصيلي اليوم')
            : __('تبقّى :days أيام على انتهاء تجربتك المجانية في تحصيلي', ['days' => $this->daysLeft()]));
    }

    public function content(): Content
    {
        return new Content(view: 'mail.trial-ending');
    }
}
