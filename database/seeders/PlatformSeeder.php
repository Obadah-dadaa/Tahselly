<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\ReminderTemplate;
use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

/**
 * Platform-level defaults: plans, system settings, global message templates. Safe to re-run.
 */
class PlatformSeeder extends Seeder
{
    public function run(): void
    {
        // Plans per customer segment; yearly = 10 months (2 months free), quarterly gets 5% off
        $plans = [
            // Ordered cheapest to most expensive; each plan lists only what it adds over the one before
            ['freelancer', 'freelancer', 'الانطلاقة', 'Starter', 99, 1, 1, 200, false,
                ['استيراد الفواتير من Excel', 'تذكيرات بريد آلية', 'تقرير أعمار الديون'],
                ['Excel invoice import', 'Automatic email reminders', 'Aging report']],
            ['agency-basic', 'agency', 'النمو', 'Growth', 149, 2, 3, 1000, false,
                ['تذكيرات واتساب', 'قوالب رسائل مخصصة'],
                ['WhatsApp reminders', 'Custom message templates']],
            ['services', 'services', 'الأعمال', 'Business', 179, 3, 5, 3000, false,
                ['إدارة الفريق والصلاحيات', 'تصدير التقارير إلى Excel'],
                ['Team & permissions', 'Report exports to Excel']],
            ['agency-pro', 'agency', 'الاحترافية', 'Professional', 199, 4, 10, 5000, true,
                ['لوحة المالك والتحليلات', 'جدول تذكيرات مرن', 'أولوية في الدعم'],
                ['Owner dashboard & analytics', 'Flexible reminder schedule', 'Priority support']],
            ['accounting', 'accounting', 'المؤسسات', 'Enterprise', 299, 5, 15, 10000, false,
                ['استيراد دفعات كبيرة من Excel', 'تقارير أعمار ديون لكل عميل', 'سجل نشاط كامل'],
                ['Bulk Excel imports', 'Per-client aging reports', 'Full activity log']],
        ];
        foreach ($plans as [$code, $category, $name, $nameEn, $price, $sort, $users, $invoices, $featured, $features, $featuresEn]) {
            Plan::updateOrCreate(['code' => $code], [
                'category' => $category, 'name' => $name, 'name_en' => $nameEn,
                'price' => $price, 'price_yearly' => $price * 10, 'max_users' => $users, 'max_invoices' => $invoices,
                'features' => $features, 'features_en' => $featuresEn, 'is_featured' => $featured, 'sort' => $sort,
            ]);
        }

        foreach ([
            ['app_name', 'تحصيلي', 'string', 'general', 'اسم النظام'],
            ['app_tagline', 'تابع فواتيرك، وحصّل أموالك في وقتها', 'string', 'general', 'الشعار النصي'],
            ['support_email', 'obadah@tahselly.com', 'string', 'general', 'بريد الدعم'],
            ['legal_entity_name', 'Tahselly', 'string', 'general', 'الاسم القانوني للشركة (يظهر في الشروط والخصوصية)'],
            ['support_whatsapp', '+971568640530', 'string', 'general', 'واتساب الدعم'],
            ['primary_color', '#0f766e', 'string', 'branding', 'اللون الأساسي'],
            ['reminders_engine_enabled', '1', 'bool', 'features', 'تفعيل محرك التذكيرات الآلي'],
            ['feature_whatsapp', '1', 'bool', 'features', 'تفعيل تذكيرات واتساب'],
            ['feature_excel_import', '1', 'bool', 'features', 'تفعيل استيراد Excel'],
            ['allow_signups', '1', 'bool', 'features', 'السماح بالتسجيل الذاتي والتجربة المجانية'],
            ['default_trial_days', '14', 'int', 'billing', 'أيام التجربة المجانية'],
            ['announcement', '', 'text', 'general', 'إعلان يظهر لكل المستخدمين (اتركه فارغاً للإخفاء)'],
            ['card_payments', '1', 'bool', 'billing', 'إظهار الدفع بالبطاقة (Stripe) في صفحة الاشتراك'],
            ['bank_name', 'Emirates NBD', 'string', 'billing', 'اسم البنك (للتحويل البنكي)'],
            ['bank_account_name', 'Tahselly Software Solutions', 'string', 'billing', 'اسم صاحب الحساب'],
            ['bank_iban', 'AE07 0331 2345 6789 0123 456', 'string', 'billing', 'رقم IBAN'],
            ['promo_video_ar', 'videos/tahselly-promo-ar.mp4', 'string', 'branding', 'فيديو البرومو العربي (مسار داخل public)'],
            ['promo_video_en', 'videos/tahselly-promo-en.mp4', 'string', 'branding', 'فيديو البرومو الإنكليزي (مسار داخل public)'],
            ['tutorial_video', 'videos/tahselly-tutorial-ar.mp4', 'string', 'branding', 'فيديو شرح الاستخدام (مسار داخل public)'],
        ] as [$key, $value, $type, $group, $label]) {
            SystemSetting::firstOrCreate(['key' => $key], compact('value', 'type', 'group', 'label'));
        }
        SystemSetting::flush();

        $templates = [
            'before_due' => [
                'subject' => 'تذكير ودّي: الفاتورة {invoice_number} تستحق في {due_date}',
                'email' => "مرحباً {client_name}،\n\nنود تذكيركم بأن الفاتورة رقم {invoice_number} بمبلغ {balance} {currency} تستحق السداد بتاريخ {due_date}.\n\nإن كنتم قد قمتم بالسداد فيرجى تجاهل هذه الرسالة.\n\nمع التحية،\n{company_name}",
                'whatsapp' => 'مرحباً {client_name} 👋 تذكير ودّي بأن الفاتورة {invoice_number} بمبلغ {balance} {currency} تستحق بتاريخ {due_date}. شكراً لكم — {company_name}',
            ],
            'on_due' => [
                'subject' => 'الفاتورة {invoice_number} مستحقة اليوم',
                'email' => "مرحباً {client_name}،\n\nالفاتورة رقم {invoice_number} بمبلغ {balance} {currency} مستحقة السداد اليوم {due_date}.\n\nنشكر لكم سرعة التحويل.\n\n{company_name}\n{company_phone}",
                'whatsapp' => 'مرحباً {client_name}، الفاتورة {invoice_number} بمبلغ {balance} {currency} مستحقة اليوم. نشكر تعاونكم — {company_name}',
            ],
            'overdue' => [
                'subject' => 'متأخرة {days_overdue} يوم: الفاتورة {invoice_number}',
                'email' => "مرحباً {client_name}،\n\nنلاحظ أن الفاتورة رقم {invoice_number} بمبلغ {balance} {currency} متأخرة عن موعد استحقاقها ({due_date}) بـ {days_overdue} يوم.\n\nنرجو ترتيب السداد في أقرب وقت أو التواصل معنا على {company_phone}.\n\n{company_name}",
                'whatsapp' => 'مرحباً {client_name}، الفاتورة {invoice_number} بمبلغ {balance} {currency} متأخرة {days_overdue} يوم. نرجو ترتيب السداد أو التواصل معنا. {company_name}',
            ],
            'final' => [
                'subject' => 'إشعار نهائي: الفاتورة {invoice_number}',
                'email' => "السادة {client_name}،\n\nهذا إشعار نهائي بخصوص الفاتورة رقم {invoice_number} بمبلغ {balance} {currency} المتأخرة منذ {days_overdue} يوم.\n\nفي حال عدم السداد خلال 7 أيام سيتم إيقاف الخدمات واتخاذ الإجراءات اللازمة.\n\n{company_name}",
                'whatsapp' => 'إشعار نهائي: الفاتورة {invoice_number} بمبلغ {balance} {currency} متأخرة {days_overdue} يوم. نرجو السداد خلال 7 أيام. {company_name}',
            ],
        ];
        $templatesEn = [
            'before_due' => [
                'subject' => 'Friendly reminder: invoice {invoice_number} is due on {due_date}',
                'email' => "Hello {client_name},\n\nThis is a friendly reminder that invoice {invoice_number} for {balance} {currency} is due on {due_date}.\n\nIf you have already paid, please ignore this message.\n\nKind regards,\n{company_name}",
                'whatsapp' => 'Hello {client_name} 👋 A friendly reminder that invoice {invoice_number} for {balance} {currency} is due on {due_date}. Thank you — {company_name}',
            ],
            'on_due' => [
                'subject' => 'Invoice {invoice_number} is due today',
                'email' => "Hello {client_name},\n\nInvoice {invoice_number} for {balance} {currency} is due for payment today, {due_date}.\n\nThank you for arranging the transfer.\n\n{company_name}\n{company_phone}",
                'whatsapp' => 'Hello {client_name}, invoice {invoice_number} for {balance} {currency} is due today. Thank you — {company_name}',
            ],
            'overdue' => [
                'subject' => '{days_overdue} days overdue: invoice {invoice_number}',
                'email' => "Hello {client_name},\n\nOur records show that invoice {invoice_number} for {balance} {currency} is {days_overdue} days past its due date ({due_date}).\n\nPlease arrange payment at your earliest convenience, or contact us on {company_phone}.\n\n{company_name}",
                'whatsapp' => 'Hello {client_name}, invoice {invoice_number} for {balance} {currency} is {days_overdue} days overdue. Please arrange payment or get in touch. {company_name}',
            ],
            'final' => [
                'subject' => 'Final notice: invoice {invoice_number}',
                'email' => "Dear {client_name},\n\nThis is a final notice regarding invoice {invoice_number} for {balance} {currency}, now {days_overdue} days overdue.\n\nIf payment is not received within 7 days, services may be suspended and further action taken.\n\n{company_name}",
                'whatsapp' => 'Final notice: invoice {invoice_number} for {balance} {currency} is {days_overdue} days overdue. Please pay within 7 days. {company_name}',
            ],
        ];
        foreach (['ar' => $templates, 'en' => $templatesEn] as $locale => $set) {
            foreach ($set as $stage => $t) {
                ReminderTemplate::firstOrCreate(['company_id' => null, 'stage' => $stage, 'channel' => 'email', 'locale' => $locale], ['subject' => $t['subject'], 'body' => $t['email']]);
                ReminderTemplate::firstOrCreate(['company_id' => null, 'stage' => $stage, 'channel' => 'whatsapp', 'locale' => $locale], ['subject' => null, 'body' => $t['whatsapp']]);
            }
        }
    }
}
