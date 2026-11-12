<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\SyncFeed;
use App\Models\SyncRun;
use App\Services\InvoiceSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "Connect your accounting system": the private address for the daily report, allowed senders,
 * column mapping for unusual reports, a manual upload to try it, and the log of received reports.
 */
class SyncController extends Controller
{
    /** Fields the owner can point to a column of their report */
    public const FIELDS = [
        'number' => 'رقم الفاتورة', 'client_name' => 'العميل', 'due_date' => 'تاريخ الاستحقاق', 'amount' => 'المبلغ الإجمالي',
        'balance' => 'المتبقي', 'paid_amount' => 'المدفوع', 'issue_date' => 'تاريخ الفاتورة', 'client_email' => 'بريد العميل',
        'client_phone' => 'هاتف العميل', 'description' => 'الوصف',
    ];

    public function index(Request $request): View
    {
        $feed = SyncFeed::for($request->user()->company);

        return view('owner.sync', [
            'feed' => $feed,
            'runs' => SyncRun::query()->latest('id')->paginate(15),
            'headers' => SyncRun::query()->whereNotNull('headers')->latest('id')->value('headers') ?? [],
            'fields' => self::FIELDS,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(['senders' => ['nullable', 'string', 'max:2000'], 'enabled' => ['boolean']]);
        SyncFeed::for($request->user()->company)->update(['senders' => $data['senders'] ?? null, 'enabled' => $request->boolean('enabled')]);

        return back()->with('success', __('تم حفظ الإعدادات'));
    }

    public function mapping(Request $request): RedirectResponse
    {
        $data = $request->validate(['mapping' => ['array'], 'mapping.*' => ['nullable', 'string', 'max:255']]);
        $mapping = array_filter(array_intersect_key($data['mapping'] ?? [], self::FIELDS), fn ($label) => filled($label));
        SyncFeed::for($request->user()->company)->update(['mapping' => $mapping ?: null]);

        return back()->with('success', __('تم حفظ ربط الأعمدة. سيُستخدم مع كل تقرير يصل.'));
    }

    public function token(Request $request): RedirectResponse
    {
        SyncFeed::for($request->user()->company)->update(['token' => SyncFeed::newToken()]);

        return back()->with('success', __('تم إنشاء عنوان جديد. حدّثه في الإرسال المجدول لنظامكم؛ العنوان القديم لم يعد يعمل.'));
    }

    public function upload(Request $request, InvoiceSyncService $sync): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240']]);
        $file = $request->file('file');
        $run = $sync->receive(SyncFeed::for($request->user()->company), $file->getClientOriginalName(), $file->get(), 'upload', $request->user()->email);

        return back()->with(...$this->result($run));
    }

    /**
     * Apply a held-back report: from a sender to allow from now on, or one the safety check stopped.
     */
    public function apply(Request $request, SyncRun $run, InvoiceSyncService $sync): RedirectResponse
    {
        abort_unless($run->isHeldBack(), 404);
        $feed = SyncFeed::for($request->user()->company);
        if ($run->source === 'email' && $run->sender && ! $feed->allows($run->sender)) {
            $feed->allow($run->sender);
        }
        $run->update(['status' => 'released']);
        $result = $sync->apply($feed, $run->path, $run->filename, $run->source, $run->sender, force: true);

        return back()->with(...$this->result($result));
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function result(SyncRun $run): array
    {
        return $run->status === 'applied'
            ? ['success', __('تمت المزامنة: :added جديدة، :updated محدّثة، :closed أُغلقت كمدفوعة', ['added' => $run->added, 'updated' => $run->updated, 'closed' => $run->closed]).($run->failed ? ' — '.__(':count أخطاء', ['count' => $run->failed]) : '')]
            : ['error', (string) $run->message];
    }
}
