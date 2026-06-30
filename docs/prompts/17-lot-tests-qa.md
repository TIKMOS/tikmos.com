# Lot 17 — Tests & QA (transverse)

> Hérite du **prompt maître**. Réf : `04` §11. **Transverse** : à dérouler en continu et en passe
> finale avant prod.

## Objectif
Garantir la **fiabilité** : couverture des flux critiques, factories complètes, qualité statique, et
revue QA finale (parcours, responsive, a11y, perf) sur l'ensemble.

## Périmètre / livrables
1. **Couverture critique (Pest)** : auth/2FA, demande→devis→acceptation, panier→commande→suivi,
   permissions/scoping, CMS (édition reflétée en front), notifications (fake), pages publiques 200.
2. **Factories & seeders** pour toutes les entités (jeu de démo réaliste, non sensible).
3. **Qualité statique** : Pint clean, Larastan sans erreur, (option) couverture minimale visée.
4. **QA manuelle** : checklist parcours (visiteur, client, admin) ; responsive 360→1440 ; a11y AA
   (clavier/contraste/focus) ; zéro erreur console/log.
5. **CI** : pipeline Pint + Larastan + Pest (bloquant au merge).

## Étapes (PDAR)
1. **Plan** : matrice de tests par module, lacunes à combler.
2. **Do** : compléter factories/tests → corriger → durcir CI.
3. **Assess** : `php artisan test` vert global ; Lighthouse ; revue a11y.
4. **Review** : aucune régression, parcours de bout en bout OK.

## Definition of Done
DoD du maître + : flux critiques couverts et verts, factories complètes, CI en place, QA manuelle
passée, zéro erreur console/log.
