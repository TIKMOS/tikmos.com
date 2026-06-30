@props([
    'variant' => 'line', // line | card | timeline
    'lines' => 3,
])

@if ($variant === 'card')
    <div {{ $attributes->merge(['class' => 'rounded-card border border-line bg-surface p-6']) }}>
        <div class="tk-shimmer h-4 w-1/3 rounded bg-tk-100"></div>
        <div class="tk-shimmer mt-4 h-8 w-2/3 rounded bg-tk-100"></div>
        <div class="tk-shimmer mt-3 h-3 w-full rounded bg-tk-100"></div>
    </div>
@elseif ($variant === 'timeline')
    <div {{ $attributes->merge(['class' => 'space-y-5']) }}>
        @for ($i = 0; $i < $lines; $i++)
            <div class="flex gap-4">
                <div class="tk-shimmer h-8 w-8 shrink-0 rounded-tile bg-tk-100"></div>
                <div class="flex-1 space-y-2">
                    <div class="tk-shimmer h-3 w-1/2 rounded bg-tk-100"></div>
                    <div class="tk-shimmer h-3 w-1/4 rounded bg-tk-100"></div>
                </div>
            </div>
        @endfor
    </div>
@else
    <div {{ $attributes->merge(['class' => 'space-y-2.5']) }}>
        @for ($i = 0; $i < $lines; $i++)
            <div class="tk-shimmer h-3 rounded bg-tk-100" style="width: {{ [100, 85, 70, 92][$i % 4] }}%"></div>
        @endfor
    </div>
@endif
