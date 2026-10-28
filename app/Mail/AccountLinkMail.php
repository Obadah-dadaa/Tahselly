<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Account emails that carry one action link: password reset or email confirmation.
 */
class AccountLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  'reset'|'verify'  $kind
     */
    public function __construct(public User $user, public string $kind, public string $url, public int $expiresInMinutes) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->kind === 'reset'
            ? __('إعادة تعيين كلمة المرور في تحصيل')
            : __('أكّد بريدك الإلكتروني في تحصيل'));
    }

    public function content(): Content
    {
        return new Content(view: 'mail.account-link');
    }
}
