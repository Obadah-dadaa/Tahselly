<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Reminder;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $companies = Company::query()->with('plan')->withCount(['users', 'invoices', 'clients'])->latest()->get();
        $mrr = (float) Subscription::query()->current()->get()->sum(fn ($s) => $s->monthlyValue());

        $reminders30 = Reminder::query()->whereDate('created_at', '>=', today()->subDays(30));
        $sent = (clone $reminders30)->where('status', 'sent')->count();
        $failed = (clone $reminders30)->where('status', 'failed')->count();

        $signups = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = today()->startOfMonth()->subMonths($i);
            $signups['labels'][] = $month->locale('ar')->translatedFormat('M');
            $signups['data'][] = $companies->filter(fn (Company $c) => $c->created_at->isSameMonth($month))->count();
        }

        return view('developer.dashboard', [
            'stats' => [
                'companies' => $companies->count(),
                'active' => $companies->where('status', 'active')->count(),
                'trial' => $companies->where('status', 'trial')->count(),
                'suspended' => $companies->whereIn('status', ['suspended', 'expired'])->count(),
                'users' => User::query()->count(),
                'invoices' => Invoice::query()->count(),
                'volume' => (float) Invoice::query()->sum('amount'),
                'mrr' => $mrr,
                'arr' => $mrr * 12,
                'reminders_sent' => $sent,
                'reminders_failed' => $failed,
                'delivery_rate' => $sent + $failed > 0 ? round($sent / ($sent + $failed) * 100, 1) : 100,
                'failed_jobs' => DB::table('failed_jobs')->count(),
                'pending_jobs' => DB::table('jobs')->count(),
            ],
            'companies' => $companies->take(8),
            'signups' => $signups,
            'activity' => ActivityLog::query()->with(['user', 'company'])->latest()->limit(10)->get(),
            'system' => [
                'php' => PHP_VERSION,
                'laravel' => app()->version(),
                'db' => DB::connection()->getDriverName().' '.DB::selectOne('select version() as v')->v,
                'env' => app()->environment(),
                'debug' => config('app.debug'),
                'mail' => config('mail.default'),
                'queue' => config('queue.default'),
                'timezone' => config('app.timezone'),
            ],
        ]);
    }
}
