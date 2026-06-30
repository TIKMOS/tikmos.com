@props([
    'steps' => [], // [['label'=>, 'date'=>, 'location'=>, 'state'=>'done|current|todo'], ...]
])

<ol {{ $attributes->merge(['class' => 'relative space-y-0']) }}>
    @foreach ($steps as $i => $step)
        @php
            $state = $step['state'] ?? 'todo';
            $isLast = $loop->last;
            $dot = match ($state) {
                'done' => 'bg-ink text-paper',
                'current' => 'bg-ink text-white ring-4 ring-ink/10',
                default => 'bg-tk-100 text-tk-400',
            };
            $line = $state === 'todo' ? 'bg-tk-200' : 'bg-ink';
            $labelColor = $state === 'todo' ? 'text-tk-400' : 'text-content';
        @endphp
        <li class="relative flex gap-4 pb-6 last:pb-0">
            @unless ($isLast)
                <span class="absolute left-[15px] top-8 h-[calc(100%-1rem)] w-0.5 {{ $line }}"></span>
            @endunless
            <span class="relative z-10 grid h-8 w-8 shrink-0 place-items-center rounded-tile {{ $dot }}">
                @if ($state === 'done')
                    <x-lucide-check class="h-4 w-4" />
                @elseif ($state === 'current')
                    <span class="h-2 w-2 rounded-full bg-white"></span>
                @else
                    <span class="h-2 w-2 rounded-full bg-tk-400"></span>
                @endif
            </span>
            <div class="-mt-0.5 flex-1">
                <p class="font-display text-[15px] font-bold {{ $labelColor }}">{{ $step['label'] ?? '' }}</p>
                <div class="mt-0.5 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-xs text-muted">
                    @if (! empty($step['date']))<span class="font-mono">{{ $step['date'] }}</span>@endif
                    @if (! empty($step['location']))<span>· {{ $step['location'] }}</span>@endif
                </div>
            </div>
        </li>
    @endforeach
</ol>
