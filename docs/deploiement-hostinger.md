# Validation continue et déploiement Hostinger/cPanel

État du 29 octobre 2026. Dispositif implémenté et testé localement ; **non installé,
non activé et non exécuté sur le serveur**. Le premier passage GitHub Actions/Linux
et les prérequis du compte réel restent à valider. Aucun accès au serveur effectué.

## Audit du projet réel

| Élément | Constat dans le dépôt |
| --- | --- |
| Framework | Laravel **13.17.0** dans `composer.lock`, contrainte `^13.8` |
| PHP | `^8.3`, plateforme Composer 8.3.0 ; machine locale PHP 8.4.26 64 bits |
| Interface | Livewire 3.8.1, Volt 1.10.5, Alpine via Livewire, Tailwind 4 |
| Assets | Vite 8.1.0, Node >=22.12 conseillé ; CI Node 24 ; TipTap et polices Bunny |
| Dépendances | Composer et npm verrouillés ; Spatie permissions/settings/media/activitylog, Purifier |
| Tests | Pest 4.7.4, PHPUnit 12.5.30, SQLite isolé ; Larastan niveau 6 et Pint disponibles |
| Production | MySQL/MariaDB prévu ; configuration réelle non consultée ; `.env.example` propose SQLite |
| État persistant | `.env`, DB, `storage/app/private`, `storage/app/public` (médias Spatie), logs, sessions/cache selon pilotes |
| Liens Laravel | `public/storage` vers `storage/app/public` ; stockage cloud possible mais non validé ici |
| Scheduler | Expiration des réservations, drain `queue:work --stop-when-empty --max-time=50`, puis purges planifiées |
| Files | Notifications `ShouldQueue` ; `sync` recommandé dans l'exemple, `database` accepté avec cron existant |
| Santé | Route Laravel `/up` déjà présente ; elle ne teste pas à elle seule DB, email ou paiement |
| Git | Origin public `TIKMOS/tikmos.com`, défaut `main`, base locale `88ca908d7d8dc19f6ab31a3c232c2d1fbb2a0b71` |
| Historique | Pas de workflow/scripts CI/CD avant ce lot ; `public/build` déjà versionné pour l'ancien déploiement |

Le remote GitLab secondaire est conservé. Le mode étranger d'`artisan` est conservé.
Le scan indicatif des signatures de clés privées/tokens connus dans les fichiers
suivis n'a pas trouvé de correspondance ; ce n'est pas un audit exhaustif de tout
l'historique ni une preuve d'absence de tout secret. Aucun `.env` réel lu ou publié.

L'ancien guide `DEPLOIEMENT.md` décrit une procédure SSH manuelle et présente le
socle comme prêt pour la production : ces assertions ne remplacent pas une recette
du serveur actuel. Ce guide-ci décrit le nouveau mécanisme, sans changer le métier.

## Architecture et choix de la preuve

1. Push `main`/`codex/**`, ou PR vers `main` : job `validate` en lecture seule.
2. Installation depuis les lockfiles, syntaxe, tests des scripts, Larastan `app`,
   Pint du helper, build Vite, Pest bloquant, caches Laravel sur SQLite séparé.
3. Pour **push main seulement**, préparation d'une archive de code + `vendor`
   **sans dev**, assets compilés et métadonnées SHA/run/tentative/migrations.
4. Job `publish` après succès : publication d'une préversion publique unique
   `deploy-SHA-RUN_ID-ATTEMPT`, fichier `tikmos.tar.gz`. Aucune réécriture d'asset.
5. Cron : `git ls-remote` HTTPS public avec timeouts. Même SHA que `current` :
   arrêt immédiat, sans API, Composer, Artisan ou migration.
6. Nouveau SHA : API publique, vérification du dépôt, du dernier run **de ce SHA**
   sur `ci.yml`/push/main, de sa tentative et des deux jobs `validate`/`publish`.
   Tous doivent être `completed/success`. Aucun fallback vers un ancien run vert.
