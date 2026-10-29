@php $rtl = app()->getLocale() === 'ar'; @endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head><meta charset="utf-8"><title>{{ $title }}</title></head>
<body style="margin:0;background:#f1f5f9;font-family:Tahoma,Arial,sans-serif;color:#0f172a">
<table width="100%" cellpadding="0" cellspacing="0" style="padding:32px 12px">
    <tr><td align="center">
        <table width="560" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;overflow:hidden;max-width:100%">
            <tr><td style="background:#0f766e;padding:20px 28px;color:#fff;font-size:18px;font-weight:bold">{{ __('تحصيلي') }}</td></tr>
            <tr><td style="padding:28px;font-size:15px;line-height:1.9;text-align:{{ $rtl ? 'right' : 'left' }}">
                <p style="margin:0 0 12px">{{ __('مرحباً :name،', ['name' => $name]) }}</p>
                <p style="margin:0 0 6px;font-size:18px;font-weight:bold">{{ $title }}</p>
                <p style="margin:0 0 22px">{{ $body }}</p>
                <a href="{{ $url }}" style="display:inline-block;background:#0f766e;color:#fff;text-decoration:none;font-weight:bold;padding:12px 26px;border-radius:10px">{{ __('فتح حسابي') }}</a>
            </td></tr>
            <tr><td style="padding:16px 28px;border-top:1px solid #e2e8f0;font-size:12px;color:#94a3b8">{{ __('أُرسلت هذه الرسالة تلقائياً من :app.', ['app' => __('تحصيلي')]) }}</td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>
