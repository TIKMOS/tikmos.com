@php
    $doc = \App\Models\LegalDocument::where('key', 'cookies')->first();
    abort_unless($doc && $doc->is_published, 404);
    $parsed = $doc->parsed();
@endphp

<x-layouts.public>
    <x-slot:head>
        <x-tk.seo :title="$doc->title" description="Politique cookies de Tikmos : catégories, finalités et gestion de votre consentement." />
    </x-slot:head>

    <x-tk.legal :title="$doc->title" :updated="$doc->updated_at->translatedFormat('F Y')" :toc="$parsed['toc']" :body="$parsed['html']">
        Vous pouvez à tout moment revoir vos choix via « Gérer les cookies » en bas de page.
    </x-tk.legal>
</x-layouts.public>
