<?php

namespace App\Http\Controllers;

use App\Services\ReceivablesReport;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function aging(ReceivablesReport $report): View
    {
        return view('reports.aging', [
            'aging' => $report->aging(),
            'buckets' => ReceivablesReport::BUCKETS,
        ]);
    }

    public function exportAging(ReceivablesReport $report): StreamedResponse
    {
        $aging = $report->aging();
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setRightToLeft(true);
        $sheet->setTitle('Aging');

        $rows = [array_merge(['العميل', 'عدد الفواتير'], array_values(ReceivablesReport::BUCKETS), ['الإجمالي'])];
        foreach ($aging['clients'] as $row) {
            $line = [$row['client']->name, $row['invoices']];
            foreach (array_keys(ReceivablesReport::BUCKETS) as $bucket) {
                $line[] = round($row[$bucket], 2);
            }
            $line[] = round($row['total'], 2);
            $rows[] = $line;
        }
        $rows[] = array_merge(['الإجمالي', ''], array_map(fn ($v) => round($v, 2), array_values($aging['totals'])), [round($aging['grand'], 2)]);
        $sheet->fromArray($rows);
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);
        $sheet->getStyle('A'.count($rows).':H'.count($rows))->getFont()->setBold(true);
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
        }, 'aging-report-'.today()->format('Y-m-d').'.xlsx');
    }
}