7. Téléchargement de l'archive publique, vérification du digest SHA-256 fourni par
   GitHub et des métadonnées internes. Préparation d'une nouvelle version privée.
8. Contrôles, caches et éventuelles migrations explicitement approuvées ; nouvelle
   vérification du HEAD **et de la même preuve CI** juste avant le basculement.
9. Basculement par renommage du lien `current`, vérification HTTP `/up` et du SHA
   public exact. Marqueur de succès uniquement après réussite.

**Solutions comparées :** l'API lie le résultat au workflow et au SHA exact ; un
manifeste seul sur `main` ne le prouve pas. Une référence Git « validée » seule
reste modifiable et n'apporte pas les assets Linux. Les artefacts internes Actions
ne sont pas téléchargés anonymement par cPanel : seul `publish` y accède avec le
`GITHUB_TOKEN` éphémère, puis expose l'archive via Releases. **Aucun token ni clé
SSH GitHub sur Hostinger**, aucun accès entrant de GitHub vers le serveur.

Le jeton automatique GitHub dispose de `contents:write` seulement dans `publish`.
Pas de `pull_request_target`, pas de secrets production dans CI. Actions épinglées
par SHA, à maintenir et revoir lors de mises à jour. L'archive n'inclut ni scripts
de contrôle, tests, docs, `.env`, cache, DB, uploads, authentification Composer ou
clé privée ; les ressources publiques déjà versionnées sont conservées.

### Limites de confiance et courses

Les personnes pouvant modifier `main`, le workflow ou les Releases contrôlent la
politique. Configurer manuellement protection de branche, revue des workflows et
restriction des permissions ; le script n'est pas une protection contre un
administrateur GitHub malveillant. Le digest protège l'intégrité du téléchargement,
pas cette autorité. Le contrôle réinterroge le dernier run sans filtrer les seuls
succès : annulation, échec, neutralité, job ignoré ou run absent refusent la version.

Une nouvelle poussée après la dernière lecture du HEAD reste possible : la version
basculée demeure celle validée et contrôlée, puis le prochain cron examinera le
nouveau HEAD. Aucun mécanisme externe ne peut verrouiller une branche GitHub pendant
un basculement local. Les releases de runs annulés ou abandonnés ne sont pas admises.

## Ce que les sources officielles confirment

Consultées le 29 octobre 2026, à distinguer des capacités **non vérifiées du compte** :

