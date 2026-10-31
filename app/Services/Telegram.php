<?php

namespace App\Services;

use App\Support\Edition;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Posts platform events (sign-ups, subscription requests, payments, messages, the daily summary)
 * to the owner's Telegram channel. Off while TELEGRAM_BOT_TOKEN / TELEGRAM_CHAT_ID are empty,
 * and never lets a Telegram problem break the action that triggered it.
 */
class Telegram
{
    public static function enabled(): bool
    {
        return filled(config('services.telegram.bot_token')) && filled(config('services.telegram.chat_id'));
    }

    /**
     * @param  array<string, string|int|float|null>  $lines  label => value, shown under the title
     */
    public static function notify(string $icon, string $title, array $lines = [], ?string $url = null): void
    {
        if (! self::enabled()) {
            return;
        }

        $site = Edition::isBeta() ? '🧪 BETA' : '🟢 LIVE';
        $text = "{$icon} <b>".e($title)."</b>  ·  {$site}";
        foreach ($lines as $label => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $text .= is_int($label) || $label === ''
                ? "\n".e((string) $value)
                : "\n<b>".e($label).':</b> '.e((string) $value);
        }
        if ($url) {
            $text .= "\n<a href=\"".e($url).'">'.e(__('فتح في تحصيلي')).'</a>';
        }

        try {
            Http::timeout(6)->asForm()->post('https://api.telegram.org/bot'.config('services.telegram.bot_token').'/sendMessage', [
                'chat_id' => config('services.telegram.chat_id'),
                'text' => $text,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => 'true',
            ])->throw();
        } catch (Throwable $e) {
            report($e);
        }
    }
}
