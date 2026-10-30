@php
    use App\Support\Edition;
    $email = \App\Models\SystemSetting::get('support_email');
    $app = __(\App\Models\SystemSetting::get('app_name', 'تحصيلي'));
    $l = fn (string $key) => Edition::limit($key);

    $sections = app()->getLocale() === 'en' ? [
        ['What the beta is', [
            "The {$app} beta (beta.tahselly.com) is a free, early version of the {$app} invoice follow-up and payment reminder platform, offered only so businesses can try it and give feedback.",
            'The beta is not a commercial service. Nothing is sold in the beta and no payment of any kind is taken in it.',
        ]],
        ['Accepting these terms', [
            'By creating a beta account you agree to these beta terms on behalf of your business. If you do not agree, do not use the beta.',
        ]],
        ['Free and time-limited', [
            "Each beta account is free for {$l('trial_days')} days from sign-up. When that period ends, access stops unless we extend it.",
            'If you would like to keep using the system, you can send a subscription request from your account. We will contact you; sending a request does not create any obligation for you or for us.',
        ]],
        ['Usage limits', [
            'To keep the beta for testing, every account is limited to:',
            [
                "{$l('max_invoices')} invoices;",
                "{$l('max_clients')} clients;",
                "{$l('max_users')} users;",
                "{$l('daily_reminders')} reminders sent per day;",
                "{$l('import_rows')} rows per imported file.",
            ],
            'We may change these limits during the beta.',
        ]],
        ['No guarantee of availability', [
            'The beta is provided "as is" and "as available". It may contain errors, change without notice, be unavailable at times, or be stopped.',
            'Reminders in the beta may be delayed or not sent. Do not rely on the beta as your only way to follow up on important invoices.',
        ]],
        ['Your data in the beta', [
            'Your data stays yours. We use it only to run the beta and to support you.',
            'Beta data may be reset or deleted when the beta ends or when an account expires. Keep your own copy of anything important; you can export reports to Excel at any time.',
            'Please use the beta with real but non-critical data, and only for clients you have a genuine business relationship with.',
        ]],
        ['Acceptable use', [
            'You agree not to use the beta to send spam, misleading or threatening messages, to message people who are not your clients, to break any UAE law, or to try to access other accounts or overload the system.',
        ]],
        ['Feedback', [
            'If you send us feedback or suggestions, we may use them to improve the service without any obligation to you.',
        ]],
        ['Limitation of liability', [
            'To the extent permitted by UAE law, we are not liable for any loss arising from use of the beta, including lost payments, lost data or business interruption.',
        ]],
        ['Ending access', [
            'We may suspend or close a beta account that breaks these terms or misuses the service. You may stop using the beta at any time and ask us to delete your data.',
        ]],
        ['Contact us', [
            "Questions about the beta: {$email}.",
        ]],
    ] : [
        ['ما هي النسخة التجريبية', [
            "النسخة التجريبية من {$app} (beta.tahselly.com) نسخة مجانية مبكرة من منصة متابعة الفواتير وتذكير العملاء بالدفع، متاحة فقط لتتمكن الشركات من تجربتها وإبداء ملاحظاتها.",
            'النسخة التجريبية ليست خدمة تجارية. لا يُباع فيها أي شيء ولا يُستوفى فيها أي دفع من أي نوع.',
        ]],
        ['قبول الشروط', [
            'بإنشاء حساب في النسخة التجريبية فإنك توافق على هذه الشروط نيابةً عن منشأتك. إذا لم توافق، لا تستخدم النسخة التجريبية.',
        ]],
        ['مجانية ومحددة المدة', [
            "كل حساب تجريبي مجاني لمدة {$l('trial_days')} يوماً من تاريخ التسجيل. عند انتهاء المدة يتوقف الوصول ما لم نقم بتمديده.",
            'إذا رغبت في الاستمرار باستخدام النظام، يمكنك إرسال طلب اشتراك من داخل حسابك وسنتواصل معك. إرسال الطلب لا يُنشئ أي التزام عليك أو علينا.',
        ]],
        ['حدود الاستخدام', [
            'لتبقى النسخة التجريبية للتجربة فقط، فإن كل حساب محدود بـ:',
            [
                "{$l('max_invoices')} فاتورة؛",
                "{$l('max_clients')} عميل؛",
                "{$l('max_users')} مستخدمين؛",
                "{$l('daily_reminders')} تذكيراً مُرسلاً في اليوم؛",
                "{$l('import_rows')} صفاً في ملف الاستيراد الواحد.",
            ],
            'قد نغيّر هذه الحدود خلال فترة النسخة التجريبية.',
        ]],
        ['لا ضمان للتوفر', [
            'تُقدَّم النسخة التجريبية «كما هي» و«حسب توفرها». قد تحتوي على أخطاء، أو تتغير دون إشعار، أو تتوقف مؤقتاً أو نهائياً.',
            'قد تتأخر التذكيرات في النسخة التجريبية أو لا تُرسل. لا تعتمد عليها كوسيلتك الوحيدة لمتابعة الفواتير المهمة.',
        ]],
        ['بياناتك في النسخة التجريبية', [
            'بياناتك ملك لك، ونستخدمها فقط لتشغيل النسخة التجريبية ودعمك.',
            'قد تُعاد تهيئة بيانات النسخة التجريبية أو تُحذف عند انتهائها أو عند انتهاء مدة الحساب. احتفظ بنسخة مما يهمك؛ يمكنك تصدير التقارير إلى Excel في أي وقت.',
            'نرجو استخدام النسخة التجريبية ببيانات حقيقية غير حرجة، ومع عملاء تربطك بهم علاقة عمل فعلية فقط.',
        ]],
        ['الاستخدام المقبول', [
            'توافق على عدم استخدام النسخة التجريبية لإرسال رسائل مزعجة أو مضللة أو تهديدية، أو مراسلة أشخاص ليسوا من عملائك، أو مخالفة أي قانون في دولة الإمارات، أو محاولة الوصول إلى حسابات أخرى أو إرهاق النظام.',
        ]],
        ['الملاحظات', [
            'إذا أرسلت لنا ملاحظات أو اقتراحات، فيحق لنا استخدامها لتحسين الخدمة دون أي التزام تجاهك.',
        ]],
        ['حدود المسؤولية', [
            'في الحدود التي يسمح بها القانون الإماراتي، لا نتحمل المسؤولية عن أي خسارة ناتجة عن استخدام النسخة التجريبية، بما في ذلك الدفعات غير المحصّلة أو فقدان البيانات أو توقف العمل.',
        ]],
        ['إنهاء الوصول', [
            'يحق لنا إيقاف أو إغلاق أي حساب تجريبي يخالف هذه الشروط أو يسيء استخدام الخدمة. ويحق لك التوقف عن الاستخدام في أي وقت وطلب حذف بياناتك.',
        ]],
        ['التواصل معنا', [
            "للاستفسار عن النسخة التجريبية: {$email}.",
        ]],
    ];
@endphp
<x-legal-page :title="__('شروط استخدام النسخة التجريبية')" updated="2026-10-30" :sections="$sections" />
