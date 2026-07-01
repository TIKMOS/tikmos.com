# Lot 19 — Nettoyage Sezy & repositionnement boutique

> Hérite du **prompt maître**. Réf : `33-audit-et-nettoyage.md` §1-2.

## Objectif
**Éliminer tout le domaine hérité Sezy** (colis / vols / livraison / achat) du projet et **normaliser
les résidus `sz→tk`**, sans rien casser (suite verte). La boutique (déjà repositionnée en contenu) est
finalisée côté modèle si besoin.

## Périmètre / livrables
1. **Suppression du domaine** (voir `33-` §1.A) : modèles (Parcel*, Flight, Courier, Delivery,
   PreferredSite), enums associés, services (Parcel/Flight/Delivery/Commission), notifications
   (Parcel*), routes (`achat`, `vols`, `suivi`, `colis`, `demandes/nouvelle`, `livreur.php`), vues
   correspondantes, migrations des tables Sezy, rôles `agent_*`/`conseiller_mobilite`/`livreur` +
   permissions `colis.*`/`vol.*`/`couriers.*`/`deliveries.*`.
2. **À trancher/recibler** : `Lead` (messages contact) → **garder** ; `ReviewSolicitationService`
   → recibler post-commande boutique ; `app/alertes` → notifications Tikmos.
3. **Normalisation `sz→tk`** : `sz-toast`/`sz-open-cookie-prefs` → `tk-*` (app.js + toast +
   cookie-consent + tous les `dispatch`), PWA (`sw.js`, `offline.html`), `design-demo`.
4. **Boutique** : variantes génériques (renommer « tailles » → « variantes » optionnelles) si utile.
5. **Nettoyage** : `config/tikmos.php` (géo Cotonou/Paris), seeders (Admin/Demo/Showcase), `.htaccess`.

## Étapes (PDAR)
1. **Plan** : lister précisément fichiers à supprimer/éditer (grep `sezy`, `Parcel`, `Flight`,
   `colis`, `livreur`, `SZ-`, `sz-`), et les tests impactés.
2. **Do** : supprimer par grappes cohérentes ; `php artisan migrate:fresh` après retrait des
   migrations Sezy ; adapter/supprimer les tests Sezy (Delivery, ParcelLifecycle…).
3. **Assess** : `php artisan test` vert ; Pint/Larastan ; `grep -ri sezy` ≈ 0 (hors historique docs).
4. **Review** : aucune route/vue/lien mort ; nav admin/client sans résidu Sezy.

## Definition of Done
DoD du maître + : **zéro occurrence Sezy fonctionnelle**, suite verte après `migrate:fresh`, aucun
lien/route mort, `sz-*` normalisés en `tk-*`.
