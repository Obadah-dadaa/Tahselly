<?php
// Outreach sender for Tahseel. Default is a dry run that writes previews only.
//   php send.php            -> previews in ./previews, nothing is sent
//   php send.php --send     -> sends one by one (60s apart), logs to sent-log.csv
//   php send.php --send --only=email@x.com   -> send to a single address
//   php send.php --test=me@x.com  -> sends the first company's email to me@x.com only, not logged
$send = in_array('--send', $argv);
$only = $test = null;
foreach ($argv as $a) {
    if (str_starts_with($a, '--only=')) $only = substr($a, 7);
    if (str_starts_with($a, '--test=')) $test = substr($a, 7);
}

$app = __DIR__.'/../tahseel';
require $app.'/vendor/autoload.php';
$laravel = require $app.'/bootstrap/app.php';
$laravel->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Mail;

$video = realpath(__DIR__.'/../tahseel-demo-share/Tahseel-Demo-English.mp4');
// Personalised per company, e.g. "Tahseel for SnapFixNow: Automated Invoice Follow-Up & Payment Collection"
$subjectFor = fn ($company) => "Tahseel for {$company}: Automated Invoice Follow-Up & Payment Collection";

$hooks = [
    'services' => "For service and maintenance companies with many open jobs and invoices, it takes the follow-up work off your team's plate.",
    'accounting' => "For accounting offices it can also be offered to your clients, so their receivables are followed up without extra effort from your team.",
    'agency' => "For agencies with retainers and project invoices, it keeps every client reminded on time without awkward follow-up calls.",
];

$body = fn ($company, $segment, $greeting) => <<<TXT
Dear {$greeting},

My name is Obadah Dadaa, a software engineer based in Dubai. I've built Tahseel, a ready-to-use platform that helps businesses collect payments on time without chasing clients manually. {$hooks[$segment]}

I've attached a 1-minute demo video showing how it works. In short, it lets you:

- Import your existing invoices from Excel, with no need to change your accounting software.
- Send automatic payment reminders by email and WhatsApp, before and after the due date, on a schedule you choose.
- Track paid, unpaid and overdue invoices at a glance, with an aging report by 30/60/90+ days.
- Record customer payments, and reminders stop automatically once an invoice is paid.
- Work in Arabic or English, for your team and for your clients.

I'm currently partnering with a small number of UAE businesses to set it up with their own invoices, including a free 14-day trial.

Would you be available for a brief 15-minute online demonstration next week?

Thank you for your time. I look forward to hearing from you.

Best regards,
Obadah Dadaa
Software Engineer
Dubai, UAE
+971 56 864 0530

If this isn't relevant to you, just reply and I won't contact you again.
TXT;

$rows = array_map('str_getcsv', file(__DIR__.'/recipients.csv', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
array_shift($rows);
@mkdir(__DIR__.'/previews');
$log = fopen(__DIR__.'/sent-log.csv', 'a');
$sentBefore = file_exists(__DIR__.'/sent-log.csv') ? file_get_contents(__DIR__.'/sent-log.csv') : '';

if ($test) {
    [$company, , $segment] = $rows[0];
    $subject = $subjectFor($company);
    Mail::raw($body($company, $segment, "{$company} Team"), function ($m) use ($test, $subject, $video) {
        $m->to($test)->subject('[TEST] '.$subject)
          ->from(config('mail.from.address'), 'Obadah Dadaa')
          ->attach($video, ['as' => 'Tahseel-Demo.mp4', 'mime' => 'video/mp4']);
    });
    exit("test sent to {$test} ({$company} version)
");
}

foreach ($rows as $i => $row) {
    [$company, $email, $segment] = $row;
    $greeting = ($row[3] ?? '') ?: "{$company} Team";
    if ($only && strcasecmp($only, $email) !== 0) continue;
    $text = $body($company, $segment, $greeting);
    $subject = $subjectFor($company);
    if (! $send) {
        file_put_contents(__DIR__.sprintf('/previews/%02d-%s.txt', $i + 1, preg_replace('/\W+/', '-', $company)),
            "To: {$email}\nSubject: {$subject}\nAttachment: ".basename($video)."\n\n{$text}\n");
        echo "preview  {$email}\n";
        continue;
    }
    if (str_contains($sentBefore, ",{$email},OK")) { echo "skip (already sent)  {$email}\n"; continue; }
    try {
        Mail::raw($text, function ($m) use ($email, $subject, $video) {
            $m->to($email)->subject($subject)
              ->from(config('mail.from.address'), 'Obadah Dadaa')
              ->attach($video, ['as' => 'Tahseel-Demo.mp4', 'mime' => 'video/mp4']);
        });
        fputcsv($log, [date('Y-m-d H:i:s'), $email, 'OK', $company]);
        echo "sent     {$email}\n";
    } catch (Throwable $e) {
        fputcsv($log, [date('Y-m-d H:i:s'), $email, 'FAILED', $e->getMessage()]);
        echo "FAILED   {$email}: {$e->getMessage()}\n";
    }
    if (! $only) sleep(60);
}
