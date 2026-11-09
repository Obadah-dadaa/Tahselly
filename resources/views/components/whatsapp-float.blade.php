{{-- Floating "chat with us on WhatsApp" button for the public pages. Renders nothing until the support number is set. --}}
@php $url = \App\Support\SupportWhatsapp::url(__('مرحباً، شفت موقع تحصيلي وعندي سؤال')); @endphp
@if($url)
    <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ __('تواصل معنا عبر واتساب') }}"
       class="group fixed bottom-5 end-5 z-40 flex items-center gap-2 rounded-full bg-[#25D366] p-3.5 text-white shadow-lg shadow-green-900/20 ring-4 ring-white/60 transition hover:scale-105 hover:bg-[#1ebe5b] sm:pe-5">
        <x-icon name="whatsapp" class="size-6" />
        <span class="hidden text-sm font-bold sm:inline">{{ __('اسألنا على واتساب') }}</span>
    </a>
@endif
