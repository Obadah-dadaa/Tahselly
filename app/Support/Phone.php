<?php

namespace App\Support;

/**
 * International phone numbers from the sign-up and checkout forms: a dial code picked from a list plus the local
 * number. A number typed with + or 00 is taken as already international. Stored as +<digits> (E.164).
 */
class Phone
{
    /** dial code => [flag, Arabic name, English name]; the UAE first, then the Gulf, then the region */
    public const COUNTRIES = [
        '971' => ['🇦🇪', 'الإمارات', 'UAE'],
        '966' => ['🇸🇦', 'السعودية', 'Saudi Arabia'],
        '965' => ['🇰🇼', 'الكويت', 'Kuwait'],
        '974' => ['🇶🇦', 'قطر', 'Qatar'],
        '973' => ['🇧🇭', 'البحرين', 'Bahrain'],
        '968' => ['🇴🇲', 'عُمان', 'Oman'],
        '962' => ['🇯🇴', 'الأردن', 'Jordan'],
        '961' => ['🇱🇧', 'لبنان', 'Lebanon'],
        '963' => ['🇸🇾', 'سوريا', 'Syria'],
        '964' => ['🇮🇶', 'العراق', 'Iraq'],
        '20' => ['🇪🇬', 'مصر', 'Egypt'],
        '212' => ['🇲🇦', 'المغرب', 'Morocco'],
        '216' => ['🇹🇳', 'تونس', 'Tunisia'],
        '213' => ['🇩🇿', 'الجزائر', 'Algeria'],
        '249' => ['🇸🇩', 'السودان', 'Sudan'],
        '967' => ['🇾🇪', 'اليمن', 'Yemen'],
        '970' => ['🇵🇸', 'فلسطين', 'Palestine'],
        '90' => ['🇹🇷', 'تركيا', 'Turkey'],
        '91' => ['🇮🇳', 'الهند', 'India'],
        '92' => ['🇵🇰', 'باكستان', 'Pakistan'],
        '63' => ['🇵🇭', 'الفلبين', 'Philippines'],
        '44' => ['🇬🇧', 'بريطانيا', 'United Kingdom'],
        '1' => ['🇺🇸', 'أمريكا / كندا', 'USA / Canada'],
    ];

    /**
     * Combines the dial code and the number typed; null when it can't be a real phone number.
     */
    public static function normalize(?string $country, ?string $number): ?string
    {
        $number = trim((string) $number);
        $digits = preg_replace('/\D+/', '', $number);
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($number, '+') || str_starts_with($digits, '00')) {
            $full = ltrim(str_starts_with($digits, '00') ? substr($digits, 2) : $digits, '0');
        } else {
            $code = array_key_exists((string) $country, self::COUNTRIES) ? (string) $country : '971';
            $local = ltrim($digits, '0');
            // Someone may type the code again after picking it (9715... or 971 05...)
            if (str_starts_with($local, $code) && strlen($local) > strlen($code) + 7) {
                $local = substr($local, strlen($code));
            }
            $full = $code.ltrim($local, '0');
        }

        return strlen($full) >= 8 && strlen($full) <= 15 ? '+'.$full : null;
    }
}
