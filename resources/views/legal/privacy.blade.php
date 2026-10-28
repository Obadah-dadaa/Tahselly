@php
    $entity = \App\Models\SystemSetting::get('legal_entity_name', 'Tahselly');
    $email = \App\Models\SystemSetting::get('support_email');
    $app = __(\App\Models\SystemSetting::get('app_name', 'تحصيلي'));

    $sections = app()->getLocale() === 'en' ? [
        ['Who we are', [
            "This policy explains how {$entity} (“we”) handles personal data in {$app}, in line with UAE Federal Decree-Law No. 45 of 2021 on the Protection of Personal Data.",
        ]],
        ['Our role', [
            'For your account details (the people who sign up and use the dashboards) we decide how data is used, so we are the controller.',
            'For the data you upload about your own clients (names, emails, phone numbers, invoices) your business is the controller and we process it only on your instructions to provide the Service.',
        ]],
        ['Data we collect', [
            [
                'Account data: name, email, mobile number, company name, address and tax registration number (TRN).',
                'Data you upload: your clients’ names and contact details, invoices, amounts, due dates, payments and the reminder messages you send.',
                'Billing data: plan, period, amounts and payment references. Card payments are processed by Stripe; we never see or store full card numbers.',
                'Usage and security data: sign-ins, actions taken in the account (activity log), IP address and technical logs.',
                'Cookies: only those needed to keep you signed in, protect forms, and remember your language and light/dark preference. We do not use advertising cookies.',
            ],
        ]],
        ['How we use it', [
            [
                'to provide the Service: store invoices, calculate balances and reports, and send the reminders you set up;',
                'to manage your trial and subscription, take payments and send you service emails (for example, trial-ending notices);',
                'to keep the Service secure, prevent abuse and fix problems;',
                'to meet our legal and tax obligations.',
            ],
            'We do not sell personal data and we do not use your clients’ data for our own marketing.',
        ]],
        ['Sharing', [
            'We share data only with providers that help us run the Service, under contracts that protect it:',
            [
                'Stripe — card payments;',
                'our email delivery provider — sending reminders and service emails;',
                'our hosting provider — storing the application and database.',
            ],
            'WhatsApp reminders open on your own device through wa.me links; we do not send your clients’ data to WhatsApp ourselves. We may also disclose data when required by UAE law or a competent authority.',
        ]],
        ['International transfers', [
            'Some providers (such as Stripe) may process data outside the UAE. When that happens we rely on transfers permitted by UAE law and on providers that offer appropriate safeguards.',
        ]],
        ['Data retention', [
            'We keep account data for as long as your account exists. If your trial or subscription ends and you do not return, we keep your data for 90 days so you can resume, then we may delete it. We keep billing records for as long as tax law requires, and security logs for up to 12 months.',
            'You can ask us to delete your account and data earlier.',
        ]],
        ['Security', [
            'Each subscriber’s data is isolated from other subscribers. Passwords are stored hashed, connections are encrypted (HTTPS) and access inside our team is limited to what is needed to run and support the Service.',
        ]],
        ['Your rights', [
            "You can ask to access, correct, delete or receive a copy of your personal data, or object to or restrict its processing, by writing to {$email}. Your clients should send requests about their data to your business; we will help you respond.",
            'You can also complain to the UAE Data Office.',
        ]],
        ['Children', [
            'The Service is for businesses and is not intended for anyone under 18.',
        ]],
        ['Changes to this policy', [
            'We will publish any update on this page with its date and notify account owners by email about important changes.',
        ]],
        ['Contact us', [
            "{$entity} — {$email}",
        ]],
    ] : [
        ['من نحن', [
            "توضح هذه السياسة كيف تتعامل {$entity} («نحن») مع البيانات الشخصية في {$app}، وفقاً للمرسوم بقانون اتحادي رقم 45 لسنة 2021 بشأن حماية البيانات الشخصية في دولة الإمارات.",
        ]],
        ['دورنا', [
            'بالنسبة لبيانات حسابك (الأشخاص الذين يسجّلون ويستخدمون لوحات التحكم) فنحن من يحدد طريقة استخدامها، أي أننا المتحكّم بها.',
            'أما البيانات التي ترفعها عن عملائك (الأسماء والبريد وأرقام الهاتف والفواتير) فمنشأتك هي المتحكّمة بها، ونحن نعالجها بناءً على تعليماتك فقط لتقديم الخدمة.',
        ]],
        ['البيانات التي نجمعها', [
            [
                'بيانات الحساب: الاسم والبريد الإلكتروني ورقم الجوال واسم الشركة وعنوانها ورقم التسجيل الضريبي.',
                'البيانات التي ترفعها: أسماء عملائك وبيانات التواصل معهم، والفواتير والمبالغ وتواريخ الاستحقاق والدفعات، ونصوص التذكيرات التي ترسلها.',
                'بيانات الفوترة: الباقة والمدة والمبالغ ومراجع الدفع. تُعالَج المدفوعات بالبطاقة عبر Stripe، ولا نطّلع على أرقام البطاقات الكاملة ولا نخزنها.',
                'بيانات الاستخدام والأمان: عمليات الدخول، والإجراءات داخل الحساب (سجل النشاط)، وعنوان IP، والسجلات التقنية.',
                'ملفات تعريف الارتباط (الكوكيز): الضرورية فقط لإبقائك مسجلاً للدخول وحماية النماذج وتذكّر اللغة والمظهر الفاتح أو الداكن. ولا نستخدم كوكيز إعلانية.',
            ],
        ]],
        ['كيف نستخدمها', [
            [
                'لتقديم الخدمة: تخزين الفواتير، وحساب الأرصدة والتقارير، وإرسال التذكيرات التي تضبطها؛',
                'لإدارة تجربتك واشتراكك وتحصيل المدفوعات وإرسال رسائل الخدمة إليك (مثل إشعار انتهاء التجربة)؛',
                'للحفاظ على أمان الخدمة ومنع إساءة استخدامها وإصلاح المشكلات؛',
                'للوفاء بالتزاماتنا القانونية والضريبية.',
            ],
            'لا نبيع البيانات الشخصية، ولا نستخدم بيانات عملائك في تسويقنا الخاص.',
        ]],
        ['مشاركة البيانات', [
            'نشارك البيانات فقط مع مزوّدين يساعدوننا في تشغيل الخدمة، بموجب عقود تحميها:',
            [
                'Stripe — للمدفوعات بالبطاقة؛',
                'مزوّد إرسال البريد الإلكتروني — لإرسال التذكيرات ورسائل الخدمة؛',
                'مزوّد الاستضافة — لتخزين التطبيق وقاعدة البيانات.',
            ],
            'تُفتح تذكيرات واتساب على جهازك أنت عبر روابط wa.me، ولا نرسل بيانات عملائك إلى واتساب بأنفسنا. وقد نفصح عن البيانات إذا اقتضى ذلك القانون الإماراتي أو طلبته جهة مختصة.',
        ]],
        ['النقل خارج الدولة', [
            'قد يعالج بعض المزوّدين (مثل Stripe) البيانات خارج دولة الإمارات، وعندها نعتمد على صور النقل التي يجيزها القانون الإماراتي وعلى مزوّدين يوفّرون ضمانات ملائمة.',
        ]],
        ['مدة الاحتفاظ بالبيانات', [
            'نحتفظ ببيانات الحساب طوال وجوده. وإذا انتهت تجربتك أو اشتراكك ولم تعد، نحتفظ ببياناتك 90 يوماً لتتمكن من الاستئناف، ثم يجوز لنا حذفها. ونحتفظ بسجلات الفوترة طوال المدة التي يفرضها قانون الضرائب، وبسجلات الأمان حتى 12 شهراً.',
            'يمكنك أن تطلب حذف حسابك وبياناتك قبل ذلك.',
        ]],
        ['الأمان', [
            'بيانات كل مشترك معزولة عن بيانات المشتركين الآخرين، وتُخزَّن كلمات المرور مشفّرة، والاتصالات مشفّرة (HTTPS)، ويقتصر وصول فريقنا على ما يلزم لتشغيل الخدمة ودعمها.',
        ]],
        ['حقوقك', [
            "يحق لك طلب الاطلاع على بياناتك الشخصية أو تصحيحها أو حذفها أو الحصول على نسخة منها، أو الاعتراض على معالجتها أو تقييدها، بمراسلتنا على {$email}. وعلى عملائك توجيه طلباتهم المتعلقة ببياناتهم إلى منشأتك، وسنساعدك في الرد عليها.",
            'كما يحق لك تقديم شكوى إلى مكتب الإمارات للبيانات.',
        ]],
        ['الأطفال', [
            'الخدمة موجهة للمنشآت وليست مخصصة لمن هم دون 18 عاماً.',
        ]],
        ['تعديل هذه السياسة', [
            'ننشر أي تحديث في هذه الصفحة مع تاريخه، ونُخطر مالكي الحسابات بالبريد عند أي تغيير جوهري.',
        ]],
        ['التواصل معنا', [
            "{$entity} — {$email}",
        ]],
    ];
@endphp
<x-legal-page :title="__('سياسة الخصوصية')" updated="2026-10-27" :sections="$sections" />
