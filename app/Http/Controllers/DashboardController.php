<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Reminder;
use App\Services\ReceivablesReport;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(ReceivablesReport $report): View
    {
        return view('dashboard.manager', [
            'kpis' => $report->kpis(),
            'aging' => $report->aging()['totals'],
            'overdueInvoices' => Invoice::query()->overdue()->with('client')->orderBy('due_date')->limit(8)->get(),
            'upcomingInvoices' => Invoice::query()->open()->with('client')
                ->whereBetween('due_date', [today(), today()->addDays(7)])->orderBy('due_date')->limit(6)->get(),
            'recentReminders' => Reminder::query()->with(['client', 'invoice'])->latest()->limit(6)->get(),
            'remindersToday' => Reminder::query()->whereDate('created_at', today())->count(),
        ]);
    }
}
