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

    public function activity(Request $request): View
    {
        return view('owner.activity', [
            'logs' => ActivityLog::query()->where('company_id', $request->user()->company_id)
                ->with('user')->latest()->paginate(30),
        ]);
    }
}
