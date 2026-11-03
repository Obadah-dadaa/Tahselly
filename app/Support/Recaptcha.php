<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Google reCAPTCHA v3: the page gets a token in the background, the server asks Google
 * how human the visitor looked (score 0.0 bot … 1.0 human) and for which form it was made.
 */
class Recaptcha
{
    public static function enabled(): bool
    {
        return filled(config('services.recaptcha.site_key')) && filled(config('services.recaptcha.secret_key'));
    }

    public static function passes(?string $token, string $action, ?string $ip): bool
    {
        if (blank($token)) {
            return false;
        }

        try {
            $result = Http::asForm()->timeout(5)->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => config('services.recaptcha.secret_key'),
                'response' => $token,
                'remoteip' => $ip,
            ])->json();
        } catch (\Throwable $e) {
            // Google unreachable: don't lock real customers out; the routes stay rate-limited
            Log::warning('reCAPTCHA check skipped: '.$e->getMessage());

            return true;
        }

        return ($result['success'] ?? false) === true
            && ($result['action'] ?? null) === $action
            && (float) ($result['score'] ?? 0) >= (float) config('services.recaptcha.min_score', 0.5);
    }
}
