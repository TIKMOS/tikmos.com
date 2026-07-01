# TIKMOS — Déploiement

Guide concret de mise en production sur `tikmos.com`. Le socle est **prod-ready** : tous les caches
Laravel (`config`/`route`/`view`/`event`) se construisent, aucune closure non-cachable. **Aucun Node
requis sur le serveur** — `public/build` est versionné.

## 1. Clé de déploiement (deploy key GitLab)
Sur le serveur :
```bash
ssh-keygen -t ed25519 -C "deploy@tikmos" -f ~/.ssh/tikmos_deploy -N ""
cat ~/.ssh/tikmos_deploy.pub    # → GitLab : Settings → Repository → Deploy keys (lecture seule)
```
`~/.ssh/config` :
```
Host gitlab.com
  HostName gitlab.com
  User git
  IdentityFile ~/.ssh/tikmos_deploy
  IdentitiesOnly yes
```

## 2. Première mise en service
```bash
git clone git@gitlab.com:slavesdigital/tikmos.com.git
cd tikmos.com
cp .env.example .env            # renseigner : DB (MySQL), MAIL_*, TIKMOS_WHATSAPP, APP_URL=https://tikmos.com
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan config:cache route:cache view:cache event:cache
```
- **Document root** du domaine → `.../tikmos.com/public`.
- **HTTPS** forcé (déjà géré via `ForceHttps`/URL::forceScheme en prod).

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
php artisan config:cache route:cache view:cache event:cache
```
> Si le front a changé **en local** : `nvm use 22 && npm run build`, committer `public/build`, puis `git pull` côté serveur (aucun build serveur).

## 6. Vérifications live (smoke)
- [ ] `/` , `/services`, `/blog`, `/boutique`, `/contact` répondent (200).
- [ ] Demande de devis → email reçu (équipe + accusé).
- [ ] Commande WhatsApp / checkout → commande créée, email équipe.
- [ ] Connexion admin **2FA** OK ; `/admin` protégé.
- [ ] `sitemap.xml` + `robots.txt` servis ; JSON-LD présent.
- [ ] `APP_DEBUG=false`, HTTPS, sauvegardes DB + `storage` planifiées.

## CI
`.gitlab-ci.yml` lance **Pint + Larastan + Pest** à chaque push (sans Node).
