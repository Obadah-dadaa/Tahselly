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

        // Growth funnel: unique visitors → free trials → paying subscribers
        $visits = DB::table('site_visits');
        $visitors = [
            'today' => (clone $visits)->where('day', today()->toDateString())->count(),
            'week' => (clone $visits)->where('day', '>=', today()->subDays(6)->toDateString())->count(),
            'month' => (clone $visits)->where('day', '>=', today()->subDays(29)->toDateString())->count(),
        ];
        $daily = (clone $visits)->where('day', '>=', today()->subDays(13)->toDateString())
            ->selectRaw('day, count(*) as total')->groupBy('day')->pluck('total', 'day');
        $visitorChart = ['labels' => [], 'data' => []];
        for ($i = 13; $i >= 0; $i--) {
            $day = today()->subDays($i);
            $visitorChart['labels'][] = $day->format('d/m');
            $visitorChart['data'][] = (int) ($daily[$day->toDateString()] ?? 0);
        }
        $trials = $companies->filter(fn (Company $c) => $c->status === 'trial' && $c->hasAccess());
        $paying = Subscription::query()->current()->distinct()->count('company_id');
        $newSignups = $companies->filter(fn (Company $c) => $c->created_at->gte(today()->subDays(29)))->count();
        $topReferrers = (clone $visits)->where('day', '>=', today()->subDays(29)->toDateString())->whereNotNull('referrer')
            ->selectRaw('referrer, count(*) as total')->groupBy('referrer')->orderByDesc('total')->limit(5)->pluck('total', 'referrer');

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
            'funnel' => [
                'visitors' => $visitors,
                'trials' => $trials->count(),
                'trials_ending' => $trials->filter(fn (Company $c) => $c->trial_ends_at && $c->trial_ends_at->lte(today()->addDays(3)))->count(),
                'paying' => $paying,
                'signups' => $newSignups,
                'signup_rate' => $visitors['month'] > 0 ? min(100, round($newSignups / $visitors['month'] * 100, 1)) : 0,
                'referrers' => $topReferrers,
            ],
            'visitorChart' => $visitorChart,
            'companies' => $companies->take(8),
            'signups' => $signups,
            'activity' => ActivityLog::query()->with(['user', 'company'])->latest()->limit(10)->get(),
            'system' => [
                'php' => PHP_VERSION,
                'laravel' => app()->version(),
                'db' => DB::connection()->getDriverName().' '.DB::connection()->getServerVersion(),
                'env' => app()->environment(),
                'debug' => config('app.debug'),
                'mail' => config('mail.default'),
                'queue' => config('queue.default'),
                'timezone' => config('app.timezone'),
            ],
        ]);
    }
}
