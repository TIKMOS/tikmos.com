# Lot 04 — Vitrine : accueil & profil

> Hérite du **prompt maître**. Réf : `06-interface-accueil.md` (cœur), `01`, `03`.

## Objectif
Livrer la **page d'accueil premium** et la page **À propos** (storytelling, **pas un CV**), orientées
offre et action. Contenu **éditable** (CMS/Settings) — c'est le « 100× mieux » qui met Tikmos en
ligne.

## Périmètre / livrables
1. **Layout public** finalisé : header (nav + CTA devis + connexion, lien actif filet or), **footer
   encre** (colonnes, réseaux), **bouton WhatsApp flottant**.
2. **Accueil** (`/`) avec les sections de `06` §2 : hero, bandeau preuve (compteurs), métiers,
   aperçu services, approche (stepper), aperçu réalisations, teaser boutique, aperçu équipe,
   témoignages, FAQ extrait, **bloc CTA final**. Sections alimentées par CMS/Settings (placeholders
   propres si vide).
3. **À propos** (`/a-propos`) : narration (parcours, valeurs, méthode), portrait, preuves, CTA.
4. **Composants** : `x-tk.service-card`, `project-card`, `product-card`, `team-card`, `stat`
   (compteur), `marquee`, `testimonial-slider`, `accordion/faq` (créés ici ou stubs si lot dédié
   plus loin — réutilisables).
5. **Interactions** : reveals scroll, hovers (filet or), compteurs, marquee, transitions
   `wire:navigate`, respect `prefers-reduced-motion`.
6. **SEO/perf de la home** : `<h1>` unique, meta + OG + JSON-LD `Person`/`ProfessionalService`, hero
   LCP-friendly, images lazy/blur-up, zéro CLS.

## Étapes (PDAR)
1. **Plan** : data de chaque section (source CMS/Settings vs placeholder), composants requis.
2. **Do** : layout → composants cartes → home (sections) → à propos → interactions → SEO.
3. **Assess** : tests (`/` et `/a-propos` 200, h1 présent, CTA WhatsApp = numéro settings, sections
   rendent avec données vides). Lighthouse mobile ≥ 90. Mobile + desktop. Pint/Larastan.
4. **Review** : charte (or parcimonieux), a11y AA, sobriété des animations.

## Tests (Pest)
- `/` répond 200, contient le hero (h1) et au moins un CTA devis + WhatsApp.
- Le numéro WhatsApp affiché provient des **settings** (pas en dur).
- `/a-propos` répond 200 avec contenu éditable.

## Definition of Done
DoD du maître + : home & à propos premium, éditables, interactions sobres, SEO/JSON-LD posés,
Lighthouse mobile ≥ 90.
