@props([
    'items' => [], // itérable d'éléments exposant ->question et ->answer (HTML)
])

{{-- Accordéon accessible : un seul panneau ouvert à la fois. --}}
<div x-data="{ open: null }" {{ $attributes }}>
    @foreach ($items as $item)
        @php($i = $loop->index)
        <div class="border-b border-line">
            <h3>
                <button type="button" @click="open === {{ $i }} ? open = null : open = {{ $i }}"
                        class="flex w-full items-center justify-between gap-4 py-5 text-left"
                        :aria-expanded="open === {{ $i }} ? 'true' : 'false'">
                    <span class="font-display text-lg font-bold text-content">{{ $item->question }}</span>
                    <x-lucide-plus class="h-5 w-5 shrink-0 text-gold-600 transition-transform duration-300" x-bind:class="open === {{ $i }} && 'rotate-45'" />
                </button>
            </h3>
            <div x-show="open === {{ $i }}" x-collapse x-cloak>
                <div class="prose prose-tk pb-5 text-muted">{!! $item->answer !!}</div>
            </div>
        </div>
    @endforeach
</div>
