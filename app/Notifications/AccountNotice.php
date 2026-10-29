<?php

namespace App\Notifications;

use App\Models\Company;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as Notifier;
use Throwable;

/**
 * Subscription news for owners and platform staff: shown under the bell icon and emailed.
 * Only the kind and its parameters are stored, so each reader sees it in their own language.
 */
class AccountNotice extends Notification
{
    use Queueable;

    /** kind => [title, body, icon] (Arabic source strings, translated when shown) */
    public const KINDS = [
        'subscription_requested' => ['طلب اشتراك بانتظار موافقتك', ':company طلبت :plan (:cycle) بمبلغ :amount درهم بتحويل بنكي — المرجع :ref.', 'clock'],
        'subscription_paid' => ['اشتراك جديد مدفوع', ':company دفعت :plan (:cycle) بمبلغ :amount درهم بالبطاقة.', 'cash'],
        'subscription_active' => ['تم تفعيل اشتراكك', 'تم تأكيد دفعتك وتفعيل باقة :plan (:cycle) حتى :date.', 'check'],
        'subscription_extended' => ['تم تمديد اشتراكك', 'أصبح اشتراكك في باقة :plan سارياً حتى :date.', 'check'],
        'subscription_cancelled' => ['تم إلغاء اشتراكك', 'أُلغي اشتراكك في باقة :plan. اختر باقة للعودة إلى حسابك.', 'alert'],
    ];

    /**
     * @param  array<string, string>  $params
     */
    public function __construct(public string $kind, public array $params, public string $url) {}

    /**
     * Notify without ever breaking the action that triggered it (e.g. when SMTP is down).
     */
    public static function send(mixed $users, string $kind, array $params, string $url): void
    {
        try {
            Notifier::send($users, new self($kind, $params, $url));
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public static function title(string $kind): string
    {
        return __(self::KINDS[$kind][0] ?? $kind);
    }

    public static function body(string $kind, array $params): string
    {
        if (isset($params['plan_code'])) {
            $params['plan'] = Plan::query()->where('code', $params['plan_code'])->first()?->localizedName() ?? $params['plan_code'];
        }
        if (isset($params['cycle'])) {
            $params['cycle'] = Plan::cycleLabel($params['cycle']);
        }
        unset($params['plan_code']);

        return __(self::KINDS[$kind][1] ?? '', $params);
    }

    /**
     * Parameters for a subscription notice.
     *
     * @return array<string, string>
     */
    public static function about(Subscription $subscription): array
    {
        $subscription->loadMissing('plan', 'company');

        return [
            'company' => $subscription->company->name,
            'plan_code' => $subscription->plan->code,
            'cycle' => $subscription->cycle,
            'amount' => number_format((float) $subscription->amount),
            'ref' => 'TAH-'.str_pad((string) $subscription->id, 5, '0', STR_PAD_LEFT),
            'date' => $subscription->ends_at?->format('Y-m-d') ?? '',
        ];
    }

    public static function staff(): Collection
    {
        return User::query()->whereIn('role', ['developer', 'platform'])->where('is_active', true)->get();
    }

    public static function owners(Company $company): Collection
    {
        return $company->users()->where('role', 'owner')->where('is_active', true)->get();
    }

    public static function icon(string $kind): string
    {
        return self::KINDS[$kind][2] ?? 'bell';
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(self::title($this->kind).' — '.__('تحصيلي'))
            ->view('mail.notice', [
                'name' => $notifiable->name,
                'title' => self::title($this->kind),
                'body' => self::body($this->kind, $this->params),
                'url' => $this->url,
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return ['kind' => $this->kind, 'params' => $this->params, 'url' => $this->url];
    }
}
