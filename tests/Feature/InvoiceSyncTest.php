<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\SyncFeed;
use App\Models\SyncRun;
use App\Models\User;
use App\Services\SyncInbox;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Daily sync with the company's accounting system through its emailed "open invoices" report.
 */
class InvoiceSyncTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private SyncFeed $feed;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlatformSeeder::class);
        Storage::fake('local');
        $this->owner = User::factory()->create(['company_id' => Company::factory()->create(['status' => 'trial'])->id, 'role' => Role::Owner, 'email' => 'owner@acme.ae', 'email_verified_at' => now()]);
        $this->feed = SyncFeed::for($this->owner->company);
    }

    /** A raw email with a CSV report attached, base64 encoded like a real mail client does */
    private function email(string $csv, string $from = 'erp@acme.ae', ?string $to = null, string $filename = 'open-invoices.csv'): string
    {
        $to ??= $this->feed->address();
        $body = chunk_split(base64_encode($csv), 76, "\r\n");

        return "From: \"ACME ERP\" <{$from}>\r\nTo: {$to}\r\nSubject: =?UTF-8?B?".base64_encode('تقرير الفواتير')."?=\r\nMIME-Version: 1.0\r\n"
            ."Content-Type: multipart/mixed;\r\n boundary=\"XYZ\"\r\n\r\npreamble\r\n--XYZ\r\nContent-Type: text/plain; charset=utf-8\r\n\r\nDaily report\r\n"
            ."--XYZ\r\nContent-Type: text/csv; name=\"{$filename}\"\r\nContent-Disposition: attachment; filename=\"{$filename}\"\r\nContent-Transfer-Encoding: base64\r\n\r\n{$body}--XYZ--\r\n";
    }

    private function report(array $rows): string
    {
        $lines = ['Invoice No,Customer Name,Due Date,Total,Balance Due'];
        foreach ($rows as $row) {
            $lines[] = implode(',', $row);
        }

        return implode("\n", $lines)."\n";
    }

    private function invoice(string $number): Invoice
    {
        return Invoice::withoutGlobalScopes()->where('number', $number)->firstOrFail();
    }

    public function test_the_daily_report_adds_new_invoices_records_payments_and_closes_settled_ones(): void
    {
        $this->feed->allow('erp@acme.ae');
        $inbox = app(SyncInbox::class);

        $runs = $inbox->handle($this->email($this->report([
            ['S-1', 'Gulf Events LLC', '2026-10-01', 5000, 5000],
            ['S-2', 'Gulf Events LLC', '2026-12-01', 1200, 1200],
            ['S-3', 'Blue Wave', '2026-11-20', 800, 800],
        ])));
        $this->assertSame([3, 0, 0], [$runs[0]->added, $runs[0]->updated, $runs[0]->closed]);
        $this->assertSame('unpaid', $this->invoice('S-1')->status);
        $this->assertNotNull($this->invoice('S-1')->synced_at);

        // Next day: S-1 partly paid, S-2 settled (gone), S-4 new
        $runs = $inbox->handle($this->email($this->report([
            ['S-1', 'Gulf Events LLC', '2026-10-01', 5000, 3000],
            ['S-3', 'Blue Wave', '2026-11-20', 800, 800],
            ['S-4', 'Blue Wave', '2026-12-15', 650, 650],
        ])));
        $this->assertSame([1, 2, 1], [$runs[0]->added, $runs[0]->updated, $runs[0]->closed]);
        $this->assertSame('partial', $this->invoice('S-1')->status);
        $this->assertEquals(2000, $this->invoice('S-1')->paid_amount);
        $this->assertSame('paid', $this->invoice('S-2')->status);
        $this->assertNotNull($this->feed->fresh()->last_received_at);

        // An invoice typed by hand in Tahselly is never closed by the report
        $manual = Invoice::factory()->create(['company_id' => $this->owner->company_id, 'number' => 'MANUAL-1', 'status' => 'unpaid']);
        $inbox->handle($this->email($this->report([['S-1', 'Gulf Events LLC', '2026-10-01', 5000, 3000], ['S-3', 'Blue Wave', '2026-11-20', 800, 800], ['S-4', 'Blue Wave', '2026-12-15', 650, 650]])));
        $this->assertSame('unpaid', $manual->fresh()->status);
    }

    public function test_a_balance_only_report_reads_a_lower_balance_as_a_payment(): void
    {
        $this->feed->allow('@acme.ae');
        $csv = "Ref No,Party's Name,Due on,Pending Amount\nT-1,Desert Rose,2026-10-10,900\n";
        app(SyncInbox::class)->handle($this->email($csv, 'accounts@acme.ae'));
        app(SyncInbox::class)->handle($this->email("Ref No,Party's Name,Due on,Pending Amount\nT-1,Desert Rose,2026-10-10,400\n", 'accounts@acme.ae'));

        $invoice = $this->invoice('T-1');
        $this->assertEquals(900, $invoice->amount);
        $this->assertEquals(500, $invoice->paid_amount);
    }

    public function test_reports_from_unknown_senders_wait_for_the_owner_and_other_addresses_are_ignored(): void
    {
        $inbox = app(SyncInbox::class);
        $this->assertSame([], $inbox->handle($this->email($this->report([['X-1', 'A', '2026-10-01', 10, 10]]), to: 'sync-zzzzzzzzzzzz@tahselly.com')));

        // Sent to the plain mailbox address: recognised by an exactly allowed sender
        $this->feed->allow('erp@acme.ae');
        $this->assertSame('applied', $inbox->handle($this->email($this->report([['P-1', 'Palm', '2026-10-01', 10, 10]]), to: 'sync@tahselly.com'))[0]->status);
        $this->assertSame([], $inbox->handle($this->email($this->report([['P-2', 'Palm', '2026-10-01', 10, 10]]), 'other@acme.ae', 'sync@tahselly.com')));
        $this->feed->update(['senders' => 'owner@acme.ae']);
        Invoice::withoutGlobalScopes()->delete();
        $this->owner->notifications()->delete();

        $run = $inbox->handle($this->email($this->report([['S-1', 'Gulf Events LLC', '2026-10-01', 5000, 5000]]), 'stranger@evil.test'))[0];
        $this->assertSame('rejected', $run->status);
        $this->assertSame(0, Invoice::withoutGlobalScopes()->count());
        $this->assertSame(1, $this->owner->notifications()->count());

        // The owner allows the sender and the held report is applied
        $this->actingAs($this->owner)->get(route('owner.sync'))->assertOk()->assertSee('stranger@evil.test')->assertSee($this->feed->address());
        $this->actingAs($this->owner)->post(route('owner.sync.apply', $run))->assertRedirect();
        $this->assertTrue($this->feed->fresh()->allows('stranger@evil.test'));
        $this->assertSame('unpaid', $this->invoice('S-1')->status);
        $this->assertSame('released', $run->fresh()->status);
    }

    public function test_a_report_that_would_close_most_invoices_is_held_back(): void
    {
        $this->feed->allow('erp@acme.ae');
        $inbox = app(SyncInbox::class);
        $rows = array_map(fn ($i) => ["S-{$i}", 'Client '.$i, '2026-10-01', 100, 100], range(1, 6));
        $inbox->handle($this->email($this->report($rows)));

        $run = $inbox->handle($this->email($this->report([$rows[0]])))[0];
        $this->assertSame('rejected', $run->status);
        $this->assertSame(0, Invoice::withoutGlobalScopes()->where('status', 'paid')->count());

        $run = $inbox->handle($this->email("Invoice No,Customer Name,Due Date,Total,Balance Due\n"))[0];
        $this->assertSame('rejected', $run->status);
    }

    public function test_the_owner_maps_unusual_columns_once_and_can_upload_by_hand(): void
    {
        $csv = "Doc Ref,Account Holder,Settle By,Outstanding AED\nK-9,Palm Co,2026-10-05,750\n";
        $this->actingAs($this->owner)->post(route('owner.sync.upload'), ['file' => UploadedFile::fake()->createWithContent('aging.csv', $csv)])->assertSessionHas('error');
        $this->assertSame('failed', SyncRun::query()->first()->status);

        $this->actingAs($this->owner)->put(route('owner.sync.mapping'), ['mapping' => ['number' => 'Doc Ref', 'client_name' => 'Account Holder', 'due_date' => 'Settle By', 'balance' => 'Outstanding AED']])->assertSessionHas('success');
        $this->actingAs($this->owner)->post(route('owner.sync.upload'), ['file' => UploadedFile::fake()->createWithContent('aging.csv', $csv)])->assertSessionHas('success');
        $this->assertEquals(750, $this->invoice('K-9')->amount);
    }

    public function test_owners_are_warned_when_the_report_stops_arriving(): void
    {
        $this->feed->update(['last_received_at' => now()->subDays(3)]);
        $this->artisan('sync:stale')->expectsOutput('Companies warned: 1');
        $this->artisan('sync:stale')->expectsOutput('Companies warned: 0');
        $this->assertSame(1, $this->owner->notifications()->count());
    }

    public function test_other_companies_cannot_apply_someone_elses_report(): void
    {
        $run = SyncRun::query()->create(['company_id' => $this->owner->company_id, 'source' => 'email', 'filename' => 'x.csv', 'path' => 'sync/x.csv', 'status' => 'rejected']);
        $other = User::factory()->create(['company_id' => Company::factory()->create()->id, 'role' => Role::Owner, 'email_verified_at' => now()]);
        $this->actingAs($other)->post(route('owner.sync.apply', $run))->assertNotFound();
    }
}
