<?php

namespace App\Http\Controllers;

use App\Services\Telegram;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Personal links in outreach emails: tahselly.com/go/s1-company-name.
 * The page counts the open from the browser (JavaScript) before moving on to the site,
 * so mail-security scanners that only fetch the link are mostly left out.
 */
class OutreachLinkController extends Controller
{
    public function show(string $ref): Response
    {
        $target = route('landing', ['lang' => 'en', 'utm_source' => 'outreach', 'utm_content' => Str::before($ref, '-')]);

        return response()->view('go', ['ref' => $ref, 'target' => $target])->header('X-Robots-Tag', 'noindex');
    }

    public function hit(Request $request, string $ref): Response
    {
        if (preg_match('/bot|crawl|spider|preview|scan|headless|python|curl/i', (string) $request->userAgent())) {
            return response()->noContent();
        }

        DB::table('link_clicks')->insert(['ref' => $ref, 'created_at' => now()]);

        // One alert per link per day
        if (Cache::add('go-alert:'.$ref.':'.today()->toDateString(), true, now()->endOfDay())) {
            [$step, $company] = [Str::before($ref, '-'), Str::of(Str::after($ref, '-'))->replace('-', ' ')->title()];
            Telegram::notify('🔗', 'فتح رابط إيميل التسويق', [
                'الشركة' => (string) $company,
                'الإيميل' => match ($step) { 's2' => 'المتابعة الأولى', 's3' => 'المتابعة الأخيرة', default => 'الإيميل الأول' },
            ]);
        }

        return response()->noContent();
    }
}
