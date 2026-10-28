<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

/**
 * "Forgot password": email a one-time link, then let the user choose a new password.
 */
class PasswordResetController extends Controller
{
    public function request(): View
    {
        return view('auth.forgot-password');
    }

    public function email(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        Password::sendResetLink($request->only('email'));

        // Same answer whether or not the address exists, so accounts can't be discovered this way
        return back()->with('success', __('إذا كان البريد مسجلاً لدينا، فستصلك رسالة فيها رابط لتعيين كلمة مرور جديدة خلال دقائق.'));
    }

    public function edit(Request $request, string $token): View
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
                event(new PasswordReset($user));
                ActivityLog::record('password.reset', 'إعادة تعيين كلمة المرور', [], $user->company_id);
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success', __('تم تغيير كلمة المرور. سجّل دخولك الآن.'))
            : back()->withInput($request->only('email'))->withErrors(['email' => __('رابط إعادة التعيين غير صالح أو منتهي. اطلب رابطاً جديداً.')]);
    }
}
