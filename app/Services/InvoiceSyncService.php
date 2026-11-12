<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\SyncFeed;
use App\Models\SyncRun;
use App\Notifications\AccountNotice;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Applies a company's "open invoices" report from its own accounting system as a snapshot:
 * new invoices are added, known ones updated (a lower balance is a payment), and synced invoices
 * that are no longer in the report are closed as paid. Nothing has to be typed twice.
 */
class InvoiceSyncService
{
    public const EXTENSIONS = ['xlsx', 'xls', 'csv'];

    /** A report that would close this share of the synced open invoices at once is held back for the owner */
    public const CLOSE_GUARD = 0.8;

    /** ...once there are at least this many of them */
    public const GUARD_MIN = 5;

    public const REFERENCE = 'Accounting sync';

    public function __construct(private InvoiceImportService $importer) {}

    /**
     * Keep a received report and apply it; an emailed one from an unknown sender waits for the owner.
     */
    public function receive(SyncFeed $feed, string $filename, string $content, string $source, ?string $sender): SyncRun
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $path = 'sync/'.$feed->company_id.'/'.now()->format('Ymd-His').'-'.Str::lower(Str::random(6)).'.'.$extension;
        Storage::disk('local')->put($path, $content);

        if ($source === 'email' && ! $feed->allows($sender)) {
            return $this->finish($feed, SyncRun::query()->create([
                'company_id' => $feed->company_id, 'source' => $source, 'sender' => $sender, 'filename' => $filename, 'path' => $path,
                'status' => 'rejected', 'message' => __('وصل التقرير من :sender وهو ليس من المرسلين المسموحين. إذا كان منكم، اسمح به ليُطبّق التقرير.', ['sender' => $sender ?: '?']),
            ]));
        }

        return $this->apply($feed, $path, $filename, $source, $sender);
    }

    public function apply(SyncFeed $feed, string $path, string $filename, string $source, ?string $sender, bool $force = false): SyncRun
    {
        $company = $feed->company;
        $run = new SyncRun(['company_id' => $company->id, 'source' => $source, 'sender' => $sender, 'filename' => $filename, 'path' => $path, 'status' => 'failed', 'rows_total' => 0, 'added' => 0, 'updated' => 0, 'closed' => 0, 'failed' => 0]);
        $owner = $company->users()->where('role', 'owner')->orderBy('id')->first();

        try {
            $sheet = $this->importer->readSheet(Storage::disk('local')->path($path), $feed->mapping ?? []);
        } catch (Throwable) {
            $run->fill(['message' => __('تعذّرت قراءة الملف. أرسل التقرير بصيغة Excel (xlsx/xls) أو CSV.')])->save();

            return $this->finish($feed, $run);
        }
        $run->headers = $sheet['headers'];
        $map = $sheet['map'];
        if (! $owner || ! InvoiceImportService::hasRequiredColumns($map)) {
            $run->fill(['message' => __('لم نتعرف على كل الأعمدة المطلوبة (رقم الفاتورة، العميل، تاريخ الاستحقاق، المبلغ أو المتبقي). حدّدها مرة واحدة من صفحة الربط.')])->save();

            return $this->finish($feed, $run);
        }

        $rows = [];
        foreach ($sheet['rows'] as $index => $row) {
            if (array_filter($row, fn ($v) => $v !== null && $v !== '')) {
                $rows[$index] = $this->importer->extract($row, $map);
            }
        }
        $numbers = array_map('strval', array_filter(array_column($rows, 'number'), fn ($n) => $n !== null && $n !== ''));
        $synced = Invoice::withoutGlobalScopes()->where('company_id', $company->id)->open()->whereNotNull('synced_at')->get();
        $missing = $synced->reject(fn (Invoice $invoice) => in_array($invoice->number, $numbers, true));
        $run->rows_total = count($rows);

        // A broken or partial report must not mark everything as paid
        if (! $force && $synced->isNotEmpty() && ($rows === [] || ($synced->count() >= self::GUARD_MIN && $missing->count() >= $synced->count() * self::CLOSE_GUARD))) {
            $run->fill(['status' => 'rejected', 'message' => __('هذا التقرير سيغلق :count من أصل :open فاتورة مفتوحة دفعة واحدة، فأوقفناه احتياطاً. راجع الملف، ثم اضغط «طبّق على أي حال» إذا كان صحيحاً.', ['count' => $missing->count(), 'open' => $synced->count()])])->save();

            return $this->finish($feed, $run);
        }

        $room = $company->openInvoiceRoom();
        $errors = [];
        foreach ($rows as $index => $data) {
            // A report with only the balance still owed: keep the original amount so a lower balance reads as a payment
            if (! isset($map['amount']) && $data['number'] !== null && $data['number'] !== '') {
                $data['amount'] = Invoice::withoutGlobalScopes()->where('company_id', $company->id)->where('number', (string) $data['number'])->value('amount') ?? $data['balance'];
            }
            try {
                $this->importer->upsertRow($data, $owner, null, $room, self::REFERENCE, true) ? $run->added++ : $run->updated++;
            } catch (Throwable $e) {
                $errors[] = ['row' => $index + 1, 'message' => $e->getMessage()];
            }
        }

        // Gone from the open-invoices report: settled in the accounting system
        foreach ($missing as $invoice) {
            if ($invoice->balance() > 0) {
                $invoice->payments()->create([
                    'company_id' => $company->id, 'user_id' => $owner->id, 'amount' => $invoice->balance(),
                    'paid_on' => today(), 'method' => 'bank_transfer', 'reference' => self::REFERENCE,
                ]);
            }
            $invoice->refreshPaymentStatus();
            $run->closed++;
        }

        $run->fill(['status' => 'applied', 'failed' => count($errors), 'errors' => $errors ?: null])->save();
        $feed->update(['last_received_at' => now(), 'stale_alerted_at' => null]);
        ActivityLog::record('sync', "مزامنة نظام المحاسبة: {$run->added} جديدة، {$run->closed} أُغلقت ({$filename})", companyId: $company->id);

        return $this->finish($feed, $run);
    }

    /**
     * Tell the owners what an emailed report did (an upload shows its result on screen instead).
     */
    private function finish(SyncFeed $feed, SyncRun $run): SyncRun
    {
        if ($run->source !== 'email') {
            return $run;
        }
        $owners = AccountNotice::owners($feed->company);
        if ($run->status === 'applied') {
            if ($run->added || $run->closed || $run->failed) {
                AccountNotice::send($owners, 'sync_applied', ['added' => (string) $run->added, 'closed' => (string) $run->closed, 'failed' => (string) $run->failed], route('owner.sync'));
            }
        } else {
            AccountNotice::send($owners, 'sync_problem', ['company' => $feed->company->name, 'message' => (string) $run->message], route('owner.sync'));
        }

        return $run;
    }

    /**
     * Warn owners once when the daily report stopped arriving (the scheduled export on their side broke).
     *
     * @return int companies warned
     */
    public function alertStale(int $hours = 48): int
    {
        $warned = 0;
        SyncFeed::query()->with('company')->where('enabled', true)->whereNull('stale_alerted_at')
            ->where('last_received_at', '<', now()->subHours($hours))
            ->each(function (SyncFeed $feed) use (&$warned): void {
                AccountNotice::send(AccountNotice::owners($feed->company), 'sync_stale', ['company' => $feed->company->name, 'date' => $feed->last_received_at->format('Y-m-d')], route('owner.sync'));
                $feed->update(['stale_alerted_at' => now()]);
                $warned++;
            });

        return $warned;
    }
}
