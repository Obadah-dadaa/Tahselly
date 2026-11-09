@php
    $appName = __(\App\Models\SystemSetting::get('app_name', __('تحصيلي')));
    $locale = app()->getLocale();
    $plans = \App\Models\Plan::query()->where('is_active', true)->ordered()->get();
    $beta = \App\Support\Edition::isBeta();
    // The beta plays its own promo cut (no prices) and skips the tutorial (it walks through billing)
    $video = $beta ? 'videos/tahselly-promo-beta-'.($locale === 'en' ? 'en' : 'ar').'.mp4' : \App\Models\SystemSetting::get($locale === 'en' ? 'promo_video_en' : 'promo_video_ar');
    $tutorial = $beta ? null : \App\Models\SystemSetting::get('tutorial_video');
    $trialOn = (bool) \App\Models\SystemSetting::get('allow_signups', true);
    // Posters and videos keep their file names when replaced; the file's time in the URL makes browsers fetch the new one
    $setupWhatsapp = \App\Support\SupportWhatsapp::url(__('مرحباً، أريد أن تجهّزوا لي حساب تحصيلي مجاناً. سأرسل لكم ملف الفواتير.'));
    $fresh = fn (string $path) => asset($path).'?v='.(@filemtime(public_path($path)) ?: 1);
    $trialDays = $beta ? \App\Support\Edition::limit('trial_days') : (int) \App\Models\SystemSetting::get('default_trial_days', 14);
    // Each plan adds features on top of the cheaper ones; every card lists them all, ticked or not
    $allFeatures = $plans->flatMap(fn ($p) => $p->localizedFeatures())->unique()->values();
    $planData = $plans->values()->map(function ($p, $i) use ($plans, $allFeatures) {
        $included = $plans->take($i + 1)->flatMap(fn ($q) => $q->localizedFeatures());

        return [
            'code' => $p->code, 'name' => $p->localizedName(), 'audience' => $p->audience(), 'featured' => $p->is_featured,
            'monthly' => (float) $p->price,
            'totals' => ['monthly' => $p->priceFor('monthly'), 'quarterly' => $p->priceFor('quarterly'), 'yearly' => $p->priceFor('yearly')],
            'users' => $p->max_users == 1 ? __('مستخدم واحد') : __('حتى :count مستخدمين', ['count' => $p->max_users]),
            'invoices' => __('حتى :count فاتورة مفتوحة', ['count' => number_format($p->max_invoices)]),
            'features' => $allFeatures->map(fn ($f) => ['text' => $f, 'on' => $included->contains($f)])->all(),
        ];
    });
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}" class="{{ $theme === 'dark' ? 'dark' : '' }} scroll-smooth">
<head>
    <meta charset="utf-8">
    <x-favicons />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('تحصيلي | نظام متابعة الفواتير وتذكير العملاء بالدفع في الإمارات') }}</title>
    <x-seo :title="__('تحصيلي | نظام متابعة الفواتير وتذكير العملاء بالدفع في الإمارات')" :schema="true" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-surface font-sans text-slate-800 antialiased" x-data="{ player: null }" @keydown.escape.window="player = null">
<header class="absolute inset-x-0 top-0 z-10">
    <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-6 py-5">
        <a href="/" class="flex items-center gap-2 text-white"><x-brand-mark class="size-9" /><span class="text-xl font-bold">{{ $appName }}</span><x-beta-badge tone="dark" /></a>
        <nav class="hidden gap-7 whitespace-nowrap text-sm text-slate-300 lg:flex">
            <a href="#video" class="hover:text-white">{{ __('شاهد العرض') }}</a>
            <a href="#features" class="hover:text-white">{{ __('المزايا') }}</a>
            <a href="#how" class="hover:text-white">{{ __('كيف يعمل') }}</a>
            @beta<a href="#interest" class="hover:text-white">{{ __('اطلب الاشتراك') }}</a>@else<a href="#pricing" class="hover:text-white">{{ __('الأسعار') }}</a>@endbeta
            <a href="{{ route('blog.index') }}" class="hover:text-white">{{ __('المدوّنة') }}</a>
            <a href="{{ route('contact') }}" class="hover:text-white">{{ __('تواصل معنا') }}</a>
        </nav>
        <div class="flex items-center gap-2">
            <x-preference-switches tone="onDark" />
            <a href="{{ route('login') }}" class="hidden whitespace-nowrap rounded-xl bg-white/10 px-4 py-2 text-sm font-semibold text-white ring-1 ring-white/20 hover:bg-white/20 sm:block">{{ __('تسجيل الدخول') }}</a>
            @if($trialOn)<a href="{{ route('trial.create') }}" class="hidden whitespace-nowrap rounded-xl bg-brand-500 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600 md:block">{{ __('ابدأ مجاناً') }}</a>@endif
        </div>
    </div>
