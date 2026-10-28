<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Reminder;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([PlatformSeeder::class]);

        $plans = Plan::query()->get()->keyBy('code');

        User::updateOrCreate(['email' => 'developer@tahseel.test'], [
            'name' => 'المبرمج', 'password' => 'password', 'role' => Role::Developer,
        ]);
        User::updateOrCreate(['email' => 'platform@tahseel.test'], [
            'name' => 'عبادة دعدع', 'password' => 'password', 'role' => Role::Platform,
        ]);

        $demo = Company::create([
            'name' => 'Creative Hub Marketing',
            'category' => 'agency',
            'email' => 'finance@creativehub.ae',
            'phone' => '04 555 0199',
            'trn' => '100234567800003',
            'address' => 'Business Bay, Dubai',
            'plan_id' => $plans['agency-pro']->id,
            'status' => 'active',
        ]);
        $this->subscribe($demo, $plans['agency-pro'], 'yearly', now()->subMonths(4), 'card');
        User::create(['company_id' => $demo->id, 'name' => 'سارة المنصوري', 'email' => 'owner@demo.test', 'password' => 'password', 'role' => Role::Owner]);
        User::create(['company_id' => $demo->id, 'name' => 'أحمد الكعبي', 'email' => 'manager@demo.test', 'password' => 'password', 'role' => Role::Manager]);
        User::create(['company_id' => $demo->id, 'name' => 'ليلى حسن', 'email' => 'accounts@demo.test', 'password' => 'password', 'role' => Role::Manager]);

        $this->seedReceivables($demo);

        // Subscribers across every category, cycle and state so the platform panel has data
        foreach ([
            ['Al Noor Accounting', 'accounting', 'monthly', 'active', 4, 'card'],
            ['Desert Bloom Events', 'agency-basic', 'quarterly', 'active', 2, 'card'],
            ['Pixel Freelance Studio', 'freelancer', 'monthly', 'active', 0, 'card'],
            ['Gulf Facility Services', 'services', 'yearly', 'active', 7, 'bank_transfer'],
            ['Oasis Legal Consultants', 'services', 'monthly', 'expired', 3, 'card'],
            ['Nour Designs', 'freelancer', 'yearly', 'active', 11, 'card'],
            ['Marina Bookkeeping', 'accounting', 'quarterly', 'active', 1, 'card'],
            ['Spark Social Agency', 'agency-basic', 'monthly', 'pending', 0, 'bank_transfer'],
        ] as [$name, $code, $cycle, $state, $monthsAgo, $method]) {
            $plan = $plans[$code];
            $company = Company::create([
                'name' => $name,
                'category' => $plan->category,
                'plan_id' => $plan->id,
                'status' => $state === 'active' ? 'active' : 'expired',
            ]);
            $company->forceFill(['created_at' => now()->subMonths($monthsAgo)->subDays(rand(1, 20))])->save();
            User::create([
                'company_id' => $company->id,
                'name' => 'Owner '.$name,
                'email' => 'owner@'.str($name)->slug().'.test',
                'password' => 'password',
                'role' => Role::Owner,
                'is_active' => $state !== 'pending',
            ]);
            if ($state === 'pending') {
                $company->subscriptions()->create(['plan_id' => $plan->id, 'cycle' => $cycle, 'amount' => $plan->priceFor($cycle), 'status' => 'pending', 'payment_method' => $method]);
            } elseif ($state === 'expired') {
                $this->subscribe($company, $plan, $cycle, now()->subMonths($monthsAgo), $method)->update(['status' => 'expired']);
            } else {
                // a renewal history for older monthly customers
                $start = now()->subMonths($monthsAgo)->subDays(rand(1, 20));
                do {
                    $sub = $this->subscribe($company, $plan, $cycle, $start, $method);
                    $start = $sub->ends_at;
                } while ($sub->ends_at->isPast());
            }
            $clients = Client::factory()->count(rand(4, 8))->create(['company_id' => $company->id]);
            foreach ($clients as $client) {
                Invoice::factory()->count(rand(2, 5))->create(['client_id' => $client->id, 'company_id' => $company->id]);
            }
        }
        // one company still on its free trial
        $trial = Company::create(['name' => 'Bright Path Consulting', 'category' => 'services', 'plan_id' => $plans['services']->id, 'status' => 'trial', 'trial_ends_at' => today()->addDays(9)]);
        User::create(['company_id' => $trial->id, 'name' => 'Owner Bright Path', 'email' => 'owner@bright-path.test', 'password' => 'password', 'role' => Role::Owner]);

        User::query()->whereNull('email_verified_at')->update(['email_verified_at' => now()]);
        Artisan::call('subscriptions:expire');
    }

    private function subscribe(Company $company, Plan $plan, string $cycle, CarbonInterface $start, string $method): Subscription
    {
        $start = Carbon::instance($start);

        return $company->subscriptions()->create([
            'plan_id' => $plan->id,
            'cycle' => $cycle,
            'amount' => $plan->priceFor($cycle),
            'status' => 'active',
            'payment_method' => $method,
            'gateway_ref' => $method === 'card' ? 'pi_demo_'.str()->random(10) : 'TRX'.rand(100000, 999999),
            'paid_at' => $start,
            'starts_at' => $start,
            'ends_at' => $start->copy()->addMonths(Subscription::CYCLE_MONTHS[$cycle]),
        ]);
    }

    private function seedReceivables(Company $company): void
    {
        $clients = collect([
            ['Blue Wave Real Estate', 'Omar Haddad', '0501112233'],
            ['Al Noor Trading LLC', 'Fatima Al Zaabi', '0552223344'],
            ['Emirates Fitness Club', 'Khalid Saeed', '0563334455'],
            ['Sahara Restaurants Group', 'Rania Youssef', '0504445566'],
            ['Marina Dental Clinic', 'Dr. Hessa Ali', '0525556677'],
            ['Nakheel Auto Parts', 'Yousef Nasser', '0546667788'],
            ['Pearl Beauty Lounge', 'Mariam Obaid', '0507778899'],
            ['Falcon Logistics', 'Saeed Rashid', '0558889900'],
            ['Jumeirah Luxury Villas', 'Noura Khalifa', '0509990011'],
            ['Smart Kids Nursery', 'Huda Salem', '0561231234'],
            ['Golden Sands Hotel', 'Ali Mahmoud', '0502342345'],
            ['TechNest Software', 'Ziad Kamal', '0553453456'],
            ['Oud Al Sharq Perfumes', 'Majid Faris', '0544564567'],
            ['Green Leaf Organics', 'Salma Adel', '0525675678'],
            ['Capital Law Firm', 'Hamad Juma', '0506786789'],
            ['Urban Coffee Roasters', 'Lina Fares', '0557897890'],
        ])->map(fn (array $c) => Client::create([
            'company_id' => $company->id,
            'name' => $c[0],
            'contact_name' => $c[1],
            'email' => 'accounts@'.str($c[0])->slug().'.ae',
            'phone' => $c[2],
            'trn' => '1003'.random_int(10000000000, 99999999999),
        ]));

        $services = ['Social media management', 'SEO monthly retainer', 'Brand identity design', 'Google Ads campaign', 'Website maintenance', 'Content production', 'Photography shoot', 'Influencer campaign'];
        $seq = 2401;
        $manager = User::query()->where('email', 'manager@demo.test')->first();

        // Six months of history; older invoices mostly paid, recent ones mostly open
        for ($i = 0; $i < 110; $i++) {
            $client = $clients->random();
            $issue = today()->subDays(random_int(0, 185));
            $due = $issue->copy()->addDays([15, 30, 30, 45][random_int(0, 3)]);
            $amount = random_int(12, 380) * 50;
            $invoice = Invoice::create([
                'company_id' => $company->id,
                'client_id' => $client->id,
                'number' => 'CH-'.$seq++,
                'issue_date' => $issue,
                'due_date' => $due,
                'amount' => $amount,
                'currency' => 'AED',
                'status' => 'unpaid',
                'description' => $services[array_rand($services)],
            ]);
            $invoice->forceFill(['created_at' => $issue])->saveQuietly();

            $age = $issue->diffInDays(today());
            $paidChance = $age > 120 ? 92 : ($age > 60 ? 70 : ($age > 30 ? 40 : 12));
            $roll = random_int(1, 100);

            $offset = (int) $due->diffInDays(today(), false);
            // Reminder history for invoices past their due date
            foreach ([-3, 0, 7, 14, 30] as $day) {
                if ($offset < $day) {
                    break;
                }
                $stage = $day < 0 ? 'before_due' : ($day === 0 ? 'on_due' : 'overdue');
                $sentAt = $due->copy()->addDays($day)->setTime(9, random_int(0, 10));
                $r = Reminder::create([
                    'company_id' => $company->id,
                    'invoice_id' => $invoice->id,
                    'client_id' => $client->id,
                    'channel' => 'email',
                    'stage' => $stage,
                    'day_offset' => $day,
                    'status' => random_int(1, 100) > 4 ? 'sent' : 'failed',
                    'automatic' => true,
                    'recipient' => $client->email,
                    'subject' => 'تذكير بالفاتورة '.$invoice->number,
                    'body' => 'تذكير آلي بالفاتورة '.$invoice->number,
                    'error' => null,
                ]);
                $r->forceFill(['created_at' => $sentAt, 'updated_at' => $sentAt])->saveQuietly();
                $invoice->reminders_count++;
                $invoice->last_reminded_at = $sentAt;
                if ($roll <= $paidChance && random_int(1, 3) === 1) {
                    break; // paid soon after this reminder
                }
            }
            $invoice->saveQuietly();

            if ($roll <= $paidChance) {
                $paidOn = $due->copy()->addDays(random_int(-10, 25));
                $paidOn = $paidOn->gt(today()) ? today() : ($paidOn->lt($issue) ? $issue->copy() : $paidOn);
                Payment::create([
                    'company_id' => $company->id,
                    'invoice_id' => $invoice->id,
                    'user_id' => $manager->id,
                    'amount' => $amount,
                    'paid_on' => $paidOn,
                    'method' => array_rand(Payment::METHODS),
                    'reference' => 'TRX'.random_int(100000, 999999),
                ]);
            } elseif ($roll <= $paidChance + 12) {
                Payment::create([
                    'company_id' => $company->id,
                    'invoice_id' => $invoice->id,
                    'user_id' => $manager->id,
                    'amount' => round($amount * random_int(25, 60) / 100, -1),
                    'paid_on' => today()->subDays(random_int(0, min(30, $age))),
                    'method' => 'bank_transfer',
                    'reference' => 'TRX'.random_int(100000, 999999),
                ]);
            }
            $invoice->refreshPaymentStatus();
            if ($invoice->status === 'paid') {
                $invoice->forceFill(['paid_at' => $invoice->payments()->max('paid_on')])->saveQuietly();
            }
        }

        foreach ([
            ['import', 'استيراد Excel: 42 فاتورة من invoices-q3.xlsx'],
            ['reminders.run', 'تشغيل التذكيرات يدوياً: 9 مرسلة'],
            ['payment.created', 'تسجيل دفعة 12,500.00 للفاتورة CH-2433'],
            ['client.created', 'إضافة عميل: Urban Coffee Roasters'],
            ['settings.reminders', 'تحديث جدول التذكيرات'],
        ] as $i => [$action, $description]) {
            (new ActivityLog)->forceFill([
                'company_id' => $company->id,
                'user_id' => $manager->id,
                'action' => $action,
                'description' => $description,
                'ip' => '127.0.0.1',
                'created_at' => now()->subHours($i * 7 + 1),
            ])->save();
        }
    }
}
