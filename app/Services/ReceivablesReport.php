<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Collection;

/**
 * Receivables analytics for the current tenant (queries respect the company scope).
 */
class ReceivablesReport
{
    /** @var array<string, string> */
    public const BUCKETS = [
        'current' => 'غير مستحقة بعد',
        'd1_30' => '1 - 30 يوم',
        'd31_60' => '31 - 60 يوم',
        'd61_90' => '61 - 90 يوم',
        'd90_plus' => 'أكثر من 90 يوم',
    ];

    public static function bucketFor(int $daysOverdue): string
    {
        return match (true) {
            $daysOverdue <= 0 => 'current',
            $daysOverdue <= 30 => 'd1_30',
            $daysOverdue <= 60 => 'd31_60',
            $daysOverdue <= 90 => 'd61_90',
            default => 'd90_plus',
        };
    }

    /**
     * @return array{totals: array<string, float>, clients: Collection<int, array<string, mixed>>, grand: float}
     */
    public function aging(?int $clientId = null): array
    {
        $totals = array_fill_keys(array_keys(self::BUCKETS), 0.0);
        $clients = [];

        Invoice::query()->open()->with('client')
            ->when($clientId, fn ($q) => $q->where('client_id', $clientId))
            ->get()
            ->each(function (Invoice $invoice) use (&$totals, &$clients): void {
                $bucket = self::bucketFor($invoice->daysOverdue());
                $balance = $invoice->balance();
                $totals[$bucket] += $balance;
                $clients[$invoice->client_id] ??= ['client' => $invoice->client] + array_fill_keys(array_keys(self::BUCKETS), 0.0) + ['total' => 0.0, 'invoices' => 0];
                $clients[$invoice->client_id][$bucket] += $balance;
                $clients[$invoice->client_id]['total'] += $balance;
                $clients[$invoice->client_id]['invoices']++;
            });

        return [
            'totals' => $totals,
            'clients' => collect($clients)->sortByDesc('total')->values(),
            'grand' => array_sum($totals),
        ];
    }

    /**
     * @return array{outstanding: float, overdue: float, overdue_count: int, due_week: float, due_week_count: int, collected_month: float, open_count: int, collection_rate: float, dso: int}
     */
    public function kpis(): array
    {
        $open = Invoice::query()->open()->get(['id', 'amount', 'paid_amount', 'due_date', 'status']);
        $overdue = $open->filter(fn (Invoice $i) => $i->due_date->lt(today()));
        $dueWeek = $open->filter(fn (Invoice $i) => $i->due_date->between(today(), today()->addDays(7)));

        $invoiced90 = (float) Invoice::query()->where('status', '!=', 'cancelled')->whereDate('issue_date', '>=', today()->subDays(90))->sum('amount');
        $collected90 = (float) Payment::query()->whereDate('paid_on', '>=', today()->subDays(90))->sum('amount');
        $outstanding = (float) $open->sum(fn (Invoice $i) => $i->balance());

        return [
            'outstanding' => $outstanding,
            'overdue' => (float) $overdue->sum(fn (Invoice $i) => $i->balance()),
            'overdue_count' => $overdue->count(),
            'due_week' => (float) $dueWeek->sum(fn (Invoice $i) => $i->balance()),
            'due_week_count' => $dueWeek->count(),
            'collected_month' => (float) Payment::query()->whereDate('paid_on', '>=', today()->startOfMonth())->sum('amount'),
            'open_count' => $open->count(),
            'collection_rate' => $invoiced90 > 0 ? round(min(100, $collected90 / $invoiced90 * 100), 1) : 0.0,
            'dso' => $invoiced90 > 0 ? (int) round($outstanding / ($invoiced90 / 90)) : 0,
        ];
    }

    /**
     * Invoiced vs collected per month for the last N months.
     *
     * @return array{labels: list<string>, invoiced: list<float>, collected: list<float>}
     */
    public function monthlyTrend(int $months = 6): array
    {
        $labels = $invoiced = $collected = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $start = today()->startOfMonth()->subMonths($i);
            $end = $start->copy()->endOfMonth();
            $labels[] = $start->locale('ar')->translatedFormat('M Y');
            $invoiced[] = (float) Invoice::query()->where('status', '!=', 'cancelled')->whereBetween('issue_date', [$start, $end])->sum('amount');
            $collected[] = (float) Payment::query()->whereBetween('paid_on', [$start, $end])->sum('amount');
        }

        return compact('labels', 'invoiced', 'collected');
    }

    /**
     * @return Collection<int, array{client: Client, balance: float, overdue: float, invoices: int}>
     */
    public function topDebtors(int $limit = 5): Collection
    {
        return $this->aging()['clients']
            ->map(fn (array $row) => [
                'client' => $row['client'],
                'balance' => $row['total'],
                'overdue' => $row['total'] - $row['current'],
                'invoices' => $row['invoices'],
            ])
            ->take($limit);
    }
}
