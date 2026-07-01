@php
    $doc = \App\Models\LegalDocument::where('key', 'privacy')->first();
    abort_unless($doc && $doc->is_published, 404);
    $parsed = $doc->parsed();
@endphp

<x-layouts.public>
    <x-slot:head>
        <x-tk.seo :title="$doc->title" description="Politique de confidentialité et protection des données chez Tikmos." />
    </x-slot:head>

    <x-tk.legal :title="$doc->title" :updated="$doc->updated_at->translatedFormat('F Y')" :toc="$parsed['toc']" :body="$parsed['html']">
        Document de base — à compléter avec votre conseil juridique avant mise en production.
    </x-tk.legal>
</x-layouts.public>