</header>

<section id="hero" class="relative overflow-hidden bg-night pb-28 pt-36 text-white">
    <div class="absolute -end-40 top-0 size-[40rem] rounded-full bg-brand-600/25 blur-3xl"></div>
    <div class="absolute -bottom-40 -start-20 size-[30rem] rounded-full bg-sky-500/15 blur-3xl"></div>
    <div class="relative mx-auto grid max-w-6xl items-center gap-14 px-6 lg:grid-cols-2">
        <div>
            <span class="inline-flex rounded-full bg-brand-500/15 px-3 py-1 text-sm text-brand-200 ring-1 ring-brand-500/30">{{ __('لوكالات التسويق · شركات الخدمات · المستقلين · مكاتب المحاسبة') }}</span>
            <h1 class="mt-6 text-4xl font-bold leading-[1.25] sm:text-5xl">{{ __('فواتيرك غير المدفوعة') }}<br><span class="text-brand-400">{{ __('تُتابَع تلقائياً.') }}</span></h1>
            <p class="mt-6 max-w-lg text-lg leading-relaxed text-slate-300">{{ __(':app يستورد فواتيرك من Excel، يرسل تذكيرات ذكية لعملائك قبل وبعد الاستحقاق عبر البريد وواتساب، ويعرض لك المستحق والمتأخر وأعمار الديون في لوحة واحدة.', ['app' => $appName]) }}</p>
            <div class="mt-8 flex flex-wrap gap-3">
                @if($trialOn)
                    <a href="{{ route('trial.create') }}" class="btn-primary bg-brand-500 px-6 py-3 text-base hover:bg-brand-600">{{ __('جرّب مجاناً :days يوماً', ['days' => $trialDays]) }}</a>
                @else
                    <a href="#@beta{{ 'interest' }}@else{{ 'pricing' }}@endbeta" class="btn-primary bg-brand-500 px-6 py-3 text-base hover:bg-brand-600">@beta{{ __('مهتم بالاشتراك؟') }}@else{{ __('اختر باقتك') }}@endbeta</a>
                @endif
                @guest
                    {{-- Instant demo: straight into a sample account, no form --}}
                    <form method="POST" action="{{ route('demo.start') }}" class="contents">
                        @csrf
                        <button class="btn bg-white px-6 py-3 text-base font-semibold text-night hover:bg-slate-100">
                            <x-icon name="bolt" class="size-5 text-brand-600" /> {{ __('جرّب النظام الآن — بدون تسجيل') }}
                        </button>
                        <x-recaptcha action="demo" class="order-last w-full" />
                    </form>
                @endguest
                @if($video)
                    <button @click="player = 'promo'" class="btn px-6 py-3 text-base text-white ring-1 ring-white/20 hover:bg-white/10">
                        <span class="grid size-7 place-items-center rounded-full bg-white text-night"><svg class="size-3.5 translate-x-px" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></span>
                        {{ __('شاهد الفيديو (دقيقة)') }}
                    </button>
                @endif
            </div>
            @if($trialOn)
                @beta
                    <p class="mt-4 text-sm text-slate-400">{{ __('نسخة تجريبية مجانية لمدة :days يوماً · بدون بطاقة · بحدود استخدام للتجربة.', ['days' => $trialDays]) }}</p>
                @else
                    <p class="mt-4 text-sm text-slate-400">{{ __('بدون بطاقة ائتمان · مجاناً لمدة :days يوماً فقط، ثم اشتراك مدفوع من :price درهم شهرياً للاستمرار.', ['days' => $trialDays, 'price' => (int) $plans->min('price')]) }}</p>
                @endbeta
            @endif
        </div>
        <div class="rounded-3xl bg-white/5 p-3 ring-1 ring-white/10 backdrop-blur">
            <div class="rounded-2xl bg-surface p-5 text-slate-800 shadow-2xl">
                <div class="grid grid-cols-2 gap-3">
                    @foreach([[__('إجمالي المستحق'), '332,990', 'text-ink'], [__('متأخرات'), '166,040', 'text-rose-600'], [__('يستحق هذا الأسبوع'), '69,050', 'text-amber-600'], [__('محصّل هذا الشهر'), '201,010', 'text-brand-700']] as [$l, $v, $c])
                        <div class="rounded-xl bg-slate-50 p-3"><p class="text-xs text-slate-500">{{ $l }}</p><p class="num mt-1 text-xl font-bold {{ $c }}">{{ $v }} <span class="text-xs text-slate-400">AED</span></p></div>
                    @endforeach
                </div>
                <div class="mt-4 space-y-2 text-sm">
                    @foreach([['Oud Al Sharq Perfumes', '6,150', 123], ['Marina Dental Clinic', '9,880', 119], ['Pearl Beauty Lounge', '16,200', 70]] as [$n, $a, $d])
                        <div class="flex items-center justify-between rounded-xl border border-slate-100 px-3 py-2"><span class="font-medium">{{ $n }}</span><span class="flex items-center gap-2"><b class="num">{{ $a }}</b><span class="rounded-full bg-rose-50 px-2 py-0.5 text-xs font-semibold text-rose-700">{{ __(':count يوم', ['count' => $d]) }}</span></span></div>
                    @endforeach
                </div>
                <div class="mt-4 flex items-center gap-2 rounded-xl bg-brand-50 px-3 py-2 text-xs text-brand-800"><x-icon name="bolt" class="size-4" /> {{ __('التذكيرات الآلية تُرسل كل يوم الساعة 9:00 صباحاً') }}</div>
            </div>
        </div>
    </div>
