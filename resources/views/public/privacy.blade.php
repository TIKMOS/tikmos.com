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
        <x-slot:notice>
            <x-tk.card>
                <h2 class="mb-3 font-display text-xl font-bold text-content">Fermeture du compte et données conservées</h2>
                <x-tk.account-closure-info />
            </x-tk.card>
        </x-slot:notice>
        Document de base — à compléter avec votre conseil juridique avant mise en production.
    </x-tk.legal>
</x-layouts.public>
