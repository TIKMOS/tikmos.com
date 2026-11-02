# Prompt 01 — Architecture et données

Tu travailles dans Tikmos (Laravel 13.17, PHP 8.3+, Spatie Settings, Livewire/Volt). Lis `docs/audits/about-admin-audit.md`, `docs/04-conventions-architecture.md` et `docs/05-base-de-donnees.md`.

Le module existant est `App\Settings\AboutSettings` avec `image`, une migration Spatie `database/settings/2026_11_01_000000_create_about_settings.php`, une vue publique `resources/views/public/about.blade.php`, une vue admin Volt `resources/views/livewire/pages/admin/reglages/about.blade.php` et `MediaService`. La vue publique contient le texte initial faisant autorité et quatre valeurs. Garde le chemin `/a-propos` et la photo existante.

Étends AboutSettings pour les textes simples : titre/sous-titre, mentions professionnelles, alt image, LinkedIn, titres/kickers des sections, visibilité, CTA et méta About. Les paragraphes et valeurs répétés ne doivent pas être un JSON opaque : ajoute des modèles/tables `AboutParagraph` et `AboutValue`, ordre explicite, état visible et clés stables. Précharge exactement les paragraphes et quatre valeurs actuels; migration compatible et idempotente, sans écraser les réglages déjà présents. Conserve le contenu image. Documente toute décision de rollback qui toucherait au contenu saisi par l'admin.

Ajoute des tests isolés sur les données initiales, l'idempotence et la conservation de l'image. Aucun contenu inventé, aucune migration sur la base habituelle. Termine avec les chemins et commandes réellement exécutées.