</section>

{{-- Promo video showcase --}}
@if($video)
<section id="video" class="relative -mt-16 px-6">
    <div class="mx-auto max-w-5xl">
        <button @click="player = 'promo'" class="group relative block w-full overflow-hidden rounded-[2rem] shadow-[0_40px_80px_-20px_rgba(2,6,23,.45)] ring-1 ring-black/5">
            <img src="{{ $fresh('videos/poster-'.($beta ? 'beta-' : '').($locale === 'en' ? 'en' : 'ar').'.jpg') }}" alt="{{ __('فيديو تعريفي') }}" class="aspect-video w-full object-cover transition duration-700 group-hover:scale-[1.03]">
            <span class="absolute inset-0 bg-gradient-to-t from-night/80 via-night/10 to-transparent"></span>
            <span class="absolute inset-0 grid place-items-center">
                <span class="relative grid size-24 place-items-center rounded-full bg-white/95 text-brand-700 shadow-2xl transition group-hover:scale-110">
                    <span class="absolute inset-0 animate-ping rounded-full bg-white/40"></span>
                    <svg class="relative size-10 translate-x-0.5" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                </span>
            </span>
            <span class="absolute inset-x-0 bottom-0 flex items-end justify-between p-6 text-start text-white sm:p-8">
                <span><span class="block text-sm text-brand-200">{{ __('فيديو تعريفي · دقيقة واحدة') }}</span><span class="mt-1 block text-2xl font-bold sm:text-3xl">{{ __('شاهد كيف يحصّل :app أموالك', ['app' => $appName]) }}</span></span>
            </span>
        </button>
        @if($tutorial)
            <button @click="player = 'tutorial'" class="mt-4 flex w-full items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-surface py-4 text-sm font-semibold text-ink hover:border-brand-500">
                <x-icon name="doc" class="size-5 text-brand-700" /> {{ __('جديد على النظام؟ شاهد شرح الاستخدام خطوة بخطوة') }}
            </button>
        @endif
    </div>
</section>

<div x-show="player" x-cloak x-transition.opacity class="fixed inset-0 z-50 grid place-items-center bg-night/90 p-4 backdrop-blur-sm" @click.self="player = null">
    <div class="relative w-full max-w-5xl">
        <button @click="player = null" class="absolute -top-12 end-0 grid size-10 place-items-center rounded-full bg-white/10 text-white hover:bg-white/20" aria-label="{{ __('إغلاق') }}">✕</button>
        <template x-if="player === 'promo'">
            <video src="{{ $fresh($video) }}" controls autoplay playsinline class="aspect-video w-full rounded-2xl bg-black shadow-2xl"></video>
        </template>
        @if($tutorial)
            <template x-if="player === 'tutorial'">
                <video src="{{ $fresh($tutorial) }}" controls autoplay playsinline class="aspect-video w-full rounded-2xl bg-black shadow-2xl"></video>
            </template>
        @endif
    </div>
</div>
@endif

