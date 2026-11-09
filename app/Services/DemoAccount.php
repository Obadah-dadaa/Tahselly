<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Reminder;
use App\Models\User;
use App\Support\Edition;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Instant demo: a private company with sample receivables for one visitor, no sign-up.
 * It never sends real email or WhatsApp (see ReminderService) and is deleted after HOURS.
 */
class DemoAccount
{
    public const HOURS = 24;

    private const CLIENTS = [
        ['Blue Wave Real Estate', 'Omar Haddad'],
        ['Al Noor Trading LLC', 'Fatima Al Zaabi'],
        ['Emirates Fitness Club', 'Khalid Saeed'],
        ['Sahara Restaurants Group', 'Rania Youssef'],
        ['Marina Dental Clinic', 'Dr. Hessa Ali'],
        ['Falcon Logistics', 'Saeed Rashid'],
        ['Pearl Beauty Lounge', 'Mariam Obaid'],
        ['Golden Sands Hotel', 'Ali Mahmoud'],
        ['Oud Al Sharq Perfumes', 'Majid Faris'],
        ['Urban Coffee Roasters', 'Lina Fares'],
    ];

    private const SERVICES = ['Social media management', 'Website maintenance', 'Monthly retainer', 'Brand identity design', 'Google Ads campaign', 'Content production'];

    public function create(string $locale): User
    {
        return DB::transaction(function () use ($locale): User {
            // On the live site the demo gets the top plan so every feature is visible
            $plan = Edition::isBeta() ? null : Plan::query()->where('is_active', true)->orderByDesc('price')->first();

            $company = Company::create([
                'name' => 'Desert Rose Services LLC',
                'category' => $plan?->category,
                'email' => 'finance@desert-rose.example',
                'phone' => '04 000 0000',
                'address' => 'Business Bay, Dubai',
                'plan_id' => $plan?->id,
                'status' => 'demo',
            ]);

            $owner = User::create([
                'company_id' => $company->id,
                'name' => $locale === 'en' ? 'Demo visitor' : 'زائر تجريبي',
                'email' => 'demo-'.Str::lower(Str::random(16)).'@demo.tahselly.invalid',
                'password' => Str::random(40),
                'role' => Role::Owner,
                'locale' => $locale,
                'is_active' => true,
            ]);
            $owner->forceFill(['email_verified_at' => now()])->save();

            $this->seedReceivables($company, $owner);

            return $owner;
        });
    }

    /**
     * Remove demo companies older than HOURS (users, invoices and the rest go with them).
     */
    public function purge(): int
    {
        $count = 0;
        Company::query()->where('status', 'demo')->where('created_at', '<', now()->subHours(self::HOURS))
            ->each(function (Company $company) use (&$count): void {
                $company->delete();
                $count++;
            });

        return $count;
    }

    private function seedReceivables(Company $company, User $owner): void
    {
        $clients = collect(self::CLIENTS)->map(fn (array $c, int $i) => Client::create([
            'company_id' => $company->id,
            'name' => $c[0],
            'contact_name' => $c[1],
            // Reserved test domain: nothing addressed here can reach a real inbox
            'email' => 'accounts@'.Str::slug($c[0]).'.example',
            'phone' => '05000000'.str_pad((string) ($i + 10), 2, '0', STR_PAD_LEFT),
        ]));

        // Due dates spread so every aging bucket, "due this week" and "paid" have rows
        $dueOffsets = [-110, -95, -80, -72, -64, -55, -48, -41, -35, -30, -26, -21, -17, -14, -10, -7, -5, -3, -1, 0, 2, 3, 5, 6, 9, 12, 16, 20, 25, 30];
        $seq = 1001;

        foreach ($dueOffsets as $i => $offset) {
            $client = $clients[$i % $clients->count()];
            $due = today()->addDays($offset);
            $issue = $due->copy()->subDays(30);
            $amount = random_int(16, 240) * 50;

            $invoice = Invoice::create([
                'company_id' => $company->id,
                'client_id' => $client->id,
                'number' => 'DR-'.$seq++,
                'issue_date' => $issue,
                'due_date' => $due,
                'amount' => $amount,
                'currency' => 'AED',
                'status' => 'unpaid',
                'description' => self::SERVICES[$i % count(self::SERVICES)],
            ]);
            $invoice->forceFill(['created_at' => $issue])->saveQuietly();

            // Reminder history for what is already past due
            foreach ([-3, 0, 7, 14, 30] as $day) {
                if (-$offset < $day) {
                    break;
                }
                $sentAt = $due->copy()->addDays($day)->setTime(9, random_int(0, 10));
                $reminder = Reminder::create([
                    'company_id' => $company->id,
                    'invoice_id' => $invoice->id,
                    'client_id' => $client->id,
                    'channel' => 'email',
                    'stage' => $day < 0 ? 'before_due' : ($day === 0 ? 'on_due' : 'overdue'),
                    'day_offset' => $day,
                    'status' => 'sent',
                    'automatic' => true,
                    'recipient' => $client->email,
                    'subject' => 'Reminder: invoice '.$invoice->number,
                    'body' => 'Reminder: invoice '.$invoice->number,
                ]);
                $reminder->forceFill(['created_at' => $sentAt, 'updated_at' => $sentAt])->saveQuietly();
                $invoice->reminders_count++;
                $invoice->last_reminded_at = $sentAt;
            }
            $invoice->saveQuietly();

            // About a third of the older invoices are paid, a few partly
            if ($offset < -20 && $i % 3 === 0) {
                $this->pay($invoice, $owner, $amount, $due->copy()->addDays(random_int(2, 12)));
            } elseif ($offset < 0 && $i % 5 === 1) {
                $this->pay($invoice, $owner, round($amount * 0.4, -1), today()->subDays(random_int(1, 5)));
            }
        }

        foreach ([
            ['import', 'استيراد Excel: 30 فاتورة من invoices.xlsx'],
            ['reminders.run', 'تشغيل التذكيرات: 6 مرسلة'],
            ['client.created', 'إضافة عميل: Urban Coffee Roasters'],
        ] as $i => [$action, $description]) {
            (new ActivityLog)->forceFill([
                'company_id' => $company->id,
                'user_id' => $owner->id,
                'action' => $action,
                'description' => $description,
                'ip' => '127.0.0.1',
                'created_at' => now()->subHours($i * 5 + 1),
            ])->save();
        }
    }

    private function pay(Invoice $invoice, User $owner, float $amount, CarbonInterface $on): void
    {
        $on = $on->isFuture() ? today() : $on;
        Payment::create([
            'company_id' => $invoice->company_id,
            'invoice_id' => $invoice->id,
            'user_id' => $owner->id,
            'amount' => $amount,
            'paid_on' => $on,
            'method' => 'bank_transfer',
            'reference' => 'TRX'.random_int(100000, 999999),
        ]);
        $invoice->refreshPaymentStatus();
        if ($invoice->status === 'paid') {
            $invoice->forceFill(['paid_at' => $on])->saveQuietly();
        }
    }
}
