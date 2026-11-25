<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

#[Fillable(['company_id', 'user_id', 'action', 'description', 'ip', 'meta'])]
class ActivityLog extends Model
{
    protected function casts(): array
    {
        return ['meta' => 'array'];
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public static function record(string $action, string $description, array $meta = [], ?int $companyId = null): self
    {
        $user = Auth::user();

        return static::create([
            'company_id' => $companyId ?? $user?->company_id,
            'user_id' => $user?->id,
            'action' => $action,
            'description' => $description,
            'ip' => request()?->ip(),
            'meta' => $meta ?: null,
        ]);
    }

    /**
     * Log lines are stored in Arabic (older rows too); English readers get them translated.
     * Each pattern matches one record() call; names, numbers and files are carried over as they are.
     */
    private const ENGLISH = [
        '/^تسجيل دخول$/u' => 'Signed in',
        '/^إضافة عميل: (.+)$/us' => 'Added client: $1',
        '/^تعديل عميل: (.+)$/us' => 'Edited client: $1',
        '/^حذف عميل: (.+)$/us' => 'Deleted client: $1',
        '/^تفعيل حساب (\S+@\S+)$/u' => 'Activated user $1',
        '/^إيقاف حساب (.+)$/us' => 'Deactivated user $1',
        '/^حذف المستخدم (.+)$/us' => 'Deleted user $1',
        '/^إيقاف الشركة (.+)$/us' => 'Suspended company $1',
        '/^إعادة تفعيل الشركة (.+)$/us' => 'Reactivated company $1',
        '/^حذف الشركة نهائياً: (.+)$/us' => 'Permanently deleted company: $1',
        '/^إنشاء شركة جديدة: (.+)$/us' => 'Created company: $1',
        '/^تحديث اشتراك (.+): (\w+)$/us' => 'Updated subscription of $1: $2',
        '/^تفعيل حساب (.+) لمدة (\d+) يوم$/us' => 'Activated $1 for $2 days',
        '/^تحديث إعدادات النظام$/u' => 'Updated system settings',
        '/^تحديث الباقات والأسعار$/u' => 'Updated plans and prices',
        '/^تحديث القوالب الافتراضية$/u' => 'Updated default templates',
        '/^اعتماد تحويل بنكي: (.+)$/us' => 'Approved bank transfer: $1',
        '/^تمديد اشتراك (.+) (\d+) شهر$/us' => 'Extended subscription of $1 by $2 months',
        '/^إلغاء اشتراك (.+)$/us' => 'Cancelled subscription of $1',
        '/^اشتراك يدوي: (.+)$/us' => 'Manual subscription: $1',
        '/^أداة المبرمج: (.+)$/us' => 'Developer tool: $1',
        '/^تأكيد البريد الإلكتروني$/u' => 'Verified email',
        '/^استيراد Excel: (\d+) فاتورة من (.+)$/us' => 'Excel import: $1 invoices from $2',
        '/^إنشاء فاتورة (.+)$/us' => 'Created invoice $1',
        '/^تعديل فاتورة (.+)$/us' => 'Edited invoice $1',
        '/^حذف فاتورة (.+)$/us' => 'Deleted invoice $1',
        '/^إلغاء فاتورة (.+)$/us' => 'Cancelled invoice $1',
        '/^إرسال تذكير يدوي للفاتورة (.+)$/us' => 'Sent a manual reminder for invoice $1',
        '/^تذكير واتساب للفاتورة (.+)$/us' => 'WhatsApp reminder for invoice $1',
        '/^تحديث بيانات الشركة$/u' => 'Updated company details',
        '/^تحديث جدول التذكيرات$/u' => 'Updated the reminder schedule',
        '/^تحديث قوالب الرسائل$/u' => 'Updated message templates',
        '/^إضافة مستخدم: (.+)$/us' => 'Added team member: $1',
        '/^تحديث صلاحيات: (.+)$/us' => 'Changed role of $1',
        '/^إعادة تعيين كلمة المرور$/u' => 'Reset password',
        '/^تسجيل دفعة ([\d,.]+) للفاتورة (.+)$/us' => 'Recorded a payment of $1 on invoice $2',
        '/^حذف دفعة من الفاتورة (.+)$/us' => 'Deleted a payment from invoice $1',
        '/^إرسال واتساب للكل: (\d+) مرسلة$/u' => 'Sent WhatsApp to all: $1 sent',
        '/^جدولة (\d+) تذكير$/u' => 'Scheduled $1 reminders',
        '/^تجهيز (\d+) رسالة واتساب$/u' => 'Prepared $1 WhatsApp messages',
        '/^إرسال (\d+) تذكير يدوي$/u' => 'Sent $1 manual reminders',
        '/^تشغيل التذكيرات يدوياً: (\d+) مرسلة$/u' => 'Ran reminders by hand: $1 sent',
        '/^دفع اشتراك عبر Stripe: (.+)$/us' => 'Paid subscription by card: $1',
        '/^طلب اشتراك: (.+)$/us' => 'Requested subscription: $1',
        '/^دفع اشتراك (.+): (.+)$/us' => 'Paid subscription $1: $2',
        '/^بدء تجربة مجانية \((\d+) يوم\)(.*)$/us' => 'Started a free trial ($1 days)$2',
        '/^اختيار الباقة: (.+)$/us' => 'Chose plan: $1',
        '/^مزامنة نظام المحاسبة: (\d+) جديدة، (\d+) أُغلقت \((.+)\)$/us' => 'Accounting sync: $1 new, $2 closed ($3)',
        '/^مكافأة إحالة: (\d+) يوم مجاناً بعد اشتراك (.+)$/us' => 'Referral reward: $1 free days after $2 subscribed',
    ];

    public function localizedDescription(): string
    {
        if (app()->getLocale() === 'ar') {
            return $this->description;
        }
        foreach (self::ENGLISH as $pattern => $english) {
            if (preg_match($pattern, $this->description)) {
                // Plan names are stored in Arabic inside subscription and trial lines
                static $plans = null;
                $plans ??= Plan::query()->whereNotNull('name_en')->pluck('name_en', 'name')->all();

                return strtr(preg_replace($pattern, $english, $this->description), $plans);
            }
        }

        return $this->description;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
