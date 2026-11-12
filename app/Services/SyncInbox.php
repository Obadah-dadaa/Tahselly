<?php

namespace App\Services;

use App\Models\SyncFeed;
use App\Models\SyncRun;
use App\Support\MimeMessage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Reads the shared sync mailbox and hands each company's emailed report to the sync service.
 * The mailbox is opened read-only with a UID cursor, so the live site and the beta can both read it:
 * each only acts on the private addresses (tokens) it knows. Off unless SYNC_INBOX_PASSWORD is set.
 */
class SyncInbox
{
    private const CURSOR = 'sync-inbox:last-uid';

    public function __construct(private InvoiceSyncService $sync) {}

    public function enabled(): bool
    {
        return extension_loaded('imap') && filled(config('services.sync_inbox.password'));
    }

    /**
     * @return int reports handled
     */
    public function check(): int
    {
        if (! $this->enabled()) {
            return 0;
        }

        $box = '{'.config('services.sync_inbox.host').':993/imap/ssl}INBOX';
        try {
            $imap = imap_open($box, config('services.sync_inbox.username'), config('services.sync_inbox.password'), OP_READONLY, 1);
        } catch (Throwable) {
            $imap = false;
        }
        if (! $imap) {
            Log::warning('Sync inbox could not be opened', ['error' => imap_last_error()]);
            imap_errors();

            return 0;
        }

        $uids = imap_search($imap, 'ALL', SE_UID) ?: [];
        $newest = $uids ? max($uids) : 0;
        $last = Cache::get(self::CURSOR);
        if ($last === null) {
            Cache::forever(self::CURSOR, $newest);
            imap_close($imap);

            return 0;
        }

        $handled = 0;
        foreach (array_filter($uids, fn (int $uid) => $uid > $last) as $uid) {
            try {
                $raw = imap_fetchheader($imap, $uid, FT_UID).imap_body($imap, $uid, FT_UID | FT_PEEK);
                $handled += count($this->handle($raw));
            } catch (Throwable $e) {
                report($e);
            }
            Cache::forever(self::CURSOR, $uid);
        }
        Cache::forever(self::CURSOR, max($newest, (int) $last));
        imap_close($imap);

        return $handled;
    }

    /**
     * Apply one raw email: find the company from the address it was sent to, then each attached report.
     *
     * @return list<SyncRun>
     */
    public function handle(string $raw): array
    {
        $message = MimeMessage::parse($raw);
        $feed = null;
        foreach ($message->addresses('to', 'cc', 'delivered-to', 'x-original-to', 'envelope-to', 'x-envelope-to') as $recipient) {
            $token = SyncFeed::tokenIn($recipient);
            if ($token && $feed = SyncFeed::query()->with('company')->where('token', $token)->where('enabled', true)->first()) {
                break;
            }
        }
        $sender = $message->from();
        // Sent to the mailbox's own address: the company whose allowed senders name this exact address
        if (! $feed && $sender && in_array(strtolower((string) config('services.sync_inbox.username')), $message->addresses('to', 'cc'), true)) {
            $matches = SyncFeed::query()->with('company')->where('enabled', true)->where('senders', 'like', '%'.$sender.'%')->get()
                ->filter(fn (SyncFeed $candidate) => in_array($sender, $candidate->senderList(), true));
            $feed = $matches->count() === 1 ? $matches->first() : null;
        }
        if (! $feed) {
            return []; // another edition's company, or not a report at all
        }

        $reports = array_filter($message->attachments(), fn (array $file) => in_array(strtolower(pathinfo($file['filename'], PATHINFO_EXTENSION)), InvoiceSyncService::EXTENSIONS, true));
        if (! $reports) {
            return [SyncRun::query()->create([
                'company_id' => $feed->company_id, 'source' => 'email', 'sender' => $sender, 'filename' => $message->subject() ?: '—',
                'status' => 'failed', 'message' => __('وصل إيميل بدون مرفق Excel أو CSV.'),
            ])];
        }

        return array_values(array_map(fn (array $file) => $this->sync->receive($feed, $file['filename'], $file['content'], 'email', $sender), $reports));
    }
}
