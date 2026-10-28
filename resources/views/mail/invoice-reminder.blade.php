@php $rtl = app()->getLocale() === 'ar'; $align = $rtl ? 'right' : 'left'; $end = $rtl ? 'left' : 'right'; @endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head><meta charset="utf-8"><title>{{ $subjectLine }}</title></head>
<body style="margin:0;background:#f1f5f9;font-family:Tahoma,Arial,sans-serif;color:#0f172a">
<table width="100%" cellpadding="0" cellspacing="0" style="padding:32px 12px">
    <tr><td align="center">
        <table width="560" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;overflow:hidden;max-width:100%">
            <tr><td style="background:#0f766e;padding:20px 28px;color:#fff;font-size:18px;font-weight:bold;text-align:{{ $align }}">{{ $invoice->company?->name }}</td></tr>
            <tr><td style="padding:28px;font-size:15px;line-height:1.9;white-space:pre-line;text-align:{{ $align }}">{{ $bodyText }}</td></tr>
            <tr><td style="padding:0 28px 28px">
                <table width="100%" cellpadding="10" style="background:#f8fafc;border-radius:12px;font-size:14px">
                    <tr><td style="color:#64748b;text-align:{{ $align }}">{{ __('رقم الفاتورة') }}</td><td style="text-align:{{ $end }}"><b>{{ $invoice->number }}</b></td></tr>
                    <tr><td style="color:#64748b;text-align:{{ $align }}">{{ __('تاريخ الاستحقاق') }}</td><td style="text-align:{{ $end }}">{{ $invoice->due_date->format('Y-m-d') }}</td></tr>
                    <tr><td style="color:#64748b;text-align:{{ $align }}">{{ __('المبلغ المتبقي') }}</td><td style="text-align:{{ $end }}"><b style="color:#be123c">{{ number_format($invoice->balance(), 2) }} {{ $invoice->currency }}</b></td></tr>
                </table>
            </td></tr>
            <tr><td style="padding:16px 28px;border-top:1px solid #e2e8f0;font-size:12px;color:#94a3b8;text-align:{{ $align }}">{{ __('أُرسلت هذه الرسالة عبر نظام تحصيلي لمتابعة الفواتير.') }}</td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>
