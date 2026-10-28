<x-layouts.app :title="__('رسائل واتساب جاهزة')">
    <x-page-header :title="__('رسائل واتساب جاهزة')" :subtitle="__('اضغط على كل زر لفتح المحادثة والرسالة مكتوبة — فقط اضغط إرسال في واتساب.')">
        <x-slot:actions><a href="{{ route('reminders.index', ['tab' => 'log']) }}" class="btn-secondary">{{ __('انتهيت') }}</a></x-slot:actions>
    </x-page-header>
    <div class="card divide-y divide-slate-100" x-data="{ done: [] }">
        @foreach($links as $i => $row)
            <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                <div>
                    <p class="font-semibold text-ink">{{ $row['invoice']->client->name }}</p>
                    <p class="text-sm text-slate-500">{{ $row['invoice']->number }} · @money($row['invoice']->balance()) {{ $row['invoice']->currency }}</p>
                </div>
                @if($row['url'])
                    <a href="{{ $row['url'] }}" target="_blank" @click="done.push({{ $i }})" class="btn-whatsapp" :class="done.includes({{ $i }}) && 'opacity-60'">
                        <x-icon name="whatsapp" class="size-4" />
                        <span x-text="done.includes({{ $i }}) ? @js(__('تم الفتح ✓')) : @js(__('افتح واتساب'))"></span>
                    </a>
                @else
                    <span class="text-sm text-rose-600">{{ __('لا يوجد رقم هاتف للعميل') }}</span>
                @endif
            </div>
        @endforeach
    </div>
</x-layouts.app>
