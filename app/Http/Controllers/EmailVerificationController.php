<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * New sign-ups confirm their email before using the app.
 */
class EmailVerificationController extends Controller
{
    public function notice(Request $request): View|RedirectResponse
    {
        return $request->user()->hasVerifiedEmail()
            ? redirect()->route('home')
            : view('auth.verify-email');
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        if (! $request->user()->hasVerifiedEmail()) {
            $request->fulfill();
            ActivityLog::record('email.verified', 'تأكيد البريد الإلكتروني');
        }

        return redirect()->route('home')->with('success', __('تم تأكيد بريدك الإلكتروني. أهلاً بك!'));
    }

    public function send(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('home');
        }
        $request->user()->sendEmailVerificationNotification();

        return back()->with('success', __('أرسلنا رابط تأكيد جديداً إلى :email.', ['email' => $request->user()->email]));
    }
}
