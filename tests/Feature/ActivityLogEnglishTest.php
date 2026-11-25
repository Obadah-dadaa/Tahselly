<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogEnglishTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_stored_arabic_log_line_reads_in_english(): void
    {
        $this->seed(PlatformSeeder::class);
        app()->setLocale('en');
        $samples = [
            'تسجيل دخول' => 'Signed in',
            'إضافة عميل: Pearl Beauty Lounge' => 'Added client: Pearl Beauty Lounge',
            'تفعيل حساب a@b.ae' => 'Activated user a@b.ae',
            'تفعيل حساب ACME لمدة 30 يوم' => 'Activated ACME for 30 days',
            'تحديث اشتراك ACME: active' => 'Updated subscription of ACME: active',
            'تمديد اشتراك ACME 3 شهر' => 'Extended subscription of ACME by 3 months',
            'استيراد Excel: 12 فاتورة من june.xlsx' => 'Excel import: 12 invoices from june.xlsx',
            'إنشاء فاتورة CH-1' => 'Created invoice CH-1',
            'تسجيل دفعة 1,500.00 للفاتورة CH-1' => 'Recorded a payment of 1,500.00 on invoice CH-1',
            'إرسال تذكير يدوي للفاتورة CH-1' => 'Sent a manual reminder for invoice CH-1',
            'جدولة 4 تذكير' => 'Scheduled 4 reminders',
            'دفع اشتراك عبر Stripe: 199.00 AED' => 'Paid subscription by card: 199.00 AED',
            'دفع اشتراك الاحترافية: 199.00 AED' => 'Paid subscription Professional: 199.00 AED',
            'بدء تجربة مجانية (30 يوم): النمو' => 'Started a free trial (30 days): Growth',
            'مزامنة نظام المحاسبة: 3 جديدة، 1 أُغلقت (open.csv)' => 'Accounting sync: 3 new, 1 closed (open.csv)',
            'مكافأة إحالة: 30 يوم مجاناً بعد اشتراك ACME' => 'Referral reward: 30 free days after ACME subscribed',
            'Something unknown' => 'Something unknown',
        ];
        foreach ($samples as $arabic => $english) {
            $this->assertSame($english, (new ActivityLog(['description' => $arabic]))->localizedDescription());
        }

        app()->setLocale('ar');
        $this->assertSame('تسجيل دخول', (new ActivityLog(['description' => 'تسجيل دخول']))->localizedDescription());
    }
}
