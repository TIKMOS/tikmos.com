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
