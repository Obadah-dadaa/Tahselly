<x-layouts.guest :title="__('المدوّنة')" :description="__('مقالات عملية عن تحصيل الفواتير المتأخرة، ورسائل تذكير العملاء بالدفع، وإدارة الذمم المدينة للشركات في الإمارات.')">
    <div class="mx-auto max-w-5xl">
        <div class="text-center">
            <p class="text-sm font-semibold text-brand-700">{{ __('المدوّنة') }}</p>
            <h1 class="mt-2 text-3xl font-bold text-ink sm:text-4xl">{{ __('كيف تتقاضى أموالك في وقتها') }}</h1>
            <p class="mx-auto mt-3 max-w-2xl text-slate-600">{{ __('مقالات عملية عن تحصيل الفواتير المتأخرة، ورسائل تذكير العملاء بالدفع، وإدارة الذمم المدينة للشركات في الإمارات.') }}</p>
        </div>

        <x-tool-banner class="mt-8" />

        <div class="mt-8 grid gap-6 md:grid-cols-2">
            @forelse($posts as $i => $post)
                <a href="{{ route('blog.show', $post['slug']) }}" class="card group flex flex-col p-7 transition hover:-translate-y-0.5 hover:shadow-lg {{ $i === 0 ? 'md:col-span-2' : '' }}">
                    <p class="text-sm text-slate-500"><time datetime="{{ $post['date'] }}">{{ \Illuminate\Support\Carbon::parse($post['date'])->translatedFormat('j F Y') }}</time> · {{ __(':count دقائق قراءة', ['count' => $post['minutes']]) }}</p>
                    <h2 class="mt-3 font-bold text-ink group-hover:text-brand-700 {{ $i === 0 ? 'text-3xl' : 'text-xl' }}">{{ $post['title'] }}</h2>
                    <p class="mt-3 flex-1 leading-relaxed text-slate-600">{{ $post['description'] }}</p>
                    <span class="mt-5 text-sm font-semibold text-brand-700">{{ __('اقرأ المقال') }} ←</span>
                </a>
            @empty
                <p class="text-center text-slate-500 md:col-span-2">{{ __('لا توجد مقالات بعد.') }}</p>
            @endforelse
        </div>
    </div>
</x-layouts.guest>
