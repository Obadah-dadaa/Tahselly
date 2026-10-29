<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The bell in the top bar: open a notice (marking it read) or clear them all.
 */
class NotificationController extends Controller
{
    public function open(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();
        $url = (string) ($notification->data['url'] ?? '');

        // Only follow links back into this site
        return str_starts_with($url, url('/')) ? redirect()->to($url) : redirect()->route('home');
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}
