{{-- Link to the free payment reminder generator, shown on the blog --}}
<a href="{{ route('tools.reminder') }}" {{ $attributes->merge(['class' => 'flex items-center gap-4 rounded-2xl border border-brand-200 bg-brand-50 p-5 transition hover:border-brand-500 dark:border-brand-800 dark:bg-brand-500/10']) }}>
    <span class="grid size-12 shrink-0 place-items-center rounded-xl bg-brand-600 text-white"><x-icon name="bolt" class="size-6" /></span>
    <span class="min-w-0">
        <span class="block font-bold text-ink">{{ __('أداة مجانية: مولّد رسائل تذكير بالدفع') }}</span>
        <span class="mt-0.5 block text-sm text-slate-600">{{ __('اكتب اسم العميل والمبلغ، واحصل على رسالة جاهزة بالعربية أو الإنجليزية خلال ثوانٍ.') }}</span>
    </span>
    <span class="ms-auto shrink-0 font-semibold text-brand-700">{{ __('جرّبها') }} <span class="ltr:hidden">←</span><span class="rtl:hidden">→</span></span>
</a>
