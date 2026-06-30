# Lot 10 — Back-office (admin & équipe)

> Hérite du **prompt maître**. Réf : `03` (espace C), `02`, `05`, `01`.

## Objectif
Consolider le **back-office** : tableau de bord KPIs + gestion complète (demandes/devis, boutique,
CRM léger, équipe & rôles), pilotable par l'admin et l'équipe selon permissions. C'est la **tour de
contrôle** qui rend le site autonome.

## Périmètre / livrables
1. **Layout admin** (sidebar, filet or actif) finalisé ; navigation par permissions.
2. **Tableau de bord** : KPIs (demandes du mois, devis envoyés/acceptés, commandes boutique, CA
   estimé, stock bas), raccourcis.
3. **Demandes / devis** : pipeline complet (filtres, affectation, statuts, devis, exports CSV).
4. **Boutique — commandes** : pipeline (statuts, notes, contact client, exports).
5. **CRM léger** : fiches clients (coordonnées, historique multi-offres : demandes + commandes).
6. **Équipe & rôles** : invitations, gestion rôles/permissions (UI sur Spatie).
7. **Audit** : journalisation des actions sensibles (activitylog) visible (option).

## Étapes (PDAR)
1. **Plan** : écrans, permissions par section, agrégations KPI (requêtes optimisées).
2. **Do** : layout/nav → dashboard → demandes → commandes → CRM → équipe/rôles.
3. **Assess** : tests (accès par rôle, KPI corrects sur dataset factory, affectation/transition,
   invitation crée le rôle, export). Pint/Larastan. Zéro N+1 (eager loading).
4. **Review** : sécurité (chaque écran derrière la bonne permission), perf des listes (pagination).

## Tests (Pest)
- `support` voit demandes/commandes mais pas `roles.manage`.
- KPI « commandes du mois » = compte attendu sur données factory.
- Affectation d'une demande à un membre persistée + visible.

## Definition of Done
DoD du maître + : back-office opérationnel et permissionné, KPIs justes, pipelines complets, exports,
tests verts, listes sans N+1.
