<?php

namespace App\Support;

use App\Models\SystemSetting;

/**
 * Click-to-chat link to Tahselly's own WhatsApp (the "support_whatsapp" setting), with an optional first message.
 */
class SupportWhatsapp
{
    public static function url(?string $text = null): ?string
    {
        $number = preg_replace('/\D+/', '', (string) SystemSetting::get('support_whatsapp'));
        if (! $number) {
            return null;
        }

        return 'https://wa.me/'.$number.($text ? '?text='.rawurlencode($text) : '');
    }
}
