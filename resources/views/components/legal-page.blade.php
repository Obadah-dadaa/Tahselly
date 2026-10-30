@props(['title', 'updated', 'sections'])
{{-- Shared layout for legal pages: table of contents + numbered sections. Content is a DRAFT until reviewed by a UAE lawyer. --}}
<x-layouts.guest :title="$title">
    @if(auth()->user()?->isPlatformStaff())
        <div class="mb-6 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ __('ملاحظة للإدارة فقط: هذا النص مسودة يجب مراجعتها مع مستشار قانوني قبل الإطلاق. يظهر هذا التنبيه لحسابات المنصة فقط.') }}</div>
    @endif
    <div class="grid gap-10 lg:grid-cols-4">
        <aside class="hidden lg:block">
            <nav class="sticky top-6 space-y-1 text-sm">
                <p class="mb-3 font-semibold text-ink">{{ __('المحتويات') }}</p>
                @foreach($sections as $i => [$heading])
                    <a href="#s{{ $i + 1 }}" class="block rounded-lg px-3 py-1.5 text-slate-600 hover:bg-slate-100 hover:text-brand-700">{{ $i + 1 }}. {{ $heading }}</a>
                @endforeach
            </nav>
        </aside>
        <article class="card p-8 lg:col-span-3 lg:p-12">
            <h1 class="text-3xl font-bold text-ink">{{ $title }}</h1>
            <p class="mt-2 text-sm text-slate-500">{{ __('آخر تحديث: :date', ['date' => $updated]) }}</p>
            <div class="mt-8 space-y-10 leading-loose text-slate-700">
                @foreach($sections as $i => [$heading, $paragraphs])
                    <section id="s{{ $i + 1 }}" class="scroll-mt-6">
                        <h2 class="text-xl font-bold text-ink">{{ $i + 1 }}. {{ $heading }}</h2>
                        @foreach($paragraphs as $paragraph)
                            @if(is_array($paragraph))
                                <ul class="mt-3 list-disc space-y-1.5 ps-6">@foreach($paragraph as $item)<li>{{ $item }}</li>@endforeach</ul>
                            @else
                                <p class="mt-3">{{ $paragraph }}</p>
                            @endif
                        @endforeach
                    </section>
                @endforeach
            </div>
            <div class="mt-12 flex flex-wrap gap-3 border-t border-slate-100 pt-6 text-sm">
                <a href="{{ route('legal.terms') }}" class="font-semibold text-brand-700 hover:underline">{{ __('شروط الاستخدام') }}</a>
                <span class="text-slate-300">·</span>
                <a href="{{ route('legal.privacy') }}" class="font-semibold text-brand-700 hover:underline">{{ __('سياسة الخصوصية') }}</a>
                <span class="text-slate-300">·</span>
                <a href="{{ route('contact') }}" class="font-semibold text-brand-700 hover:underline">{{ __('تواصل معنا') }}</a>
                <span class="text-slate-300">·</span>
                <a href="{{ route('blog.index') }}" class="font-semibold text-brand-700 hover:underline">{{ __('المدوّنة') }}</a>
                <span class="text-slate-300">·</span>
                <a href="{{ route('landing') }}" class="text-slate-500 hover:underline">{{ __('العودة للرئيسية') }}</a>
            </div>
        </article>
    </div>
</x-layouts.guest>
