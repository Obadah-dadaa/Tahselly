<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ImportBatch;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\User;
use App\Support\Edition;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Throwable;

class InvoiceImportService
{
    /**
     * Accepted header aliases (English & Arabic) mapped to internal field names. Besides our template they
     * cover the invoice exports of Zoho Books, QuickBooks and Tally (Bills Receivable / outstanding report).
     * The import page shows the last two aliases of each field, so the Arabic ones stay last.
     *
     * @var array<string, list<string>>
     */
    public const COLUMNS = [
        'number' => ['invoice_number', 'invoice no', 'invoice', 'number', 'invoice number', 'invoice#', 'invoice #', 'invoice no.', 'num', 'no.', 'bill no', 'bill no.', 'ref. no.', 'ref no', 'ref no.', 'voucher no', 'voucher no.', 'reference', 'رقم المرجع', 'رقم الفاتورة'],
        'client_name' => ['client_name', 'client', 'customer', 'customer name', 'customer display name', 'customer full name', 'client name', "party's name", 'party name', 'party', 'company name', 'name', 'اسم الطرف', 'العميل', 'اسم العميل'],
        'client_email' => ['client_email', 'email', 'e-mail', 'email id', 'customer email', 'email address', 'البريد', 'البريد الإلكتروني'],
        'client_phone' => ['client_phone', 'phone', 'mobile', 'mobile phone', 'phone number', 'customer phone', 'contact number', 'الهاتف', 'الجوال'],
        'issue_date' => ['issue_date', 'invoice date', 'date', 'bill date', 'voucher date', 'transaction date', 'تاريخ الإصدار', 'تاريخ الفاتورة'],
        'due_date' => ['due_date', 'due', 'due date', 'due on', 'payment due date', 'تاريخ الاستحقاق'],
        'amount' => ['amount', 'total', 'invoice amount', 'total amount', 'bill amount', 'grand total', 'invoice total', 'total (aed)', 'amount (aed)', 'opening amount', 'المبلغ', 'الإجمالي'],
        'paid_amount' => ['paid_amount', 'paid', 'amount paid', 'paid amount', 'payment made', 'payments', 'المدفوع'],
        // What is still owed; with a total it gives the paid part, alone it is the amount (Tally's "Pending Amount")
        'balance' => ['balance', 'balance due', 'open balance', 'pending amount', 'outstanding', 'outstanding amount', 'amount due', 'المتبقي', 'الرصيد المستحق'],
        'description' => ['description', 'notes', 'subject', 'narration', 'memo', 'الوصف', 'ملاحظات'],
    ];

