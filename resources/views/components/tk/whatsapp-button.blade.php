@props([
    'phone' => null,        // ex. 2290166386417 (sinon : réglage GeneralSettings)
    'message' => null,
    'floating' => true,
])

@php
    // Source unique de vérité : GeneralSettings.whatsapp_number (fallback config).
    $phone ??= app(\App\Settings\GeneralSettings::class)->whatsapp_number ?: config('tikmos.whatsapp');
    $message ??= 'Bonjour '.config('app.name').',';
    $href = 'https://wa.me/'.preg_replace('/\D/', '', $phone).'?text='.rawurlencode($message);
    // FAB : surtout PAS de pb-safe ici (padding interne = env(safe-area-inset-bottom) qui
    // varie au scroll sur iOS et décale l'icône). transform-gpu stabilise le rendu fixed.
    $float = $floating
        ? 'fixed bottom-24 right-4 z-40 h-14 w-14 rounded-full md:bottom-6 shadow-pop transform-gpu'
        : 'min-h-[50px] px-5 rounded-btn';
@endphp

<a href="{{ $href }}" target="_blank" rel="noopener"
   aria-label="Contacter {{ config('app.name') }} sur WhatsApp"
   {{ $attributes->merge(['class' => 'inline-flex items-center justify-center gap-2 bg-[#25D366] font-medium text-white transition-transform hover:scale-[1.04] '.$float]) }}>
    {{-- Vraie icône WhatsApp (glyphe officiel) --}}
    <svg viewBox="0 0 24 24" class="{{ $floating ? 'h-7 w-7' : 'h-5 w-5' }}" fill="currentColor" aria-hidden="true">
        <path d="M.057 24l1.687-6.163a11.867 11.867 0 0 1-1.587-5.946C.16 5.335 5.495 0 12.05 0a11.821 11.821 0 0 1 8.413 3.488 11.824 11.824 0 0 1 3.48 8.414c-.003 6.557-5.338 11.892-11.893 11.892a11.9 11.9 0 0 1-5.688-1.448L.057 24zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01a1.092 1.092 0 0 0-.792.372c-.272.297-1.04 1.016-1.04 2.479s1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
    </svg>
    @unless ($floating)<span>WhatsApp</span>@endunless
</a>
