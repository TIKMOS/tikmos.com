@props(['variant' => 'secondary'])

@php
    $variants = [
        'secondary' => 'border border-tk-300 text-content hover:border-ink hover:bg-tk-50',
        'accent' => 'bg-ink text-paper hover:bg-tk-800',
        'ghost' => 'text-content hover:bg-tk-100',
    ];
    $classes = 'inline-flex items-center justify-center gap-2 rounded-btn px-4 min-h-[44px] text-sm font-medium transition-colors '.($variants[$variant] ?? $variants['secondary']);
@endphp

<button
    x-data="{ deferred: null, show: false }"
    x-init="
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferred = e;
            show = ! window.matchMedia('(display-mode: standalone)').matches;
        });
        window.addEventListener('appinstalled', () => show = false);
    "
    x-show="show"
    x-cloak
    type="button"
    @click="if (deferred) { deferred.prompt(); deferred.userChoice.finally(() => { show = false; deferred = null; }); }"
    {{ $attributes->merge(['class' => $classes]) }}
>
    <x-lucide-download class="h-[18px] w-[18px]" />
    Installer l'app
</button>