- [Hostinger — terminal cPanel](https://www.hostinger.com/support/4622295-how-to-connect-to-your-cpanel-account-via-ssh-at-hostinger/) : accès shell disponible sur les offres cPanel concernées, activation depuis le compte. Cela n'autorise aucun accès entrant automatisé, et ne confirme pas les binaires de ce compte.
- [cPanel — créer un Cron](https://support.cpanel.net/hc/en-us/articles/360053606793-How-to-create-or-edit-cron-jobs-in-the-cPanel-user-interface) : interface Cron, chemins complets et sélection 5 minutes ; disponibilité et minimum effectif à confirmer auprès de Hostinger.
- [cPanel — Composer](https://docs.cpanel.net/knowledge-base/web-services/how-to-set-up-php-composer/) : disponibilité variable selon version, installation/configuration à vérifier ; ne pas présumer `/usr/bin/composer`.
- [cPanel — document root](https://support.cpanel.net/hc/en-us/articles/1500004206502-How-to-find-the-document-root-of-a-domain) : chemin visible dans Domains. La modification du domaine principal ou l'acceptation d'un lien dépendent de l'hébergeur.
- [GitHub — workflow runs](https://docs.github.com/en/rest/actions/workflow-runs) : consultation publique sans authentification, filtres SHA/branche/événement.
- [GitHub — assets de release](https://docs.github.com/en/rest/releases/assets) : URL publique et digest SHA-256 ; ne pas confondre avec les artefacts Actions.
- [GitHub — quotas](https://docs.github.com/en/rest/using-the-rest-api/rate-limits-for-the-rest-api) : 60 requêtes/heure/IP sans authentification, potentiellement partagées avec d'autres clients.
- [Laravel 13 — déploiement](https://laravel.com/docs/13.x/deployment) : PHP/extensions, root `public`, droits d'écriture, caches et route de santé. Le mécanisme évite `optimize:clear`, qui peut aussi purger le cache applicatif.

### Vérifications obligatoires dans le compte réel

- Offre réellement **cPanel**, non hPanel supposé équivalent ; Cron Bash autorisé,
  minimum 5/10/15 minutes, ressources CPU/RAM/processus/inodes et durée maximale.
- PHP CLI et web 8.3+ **64 bits**, mêmes extensions : ctype/curl/dom/fileinfo/filter/
  hash/mbstring/openssl/PDO MySQL/session/tokenizer/XML, Phar/zlib, GD/exif/intl si utilisés.
  `composer check-platform-reqs --no-dev` reste bloquant pour le verrou réel.
- PHP CLI peut être `/usr/local/bin/ea-php83`, `/opt/cpanel/ea-php83/root/usr/bin/php`
  ou autre : **exemples à vérifier**, pas chemins détectés du compte.
- Bash >=4, Git HTTPS, curl, GNU tar, timeout, realpath, stat, df, find, awk, ln et
  `mv -T` ; Composer 2 PHAR accessible, pas de Node nécessaire sur cible.
- `flock` réellement disponible et fiable sur le volume, sinon verrou `mkdir` ;
  liens symboliques et renommage atomique sur le **même système de fichiers**.
- HTTPS sortant avec CA valides vers GitHub/API et ses CDN de téléchargement ;
  ne jamais désactiver TLS. Tester le quota API depuis l'IP mutualisée.
- Root HTTP exactement `APP_ROOT/current/public`, suivi de `current` et de
  `public/storage`, exécution PHP autorisée, comportement PHP-FPM/OPcache après
  basculement ; aucune garantie de rafraîchissement OPcache sans recette réelle.
- Même utilisateur/propriétaire approprié pour cron/PHP, lecture Apache des assets,
  DB accessible, cache/session persistants et horloges synchronisées.
- Tout fichier utilisateur hors `storage` et tout ancien `public_html` modifié
  doivent être inventoriés avant adoption. Le dispositif ne copie pas ces données
  arbitraires et n'écrase jamais l'ancien site pour essayer de les absorber.

Si une exigence manque, **ne pas activer**. Pas de mode déploiement en place dégradé.

## Installation initiale manuelle (sans toucher au site depuis ce poste)

Les chemins ci-dessous contiennent des paramètres **à remplacer**. Installer les
scripts de contrôle manuellement depuis une version revue, hors du web, par le
gestionnaire de fichiers cPanel ou son terminal. Ne jamais les exécuter depuis une
archive nouvellement téléchargée : leur mise à jour est une opération distincte.

```text
/home/CPANEL_USER/tikmos-deploy/
  config.sh                 # 600, propriétaire cron, non versionné
  control/                  # check-updates.sh, deploy.sh, lib.sh, verify.php
  releases/                 # versions, anciennes versions conservées
  current -> releases/initial-SHA
  shared/.env               # 600, clé et configuration existantes conservées
  shared/storage/           # même stockage persistant que le site existant
  downloads/ logs/ state/ recovery/   # privés
```

1. Sauvegarder DB, `.env`, médias et code existants ; vérifier une restauration en
   environnement séparé. Ne pas déplacer/supprimer le site en place. Noter son SHA
   exact et vérifier son checkout propre (`git status --porcelain`).
2. Créer `APP_ROOT` hors de `public_html` et ses répertoires privés. Le contrôle
   doit appartenir au compte Cron et ne pas être modifiable par d'autres utilisateurs.
   Copier `deploy/config.example.sh` vers `APP_ROOT/config.sh`, mode **600**,
   renseigner tous les chemins/binaires/URLs. Garder `ENABLED=false`, `DRY_RUN=true`.
3. Préparer, pendant une fenêtre opérateur, une **copie** du code actuellement servi
   dans `releases/initial-SHA`. Copier les dossiers runtime `app`, `bootstrap`,
   `config`, `database`, `lang`, `public`, `resources`, `routes`, `vendor` et les
   fichiers `artisan`, `composer.json`, `composer.lock`. Ne pas recopier `.env`,
   `storage`, `public/storage`, `public/hot` ou les caches générés de bootstrap.
   Cette étape adopte la version existante ; elle ne déploie pas de nouveau commit.
4. Préserver la même DB et **la même APP_KEY**. Copier le `.env` existant en privé
   dans `shared/.env` sans l'afficher, ou gérer sa consolidation volontairement.
   Conserver un seul stockage de fichiers : `shared/storage` peut pointer vers le
   stockage existant conservé. Ne pas faire une copie à chaud qui perdrait les
   nouveaux uploads. Créer dans la copie les liens `.env`, `storage`, `public/storage`.
5. Générer les métadonnées de cette copie depuis les fichiers existants :

   ```bash
   SHA=$(/CHEMIN/GIT -C /CHEMIN/SOURCE rev-parse HEAD)
   /CHEMIN/PHP /CHEMIN/CONTROL/verify.php baseline /CHEMIN/SOURCE "$SHA" > /CHEMIN/INITIAL/.deploy-release.json
   printf '%s\n' "$SHA" > /CHEMIN/INITIAL/public/deployment-version.txt
   ```

   Ne pas inventer un SHA ou empreinte. Les métadonnées `manual_baseline` ne sont
   pas une preuve CI : seuls les futurs candidats passent la vérification GitHub.
6. `storage` et `bootstrap/cache` doivent être accessibles au PHP du compte.
   Préparer `bootstrap/cache/views` privé dans la copie. Pour les caches de la
   **copie seulement**, utiliser `VIEW_COMPILED_PATH=/CHEMIN/INITIAL/bootstrap/cache/views`
   avec `config:cache`, `route:cache`, `event:cache`, `view:cache`. Ne pas purger le
   cache habituel. Vérifier que les caches ne pointent pas vers l'ancienne copie.
7. Relier `current` à cette copie, puis configurer **manuellement** le document root
   dans cPanel, seulement après test/restauration préparés. Tester le site, `/up`,
   `deployment-version.txt`, fichiers publics, connexion, médias et scheduler.
   Prévoir des droits de traversée adaptés : p.ex. 711 sur les conteneurs,
   755/644 sur dossiers/fichiers publics, privé 700/600 ailleurs. Ni 777 ni chmod
   récursif du stockage existant. Le besoin Apache/PHP réel doit être vérifié.
8. Adapter le cron Laravel existant au chemin `current/artisan` : ne pas dupliquer
   ses commandes ni oublier l'expiration des stocks. Les workers permanents ne sont
   pas gérés ; seuls `sync` ou drain court `database` sont admis par ce dispositif.
9. Après validation réelle de ces propriétés, mettre `SYMLINK_CONFIRMED=true`.

Cette adoption nécessite un opérateur connaissant le site. Si le document root ne
peut pas suivre le lien (cas possible du domaine principal), rester sur le manuel
et demander à Hostinger une configuration compatible. Ne pas déplacer `index.php`
à la racine du projet ou exposer `.env`, `vendor` et les scripts par commodité.

## Diagnostic, essai, activation et Cron

Publier d'abord le workflow via une PR revue et fusionnée sur `main`. Attendre un
premier workflow intégralement réussi et sa préversion publique. Aucun run n'existe
avant cette publication ; un diagnostic refusant l'absence de CI est attendu.

```bash
TIKMOS_DEPLOY_CONFIG=/home/CPANEL_USER/tikmos-deploy/config.sh \
  /bin/bash /home/CPANEL_USER/tikmos-deploy/control/check-updates.sh diagnostic

TIKMOS_DEPLOY_CONFIG=/home/CPANEL_USER/tikmos-deploy/config.sh \
  /bin/bash /home/CPANEL_USER/tikmos-deploy/control/check-updates.sh dry-run
```

`diagnostic` ne crée aucun état ni fichier et vérifie binaires/configuration et
preuve distante ; il ne garantit pas permissions web, capacités symlinks, DB ou
OPcache. `dry-run` prend le verrou, journalise sa décision et met à jour seulement
les marqueurs privés de diagnostic ; il ne télécharge/installe pas l'application,
n'appelle pas Composer/Artisan et ne modifie pas la DB.

Après diagnostic, répétition sur staging, sauvegardes et approbation opérateur,
mettre `ENABLED=true`, `DRY_RUN=false`. Lancer une fois `run`, observer ses journaux,
tester les parcours essentiels et le SHA servi avant de programmer le Cron.

Commande cPanel, champs temporels `*/5 * * * *` **si l'offre le permet** :

```cron
*/5 * * * * TIKMOS_DEPLOY_CONFIG=/home/CPANEL_USER/tikmos-deploy/config.sh /bin/bash /home/CPANEL_USER/tikmos-deploy/control/check-updates.sh run >> /home/CPANEL_USER/tikmos-deploy/logs/cron.log 2>&1
```

Dans l'interface cPanel, mettre uniquement ce qui suit les cinq champs dans
« Command ». Remplacer `CPANEL_USER` et `/bin/bash` après vérification ; aucun nom
de compte réel n'a été inventé. Ne pas exposer ce Cron par une URL HTTP.

Sans nouveauté : un `ls-remote` et lecture locale, zéro appel API. Avec une
nouveauté : quatre requêtes API par preuve, répétées avant le basculement ; un
run non terminé s'arrête plus tôt. Choisir 10/15 minutes si quota mutualisé,
ressources ou offre l'exigent. Un HTTP 403/429/5xx ou délai réseau conserve le site,
sans retry agressif ni utilisation d'un token comme contournement. Prévoir la
rotation du fichier **cron.log par cPanel**, car il contient aussi les erreurs
de configuration avant ouverture des journaux internes.

## Migrations, caches, dépendances et persistance

Le serveur n'installe pas les dépendances : CI produit `vendor` depuis
`composer.lock` avec `--no-dev --no-scripts --no-plugins --optimize-autoloader`.
Sur cible, Composer vérifie les extensions réelles, puis `package:discover` est
exécuté dans la version préparée. Aucune mise à jour de lockfile ni Node sur cible.

Les fingerprints couvrent migrations Laravel **et** Spatie settings. S'ils changent,
le déploiement refuse, sauf les trois conditions liées au SHA cible :

- `MIGRATION_APPROVED_SHA=SHA` après revue de migrations **compatibles ancien/nouveau** ;
- `BACKUP_CONFIRMED_SHA=SHA` après sauvegarde DB vérifiée par l'opérateur ;
- `DB_BACKUP_FILE` non vide, privé 600 sous `APP_ROOT/recovery`, récent (1 heure par défaut).

Même si l'empreinte des fichiers est identique, des migrations non appliquées dans
la DB imposent les mêmes approbations. Une lecture du registre Laravel, incluant
les chemins Spatie, est effectuée avant préparation puis après migration/caches.
Il ne suffit donc pas que les fichiers existent déjà dans l'ancien checkout.

Le script ne sait pas prouver qu'un dump SQL est complet/restaurable : c'est une
attestation de l'opérateur. Il ne sauvegarde pas automatiquement la DB via des
mots de passe en ligne de commande. Sans ces conditions, aucun `migrate` n'est
exécuté. Pas de seed, `migrate:fresh`, `db:wipe`, rollback DB ou purge applicative.
Retirer les approbations après usage ; elles ne valent jamais pour le SHA suivant.

Les caches config/routes/events et les vues sont propres à la nouvelle version ;
`VIEW_COMPILED_PATH` est fixé par son cache de configuration. Les anciens caches
ne sont pas supprimés. `.env`, données DB, sessions, quotas OTP/formulaires et
uploads restent partagés. **Une migration partiellement exécutée peut affecter
l'ancien site : le retour de code ne restaure pas la DB.** Les migrations
irréversibles/incompatibles demandent une intervention dédiée, hors automatisation.

Pas de worker permanent requis ou redémarré. Les anciens drains courts peuvent
finir leur travail avec le code précédent ; vérifier la compatibilité des jobs
et du schéma, la durée des drains, les verrous et le cron du compte réel.

## Journaux, échecs, désactivation et récupération

```bash
TIKMOS_DEPLOY_CONFIG=/home/CPANEL_USER/tikmos-deploy/config.sh \
  /bin/bash /home/CPANEL_USER/tikmos-deploy/control/check-updates.sh status
```

Affiche version servie, dernier SHA validé observé, succès/échec et activation.
`status` ne fait pas d'appel réseau ; lancer `diagnostic` pour connaître la preuve
distante actuelle. `logs/deploy.log` contient dates UTC, SHA courant/cible, étapes,
résultat, health check et durée ; rotation 1 Mio + une archive `.1`.
`state/command-output.private` conserve les détails d'une commande en échec,
**strictement privés** : ils peuvent contenir SQL, configuration ou chemins.
Ne pas les envoyer à GitHub, ne pas les copier dans un ticket sans nettoyage.

- Échec avant basculement : `current` inchangé, version préparée conservée pour
  diagnostic, pas de marqueur de succès. Avant réessai de la même tentative,
  déplacer manuellement son dossier échoué hors de `releases`, sans le supprimer
  aveuglément. Un nouveau run/tentative produit un autre nom unique.
- Échec du health check : tentative de retour atomique au précédent lien, contrôle
  de santé du code restauré. Si ce contrôle échoue aussi, intervention immédiate.
  Le log signale explicitement `database=not-restored`.
- Interruption fatale, kill -9, arrêt machine après basculement : pas de promesse
  de rollback automatique. Le lien courant/sa métadonnée font foi, `last-success`
  peut être ancien. Inspecter, tester et restaurer manuellement si nécessaire.
- Verrou `flock` libéré par le noyau. Fallback `mkdir` retiré à la sortie normale
  ou erreur interceptée ; jamais casser automatiquement un verrou périmé. Vérifier
  qu'aucun processus ne tourne, puis supprimer **le seul verrou** sous `state`.
- Désactivation : `ENABLED=false`, puis désactiver la ligne Cron. Le site continue.

Les chemins précédents sont sauvegardés dans `recovery/previous-SHA`. Après revue
des migrations et décision opérateur, restaurer le lien précédent avec un lien
temporaire **dans APP_ROOT** puis `mv -Tf` vers `current`, jamais copier par-dessus
le site. Vérifier `/up`, le SHA et les parcours ; remettre d'abord le Cron hors
service pour éviter qu'il ne redéploie. Aucune restauration DB automatique.

Aucun nettoyage automatique des anciennes versions/archives : surveiller espace
et inodes ; archiver/supprimer manuellement les seules versions inutilisées après
revue, jamais `shared`, l'actuelle ou la version de récupération. Les préversions
GitHub validées restent publiques ; leur cycle de rétention est aussi manuel.

## Vérifications exécutées et limites

Voir le [rapport de validation](validations/2026-10-29-ci-deploiement/README.md)
pour commandes, résultats, échecs rencontrés puis corrigés et périmètres non testés.
Les tests locaux simulent GitHub/Composer/Artisan/HTTP dans des dossiers temporaires.
Ils ne prouvent pas le fonctionnement Linux/Hostinger, la restauration réelle,
MySQL, SMTP, KKiaPay, cache partagé ou les restrictions de ce compte. Les décisions
d'activation, droits, sauvegardes, migrations et premier cutover restent manuelles.
