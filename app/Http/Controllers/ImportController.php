<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\ImportBatch;
use App\Services\InvoiceImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportController extends Controller
{
    public function index(): View
    {
        return view('import.index', [
            'batches' => ImportBatch::query()->with('user')->latest()->paginate(10),
            'columns' => InvoiceImportService::COLUMNS,
        ]);
    }

    public function store(Request $request, InvoiceImportService $importer): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240']]);

        $batch = $importer->import($request->file('file'), $request->user());
        ActivityLog::record('import', "استيراد Excel: {$batch->rows_imported} فاتورة من {$batch->filename}");

        return redirect()->route('import.show', $batch)->with(
            $batch->rows_failed ? 'error' : 'success',
            __('تم استيراد :imported من :total صف', ['imported' => $batch->rows_imported, 'total' => $batch->rows_total]).($batch->rows_failed ? ' — '.__(':count أخطاء', ['count' => $batch->rows_failed]) : '')
        );
    }

    public function show(ImportBatch $batch): View
    {
        $batch->load(['invoices.client', 'user']);

        return view('import.show', compact('batch'));
    }

    public function template(InvoiceImportService $importer): StreamedResponse
    {
        $spreadsheet = $importer->template();

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
        }, 'tahselly-invoices-template.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}
