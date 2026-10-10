# Analyse technique avant publication GitHub

Date : 10 octobre 2026. Revue statique ciblée, pas un audit exhaustif ni une validation de production.

## Périmètre

Le projet à publier est le dossier `tikmos.com`, déjà doté de 32 commits sur `main` au début de la revue. Le dossier voisin `tikmos-chaussure` et l'archive `tikmos.zip` ne font pas partie de cette publication. Destination : https://github.com/TIKMOS/tikmos.com.

## Architecture observée

- PHP ^8.3, Laravel ^13.8, Livewire 3 et Volt ; interface Blade, Tailwind 4, Vite 8 et TipTap.
- Vitrine : services, réalisations, blog, équipe, témoignages, FAQ, contact et pages légales.
- Commerce : catalogue, panier, commandes, devis, paiement à la livraison et passerelle KKiaPay.
- Espaces client et administration ; routes protégées par authentification, approbation, rôles et permissions Spatie.
- Services métier dédiés, enums, réglages persistants, médias et journal d'activité.
- Suite Pest/PHPUnit existante couvrant notamment authentification, droits, commandes, paiements, médias et CMS.
- `public/build` est volontairement versionné pour les serveurs sans Node.

## Points prioritaires

### Haute : création des administrateurs

`database/seeders/AdminSeeder.php` crée trois comptes avec le mot de passe de repli `password` si `TIKMOS_ADMIN_PASSWORD` est absent. Tous reçoivent le rôle SuperAdmin. `firstOrCreate` ne modifie pas le mot de passe des comptes déjà existants.

Avant une mise en production : imposer un secret explicite pour la création, éviter les comptes secondaires automatiques et vérifier les comptes existants. Changer seulement la variable d'environnement puis relancer le seeder ne réinitialise pas les mots de passe déjà enregistrés.

### Haute : rattachement des transactions de paiement

La vérification serveur et le verrouillage du paiement local existent. Cependant, `PaymentService::verify()` accepte une référence externe, et `Payment::markSucceeded()` ne contrôle pas son unicité entre paiements. La migration des paiements crée un simple index sur `provider_reference`. Le même paiement externe pourrait ainsi être présenté pour plusieurs paiements locaux de même montant, selon le comportement de la passerelle. Constat statique à reproduire avec des tests dédiés.

Ajouter une contrainte unique par fournisseur et référence externe, vérifier le rattachement à la commande attendue et couvrir les appels concurrents. Le montant absent est également accepté par `markSucceeded()` : la validation des résultats en ligne devrait exiger un montant confirmé.

### Moyenne : validation et intégration continue

Le terminal dispose de PHP 8.2.28, incompatible avec le minimum PHP 8.3 du projet. `php artisan test` s'arrête au contrôle de plateforme Composer ; aucun test applicatif n'a donc été exécuté lors de cette revue.

La CI existante est `.gitlab-ci.yml` (Pint, Larastan, Pest). Elle ne s'exécute pas sur GitHub. Prévoir sa migration vers GitHub Actions avec PHP compatible, extensions nécessaires et base SQLite de test.

### Moyenne : documentation et données de démonstration

Le README décrit encore une boutique de chaussures alors que des migrations et documents récents ciblent équipements informatiques et accessoires. Plusieurs migrations insèrent du contenu de démonstration ; revoir ces données avant une première installation en production. Les affirmations antérieures « prod-ready » ne constituent pas une validation de cette revue.

## Contrôles de publication

- `composer validate --no-check-publish` : réussi.
- `npm ci --ignore-scripts --no-audit --no-fund` puis `npm run build` : réussis avec Node 24.13.0 et les certificats système Windows (`NODE_USE_SYSTEM_CA=1`). Les assets générés pour ce contrôle ont été remis à leur état versionné ; aucune mise à jour des dépendances ni audit de vulnérabilités npm effectué.
- `.env` local, `vendor`, `node_modules`, journaux et données de stockage exclus de Git selon les règles existantes.
- Historique parcouru : seul `.env.example` apparaît parmi les chemins d'environnement recherchés ; aucun chemin SQL, SQLite, PEM, KEY, P12 ou ZIP trouvé dans cet historique.
- Recherche ciblée sur les 32 commits de `main` : aucune correspondance pour les formats recherchés de jetons GitHub, identifiants AWS et en-têtes de clés privées. Ce contrôle par motifs ne garantit pas l'absence de tout secret.
- Extension de `.gitignore` à `.env.*`, en conservant `.env.example` versionné.
- Guide de déploiement actualisé pour le dépôt GitHub ; ancien accès GitLab à conserver comme remote séparé.
- Le changement local de mode du fichier `artisan` (755 vers 644 sur Windows) n'est pas une modification de son contenu ; conserver le bit exécutable versionné.

## Suite conseillée

1. Utiliser PHP 8.3 ou supérieur compatible avec le verrou Composer, puis lancer Pest, Pint et Larastan.
2. Corriger les deux points prioritaires de sécurité avec tests de non-régression.
3. Ajouter la CI GitHub, y compris le build frontend.
4. Effectuer une recette navigateur des parcours client/admin, commandes et paiements avant déploiement.
