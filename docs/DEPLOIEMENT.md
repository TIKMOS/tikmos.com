# TIKMOS — Déploiement

Guide concret de mise en production sur `tikmos.com`. Le socle est **prod-ready** : tous les caches
Laravel (`config`/`route`/`view`/`event`) se construisent, aucune closure non-cachable. **Aucun Node
requis sur le serveur** — `public/build` est versionné.

## 1. Clé de déploiement (deploy key GitHub)
Sur le serveur :
```bash
ssh-keygen -t ed25519 -C "deploy@tikmos" -f ~/.ssh/tikmos_deploy -N ""
cat ~/.ssh/tikmos_deploy.pub    # → dépôt GitHub : Settings → Deploy keys (lecture seule)
```
`~/.ssh/config` :
```
Host github.com
  HostName github.com
  User git
  IdentityFile ~/.ssh/tikmos_deploy
  IdentitiesOnly yes
```

## 2. Première mise en service
```bash
git clone git@github.com:TIKMOS/tikmos.com.git
cd tikmos.com
cp .env.example .env            # renseigner : DB (MySQL), MAIL_*, TIKMOS_WHATSAPP, APP_URL=https://tikmos.com
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan db:seed --class=RolePermissionSeeder --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```
- **Document root** du domaine → `.../tikmos.com/public`.
- **HTTPS** forcé (déjà géré via `ForceHttps`/URL::forceScheme en prod).

### 2.1. Créer explicitement le premier administrateur

`php artisan db:seed` initialise les rôles/permissions, **sans créer de compte**,
même si des variables administrateur sont renseignées. `AdminSeeder` n'est jamais
appelé par le seeding courant. Il ne crée plus de comptes secondaires ni de mot
de passe par défaut. Cette procédure est réservée au premier administrateur ;
les comptes supplémentaires passent ensuite par la gestion du personnel.

1. Sur l'installation cible autorisée, renseigner temporairement dans le `.env`
   privé les trois variables ci-dessous. Elles sont volontairement vides dans
   `.env.example` ; ne pas copier un secret réel dans un terminal enregistré,
   une commande de shell, un ticket ou un fichier versionné.

   ```dotenv
   TIKMOS_ADMIN_NAME=
   TIKMOS_ADMIN_EMAIL=
   TIKMOS_ADMIN_PASSWORD=
   ```

   Fournir un nom (255 caractères maximum), une adresse email valide et un mot
   de passe unique généré par un gestionnaire : **16 caractères minimum,
   72 octets UTF-8 maximum**, avec majuscule, minuscule, chiffre et symbole,
   sans octet nul. La limite en octets évite la troncature avec bcrypt.
   Le nom et l'email sont normalisés (espaces extérieurs, email en minuscules).
   Aucun mot de passe, hachage ou email configuré n'est imprimé par le seeder.

2. Recharger la configuration et lancer volontairement le seeder :

   ```bash
   php artisan config:clear
   php artisan db:seed --class=RolePermissionSeeder --force
   php artisan db:seed --class=AdminSeeder --force
   ```

   `--force` permet l'exécution en production uniquement après décision de
   l'opérateur ; il ne remplace pas la validation des données. Une configuration
   absente ou invalide fait échouer l'appel explicite avec un message utile.
   L'application lit ces valeurs via `config('admin.*')`, sans appel à `env()`
   dans le seeder, pour respecter le fonctionnement du cache Laravel.

3. Contrôler le résultat : le seeder crée un seul SuperAdmin actif avec mot de
   passe haché, sans prétendre que son email est déjà vérifié et sans appareil
   de confiance. La double authentification reste obligatoire selon la
   configuration habituelle du site ; tester la réception du code séparément.

   Une répétition avec la même adresse et une configuration complète valide
   conserve **tous** les attributs du SuperAdmin existant : mot de passe, nom,
   statut, 2FA et rôles supplémentaires. Elle ne réactive pas un compte suspendu.
   Une adresse appartenant à un client, un autre rôle ou un compte supprimé
   provoque un refus sans promotion ni restauration. Les correspondances
   historiques ambiguës sont refusées. Si un SuperAdmin existe sous une autre
   adresse, aucun second administrateur initial n'est créé.

4. Après réussite, retirer les trois valeurs de provisionnement du `.env`
   privé, puis purger et reconstruire le cache :

   ```bash
   php artisan config:clear
   php artisan config:cache
   ```

   Ne pas garder le mot de passe initial dans un cache de configuration ou une
   sauvegarde de ce cache. La suppression de ces variables ne modifie pas le
   compte créé. Pour une nouvelle exécution explicite, les trois données doivent
   être à nouveau présentes et valides ; le seeder ne sert pas à changer un mot
   de passe existant.

