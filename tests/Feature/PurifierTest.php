<?php

declare(strict_types=1);

/**
 * Le contenu riche (CMS, fiches, FAQ) est purifié via mews/purifier (helper clean())
 * AVANT stockage. On vérifie que le HTML malveillant est neutralisé et le HTML légitime conservé.
 */
it('neutralise le HTML malveillant et conserve le HTML légitime', function (): void {
    $dirty = '<p>Bonjour <strong>Tikmos</strong></p>'
        .'<script>alert("xss")</script>'
        .'<a href="javascript:alert(1)">lien</a>'
        .'<img src=x onerror="alert(1)">';

    $clean = clean($dirty);

    expect($clean)
        ->toContain('<strong>Tikmos</strong>')   // contenu légitime conservé
        ->not->toContain('<script')               // script supprimé
        ->not->toContain('onerror')               // handler inline supprimé
        ->not->toContain('javascript:');          // URI dangereuse supprimée
});
