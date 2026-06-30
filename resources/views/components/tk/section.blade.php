@props([
    'tone' => 'canvas', // canvas | paper | ink
    'container' => true,
    'id' => null,
])

@php
    $tones = [
        'canvas' => 'bg-canvas text-content',
        'paper' => 'bg-surface text-content',
        'ink' => 'bg-ink text-paper',
    ];
    $classes = 'py-16 md:py-24 '.($tones[$tone] ?? $tones['canvas']);
@endphp

<section @if ($id) id="{{ $id }}" @endif {{ $attributes->merge(['class' => $classes]) }}>
    @if ($container)
        <div class="mx-auto w-full max-w-6xl px-5 sm:px-6 lg:px-8">
            {{ $slot }}
        </div>
    @else
        {{ $slot }}
    @endif
</section>
