# Gestion de la page À propos

## Objectif et fonctionnement

L'URL publique reste `/a-propos`. Le panneau d'administration est `/admin/reglages/a-propos`, accessible depuis Réglages → À propos aux comptes autorisés par `cms.pages.manage`. Les modifications sont enregistrées et publiées immédiatement; cette page n'a pas de brouillon ni d'historique de version. Il est recommandé d'ouvrir l'aperçu public dans un autre onglet avant et après une sauvegarde.

## Architecture

- `AboutSettings` (groupe Spatie `about`) conserve les champs simples, les options de visibilité et les chemins d'image.
- `AboutParagraph` stocke les paragraphes de parcours, avec clé stable et ordre.
- `AboutValue` stocke les valeurs, leur icône sous liste blanche, leur ordre et leur visibilité. Masquer conserve le contenu et permet de le réactiver.
- `resources/views/livewire/pages/admin/reglages/about.blade.php` est l'unique formulaire administratif.
- `resources/views/public/about.blade.php` consomme les réglages; `x-tk.seo` produit les méta balises partagées.
- Le contenu est échappé par Blade. L'éditeur ne permet pas d'HTML libre. Les CTA n'acceptent que des chemins internes à barre unique ou des destinations HTTPS.

Les migrations initialisent les textes déjà présents dans la vue et les quatre valeurs originales avec `insertOrIgnore`; elles n'écrasent pas le réglage photo antérieur. La migration des tables n'a pas de rollback destructif : sauvegarder le contenu de `about_paragraphs`, `about_values` et des réglages avant une évolution/suppression manuelle.

## Médias

La photo principale et l'image Open Graph facultative utilisent `MediaService`, les types PNG/JPEG/WebP et la limite de 2 Mo, sur le disque public `storage/app/public`. La photo de l'accueil/about reste servie par l'image configurée; sans photo, `/img/about-illustration.svg` sert de fallback. L'image Open Graph About remplace seulement le fallback global pour cette page. Les nouvelles images sont enregistrées avant suppression des anciennes afin qu'un échec de persistance conserve le média actuellement utilisé.

Production doit conserver `storage/app/public` entre versions et exposer `public/storage` au moyen de `php artisan storage:link`. Vérifier que PHP peut écrire dans le stockage et que le lien symbolique pointe vers le stockage persistant attendu par l'hébergeur. Une copie du dépôt ou un artifact GitHub ne contient pas les fichiers uploadés par les admins.

## Référencement

Le panneau admin gère le meta title, la description et une image Open Graph facultative. Le canonical reste l'URL absolue de `/a-propos`; la valeur `Person` du JSON-LD est sérialisée par PHP avec encodage des caractères HTML et garde un schéma fixe. Le JSON-LD et les autres pages continuent d'utiliser le système commun `x-tk.seo`.

## Tests et maintenance

Les tests `tests/Feature/Admin/AboutImageSettingsTest.php` et `tests/Feature/Admin/AboutPageManagementTest.php` vérifient image, valeurs initiales, persistance éditoriale, visibilité, URL dangereuse, échappement, SEO et accès. La validation locale du 2026-11-01 est consignée dans `docs/audits/about-admin-audit.md`; la suite Pest complète garde un échec d'environnement existant lié à la lecture de fichiers temporaires Livewire sous PHP Windows. Pour des environnements existants, sauvegarder DB et `storage/app/public`, appliquer les migrations Laravel et Spatie Settings, puis vérifier la page admin et les fichiers publics.

## Limites

Il n'existe pas de brouillon, aperçu non publié, historique/restauration de version ou journal éditorial dédié à la page. Les paragraphes supprimés par l'enregistrement sont retirés; les valeurs sont masquables et restaurables. MySQL de production, concurrence/cache distribué, permissions du stockage et comportement de l'hébergement doivent être testés sur l'environnement de déploiement. Ce dépôt n'exécute pas lui-même la migration ni le déploiement de production.
