<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Tells the owner on Telegram when a new email lands in the business mailbox (sender and subject only).
 * Reads the inbox read-only over IMAP, so nothing is marked as read or changed.
 * Off unless MAILWATCH_PASSWORD is set and the PHP imap extension is loaded (it is on the server).
 */
class InboxWatcher
{
    private const CURSOR = 'mailwatch:last-uid';

    /** Our own senders: their mail (copies, system notices) is not news */
    private const IGNORE = ['noreply@tahselly.com', 'obadah@tahselly.com'];

    public function enabled(): bool
    {
        return extension_loaded('imap') && filled(config('services.mailwatch.password')) && Telegram::enabled();
    }

    /**
     * @return int how many new emails were announced
     */
    public function check(): int
    {
        if (! $this->enabled()) {
            return 0;
        }

        $box = '{'.config('services.mailwatch.host').':993/imap/ssl}INBOX';
        try {
            $imap = imap_open($box, config('services.mailwatch.username'), config('services.mailwatch.password'), OP_READONLY, 1);
        } catch (Throwable) {
            $imap = false;
        }
        if (! $imap) {
            Log::warning('Mail watch could not open the inbox', ['error' => imap_last_error()]);
            imap_errors();

            return 0;
        }

        $uids = imap_search($imap, 'ALL', SE_UID) ?: [];
        $newest = $uids ? max($uids) : 0;
        $last = Cache::get(self::CURSOR);

        // First run: start from now instead of announcing the whole inbox
        if ($last === null) {
            Cache::forever(self::CURSOR, $newest);
            imap_close($imap);

            return 0;
        }

        $announced = 0;
        foreach (array_filter($uids, fn (int $uid) => $uid > $last) as $uid) {
            $header = imap_fetch_overview($imap, (string) $uid, FT_UID)[0] ?? null;
            if (! $header) {
                continue;
            }
            $from = $this->decode($header->from ?? '');
            if ($this->isOurs($from)) {
                continue;
            }
            Telegram::notify('📬', 'إيميل جديد', [
                'من' => $from,
                'الموضوع' => $this->decode($header->subject ?? '') ?: '—',
            ]);
            $announced++;
        }
        Cache::forever(self::CURSOR, max($newest, (int) $last));
        imap_close($imap);

        return $announced;
    }

    private function decode(string $value): string
    {
        $text = function_exists('iconv_mime_decode') ? @iconv_mime_decode($value, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8') : $value;

        return trim((string) ($text ?: $value));
    }

    private function isOurs(string $from): bool
    {
        foreach (self::IGNORE as $address) {
            if (stripos($from, $address) !== false) {
                return true;
            }
        }

        return false;
    }
}
