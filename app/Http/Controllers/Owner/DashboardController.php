<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Payment;
use App\Models\Reminder;
use App\Models\User;
use App\Services\ReceivablesReport;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, ReceivablesReport $report): View
    {
        $companyId = $request->user()->company_id;
        $aging = $report->aging();

        // Payments recovered after at least one reminder was sent = value attributed to the system
        $recoveredAfterReminder = (float) Payment::query()
            ->whereDate('paid_on', '>=', today()->subDays(30))
            ->whereHas('invoice', fn ($q) => $q->where('reminders_count', '>', 0))
            ->sum('amount');

        return view('owner.dashboard', [
            'company' => $request->user()->company->load('plan'),
            'kpis' => $report->kpis(),
            'trend' => $report->monthlyTrend(6),
            'aging' => $aging,
            'buckets' => ReceivablesReport::BUCKETS,
            'topDebtors' => $report->topDebtors(6),
            'recovered' => $recoveredAfterReminder,
            'remindersMonth' => Reminder::query()->whereDate('created_at', '>=', today()->startOfMonth())->count(),
            'team' => User::query()->where('company_id', $companyId)->orderBy('role')->get(),
            'activity' => ActivityLog::query()->where('company_id', $companyId)->with('user')->latest()->limit(8)->get(),
        ]);
    }

    /**
     * The team's day-to-day work only (invoices, payments, reminders, clients, team and settings),
     * without subscription or platform events.
     */
    public const ACTIVITY_TYPES = [
        'invoices' => ['invoice.%'],
        'payments' => ['payment.%'],
        'reminders' => ['reminder.%', 'reminders.%'],
        'clients' => ['client.%', 'import'],
        'team' => ['team.%', 'settings.%', 'login'],
    ];

    public function activity(Request $request): View
    {
        $company = $request->user()->company;
        $type = array_key_exists((string) $request->type, self::ACTIVITY_TYPES) ? $request->type : null;
        $patterns = $type ? self::ACTIVITY_TYPES[$type] : array_merge(...array_values(self::ACTIVITY_TYPES));
        $team = $company->users()->orderBy('name')->get(['id', 'name']);

        $logs = ActivityLog::query()->where('company_id', $company->id)
            // Only people on this company's team (or the system itself), never platform staff
            ->where(fn ($q) => $q->whereNull('user_id')->orWhereIn('user_id', $team->pluck('id')))
            ->where(fn ($q) => collect($patterns)->each(fn ($p) => $q->orWhere('action', 'like', $p)))
            ->when($request->integer('user'), fn ($q, $id) => $q->where('user_id', $id))
            ->with('user')->latest()->paginate(30)->withQueryString();

        return view('owner.activity', compact('logs', 'team', 'type'));
    }
}
