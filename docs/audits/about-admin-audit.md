# Audit — administration de la page À propos

Date : 2026-11-01. Dépôt observé sur la branche `codex/about-page-management`.

## Architecture constatée

Tikmos utilise Laravel 13.17, PHP 8.3+, Livewire 3 / Volt, Blade, Tailwind CSS 4 et Alpine.js. Spatie Permission assure le RBAC; Spatie Laravel Settings stocke les réglages; Media Library sert les médias rattachés aux modèles, tandis que les images de réglages passent par `MediaService` sur le disque `public`. Routes dans `routes/web.php` et `routes/admin.php`; pages interactives en Volt single-file sous `resources/views/livewire/pages/admin/`. Tests Pest, Pint et Larastan. Le workflow GitHub Actions `ci.yml` valide le dépôt et construit une archive de livraison.

## Fichiers inspectés et état existant

- `composer.json`, `composer.lock`, `package.json`, `config/settings.php` : stack, versions et outils.
- `routes/web.php`, `routes/admin.php` : `/a-propos` est une vue Blade; `/admin/reglages/a-propos` est protégée par auth, compte approuvé, rôle de gestion et `cms.pages.manage`.
- `resources/views/public/about.blade.php` : textes du hero, parcours, quatre valeurs, CTA, lien LinkedIn et JSON-LD sont codés en dur; seule l’image lit AboutSettings.
- `app/Settings/AboutSettings.php`, `database/settings/2026_11_01_000000_create_about_settings.php`, `app/Providers/AppServiceProvider.php` : persistance et chargement de `about.image`.
- `resources/views/livewire/pages/admin/reglages/about.blade.php`, `resources/views/livewire/pages/admin/reglages.blade.php` : éditeur de photo seul; entrée existante sous Réglages.
- `app/Services/MediaService.php` : stockage et suppression des images sur disque public.
- `resources/views/components/tk/seo.blade.php`, `app/Settings/SeoSettings.php` : composant commun aux métadonnées; il accepte title, description, image et canonical.
- `config/permissions.php`, `database/seeders/RolePermissionSeeder.php` : permission CMS déjà présente et attribuée aux rôles appropriés.
- `resources/views/livewire/pages/admin/reglages/pages.blade.php`, `app/Models/LegalDocument.php` : les documents légaux ont publication/version; About n'a pas de workflow de brouillon.
- `tests/Feature/Admin/AboutImageSettingsTest.php`, `tests/Feature/Admin/LegalDocsTest.php`, `tests/Feature/Settings/SeoFeatureSettingsTest.php` : tests existants pertinents.

## Écart, risques et stratégie

Le module existant ne gère que la photo. Les textes, paragraphes, quatre valeurs, CTA, lien LinkedIn et métadonnées spécifiques restent fixes. Le SEO global existe et doit être réutilisé via `x-tk.seo`; le JSON-LD reste un schéma fixe. Le workflow légal ne s'applique pas à About; l'enregistrement sera explicitement une publication immédiate. Compléter le Volt et les réglages existants, sans seconde interface ni nouvel éditeur HTML. Utiliser AboutSettings pour les champs simples et deux tables relationnelles pour les paragraphes et valeurs ordonnés/visibles. Les migrations initialisent les textes exacts actuellement servis et ne remplacent pas de réglages existants. La permission reste `cms.pages.manage`, contrôlée également aux actions mutantes. Tout texte reste échappé; seules les URL internes à chemin absolu ou HTTPS sont admises. Les uploads demeurent publics et nécessitent `storage:link` et un stockage persistant en production.

## Validation locale et limites

- Tests About image + gestion finale : 9 tests, 50 assertions, réussis. Tests voisins admin/réglages/SEO : 28 tests, 107 assertions, réussis.
- Suite complète exécutée avant les derniers ajustements de test/associativité : 603 tests, 602 passés, 1 échec dans le test préexistant `MediaTest` d'upload produit. L'échec indique que le fichier temporaire Livewire n'est pas lisible selon le `FileinfoMimeTypeGuesser` sous PHP Windows; le test isolé reproduit la même erreur. Ce résultat n'est pas présenté comme une suite verte.
- Pint `--test` sur les classes/migrations/tests PHP du module : conforme. Syntaxe PHP vérifiée. Build Vite réussi; il produit `app-Db5vrdc2.css` et son manifeste correspondant. Le build doit rester hors des suites Pest.
- Larastan a été lancé mais le processus local retourne le code 1 sans diagnostic affiché; analyse statique non validée.
- Test d'administration/SEO exécuté avec bootstrap PHPUnit temporaire sans changement de `phpunit.xml`; le bootstrap standard est rejeté par la fonction `is_readable` de ce PHP Windows, bien que le fichier soit lisible via les autres commandes. Les tests utilisent SQLite en mémoire, cache/session array et mail array.
- `git fetch origin` a échoué avec `getaddrinfo thread failed to start`. L'état distant actuel n'a donc pas pu être actualisé ni vérifié dans cette session; aucun commit ni push n'a été effectué.

La configuration de production, le stockage hébergeur, MySQL, SMTP, cache partagé et contenus stockés hors du dépôt n'ont pas été examinés. Aucune migration sur la base habituelle, modification de compte ou déploiement n'a été effectué.