### 2.2. Vérifier les comptes d'une installation existante

La présence du repli dans l'ancien code **ne prouve pas** que des comptes déployés
l'utilisent. Cette correction ne consulte ni ne modifie les comptes réels et
ne révoque aucun rôle. Un opérateur autorisé doit examiner séparément la liste
des SuperAdmin (y compris suspendus/supprimés), la légitimité des accès et les
coordonnées de récupération, sans exporter les mots de passe ou leurs hachages.
Si un ancien secret de démonstration a pu être utilisé, effectuer une rotation
contrôlée via le parcours de gestion/réinitialisation, puis vérifier la 2FA et les
sessions/appareils concernés. Préserver un accès administratif légitime pendant
cette revue. Réexécuter `AdminSeeder` ne remédie pas aux secrets historiques.

Preuves et démonstration sur SQLite en mémoire :
[validation locale de la correction 06](validations/2026-10-24-admin/README.md).
Références officielles : [seeders explicites](https://laravel.com/framework/docs/seeding#running-seeders)
et [cache de configuration](https://laravel.com/framework/docs/configuration#configuration-caching).

## 3. Environnement prod (.env)
```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tikmos.com
DB_CONNECTION=mysql   # + DB_HOST/DATABASE/USERNAME/PASSWORD
QUEUE_CONNECTION=database
SESSION_DRIVER=database
CACHE_STORE=database
MAIL_MAILER=smtp      # + MAIL_HOST/PORT/USERNAME/PASSWORD/FROM
TIKMOS_WHATSAPP=2290197440615
```

## 4. Queue & scheduler (notifications, sitemap)
Cron unique (hébergement mutualisé compatible) :
```
* * * * * cd /home/UXXXX/tikmos.com && php artisan schedule:run >> /dev/null 2>&1
```
Le scheduler traite la file (`queue:work --stop-when-empty`) pour les emails.

## 5. Déploiements suivants

**Avant la migration du compteur de commandes (correction 08), suivre les
préconditions ci-dessous.** Un historique existant exige une initialisation
explicite ; ne pas lancer la séquence générale sans cette préparation.

```bash
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```
> Si le front a changé **en local** : `nvm use 22 && npm run build`, committer `public/build`, puis `git pull` côté serveur (aucun build serveur).

### Numérotation atomique des commandes — correction 08

La migration additive `2026_10_26_010000_create_order_number_sequences_table.php`
crée un compteur annuel sans renuméroter les commandes ni modifier leurs URLs.
Elle doit précéder l'utilisation du nouvel allocateur. Procédure pour un futur
déploiement autorisé ; **aucune de ces opérations n'a été exécutée sur la base
habituelle pendant la validation locale** :

1. Arrêter toutes les écritures de commandes : trafic, traitements et workers
   concernés. Les anciennes et nouvelles versions ne doivent pas écrire ensemble.
   Sauvegarder la base et, s'il existe déjà, le compteur annuel.
2. Pour chaque année des numéros `TKM-CMD-YYYY-…`, relever le maximum attribué dans
   les données et les archives, y compris les commandes supprimées. L'année de
   `created_at` n'est pas une source de vérité. Une année entièrement supprimée
   doit aussi être renseignée si des numéros y ont déjà été attribués.
3. Renseigner `ORDER_NUMBER_SEQUENCE_FLOORS` avec un objet JSON de plafonds vérifiés.
   Exemple **fictif**, à remplacer par les valeurs auditées de la cible :

   ```dotenv
   ORDER_NUMBER_SEQUENCE_FLOORS='{"2025":240,"2026":4200}'
   ```

   Chaque année historique présente exige une valeur entière non négative, au
   moins égale au maximum actuel. Les références canoniques mal formées et les
   séquences hors limites sont refusées sans modification des commandes. Le code
   ne peut pas déduire des numéros supprimés absents des archives : établir ce
   plafond avant de poursuivre. Sur une installation neuve sans historique, la
   variable peut rester vide.
4. Sur la cible autorisée, rendre cette configuration disponible à Laravel avant
   la migration : si elle était cachée, `php artisan config:clear`, puis appliquer
   `php artisan migrate --force` et reconstruire le cache avec
   `php artisan config:cache`. La variable ne sert qu'à l'initialisation de la
   migration ; la modifier ensuite ne réinitialise pas le compteur persistant.
5. Vérifier les compteurs, la contrainte unique `orders.number` et le caractère
   transactionnel des tables commandes/articles/produits sur MySQL (InnoDB), puis
   reprendre les écritures après le contrôle fonctionnel autorisé.

Une commande supprimée ne libère pas son numéro. Une transaction annulée annule
aussi son incrément ; ce numéro non validé peut donc être utilisé ultérieurement.
Les plafonds et suppressions peuvent créer des trous, sans garantie de continuité.
Tout import futur doit réconcilier explicitement les compteurs vers le haut avant
la reprise des créations ; aucun recalcul silencieux n'est effectué à l'exécution.

Le rollback de cette migration supprime uniquement la table de compteurs. Il
détruit toutefois la mémoire des numéros supprimés : conserver sa sauvegarde et
reconstituer des plafonds vérifiés avant toute réinstallation. Ne pas abaisser un
compteur existant ni revenir à l'ancien écrivain sur une base continuant à recevoir
des commandes.

[Stratégie, démonstration et contrôles locaux](validations/2026-10-26-numerotation/README.md).
La migration et la concurrence MySQL restent **non validées** ; la concurrence
réellement testée concerne SQLite avec des processus indépendants.

### Protection des formulaires publics — correction 09

`PublicFormSubmissionLimiter` utilise `cache.limiter` si défini, sinon
`cache.default` (configuration initiale : `database`). Aucun service tiers ou
nouveau schéma requis : le pilote database utilise les tables existantes `cache`
et `cache_locks`. Aucune migration ni purge du cache habituel n'a été exécutée
pendant cette correction.

Avant utilisation sur la cible, vérifier séparément :

- Un cache persistant prenant en charge les verrous Laravel. Pilotes acceptés :
  database, file, Redis, Memcached ou DynamoDB. Array est autorisé uniquement sous
  l'environnement `testing` ; null, failover et les pilotes non admis sont refusés.
- Tous les processus et serveurs doivent partager le même stockage de compteurs
  **et** de verrous, le même préfixe d'application et la même `APP_KEY`. Un cache
  fichier local convient seulement aux processus utilisant réellement le même
  volume avec des verrous fiables ; des disques locaux indépendants ne constituent
  pas un cache partagé. Les pilotes distribués non testés restent à recetter.
- Les sessions des visiteurs doivent être persistantes et correctement transmises
  par les cookies. Sur plusieurs serveurs, partager également les sessions ou
  utiliser une configuration de routage adaptée ; les comptes restent identifiés
  par leur identifiant authentifié.
- L'IP est fournie par `Request::ip()` : vérifier les mandataires de confiance sur
  la cible, sans accepter aveuglément un en-tête client. Cette correction ne modifie
  pas la configuration des proxies. Synchroniser les horloges des serveurs.

Les clés `public-forms:v1:*` contiennent des HMAC, sans email, téléphone, IP ou
identifiant de session en clair. La rotation de clé ou un changement de préfixe
réinitialise de fait ces quotas : ne pas les utiliser comme mesure comptable.
Les fenêtres expirent après 10 ou 30 minutes ; elles ne sont pas glissantes.

Un verrou commun de 10 secondes protège uniquement la lecture et la réservation
des compteurs, jamais la création métier ni les notifications. Le plafond peut
causer une sérialisation brève ; sous contention, un refus demande de réessayer
dans 2 secondes sans perdre les champs. Une panne ou une configuration de cache
inadaptée refuse l'envoi avec un délai de 30 secondes et journalise seulement le
type d'exception, sans détails de connexion ni coordonnées. Ne pas purger le cache
de production pour lever arbitrairement un quota.

[Règles fonctionnelles](03-cadre-fonctionnel.md#formulaires-publics--correction-09-27-octobre-2026)
et [rapport de validation](validations/2026-10-27-formulaires/README.md).
MySQL, SMTP réel, cache partagé entre processus/serveurs et comportement sous charge
de production restent **non validés**. Les tests database de ce lot utilisent SQLite
isolé et recréent un adaptateur ; ils ne prouvent pas une concurrence distribuée.

## 6. Vérifications live (smoke)

### Réservations de stock en ligne — correction 07

La migration `2026_10_26_000000_add_stock_reservation_to_orders.php` doit être appliquée
sur la cible autorisée avant d'utiliser le nouveau parcours. Elle conserve les commandes
historiques sans réservation automatique ; réconcilier séparément leurs paiements/stocks.
Cette migration n'a pas été exécutée sur la base habituelle pendant la validation locale.

Le cron Laravel existant doit appeler `php artisan schedule:run` chaque minute. Il exécute
`orders:expire-reservations` en processus via `Schedule::call()`, sans `proc_open`. Vérifier
séparément ce fonctionnement sur la cible et surveiller les erreurs du planificateur :
une panne du cron retarde la remise en vente du stock, mais ne prolonge pas la réservation.
La commande ne doit pas être lancée sur la base habituelle pour une simple recette.

Une confirmation tardive est enregistrée comme paiement reçu à vérifier ; préparation
bloquée, aucun remboursement automatique. Procédure et limites :
[cycle de paiement F07](30-systeme-paiement.md#correction-f07--réservation-et-reprise-26-octobre-2026),
[preuves locales](validations/2026-10-26-paiement/README.md).

- [ ] `/` , `/services`, `/blog`, `/boutique`, `/contact` répondent (200).
- [ ] Demande de devis → email reçu (équipe + accusé).
- [ ] Commande WhatsApp / checkout → commande créée, email équipe.
- [ ] Connexion admin **2FA** OK ; `/admin` protégé.
- [ ] `sitemap.xml` + `robots.txt` servis ; JSON-LD présent.
- [ ] `APP_DEBUG=false`, HTTPS, sauvegardes DB + `storage` planifiées.

## CI
`.gitlab-ci.yml` lance **Pint + Larastan + Pest** à chaque push (sans Node).

## Conditions du cache et du transport OTP (correction 10)

Le challenge 2FA nécessite un cache persistant avec verrous. Il emploie
`cache.limiter` lorsqu'il est défini, sinon `cache.default`. Les compteurs et
l'empreinte du code se trouvent dans **le même** store. Le pilote `array` est réservé
aux tests, les pilotes sans verrous et le failover vers un store non partagé sont
refusés. Une erreur de cache bloque temporairement le challenge au lieu de permettre
une connexion sans OTP. Aucun effacement du cache habituel n'est nécessaire.

Sur plusieurs processus ou serveurs, partager le même stockage, les verrous, le
préfixe de cache et `APP_KEY`. Le pilote fichier exige un stockage commun assurant
les verrous ; des disques locaux séparés ne protègent pas un déploiement à plusieurs
serveurs. Synchroniser leurs horloges. Une perte/éviction ou un changement de store,
de préfixe ou de clé peut faire perdre les quotas et invalider les codes ; ne pas
utiliser un cache régulièrement purgé pour cette protection.

Le verrou par compte dure **60 secondes**, et couvre l'envoi synchrone ainsi que
la consommation du code. Configurer un timeout de transport strictement inférieur à
ce bail, en laissant une marge pour les accès au cache. Le timeout global effectif
(connexion, lecture, éventuelles reprises) doit rester inférieur à 60 secondes.
Vérifier séparément ce comportement avec le transport de production ; il n'a pas été
validé par la recette avec notifications simulées. Un autre appel pendant le verrou
est refusé avec un délai de réessai de 2 secondes. Une panne d'envoi réserve néanmoins
le délai de 60 secondes et une place dans le quota ; elle ne remplace pas le code
encore valide si le transport lève une exception. Une remise au transport sans
exception ne garantit pas la réception de l'email. Le service revérifie la propriété
du verrou après le transport : un envoi ayant perdu son bail est refusé sans
réécrire le code ni les compteurs plus récents. Ce garde-fou ne remplace pas le
réglage du timeout, ni une recette de concurrence sur le stockage de production.

Seuils par compte dans `config/tikmos.php` : code 600 s, renvoi 60 s, 5 envois sur
900 s, 3 comparaisons incorrectes sur 900 s. Les compteurs survivent aux renvois,
aux nouvelles sessions et aux succès jusqu'à la fin de leurs fenêtres fixes.
Le premier envoi est inclus. Aucun quota fondé sur un email non vérifié.

Avant production, vérifier séparément le cache partagé, la concurrence réelle,
les timeouts et la délivrabilité SMTP. Les tests SQLite avec recréation d'adaptateur
et la recette fichier ne constituent pas une validation distribuée ou MySQL.
Garder `APP_ENV=production`, `APP_DEBUG=false` ; le code de développement ne doit
jamais apparaître sur le challenge. Aucun compte ou appareil déjà déployé n'a été
modifié dans cette correction.
