# Prompt 02 — Interface d'administration

Dans Tikmos, lis l'audit About puis complète seulement la page Volt existante `resources/views/livewire/pages/admin/reglages/about.blade.php`, son entrée de réglages et sa route `admin.reglages.about`. Réutilise les composants `x-tk.*`, `MediaService` et les modèles `AboutSettings`, `AboutParagraph`, `AboutValue` du prompt 01.

Gère les champs d'introduction, photo et alt, lien LinkedIn, paragraphes répétables (ajout/suppression/ordre), valeurs (ajout/modification/ordre/masquage-restauration), visibilité des sections, CTA et SEO. Pas de HTML libre. Indique que l'enregistrement publie immédiatement, puisqu'aucun workflow brouillon About n'existe. Préserve l'image actuelle tant qu'un nouveau fichier n'est pas enregistré; restaurer l'illustration existante lors de suppression explicite.

Valide toutes les actions côté serveur, y compris Livewire direct. URL permises : chemin local commençant par une barre unique ou HTTPS seulement. Refuse `javascript:`, `data:`, `//host` et les schémas inconnus. Icônes sous liste blanche. Upload image selon `MediaService` et validation MIME/taille existantes. Erreur de validation ne doit pas effacer le contenu courant. Teste navigation clavier, mobile/bureau, états d'erreur et permission. Rapport final avec fichiers et tests.
