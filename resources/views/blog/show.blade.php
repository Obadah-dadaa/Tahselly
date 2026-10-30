@php
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'BlogPosting',
        'headline' => $post['title'],
        'description' => $post['description'],
        'datePublished' => $post['date'],
        'inLanguage' => app()->getLocale(),
        'mainEntityOfPage' => route('blog.show', $post['slug']).'?lang='.app()->getLocale(),
        'image' => asset('og-image.png'),
        'author' => ['@type' => 'Person', 'name' => 'Obadah Dadaa', 'url' => route('contact')],
        'publisher' => ['@type' => 'Organization', 'name' => 'Tahselly', 'logo' => ['@type' => 'ImageObject', 'url' => asset('icon-512.png')]],
    ];
@endphp
<x-layouts.guest :title="$post['title']" :description="$post['description']">
    <x-slot:head>
        <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    </x-slot:head>

    <article class="mx-auto max-w-3xl">
        <a href="{{ route('blog.index') }}" class="text-sm font-semibold text-brand-700 hover:underline">→ {{ __('المدوّنة') }}</a>
        <h1 class="mt-4 text-3xl font-bold leading-tight text-ink sm:text-4xl">{{ $post['title'] }}</h1>
        <p class="mt-4 text-sm text-slate-500">
            {{ __('عبادة دعدع') }} · <time datetime="{{ $post['date'] }}">{{ \Illuminate\Support\Carbon::parse($post['date'])->translatedFormat('j F Y') }}</time> · {{ __(':count دقائق قراءة', ['count' => $post['minutes']]) }}
        </p>

        <div class="article mt-8">{!! $post['html'] !!}</div>

        <div class="mt-12 rounded-3xl bg-night p-8 text-white sm:p-10">
            <div class="flex items-center gap-3"><x-brand-mark variant="plain" class="size-10 text-white" /><span class="text-xl font-bold">{{ __(\App\Models\SystemSetting::get('app_name', 'تحصيلي')) }}</span></div>
            <p class="mt-4 text-2xl font-bold leading-snug">{{ __('دع النظام يتابع فواتيرك ويذكّر عملاءك بالدفع، بالبريد وواتساب.') }}</p>
            <p class="mt-2 text-slate-300">@beta{{ __('جرّب النسخة التجريبية مجاناً، بدون بطاقة.') }}@else{{ __('جرّبه مجاناً 14 يوماً، بدون بطاقة. من 99 درهم شهرياً بعدها.') }}@endbeta</p>
            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('trial.create') }}" class="btn-primary bg-brand-500 px-6 py-3 text-base hover:bg-brand-600">{{ __('ابدأ مجاناً') }}</a>
                <a href="{{ route('landing') }}#@beta{{ 'interest' }}@else{{ 'pricing' }}@endbeta" class="rounded-xl px-6 py-3 font-semibold text-white ring-1 ring-white/25 hover:bg-white/10">@beta{{ __('مهتم بالاشتراك؟') }}@else{{ __('الأسعار') }}@endbeta</a>
            </div>
        </div>

        @if($more->count())
            <div class="mt-14">
                <h2 class="text-xl font-bold text-ink">{{ __('مقالات أخرى') }}</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-3">
                    @foreach($more as $other)
                        <a href="{{ route('blog.show', $other['slug']) }}" class="card p-5 hover:shadow-lg">
                            <p class="font-semibold leading-snug text-ink">{{ $other['title'] }}</p>
                            <p class="mt-2 text-xs text-slate-500">{{ __(':count دقائق قراءة', ['count' => $other['minutes']]) }}</p>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </article>
</x-layouts.guest>
