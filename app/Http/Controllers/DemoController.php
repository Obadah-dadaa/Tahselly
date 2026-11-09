<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Services\DemoAccount;
use App\Services\Telegram;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * "See it working" button on the landing page: logs the visitor into a fresh demo company.
 */
class DemoController extends Controller
{
    public function store(Request $request, DemoAccount $demo): RedirectResponse
    {
        $user = $demo->create(app()->getLocale());

        Auth::login($user);
        $request->session()->regenerate();

        Telegram::notify('👀', 'زائر فتح الديمو الفوري', [
            'اللغة' => app()->getLocale(),
            'جاء من' => parse_url((string) $request->headers->get('referer'), PHP_URL_QUERY) ?: '—',
        ]);

        return redirect()->route('home')->with('success', __('هذا حساب تجريبي فيه بيانات وهمية. جرّب كل شيء بحرية — لن تُرسل أي رسالة حقيقية.'));
    }

    /**
     * Leave the demo for the real sign-up form (or the request form when sign-ups are closed).
     */
    public function leave(Request $request): RedirectResponse
    {
        $locale = app()->getLocale();
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->put('locale', $locale); // the sign-up form opens in the language they were using

        return SystemSetting::get('allow_signups', true)
            ? redirect()->route('trial.create')
            : redirect()->to(route('landing').'#interest');
    }
}
