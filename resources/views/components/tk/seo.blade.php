@props([
    'title' => null,
    'description' => null,
    'image' => null,
    'type' => 'website',
    'canonical' => null,
])

@php
    $seoSettings = app(\App\Settings\SeoSettings::class);
    $siteName = config('app.name', 'Tikmos');
    $description ??= $seoSettings->default_description;
    $fullTitle = $title ? $title.' — '.$siteName : $seoSettings->default_title;
    $canonical = $canonical ?? url()->current();
    $image = $image
        ?? ($seoSettings->og_image ? \Illuminate\Support\Facades\Storage::disk('public')->url($seoSettings->og_image) : asset('icons/og-default.png'));
@endphp

<title>{{ $fullTitle }}</title>
<meta name="description" content="{{ $description }}">
<link rel="canonical" href="{{ $canonical }}">

<meta property="og:type" content="{{ $type }}">
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:title" content="{{ $fullTitle }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:image" content="{{ $image }}">
<meta property="og:locale" content="fr_FR">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $fullTitle }}">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:image" content="{{ $image }}">

{{ $slot ?? '' }}
