@php
    $entity = \App\Models\SystemSetting::get('legal_entity_name', 'Tahselly');
    $email = \App\Models\SystemSetting::get('support_email');
    $days = (int) \App\Models\SystemSetting::get('default_trial_days', 14);
    $app = __(\App\Models\SystemSetting::get('app_name', 'تحصيلي'));

    $sections = app()->getLocale() === 'en' ? [
        ['Definitions', [
            "“Service” means the {$app} invoice follow-up and payment reminder platform, its website and dashboards. “We” or “us” means {$entity}. “Subscriber” or “you” means the business that creates an account. “Your clients” are the people and companies whose invoices you manage in the Service.",
        ]],
        ['Accepting these terms', [
            'By creating an account, starting a free trial or paying for a subscription, you agree to these terms on behalf of your business. If you do not agree, do not use the Service.',
        ]],
        ['The Service', [
            'The Service lets you import or enter invoices, track due dates and balances, send payment reminders to your clients by email and WhatsApp, record payments and view reports such as receivables aging.',
            'Reminders are a follow-up tool. The Service does not collect money on your behalf and does not guarantee that any client will pay.',
        ]],
        ['Your account and users', [
            'You must provide accurate information and keep your password secret. You are responsible for everything done under your account, including by users you add (owners and managers).',
            'The number of users and invoices you may use depends on your plan.',
        ]],
        ['Free trial', [
            "New accounts may start a free trial of {$days} days without a payment card. The trial is free for this period only.",
            'When the trial ends, access to your account and automatic reminders stop until you choose a plan and pay for it. We do not charge you automatically at the end of the trial.',
            'We will email you before the trial ends. Your data is kept so you can continue where you left off if you subscribe (see “Data retention” in the Privacy Policy).',
            'We may limit trials to one per business and may refuse or end a trial that is being misused.',
        ]],
        ['Plans, prices and payment', [
            'Prices are shown in UAE dirhams (AED) on our pricing page for monthly, quarterly and yearly periods. Value added tax (VAT) is added where it applies.',
            'You can pay by card through our payment processor Stripe, or by bank transfer. A subscription period starts once payment is confirmed. We do not store your card details.',
            'Subscriptions do not renew automatically. Before your period ends you can renew or change plan from the billing page. When a period ends without renewal, access stops until you pay again.',
            'We may change prices for future periods with at least 30 days’ notice. Changes never affect a period you have already paid for.',
        ]],
        ['Cancellation and refunds', [
            'You can stop using the Service at any time by not renewing. Amounts paid for a period that has started are not refundable, except where UAE law requires otherwise or if we end the Service for a reason that is not your fault, in which case we refund the unused part of the period.',
        ]],
        ['Acceptable use', [
            'You must use the Service lawfully. In particular, you agree that:',
            [
                'you have a genuine business relationship with each client you send reminders to, and the right to contact them for payment;',
                'the invoices and amounts you enter are accurate and actually owed to you;',
                'you will not use the Service to send spam, threats, harassment, misleading or defamatory messages;',
                'you will comply with UAE laws on electronic communications, consumer protection and debt collection;',
                'you will not try to access other subscribers’ data, overload or attack the Service, or resell it without our written consent.',
            ],
        ]],
        ['Your data', [
            'You own the data you put into the Service, including your clients’ details and invoices. You give us permission to store and process it only to provide the Service to you, as described in the Privacy Policy.',
            'For your clients’ personal data you are the controller and we act as your processor. You are responsible for having a lawful basis to give us that data and to contact your clients.',
            'You can export your aging report to Excel at any time, and you can ask us for a copy of your data.',
        ]],
        ['Availability and support', [
            'We work to keep the Service available and secure, but we do not promise it will be uninterrupted or error-free. We may carry out maintenance, preferably outside UAE business hours.',
            "Support is available at {$email}.",
        ]],
        ['Limitation of liability', [
            'To the extent permitted by law, we are not liable for indirect or consequential losses, lost profits, or for unpaid invoices or reminders that your clients do not receive or act on.',
            'Our total liability for any claim relating to the Service is limited to the amount you paid us in the 12 months before the claim.',
        ]],
        ['Suspension and termination', [
            'We may suspend or close an account that breaches these terms, puts other users or the Service at risk, or is used for unlawful purposes. Where reasonable we will warn you first and give you a chance to fix the problem.',
        ]],
        ['Changes to these terms', [
            'We may update these terms. We will publish the new version on this page with its date and, for important changes, notify account owners by email at least 14 days before they apply.',
        ]],
        ['Governing law', [
            'These terms are governed by the laws of the United Arab Emirates as applied in the Emirate of Dubai. The courts of Dubai have jurisdiction over any dispute, unless the law requires otherwise.',
        ]],
        ['Contact us', [
            "{$entity} — {$email}",
        ]],
    ] : [
        ['التعريفات', [
            "«الخدمة» هي منصة {$app} لمتابعة الفواتير وتذكير العملاء بالدفع، بما فيها الموقع ولوحات التحكم. و«نحن» تعني {$entity}. و«المشترك» أو «أنت» تعني المنشأة التي تُنشئ حساباً. و«عملاؤك» هم الأشخاص والشركات الذين تُدير فواتيرهم داخل الخدمة.",
        ]],
        ['قبول الشروط', [
            'بإنشائك حساباً أو بدئك تجربةً مجانية أو دفعك لاشتراك، فإنك توافق على هذه الشروط نيابةً عن منشأتك. إن لم توافق عليها فلا تستخدم الخدمة.',
        ]],
        ['الخدمة', [
            'تتيح لك الخدمة استيراد الفواتير أو إدخالها، ومتابعة مواعيد استحقاقها وأرصدتها، وإرسال تذكيرات الدفع لعملائك بالبريد الإلكتروني وواتساب، وتسجيل الدفعات، والاطلاع على التقارير مثل تقرير أعمار الديون.',
            'التذكيرات أداة متابعة فقط؛ فالخدمة لا تُحصِّل الأموال نيابةً عنك، ولا تضمن أن يدفع أي عميل.',
        ]],
        ['الحساب والمستخدمون', [
            'يجب أن تقدّم بيانات صحيحة وأن تحافظ على سرية كلمة المرور. وأنت مسؤول عن كل ما يجري في حسابك، بما في ذلك ما يقوم به المستخدمون الذين تضيفهم (المالكون والمديرون).',
            'يتحدد عدد المستخدمين والفواتير المسموح بها بحسب باقتك.',
        ]],
        ['التجربة المجانية', [
            "يمكن للحسابات الجديدة بدء تجربة مجانية لمدة {$days} يوماً دون بطاقة دفع، وهي مجانية لهذه المدة فقط.",
            'عند انتهاء التجربة يتوقف الوصول إلى حسابك وتتوقف التذكيرات الآلية إلى أن تختار باقة وتدفع اشتراكها. ولا نخصم منك أي مبلغ تلقائياً عند انتهاء التجربة.',
            'سنراسلك بالبريد الإلكتروني قبل انتهاء التجربة. وتبقى بياناتك محفوظة لتكمل من حيث توقفت إذا اشتركت (انظر «مدة الاحتفاظ بالبيانات» في سياسة الخصوصية).',
            'يحق لنا قصر التجربة على مرة واحدة لكل منشأة، ورفض أي تجربة أو إنهاؤها عند إساءة استخدامها.',
        ]],
        ['الباقات والأسعار والدفع', [
            'تُعرض الأسعار بالدرهم الإماراتي في صفحة الأسعار للمدد الشهرية والربع سنوية والسنوية، وتُضاف ضريبة القيمة المضافة حيثما تنطبق.',
            'يمكنك الدفع بالبطاقة عبر مزوّد الدفع Stripe أو بالتحويل البنكي، وتبدأ مدة الاشتراك بعد تأكيد الدفع. ولا نحتفظ ببيانات بطاقتك.',
            'لا تتجدد الاشتراكات تلقائياً؛ يمكنك قبل انتهاء مدتك التجديد أو تغيير الباقة من صفحة الاشتراك. وإذا انتهت المدة دون تجديد يتوقف الوصول حتى تدفع من جديد.',
            'يجوز لنا تعديل الأسعار للمدد المستقبلية بإشعار مسبق لا يقل عن 30 يوماً، ولا يسري أي تعديل على مدة دفعتَ ثمنها.',
        ]],
        ['الإلغاء والاسترداد', [
            'يمكنك التوقف عن استخدام الخدمة في أي وقت بعدم التجديد. ولا تُسترد المبالغ المدفوعة عن مدة بدأت، إلا إذا أوجب القانون الإماراتي غير ذلك، أو إذا أنهينا الخدمة لسبب لا يعود إليك، فنردّ عندها قيمة الجزء غير المستخدم من المدة.',
        ]],
        ['الاستخدام المقبول', [
            'يجب استخدام الخدمة بصورة مشروعة، وتوافق تحديداً على ما يلي:',
            [
                'أن تربطك علاقة تجارية حقيقية بكل عميل ترسل إليه تذكيراً، وأن يحق لك مراسلته بشأن الدفع؛',
                'أن تكون الفواتير والمبالغ التي تدخلها صحيحة ومستحقة لك فعلاً؛',
                'ألا تستخدم الخدمة لإرسال رسائل مزعجة أو تهديد أو مضايقة أو رسائل مضللة أو مسيئة؛',
                'أن تلتزم بقوانين دولة الإمارات الخاصة بالاتصالات الإلكترونية وحماية المستهلك وتحصيل الديون؛',
                'ألا تحاول الوصول إلى بيانات مشتركين آخرين، أو إرهاق الخدمة أو مهاجمتها، أو إعادة بيعها دون موافقتنا الكتابية.',
            ],
        ]],
        ['بياناتك', [
            'البيانات التي تُدخلها في الخدمة ملكٌ لك، بما فيها بيانات عملائك وفواتيرهم. وتمنحنا الإذن بتخزينها ومعالجتها لغرض تقديم الخدمة لك فقط، على النحو المبيّن في سياسة الخصوصية.',
            'فيما يخص البيانات الشخصية لعملائك، فأنت المتحكّم بها ونحن نعالجها نيابةً عنك، وأنت مسؤول عن وجود أساس قانوني لتزويدنا بها ولمراسلة عملائك.',
            'يمكنك تصدير تقرير أعمار الديون إلى Excel في أي وقت، كما يمكنك طلب نسخة من بياناتك.',
        ]],
        ['التوفر والدعم', [
            'نعمل على إبقاء الخدمة متاحة وآمنة، لكننا لا نتعهد بأن تعمل دون انقطاع أو خطأ. وقد نُجري أعمال صيانة، ويُفضَّل أن تكون خارج ساعات العمل في الإمارات.',
            "الدعم متاح عبر البريد: {$email}.",
        ]],
        ['حدود المسؤولية', [
            'في الحدود التي يسمح بها القانون، لا نتحمل المسؤولية عن الأضرار غير المباشرة أو التبعية أو فوات الأرباح، ولا عن الفواتير غير المدفوعة أو التذكيرات التي لا يستلمها عملاؤك أو لا يستجيبون لها.',
            'تقتصر مسؤوليتنا الإجمالية عن أي مطالبة تتعلق بالخدمة على المبلغ الذي دفعته لنا خلال الأشهر الاثني عشر السابقة للمطالبة.',
        ]],
        ['التعليق والإنهاء', [
            'يجوز لنا تعليق أي حساب أو إغلاقه إذا خالف هذه الشروط، أو عرّض المستخدمين الآخرين أو الخدمة للخطر، أو استُخدم لأغراض غير مشروعة، ونُنبّهك أولاً متى كان ذلك معقولاً لتتمكن من تصحيح المشكلة.',
        ]],
        ['تعديل الشروط', [
            'قد نُحدّث هذه الشروط، وننشر النسخة الجديدة في هذه الصفحة مع تاريخها، ونُخطر مالكي الحسابات بالبريد قبل 14 يوماً على الأقل من سريان أي تغيير جوهري.',
        ]],
        ['القانون الواجب التطبيق', [
            'تخضع هذه الشروط لقوانين دولة الإمارات العربية المتحدة كما تُطبَّق في إمارة دبي، وتختص محاكم دبي بالنظر في أي نزاع، ما لم يقضِ القانون بغير ذلك.',
        ]],
        ['التواصل معنا', [
            "{$entity} — {$email}",
        ]],
    ];
@endphp
<x-legal-page :title="__('شروط الاستخدام')" updated="2026-10-27" :sections="$sections" />
