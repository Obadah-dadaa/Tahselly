<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessageMail;
use App\Models\InterestRequest;
use App\Models\SystemSetting;
use App\Notifications\AccountNotice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * "Interested in subscribing?" form: the beta's replacement for plans and checkout.
 * The request lands in the developer inbox (bell + email) and in the support mailbox.
 */
class InterestController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $thanks = __('وصلنا طلبك، سنتواصل معك قريباً لتفعيل اشتراكك.');
        // Bots fill the hidden field; pretend it worked and keep nothing
        if (filled($request->input('website'))) {
            return back()->withFragment('interest')->with('success', $thanks);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'company_name' => ['nullable', 'string', 'max:190'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['required', 'string', 'max:30'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);
        $user = $request->user();
        $interest = InterestRequest::create($data + ['company_id' => $user?->company_id, 'user_id' => $user?->id]);

        AccountNotice::send(AccountNotice::staff(), 'interest_requested', [
            'name' => $interest->name,
            'company' => $interest->company_name ?: $user?->company?->name ?: '—',
            'email' => $interest->email,
            'phone' => $interest->phone,
        ], route('developer.interest.index'));
        try {
            Mail::to(SystemSetting::get('support_email'))->send(new ContactMessageMail([
                'name' => $interest->name,
                'email' => $interest->email,
                'company' => $interest->company_name ?: $user?->company?->name,
                'message' => "Subscription request\nPhone: {$interest->phone}\n\n".($interest->message ?: '—'),
            ]));
        } catch (Throwable $e) {
            report($e);
        }

        return back()->withFragment('interest')->with('success', $thanks);
    }
}
