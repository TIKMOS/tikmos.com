# Lot 22 — Journal d'activité & audit granulaire

> Hérite du **prompt maître**. Réf : `32-journal-activite.md`.

## Objectif
Tracer **au plus fin** l'activité de chaque utilisateur/client (qui, quoi, quand, sur quel objet,
avant/après, IP/appareil) et la rendre lisible/filtrable depuis l'admin.

## Périmètre / livrables
1. Trait **`Auditable`** (config `LogsActivity` + libellé + `logName` cohérents) appliqué aux entités
   sensibles (services, devis, commandes, produits, articles, CMS, rôles, réglages, paiements).
2. **`AuditContext`** (middleware) : IP/UA/canal attachés à chaque activité. `login_logs` déjà présents.
3. **`ActivityFeed`** : timeline **normalisée** agrégeant `activity_log` + `*_events` + `login_logs`.
4. Admin **Audit** enrichi (filtres acteur/objet/action/période/IP, diff lisible) + onglet **Activité**
   sur fiche user/client + fiches objets.
5. Rétention `audit:prune --days` planifiée ; export CSV ; permissions (`audit.view`).

## Étapes (PDAR) + DoD
Voir `32-` §7. Tests : une action CRUD/paiement/login crée l'activité attendue (acteur, diff,
contexte) ; filtrage par acteur ; accès refusé sans permission. Suite verte, Pint/Larastan.
