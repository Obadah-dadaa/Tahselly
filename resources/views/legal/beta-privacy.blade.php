@php
    $email = \App\Models\SystemSetting::get('support_email');
    $app = __(\App\Models\SystemSetting::get('app_name', 'تحصيلي'));

    $sections = app()->getLocale() === 'en' ? [
        ['About this notice', [
            "This notice explains how personal data is handled in the {$app} beta (beta.tahselly.com), in line with UAE Federal Decree-Law No. 45 of 2021 on the Protection of Personal Data. The beta is free and for testing only.",
        ]],
        ['What we collect', [
            [
                'Your account details: name, email, phone, company name, and your password (stored encrypted).',
                'What you enter: your clients, their contact details, invoices, payments and reminder settings.',
                'A log of reminders sent and actions taken in your account.',
                'Basic technical data needed to run the site securely (for example sign-in times). We count visits without storing your IP address.',
                'Subscription requests you send us (name, company, email, phone and your message).',
            ],
        ]],
        ['How we use it', [
            [
                'To run the beta: show your dashboard and send the reminders you set up to your clients.',
                'To support you and reply to your subscription request.',
                'To improve the product from how the beta is used.',
            ],
            'We do not sell your data, and we do not use your clients\' details for our own marketing.',
        ]],
        ['Your clients\' data', [
            'You control your clients\' data and are responsible for having the right to contact them. We process it only to send the reminders you configure.',
        ]],
        ['How long we keep it', [
            'Beta data is kept only while the beta runs. It may be deleted when your beta account expires, and it is deleted when the beta ends, unless you have moved to a paid account. You can ask us to delete it sooner.',
        ]],
        ['Who sees it', [
            'Only you and the users you add. Our hosting and email providers process data on our behalf to run the service. We share data with authorities only when the law requires it.',
        ]],
        ['Your rights', [
            "You can ask to see, correct, export or delete your data at any time by writing to {$email}.",
        ]],
        ['Contact', [
            "Privacy questions: {$email}.",
        ]],
    ] : [
        ['عن هذا الإشعار', [
            "يوضح هذا الإشعار كيفية التعامل مع البيانات الشخصية في النسخة التجريبية من {$app} (beta.tahselly.com)، وفقاً للمرسوم بقانون اتحادي رقم 45 لسنة 2021 بشأن حماية البيانات الشخصية. النسخة التجريبية مجانية ومخصصة للتجربة فقط.",
        ]],
        ['ما الذي نجمعه', [
            [
                'بيانات حسابك: الاسم والبريد والجوال واسم الشركة وكلمة المرور (مخزّنة مشفّرة).',
                'ما تُدخله: عملاؤك وبيانات التواصل معهم والفواتير والدفعات وإعدادات التذكير.',
                'سجل التذكيرات المُرسلة والإجراءات في حسابك.',
                'بيانات تقنية أساسية لازمة لتشغيل الموقع بأمان (مثل أوقات تسجيل الدخول). نحصي الزيارات دون تخزين عنوان IP.',
                'طلبات الاشتراك التي ترسلها (الاسم والشركة والبريد والجوال ورسالتك).',
            ],
        ]],
        ['كيف نستخدمها', [
            [
                'لتشغيل النسخة التجريبية: عرض لوحتك وإرسال التذكيرات التي تضبطها لعملائك.',
                'لدعمك والرد على طلب اشتراكك.',
                'لتحسين المنتج بناءً على طريقة استخدام النسخة التجريبية.',
            ],
            'لا نبيع بياناتك، ولا نستخدم بيانات عملائك في تسويقنا.',
        ]],
        ['بيانات عملائك', [
            'أنت المتحكم في بيانات عملائك والمسؤول عن حقك في التواصل معهم. نعالجها فقط لإرسال التذكيرات التي تضبطها.',
        ]],
        ['مدة الاحتفاظ', [
            'نحتفظ ببيانات النسخة التجريبية طوال فترة تشغيلها فقط. قد تُحذف عند انتهاء مدة حسابك التجريبي، وتُحذف عند انتهاء النسخة التجريبية ما لم تنتقل إلى حساب مدفوع. ويمكنك طلب حذفها قبل ذلك.',
        ]],
        ['من يطّلع عليها', [
            'أنت والمستخدمون الذين تضيفهم فقط. يعالج مزوّدو الاستضافة والبريد البيانات نيابةً عنا لتشغيل الخدمة. ولا نشارك البيانات مع الجهات الرسمية إلا عندما يفرض القانون ذلك.',
        ]],
        ['حقوقك', [
            "يمكنك طلب الاطلاع على بياناتك أو تصحيحها أو تصديرها أو حذفها في أي وقت بمراسلة {$email}.",
        ]],
        ['التواصل', [
            "للاستفسارات المتعلقة بالخصوصية: {$email}.",
        ]],
    ];
@endphp
<x-legal-page :title="__('سياسة الخصوصية للنسخة التجريبية')" updated="2026-10-30" :sections="$sections" />
