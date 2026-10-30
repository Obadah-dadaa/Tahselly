@php
    $rtl = app()->getLocale() === 'ar';
    $plan = $company->plan;
    $days = max(0, (int) today()->diffInDays($company->trial_ends_at, false));
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head><meta charset="utf-8"><title>{{ __('تجربتك المجانية') }}</title></head>
<body style="margin:0;background:#f1f5f9;font-family:Tahoma,Arial,sans-serif;color:#0f172a">
<table width="100%" cellpadding="0" cellspacing="0" style="padding:32px 12px">
    <tr><td align="center">
        <table width="560" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;overflow:hidden;max-width:100%">
            <tr><td style="background:#0f766e;padding:18px 28px;color:#fff;font-size:18px;font-weight:bold"><img src="{{ asset('icon-192.png') }}" width="30" height="30" alt="" style="vertical-align:middle;border:0;margin-inline-end:10px">{{ __('تحصيلي') }}</td></tr>
            <tr><td style="padding:28px;font-size:15px;line-height:1.9;text-align:{{ $rtl ? 'right' : 'left' }}">
                <p style="margin:0 0 12px">{{ __('مرحباً :name،', ['name' => $company->users->first()?->name ?? $company->name]) }}</p>
                <p style="margin:0 0 12px">
                    @if($days === 0)
                        <b>{{ __('اليوم آخر يوم في تجربتك المجانية.') }}</b>
                    @else
                        <b>{{ __('تبقّى :days أيام على انتهاء تجربتك المجانية', ['days' => $days]) }}</b> ({{ $company->trial_ends_at->format('Y-m-d') }}).
                    @endif
                </p>
                @beta
                <p style="margin:0 0 20px">{{ __('بعد انتهاء النسخة التجريبية يتوقف الوصول إلى حسابك. إذا كنت مهتماً بالاستمرار، أرسل طلب الاشتراك وسنتواصل معك.') }}</p>
                <a href="{{ route('owner.billing') }}" style="display:inline-block;background:#0f766e;color:#fff;text-decoration:none;font-weight:bold;padding:12px 24px;border-radius:10px">{{ __('أرسل طلب الاشتراك') }}</a>
                @else
                <p style="margin:0 0 12px">{{ __('بعد انتهاء التجربة سيتوقف الوصول إلى حسابك والتذكيرات الآلية لعملائك حتى تشترك. بياناتك محفوظة ولن يُحذف شيء.') }}</p>
                @if($plan)
                    <p style="margin:0 0 20px">{{ __('باقتك: :plan — :price درهم شهرياً (أو وفّر شهرين بالاشتراك السنوي).', ['plan' => $plan->localizedName(), 'price' => (int) $plan->price]) }}</p>
                @endif
                <a href="{{ route('owner.billing') }}" style="display:inline-block;background:#0f766e;color:#fff;text-decoration:none;font-weight:bold;padding:12px 24px;border-radius:10px">{{ __('اشترك الآن') }}</a>
                @endbeta
            </td></tr>
            <tr><td style="padding:16px 28px;border-top:1px solid #e2e8f0;font-size:12px;color:#94a3b8">@beta{{ __('النسخة التجريبية مجانية ولا يوجد فيها أي دفع.') }}@else{{ __('لا يتم خصم أي مبلغ تلقائياً — الاشتراك بقرارك.') }}@endbeta</td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>
