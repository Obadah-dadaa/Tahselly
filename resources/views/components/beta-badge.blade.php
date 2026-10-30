{{-- Small "BETA" tag raised beside the product name (beta site only). tone: dark grounds (landing, sidebar) or light. --}}
@props(['tone' => 'light'])
@beta
    <span {{ $attributes->merge(['class' => 'self-start -ms-1 mt-0.5 rounded px-1 py-px text-[9px] font-bold leading-none tracking-wider '.($tone === 'dark'
        ? 'bg-brand-500/20 text-brand-200 ring-1 ring-inset ring-brand-400/40'
        : 'bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-200')]) }}>BETA</span>
@endbeta