<section id="features" class="mx-auto max-w-6xl px-6 py-24">
    <h2 class="text-center text-3xl font-bold text-ink">{{ __('كل ما تحتاجه لتحصيل أموالك في وقتها') }}</h2>
    <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
        @foreach([
            ['upload', __('استيراد Excel'), __('ارفع ملف فواتيرك كما هو — يتعرف على الأعمدة بالعربية والإنجليزية وينشئ العملاء تلقائياً.')],
            ['clock', __('مواعيد الاستحقاق'), __('تابع ما يستحق اليوم، هذا الأسبوع، وما تأخر — بفلاتر واضحة لكل فاتورة.')],
            ['bell', __('تذكيرات آلية + سجل كامل'), __('قبل الاستحقاق، يومه، وبعده بـ 7 و14 و30 يوم، وإنذار نهائي. كل رسالة محفوظة في السجل.')],
            ['chart', __('تقارير أعمار الديون'), __('تقسيم الذمم إلى 0-30، 31-60، 61-90، +90 يوم لكل عميل مع تصدير Excel بنقرة.')],
        ] as [$icon, $title, $text])
            <div class="rounded-2xl border border-slate-100 bg-surface p-6 shadow-sm">
                <span class="grid size-12 place-items-center rounded-xl bg-brand-50 text-brand-700"><x-icon :name="$icon" class="size-6" /></span>
                <h3 class="mt-5 font-bold text-ink">{{ $title }}</h3>
                <p class="mt-2 text-sm leading-relaxed text-slate-500">{{ $text }}</p>
            </div>
        @endforeach
    </div>
</section>

<section id="how" class="bg-slate-50 py-24">
    <div class="mx-auto max-w-6xl px-6">
        <h2 class="text-center text-3xl font-bold text-ink">{{ __('ثلاث خطوات فقط') }}</h2>
        <div class="mt-14 grid gap-8 md:grid-cols-3">
            @foreach([['1', __('ارفع فواتيرك'), __('من Excel أو أدخلها يدوياً في ثوانٍ.')], ['2', __('اضبط الجدول'), __('حدد متى وكيف يتم تذكير العملاء، وخصص نص الرسائل.')], ['3', __('استلم أموالك'), __('النظام يتابع يومياً وأنت تراقب التحصيل من لوحة المالك.')]] as [$n, $t, $d])
                <div class="text-center"><span class="mx-auto grid size-14 place-items-center rounded-full bg-night text-xl font-bold text-white">{{ $n }}</span><h3 class="mt-5 text-lg font-bold text-ink">{{ $t }}</h3><p class="mt-2 text-slate-500">{{ $d }}</p></div>
            @endforeach
        </div>
    </div>
</section>

