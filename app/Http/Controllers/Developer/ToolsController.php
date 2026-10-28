<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\SystemSetting;
use App\Services\PaymentGateway;
use App\Services\ReminderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ToolsController extends Controller
{
    /**
     * Whitelisted maintenance actions available as one-click buttons.
     *
     * @var array<string, array{label: string, description: string}>
     */
    public const ACTIONS = [
        'run-reminders' => ['label' => 'تشغيل التذكيرات الآن', 'description' => 'ينفذ reminders:send لكل الشركات'],
        'clear-cache' => ['label' => 'مسح الكاش', 'description' => 'optimize:clear — الكاش، الإعدادات، المسارات، الواجهات'],
        'migrate' => ['label' => 'تشغيل الترحيلات', 'description' => 'migrate --force لتطبيق أي تحديثات على قاعدة البيانات'],
        'retry-failed' => ['label' => 'إعادة المهام الفاشلة', 'description' => 'queue:retry all'],
        'clear-log' => ['label' => 'تفريغ ملف السجل', 'description' => 'يفرغ storage/logs/laravel.log'],
        'test-stripe' => ['label' => 'اختبار الاتصال بـ Stripe', 'description' => 'GET /v1/account بالمفتاح الحالي'],
    ];

    public function index(PaymentGateway $gateway): View
    {
        $tables = collect(Schema::getTables(DB::getDatabaseName()))->map(fn (array $t) => [
            'name' => $t['name'],
            'rows' => DB::table($t['name'])->count(),
            'size' => round(($t['size'] ?? 0) / 1024, 1),
        ])->sortByDesc('rows')->values();

        return view('developer.tools', [
            'actions' => self::ACTIONS,
            'tables' => $tables,
            'routes' => collect(app('router')->getRoutes()->getRoutes())
                ->filter(fn ($r) => $r->getName() && ! str_starts_with($r->getName(), 'boost') && ! str_starts_with((string) $r->getName(), 'storage'))
                ->map(fn ($r) => ['method' => implode('|', array_diff($r->methods(), ['HEAD'])), 'uri' => $r->uri(), 'name' => $r->getName(), 'middleware' => implode(', ', array_diff($r->gatherMiddleware(), ['web']))])
                ->values(),
            'output' => session('tool_output'),
            'gateway' => [
                'mode' => $gateway->mode(),
                'webhook' => filled(config('services.stripe.webhook_secret')),
                'webhook_url' => route('stripe.webhook'),
            ],
        ]);
    }

    public function run(Request $request, string $action, ReminderService $reminders, PaymentGateway $gateway): RedirectResponse
    {
        abort_unless(array_key_exists($action, self::ACTIONS), 404);

        $output = match ($action) {
            'run-reminders' => json_encode($reminders->runAll(), JSON_UNESCAPED_UNICODE),
            'clear-cache' => $this->artisan('optimize:clear'),
            'migrate' => $this->artisan('migrate', ['--force' => true]),
            'retry-failed' => $this->artisan('queue:retry', ['id' => ['all']]),
            'clear-log' => $this->clearLog(),
            'test-stripe' => (fn (array $r) => ($r['ok'] ? 'OK — ' : 'FAILED — ').$r['message'])($gateway->testConnection()),
        };
        SystemSetting::flush();
        ActivityLog::record('dev.tool', 'أداة المبرمج: '.self::ACTIONS[$action]['label']);

        return back()->with('success', __('تم التنفيذ: ').self::ACTIONS[$action]['label'])->with('tool_output', $output);
    }

    public function logs(Request $request): View
    {
        $path = storage_path('logs/laravel.log');
        $lines = [];
        if (is_file($path)) {
            $content = file_get_contents($path, false, null, max(0, filesize($path) - 200_000));
            preg_match_all('/^\[(\d{4}-\d{2}-\d{2} [\d:]+)\] (\w+)\.(\w+): (.*?)(?=^\[\d{4}-\d{2}-\d{2}|\z)/ms', $content, $matches, PREG_SET_ORDER);
            foreach (array_reverse($matches) as $m) {
                if ($request->level && strtolower($m[3]) !== $request->level) {
                    continue;
                }
                $lines[] = ['time' => $m[1], 'env' => $m[2], 'level' => strtolower($m[3]), 'message' => mb_strimwidth(trim($m[4]), 0, 4000, '…')];
                if (count($lines) >= 100) {
                    break;
                }
            }
        }

        return view('developer.logs', ['entries' => $lines, 'size' => is_file($path) ? round(filesize($path) / 1024, 1) : 0]);
    }

    public function activity(): View
    {
        return view('developer.activity', [
            'logs' => ActivityLog::query()->with(['user', 'company'])->latest()->paginate(40),
        ]);
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function artisan(string $command, array $params = []): string
    {
        Artisan::call($command, $params);

        return trim(Artisan::output()) ?: 'Done';
    }

    private function clearLog(): string
    {
        $path = storage_path('logs/laravel.log');
        if (is_file($path)) {
            file_put_contents($path, '');
        }

        return 'Log cleared';
    }
}
