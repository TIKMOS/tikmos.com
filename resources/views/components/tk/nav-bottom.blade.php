@props([
    'items' => [], // [['label'=>, 'icon'=>, 'route'=>, 'active'=>bool, 'fab'=>bool], ...]
])

<nav class="fixed inset-x-0 bottom-0 z-40 border-t border-line bg-ink pb-safe md:hidden" aria-label="Navigation principale">
    <ul class="flex items-stretch justify-around px-1">
        @foreach ($items as $item)
            @php
                $active = $item['active'] ?? false;
                $fab = $item['fab'] ?? false;
                $href = isset($item['route']) ? (\Illuminate\Support\Facades\Route::has($item['route']) ? route($item['route']) : ($item['url'] ?? '#')) : ($item['url'] ?? '#');
            @endphp
            <li class="flex-1">
                <a href="{{ $href }}" wire:navigate
                   class="flex min-h-11 flex-col items-center gap-1 py-2.5 text-[10px] font-medium focus-visible:outline-paper {{ $active ? 'text-white' : 'text-tk-400' }}"
                   @if ($active) aria-current="page" @endif>
                    @if ($fab)
                        <span class="-mt-5 grid h-12 w-12 place-items-center rounded-tile bg-ink text-white shadow-pop">
                            <x-dynamic-component :component="'lucide-'.$item['icon']" class="h-6 w-6" />
                        </span>
                        <span class="sr-only">{{ $item['label'] }}</span>
                    @else
                        <x-dynamic-component :component="'lucide-'.$item['icon']" class="h-[22px] w-[22px]" />
                        <span>{{ $item['label'] }}</span>
                    @endif
                </a>
            </li>
        @endforeach
    </ul>
</nav>
