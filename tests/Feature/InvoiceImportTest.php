<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\ImportBatch;
use App\Models\Invoice;
use App\Models\User;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class InvoiceImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_csv_with_arabic_headers_imports_valid_rows_and_reports_errors(): void
    {
        $this->seed(PlatformSeeder::class);
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'manager']);

        $csv = "رقم الفاتورة,العميل,تاريخ الاستحقاق,المبلغ,المدفوع\n"
            ."AR-1,شركة الريادة,2026-09-01,4500,1000\n"
            ."AR-2,شركة الريادة,15/12/2026,\"7,250\",0\n"
            ."AR-3,,2026-10-01,100,0\n";
        $file = UploadedFile::fake()->createWithContent('invoices.csv', $csv);

        $this->actingAs($user)->post(route('import.store'), ['file' => $file])->assertRedirect();

        $batch = ImportBatch::query()->first();
        $this->assertSame([3, 2, 1], [$batch->rows_total, $batch->rows_imported, $batch->rows_failed]);

        $first = Invoice::query()->where('number', 'AR-1')->first();
        $this->assertSame('partial', $first->status);
        $this->assertEquals(1000, $first->paid_amount);
        $this->assertSame('2026-12-15', Invoice::query()->where('number', 'AR-2')->first()->due_date->toDateString());
        $this->assertEquals(7250, Invoice::query()->where('number', 'AR-2')->value('amount'));

        $this->actingAs($user)->get(route('import.show', $batch))->assertOk()->assertSee('11,750.00');
    }

    public function test_zoho_books_export_uses_total_and_balance(): void
    {
        $this->seed(PlatformSeeder::class);
        $user = User::factory()->create(['company_id' => Company::factory()->create()->id, 'role' => 'manager']);

        $csv = "Invoice Date,Invoice ID,Invoice Number,Invoice Status,Customer Name,Due Date,Currency Code,Total,Balance
"
            ."2026-10-01,4567000001,INV-000101,Partially Paid,Gulf Events LLC,2026-10-31,AED,5000,3000
"
            ."2026-10-05,4567000002,INV-000102,Overdue,Gulf Events LLC,2026-11-04,AED,1200,1200
";
        $this->actingAs($user)->post(route('import.store'), ['file' => UploadedFile::fake()->createWithContent('Invoice.csv', $csv)]);

        $partial = Invoice::query()->where('number', 'INV-000101')->first();
        $this->assertEquals(5000, $partial->amount);
        $this->assertEquals(2000, $partial->paid_amount);
        $this->assertSame('partial', $partial->status);
        $this->assertEquals(0, Invoice::query()->where('number', 'INV-000102')->value('paid_amount'));
    }

    public function test_tally_outstanding_report_with_a_title_block(): void
    {
        $this->seed(PlatformSeeder::class);
        $user = User::factory()->create(['company_id' => Company::factory()->create()->id, 'role' => 'manager']);

        $csv = "Desert Rose Trading LLC,,,,
"
            ."Bills Receivable,,,,
"
            ."1-Oct-26 to 9-Nov-26,,,,
"
            ."Date,Ref. No.,Party's Name,Pending Amount,Due on
"
            ."01/10/2026,TR/55,Falcon Logistics,\"8,400.00\",31/10/2026
";
        $this->actingAs($user)->post(route('import.store'), ['file' => UploadedFile::fake()->createWithContent('Bills Receivable.csv', $csv)]);

        $batch = ImportBatch::query()->first();
        $this->assertSame([1, 1, 0], [$batch->rows_total, $batch->rows_imported, $batch->rows_failed]);
        $invoice = Invoice::query()->where('number', 'TR/55')->first();
        $this->assertSame('Falcon Logistics', $invoice->client->name);
        $this->assertEquals(8400, $invoice->amount);
        $this->assertSame('2026-10-31', $invoice->due_date->toDateString());
    }

    public function test_template_download_is_an_xlsx_file(): void
    {
        $this->seed(PlatformSeeder::class);
        $user = User::factory()->create(['company_id' => Company::factory()->create()->id, 'role' => 'owner']);

        $this->actingAs($user)->get(route('import.template'))
            ->assertOk()
            ->assertDownload('tahselly-invoices-template.xlsx');
    }
}
