# Lot 23 — QA espaces client/admin & configurabilité complète

> Hérite du **prompt maître**. Réf : `33-audit-et-nettoyage.md` §3-5.

## Objectif
Garantir des **espaces client & admin complets, solides, sans bug**, et rendre **chaque zone du site
configurable** depuis l'admin (structuré, simple, interactif).

## Périmètre / livrables
1. **QA espace client** (`33-` §3) : dashboard, devis (accepter/refuser, timeline, notifs), commandes
   (suivi, variante, paiement), profil (RGPD, sécurité), nav responsive, zéro erreur console/log.
2. **QA espace admin** (`33-` §4) : CRUD complets permissionnés, dashboard KPIs, aucun écran Sezy.
3. **Configurabilité** (`33-` §5) : hub **Réglages** réorganisé (Général, Accueil, SEO, Paiements,
   Médias, Fonctionnalités, Légales, Équipe/Partenaires) ; **navigation & footer éditables** (liens,
   ordre, visibilité) ; sections de pages éditables (ordre/visibilité étendus au-delà de l'accueil).
4. **Aucune valeur en dur** côté vitrine (tout via CMS/Settings).

## Étapes (PDAR) + DoD
Revue de bout en bout (parcours visiteur/client/admin), responsive 360→1440, a11y AA. Tests de
non-régression + tests des nouveaux réglages (nav/footer/sections éditables). Suite verte,
Pint/Larastan, Lighthouse ≥ 90.
