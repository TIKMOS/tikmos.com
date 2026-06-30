# Lot 18 — Mise en production

> Hérite du **prompt maître**. Réf : `04` §12, `02` §7.

## Objectif
Déployer Tikmos sur `tikmos.com` de façon **sûre et reproductible** (hébergement mutualisé compatible,
VPS-ready), avec caches, queue/cron, HTTPS et sauvegardes.

## Périmètre / livrables
1. **Environnement** : `.env` prod (`APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://tikmos.com`,
   DB MySQL, mail, WhatsApp, clés). `php artisan key:generate`.
2. **Build** : `composer install --no-dev --optimize-autoloader`, `npm ci && npm run build`.
3. **Caches** : `config:cache route:cache view:cache event:cache` ; `storage:link`.
4. **DB** : `migrate --force` + seed (rôles/permissions/settings ; pas de données sensibles).
5. **Queue & scheduler** : `QUEUE_CONNECTION=database` ; cron Hostinger
   `* * * * * cd /home/UXXXX/app && php artisan schedule:run >> /dev/null 2>&1` (queue work, sitemap).
6. **Sécurité prod** : HTTPS forcé + HSTS, en-têtes sécurité, doc root `public/`, permissions fichiers.
7. **Domaines** : `tikmos.com` (+ éventuels annexes en redirection ; sous-domaine app si retenu).
8. **Sauvegardes** : DB + `storage` planifiées.
9. **Vérification live** : parcours clés (devis, commande WhatsApp, login admin 2FA), 404/500 OK,
   sitemap/robots, Lighthouse, monitoring/logs.

## Étapes (PDAR)
1. **Plan** : checklist déploiement + rollback.
2. **Do** : env → build → caches → migrate → queue/cron → sécurité → domaines.
3. **Assess** : smoke tests live (toutes pages clés 200, mail part, WhatsApp ouvre, 2FA OK).
4. **Review** : `APP_DEBUG=false`, secrets absents du repo, sauvegardes vérifiées.

## Checklist live
- [ ] HTTPS + HSTS, doc root `public/`, `APP_DEBUG=false`.
- [ ] Caches Laravel actifs, `storage:link`, assets `public/build` déployés.
- [ ] migrate --force OK ; rôles/permissions/settings seedés.
- [ ] Queue tourne (cron) ; mails transactionnels partent.
- [ ] Parcours devis + commande WhatsApp + admin 2FA OK en prod.
- [ ] SEO live (sitemap/robots/JSON-LD) ; Lighthouse mobile ≥ 90.
- [ ] Sauvegardes DB + storage planifiées.

## Definition of Done
DoD du maître + : site en ligne sur `tikmos.com`, sécurisé, performant, sauvegardé, parcours clés
vérifiés en production.
