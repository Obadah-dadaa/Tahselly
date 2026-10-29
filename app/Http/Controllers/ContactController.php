<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessageMail;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

/**
 * Public contact page: the founder's details and a short form that emails the support inbox.
 */
class ContactController extends Controller
{
    public function show(): View
    {
        return view('contact', [
            'email' => SystemSetting::get('support_email'),
            'whatsapp' => preg_replace('/\D+/', '', (string) SystemSetting::get('support_whatsapp')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Bots fill the hidden field; pretend it worked and send nothing
        if (filled($request->input('website'))) {
            return back()->with('success', __('وصلتنا رسالتك، سنرد عليك قريباً.'));
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'company' => ['nullable', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
        ]);

        try {
            Mail::to(SystemSetting::get('support_email'))->send(new ContactMessageMail($data));
        } catch (Throwable $e) {
            report($e);

            return back()->withInput()->with('error', __('تعذّر إرسال رسالتك الآن. راسلنا مباشرة على البريد أو واتساب.'));
        }

        return back()->with('success', __('وصلتنا رسالتك، سنرد عليك قريباً.'));
    }
}
