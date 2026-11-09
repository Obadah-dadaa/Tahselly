{{-- Google tag (Analytics / Ads), only once its ID is set in the system settings. A sign-up or demo start
     flashes "conversion", which is reported here on the next page. --}}
@php
    $tagId = trim((string) \App\Models\SystemSetting::get('google_tag_id'));
    $conversion = session('conversion');
    $adsLabel = $conversion ? trim((string) \App\Models\SystemSetting::get($conversion === 'sign_up' ? 'google_ads_signup_conversion' : 'google_ads_demo_conversion')) : '';
@endphp
@if($tagId && ! \App\Support\Edition::isBeta())
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ urlencode($tagId) }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', @json($tagId));
        @if($conversion)
            gtag('event', @json($conversion));
            @if($adsLabel)
                gtag('event', 'conversion', { send_to: @json($adsLabel) });
            @endif
        @endif
    </script>
@endif