{{-- Done-for-you setup: the visitor sends their Excel on WhatsApp and we set the account up --}}
<section id="setup" class="mx-auto max-w-6xl px-6 py-24">
    <div class="relative overflow-hidden rounded-[2rem] bg-night px-6 py-12 text-white sm:px-12">
        <div class="absolute -end-24 -top-24 size-80 rounded-full bg-brand-600/30 blur-3xl"></div>
        <div class="relative grid items-center gap-10 lg:grid-cols-2">
            <div>
                <span class="inline-flex rounded-full bg-emerald-400/15 px-3 py-1 text-sm font-bold text-emerald-300 ring-1 ring-emerald-400/30">{{ __('مجاناً') }}</span>
                <h2 class="mt-5 text-3xl font-bold leading-snug">{{ __('ليس لديك وقت؟ نجهّز النظام لك بأنفسنا') }}</h2>
                <p class="mt-4 leading-relaxed text-slate-300">{{ __('أرسل لنا ملف Excel بفواتيرك المفتوحة بأي شكل كان. نستورد الفواتير، نضبط مواعيد التذكير ونصوص الرسائل، ونسلّمك الحساب جاهزاً خلال يوم عمل واحد — دون أي تكلفة.') }}</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ $setupWhatsapp ?? route('contact') }}" @if($setupWhatsapp) target="_blank" rel="noopener" @endif class="btn bg-[#25D366] px-6 py-3 text-base font-bold text-white hover:bg-[#1ebe5b]">
                        <x-icon name="whatsapp" class="size-5" /> {{ __('أرسل ملفك على واتساب') }}
                    </a>
                    @guest
                        <a href="#hero" class="btn px-6 py-3 text-base text-white ring-1 ring-white/20 hover:bg-white/10">{{ __('أو جرّب الديمو أولاً') }}</a>
                    @endguest
                </div>
            </div>
            <ol class="space-y-4">
                @foreach([
                    ['1', __('أرسل الملف'), __('ملف Excel أو PDF أو حتى صورة لكشف الحساب — كما هو عندك.')],
                    ['2', __('نجهّز حسابك'), __('نستورد العملاء والفواتير ونضبط التذكيرات بالعربية والإنجليزية.')],
                    ['3', __('تبدأ التحصيل'), __('تصل أول التذكيرات لعملائك من صباح اليوم التالي، وأنت تتابع من لوحة واحدة.')],
                ] as [$n, $t, $d])
                    <li class="flex gap-4 rounded-2xl bg-white/5 p-5 ring-1 ring-white/10">
                        <span class="grid size-10 shrink-0 place-items-center rounded-full bg-brand-500 font-bold">{{ $n }}</span>
                        <span><span class="block font-bold">{{ $t }}</span><span class="mt-1 block text-sm text-slate-300">{{ $d }}</span></span>
                    </li>
                @endforeach
            </ol>
        </div>
    </div>
</section>

@beta
{{-- Beta: no prices or plans; visitors who want to subscribe leave their details --}}
<section id="interest" class="mx-auto max-w-5xl px-6 py-24">
    <div class="grid items-start gap-10 lg:grid-cols-5">
        <div class="lg:col-span-2">
            <span class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">BETA</span>
            <h2 class="mt-4 text-3xl font-bold text-ink">{{ __('مهتم بالاشتراك؟') }}</h2>
            <p class="mt-4 leading-relaxed text-slate-600">{{ __('هذه نسخة تجريبية مجانية للتعرّف على النظام، بحدود استخدام. إذا أردت استخدامه لشركتك دون حدود، اترك بياناتك وسنتواصل معك لتفعيل حسابك.') }}</p>
            <ul class="mt-6 space-y-2 text-sm text-slate-600">
                <li class="flex items-center gap-2"><x-icon name="check" class="size-4 text-brand-600" /> {{ __('حتى :count فاتورة و:clients عميل في التجربة', ['count' => \App\Support\Edition::limit('max_invoices'), 'clients' => \App\Support\Edition::limit('max_clients')]) }}</li>
                <li class="flex items-center gap-2"><x-icon name="check" class="size-4 text-brand-600" /> {{ __('كل المزايا مفتوحة، بدون بطاقة') }}</li>
                <li class="flex items-center gap-2"><x-icon name="check" class="size-4 text-brand-600" /> {{ __('نرد خلال يوم عمل') }}</li>
            </ul>
        </div>
        <div class="card p-6 lg:col-span-3 lg:p-8">
            <x-interest-form />
        </div>
    </div>
</section>
@else
{{-- Pricing: one ladder of plans, cheapest first; every card shows all features so the differences are visible --}}
<section id="pricing" class="mx-auto max-w-7xl px-6 py-24"
         x-data="{ cycle: 'monthly', plans: @js($planData),
                   months: { monthly: 1, quarterly: 3, yearly: 12 },
                   billed: { monthly: @js(__('يُدفع شهرياً')), quarterly: @js(__('يُدفع :amount درهم كل 3 أشهر')), yearly: @js(__('يُدفع :amount درهم سنوياً')) },
                   saveLabel: @js(__('وفّر :percent%')),
                   perMonth(p) { return Math.round(p.totals[this.cycle] / this.months[this.cycle]) },
                   saving(p) { return Math.round((1 - p.totals[this.cycle] / (p.monthly * this.months[this.cycle])) * 100) },
                   fmt(n) { return Number(n).toLocaleString('en-US') } }">
    <h2 class="text-center text-3xl font-bold text-ink">{{ __('باقة تناسب نوع عملك') }}</h2>
    <p class="mt-3 text-center text-slate-500">{{ __('بدون رسوم إعداد. ادفع شهرياً أو وفّر مع الاشتراك السنوي.') }}</p>
    @if($trialOn)
        <p class="mx-auto mt-4 w-fit rounded-full bg-amber-50 px-4 py-1.5 text-center text-sm font-semibold text-amber-800">{{ __('كل الباقات: تجربة مجانية :days يوماً بدون بطاقة، ثم يتطلب الاستمرار الدفع.', ['days' => $trialDays]) }}</p>
    @endif

    <div class="mt-8 flex justify-center">
        <div class="inline-flex rounded-2xl bg-slate-100 p-1 text-sm font-semibold">
            @foreach(['monthly' => __('شهري'), 'quarterly' => __('ربع سنوي -5%'), 'yearly' => __('سنوي — شهران مجاناً')] as $c => $l)
                <button @click="cycle='{{ $c }}'" :class="cycle==='{{ $c }}' ? 'bg-surface text-ink shadow' : 'text-slate-500'" class="rounded-xl px-4 py-2 transition">{{ $l }}</button>
            @endforeach
        </div>
    </div>

    <div class="mt-14 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
        <template x-for="plan in plans" :key="plan.code">
            <div class="relative flex flex-col rounded-3xl p-6" :class="plan.featured ? 'bg-night text-white shadow-2xl ring-4 ring-brand-500/40 xl:-my-4 xl:py-10' : 'border border-slate-200 bg-surface'">
                <span x-show="plan.featured" class="absolute inset-x-0 -top-3 mx-auto w-fit whitespace-nowrap rounded-full bg-brand-500 px-4 py-1 text-xs font-bold text-white shadow">{{ __('الأكثر طلباً') }}</span>
                <h3 class="text-xl font-bold" x-text="plan.name"></h3>
                <p class="mt-1 min-h-10 text-sm" :class="plan.featured ? 'text-slate-300' : 'text-slate-500'" x-text="plan.audience"></p>

                <div class="mt-5 flex h-6 items-center gap-2 text-sm">
                    <template x-if="cycle !== 'monthly'">
                        <span class="flex items-center gap-2">
                            <s class="num text-slate-400" x-text="fmt(plan.monthly)"></s>
                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-700" x-text="saveLabel.replace(':percent', saving(plan))"></span>
                        </span>
                    </template>
                </div>
                <p class="mt-1"><span class="num text-4xl font-bold" x-text="fmt(perMonth(plan))"></span> <span class="text-sm" :class="plan.featured ? 'text-slate-400' : 'text-slate-500'">{{ __('درهم / شهرياً') }}</span></p>
                <p class="mt-1 text-xs" :class="plan.featured ? 'text-slate-400' : 'text-slate-500'" x-text="billed[cycle].replace(':amount', fmt(plan.totals[cycle]))"></p>

                <a :href="`{{ url('/subscribe') }}/${plan.code}?cycle=${cycle}`" class="mt-6 w-full" :class="plan.featured ? 'btn-primary bg-brand-500' : 'btn-secondary'">{{ __('اشترك الآن') }}</a>
                @if($trialOn)
                @endif

                <ul class="mt-6 space-y-2.5 border-t pt-6 text-sm" :class="plan.featured ? 'border-white/10' : 'border-slate-100'">
                    <li class="flex items-start gap-2 font-semibold"><x-icon name="check" class="mt-0.5 size-4 shrink-0 text-brand-500" /> <span x-text="plan.users"></span></li>
                    <li class="flex items-start gap-2 font-semibold"><x-icon name="check" class="mt-0.5 size-4 shrink-0 text-brand-500" /> <span x-text="plan.invoices"></span></li>
                    <template x-for="f in plan.features">
                        <li class="flex items-start gap-2" :class="f.on ? '' : 'opacity-40'">
                            <span x-show="f.on" class="shrink-0"><x-icon name="check" class="mt-0.5 size-4 text-brand-500" /></span>
                            <span x-show="!f.on" class="mt-0.5 grid size-4 shrink-0 place-items-center text-xs">✕</span>
                            <span :class="f.on ? '' : 'line-through'" x-text="f.text"></span>
                        </li>
                    </template>
                </ul>
            </div>
        </template>
    </div>
</section>
@endbeta

<footer class="border-t border-slate-100 py-10 text-center text-sm text-slate-500">
    © {{ date('Y') }} {{ $appName }}
    <span class="mx-2">·</span><a href="{{ route('blog.index') }}" class="hover:text-brand-700">{{ __('المدوّنة') }}</a>
    <span class="mx-2">·</span><a href="{{ route('contact') }}" class="hover:text-brand-700">{{ __('تواصل معنا') }}</a>
    <span class="mx-2">·</span><a href="{{ route('legal.terms') }}" class="hover:text-brand-700">{{ __('شروط الاستخدام') }}</a>
    <span class="mx-2">·</span><a href="{{ route('legal.privacy') }}" class="hover:text-brand-700">{{ __('سياسة الخصوصية') }}</a>
</footer>
<x-whatsapp-float />
</body>
</html>
