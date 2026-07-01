@props([
    'media' => null,          // Spatie\MediaLibrary\...\Media|null
    'sizes' => '100vw',       // indice `sizes` pour le navigateur
    'priority' => false,      // true pour l'image LCP (hero) → eager + fetchpriority high
    'alt' => null,            // override du texte alternatif (sinon custom property `alt`)
    'width' => null,
    'height' => null,
    'fallback' => true,       // afficher un cadre neutre si pas de média
])

@php
    use App\Media\HasStandardImages;

    $m = $media;
    $blurUp = rescue(fn () => app(\App\Settings\MediaSettings::class)->blur_up, true, false);

    // Construit le srcset à partir des conversions générées (WebP).
    $srcset = [];
    $src = null;
    $lqip = null;

    if ($m) {
        foreach (HasStandardImages::$standardImageWidths as $name => $w) {
            if ($m->hasGeneratedConversion($name)) {
                $url = $m->getUrl($name);
                $srcset[] = $url.' '.$w.'w';
                if ($name === 'card') {
                    $src = $url;
                }
                if ($name === 'thumb') {
                    $lqip = $url;
                }
            }
        }
        // Repli : original si aucune conversion générée.
        $src ??= $srcset ? explode(' ', $srcset[array_key_last($srcset)])[0] : $m->getUrl();
        $altText = $alt ?? $m->getCustomProperty('alt') ?? $m->name;
    }
@endphp

@if ($m)
    <img
        src="{{ $src }}"
        @if ($srcset) srcset="{{ implode(', ', $srcset) }}" sizes="{{ $sizes }}" @endif
        alt="{{ $altText }}"
        @if ($width) width="{{ $width }}" @endif
        @if ($height) height="{{ $height }}" @endif
        decoding="{{ $priority ? 'auto' : 'async' }}"
        loading="{{ $priority ? 'eager' : 'lazy' }}"
        @if ($priority) fetchpriority="high" @endif
        @if ($blurUp && $lqip)
            x-data="{ loaded: false }"
            x-on:load="loaded = true"
            :class="loaded ? 'opacity-100' : 'opacity-0'"
            style="background-image:url('{{ $lqip }}');background-size:cover;background-position:center"
        @endif
        {{ $attributes->merge(['class' => 'transition-opacity duration-500']) }}
    >
@elseif ($fallback)
    <div {{ $attributes->merge(['class' => 'grid place-items-center bg-canvas text-tk-300']) }}>
        <x-lucide-image class="h-8 w-8" />
    </div>
@endif
