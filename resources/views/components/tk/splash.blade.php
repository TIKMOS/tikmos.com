{{-- Splash de marque : flèches qui s'échangent, disparaît au chargement. Respecte reduced-motion. --}}
<div
    x-data="{ show: true }"
    x-init="window.addEventListener('load', () => setTimeout(() => show = false, 450))"
    x-show="show"
    x-transition:leave="transition ease-in duration-300"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-[100] grid place-items-center bg-ink"
    role="status" aria-label="Chargement"
>
    <svg viewBox="0 0 64 28" class="h-12 w-auto motion-safe:animate-[sz-pulse_1.2s_ease-in-out_infinite]" aria-hidden="true">
        <rect x="0" y="0" width="28" height="28" rx="1" fill="#fff" />
        <rect x="36" y="0" width="28" height="28" rx="1" fill="#fff" />
        <g fill="none" stroke="#0b0b0c" stroke-width="4">
            <path d="M20 20 L8 8" /><path d="M8 15 L8 8 L15 8" />
        </g>
        <g fill="none" stroke="#0b0b0c" stroke-width="4">
            <path d="M44 8 L56 20" /><path d="M49 20 L56 20 L56 13" />
        </g>
    </svg>
</div>

@once
    @push('styles')
        <style>
            @keyframes sz-pulse { 0%,100% { transform: scale(1); opacity: 1 } 50% { transform: scale(.92); opacity: .7 } }
        </style>
    @endpush
@endonce
