{{-- Search and sharing tags for public pages: description, canonical + hreflang (each language has its own ?lang= URL),
     Open Graph / Twitter card, and on the home page the structured data that tells Google what Tahselly is. --}}
@props(['description' => null, 'title' => null, 'schema' => false])
@php
    $locale = app()->getLocale();
    $appName = __(\App\Models\SystemSetting::get('app_name', 'تحصيلي'));
    $public = ! \App\Support\Edition::isBeta() && request()->routeIs('landing', 'contact', 'legal.terms', 'legal.privacy', 'trial.create', 'blog.index', 'blog.show');
    $base = url()->current();
    $description ??= __('تحصيلي يتابع فواتيرك غير المدفوعة ويذكّر عملاءك بالدفع تلقائياً عبر البريد وواتساب، قبل الاستحقاق وبعده. للشركات والوكالات والمستقلين في الإمارات. جرّبه مجاناً 14 يوماً.');
    $title ??= $appName;
@endphp
<meta name="description" content="{{ $description }}">
@if($public)
    <meta name="robots" content="index, follow, max-image-preview:large">
    <link rel="canonical" href="{{ $base }}?lang={{ $locale }}">
    <link rel="alternate" hreflang="ar" href="{{ $base }}?lang=ar">
    <link rel="alternate" hreflang="en" href="{{ $base }}?lang=en">
    <link rel="alternate" hreflang="x-default" href="{{ $base }}">
@else
    <meta name="robots" content="noindex, nofollow">
@endif
<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ $appName }}">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ $base }}?lang={{ $locale }}">
<meta property="og:image" content="{{ asset('og-image.png') }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale" content="{{ $locale === 'ar' ? 'ar_AE' : 'en_AE' }}">
<meta property="og:locale:alternate" content="{{ $locale === 'ar' ? 'en_AE' : 'ar_AE' }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $title }}">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:image" content="{{ asset('og-image.png') }}">
@if($schema && ! \App\Support\Edition::isBeta())
    @php
        $plans = \App\Models\Plan::query()->where('is_active', true)->ordered()->get();
        $graph = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization', '@id' => url('/').'#org',
                    'name' => 'Tahselly', 'alternateName' => 'تحصيلي', 'url' => url('/'),
                    'logo' => asset('icon-512.png'), 'email' => \App\Models\SystemSetting::get('support_email'),
                    'areaServed' => 'AE',
                    'contactPoint' => [['@type' => 'ContactPoint', 'contactType' => 'customer support', 'email' => \App\Models\SystemSetting::get('support_email'),
                        'telephone' => \App\Models\SystemSetting::get('support_whatsapp'), 'availableLanguage' => ['Arabic', 'English'], 'areaServed' => 'AE']],
                ],
                ['@type' => 'WebSite', '@id' => url('/').'#site', 'url' => url('/'), 'name' => 'Tahselly', 'alternateName' => 'تحصيلي', 'inLanguage' => ['ar', 'en'], 'publisher' => ['@id' => url('/').'#org']],
                [
                    '@type' => 'SoftwareApplication', 'name' => 'Tahselly', 'alternateName' => 'تحصيلي',
                    'applicationCategory' => 'BusinessApplication', 'operatingSystem' => 'Web',
                    'description' => $description, 'url' => url('/'), 'image' => asset('og-image.png'),
                    'publisher' => ['@id' => url('/').'#org'],
                    'offers' => [
                        '@type' => 'AggregateOffer', 'priceCurrency' => 'AED', 'offerCount' => $plans->count(),
                        'lowPrice' => (string) (float) $plans->min('price'), 'highPrice' => (string) (float) $plans->max('price'),
                    ],
                ],
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($graph, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endif
