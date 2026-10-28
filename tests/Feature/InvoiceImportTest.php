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
    }

    public function test_template_download_is_an_xlsx_file(): void
    {
        $this->seed(PlatformSeeder::class);
        $user = User::factory()->create(['company_id' => Company::factory()->create()->id, 'role' => 'owner']);

        $this->actingAs($user)->get(route('import.template'))
            ->assertOk()
            ->assertDownload('tahseel-invoices-template.xlsx');
    }
}