    public function import(UploadedFile $file, User $user): ImportBatch
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, false, false);

        $batch = ImportBatch::create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'filename' => $file->getClientOriginalName(),
        ]);

        // Accounting exports often start with a title block (company name, report dates): use the first row that looks like headers
        $headerIndex = $this->headerRowIndex($rows);
        $map = $this->mapHeader($rows[$headerIndex] ?? []);
        $rows = array_slice($rows, $headerIndex + 1, preserve_keys: true);
        $errors = [];
        $imported = 0;
        $total = 0;
        $company = $user->company;
        // New open invoices this plan still allows (null = no limit)
        $room = $company?->openInvoiceRoom();
        $dataRows = count(array_filter($rows, fn ($row) => array_filter($row, fn ($v) => $v !== null && $v !== '')));

        if (! isset($map['number'], $map['client_name'], $map['due_date']) || ! (isset($map['amount']) || isset($map['balance']))) {
            $errors[] = ['row' => 1, 'message' => 'الأعمدة المطلوبة: رقم الفاتورة، العميل، تاريخ الاستحقاق، المبلغ'];
        } elseif (Edition::isBeta() && $dataRows > Edition::limit('import_rows')) {
            $total = $dataRows;
            $errors[] = ['row' => 1, 'message' => __('الملف فيه :count صف. النسخة التجريبية تسمح حتى :limit صف في الملف الواحد.', ['count' => $dataRows, 'limit' => Edition::limit('import_rows')])];
        } elseif ($company && $dataRows > Plan::IMPORT_ROWS_LIMIT && ! $company->canUse('bulk_import')) {
            $total = $dataRows;
            $errors[] = ['row' => 1, 'message' => __('الملف فيه :count صف. باقتك تسمح حتى :limit صف في الملف الواحد؛ الملفات الأكبر متاحة من باقة :plan. قسّم الملف أو قم بترقية باقتك.', [
                'count' => $dataRows, 'limit' => Plan::IMPORT_ROWS_LIMIT, 'plan' => Plan::minimumFor('bulk_import')?->localizedName(),
            ])];
        } else {
            foreach ($rows as $index => $row) {
                if (! array_filter($row, fn ($v) => $v !== null && $v !== '')) {
                    continue;
                }
                $total++;
                $rowNumber = $index + 1; // keys are the sheet's own row positions
                try {
                    $this->importRow($this->extract($row, $map), $user, $batch, $room);
                    $imported++;
                } catch (Throwable $e) {
                    $errors[] = ['row' => $rowNumber, 'message' => $e->getMessage()];
                }
            }
        }

        $batch->update([
            'rows_total' => $total,
            'rows_imported' => $imported,
            'rows_failed' => count($errors),
            'errors' => $errors ?: null,
        ]);

        return $batch;
    }

    /**
     * @param  array<int, mixed>  $header
     * @return array<string, int>
     */
    /**
     * The index of the header row among the first rows: the one that names the most known columns.
     *
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function headerRowIndex(array $rows): int
    {
        $best = 0;
        $bestCount = 0;
        foreach (array_slice($rows, 0, 15, true) as $index => $row) {
            $count = count($this->mapHeader($row));
            if ($count > $bestCount) {
                [$best, $bestCount] = [$index, $count];
            }
        }

        return $best;
    }

    private function mapHeader(array $header): array
    {
        $map = [];
        foreach ($header as $i => $label) {
            $normalized = rtrim(preg_replace('/\s+/u', ' ', mb_strtolower(trim((string) $label))), ':');
            foreach (self::COLUMNS as $field => $aliases) {
                if (! isset($map[$field]) && in_array($normalized, $aliases, true)) {
                    $map[$field] = $i;
                }
            }
        }

        return $map;
    }

    /**
     * @param  array<int, mixed>  $row
     * @param  array<string, int>  $map
     * @return array<string, mixed>
     */
    private function extract(array $row, array $map): array
    {
        $data = [];
        foreach (array_keys(self::COLUMNS) as $field) {
            $value = isset($map[$field]) ? ($row[$map[$field]] ?? null) : null;
            $data[$field] = is_string($value) ? trim($value) : $value;
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function importRow(array $data, User $user, ImportBatch $batch, ?int &$room): void
    {
        if (! $data['number'] || ! $data['client_name']) {
            throw new \InvalidArgumentException('رقم الفاتورة واسم العميل مطلوبان');
        }
        $amount = $this->number($data['amount'] ?? null) ?: $this->number($data['balance'] ?? null);
        if ($amount <= 0) {
            throw new \InvalidArgumentException('المبلغ غير صالح');
        }
        $dueDate = $this->date($data['due_date']) ?? throw new \InvalidArgumentException('تاريخ الاستحقاق غير صالح');
        $issueDate = $this->date($data['issue_date']) ?? $dueDate->copy()->subDays(30);
        $paid = min($this->number($data['paid_amount']), $amount);
        if ($data['paid_amount'] === null && $data['balance'] !== null && $data['balance'] !== '') {
            // Exports with a total and the balance still due (Zoho Books, QuickBooks)
            $paid = max(0, $amount - min($this->number($data['balance']), $amount));
        }

        // Updating an existing invoice is always allowed; a new open one needs room in the plan
        if ($room !== null && ($paid < $amount || Edition::isBeta()) && ! Invoice::query()->where('company_id', $user->company_id)->where('number', (string) $data['number'])->exists()) {
            if ($room <= 0) {
                throw new \InvalidArgumentException($user->company->invoiceLimitMessage());
            }
            $room--;
        }

        DB::transaction(function () use ($data, $user, $batch, $amount, $dueDate, $issueDate, $paid): void {
            $client = Client::firstOrCreate(
                ['company_id' => $user->company_id, 'name' => (string) $data['client_name']],
                ['email' => $data['client_email'] ?: null, 'phone' => $data['client_phone'] ? (string) $data['client_phone'] : null],
            );

            $invoice = Invoice::updateOrCreate(
                ['company_id' => $user->company_id, 'number' => (string) $data['number']],
                [
                    'client_id' => $client->id,
                    'import_batch_id' => $batch->id,
                    'issue_date' => $issueDate,
                    'due_date' => $dueDate,
                    'amount' => $amount,
                    'currency' => $user->company?->currency ?? 'AED',
                    'description' => $data['description'] ?: null,
                ],
            );

            $alreadyPaid = (float) $invoice->payments()->sum('amount');
            if ($paid > $alreadyPaid) {
                $invoice->payments()->create([
                    'company_id' => $user->company_id,
                    'user_id' => $user->id,
                    'amount' => $paid - $alreadyPaid,
                    'paid_on' => today(),
                    'method' => 'bank_transfer',
                    'reference' => 'Excel import',
                ]);
            }
            $invoice->refreshPaymentStatus();
        });
    }

    private function number(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return (float) str_replace([',', ' ', 'AED', 'د.إ'], '', (string) $value);
    }

    private function date(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value));
        }
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y', 'Y/m/d'] as $format) {
            try {
                $date = Carbon::createFromFormat('!'.$format, (string) $value);
                if ($date !== false && $date->format($format) === (string) $value) {
                    return $date;
                }
            } catch (Throwable) {
                continue;
            }
        }
        try {
            return Carbon::parse((string) $value);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Build a sample spreadsheet users can download and fill.
     */
    public function template(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['invoice_number', 'client_name', 'client_email', 'client_phone', 'issue_date', 'due_date', 'amount', 'paid_amount', 'description'],
            ['INV-1001', 'Blue Wave Marketing', 'accounts@bluewave.ae', '0501234567', today()->subDays(40)->format('Y-m-d'), today()->subDays(10)->format('Y-m-d'), 5250, 0, 'Social media retainer'],
            ['INV-1002', 'Al Noor Trading', 'finance@alnoor.ae', '0559876543', today()->subDays(5)->format('Y-m-d'), today()->addDays(25)->format('Y-m-d'), 12000, 2000, 'Bookkeeping Q3'],
        ]);
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->getStyle('A1:I1')->getFont()->setBold(true);

        return $spreadsheet;
    }
}
