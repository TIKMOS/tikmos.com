# Lot 11 — Témoignages & FAQ

> Hérite du **prompt maître**. Réf : `05` (testimonials, faq_items), `06`, `01`.

## Objectif
Renforcer la **preuve sociale** et lever les objections : témoignages (slider + page) et FAQ
(accordéon), tous éditables et modérables.

## Périmètre / livrables
1. **Modèle `Testimonial`** (repalettiser l'existant) : author_name, role, quote, rating, photo,
   position, is_published. **`x-tk.testimonial-slider`** + page `/temoignages`.
2. **Modèle `FaqItem`** (repalettiser) : question, answer (HTML), category, position, is_published.
   **`x-tk.accordion`** + page `/faq` + extraits par page (services/produits).
3. **Admin** : CRUD témoignages (modération, mise en avant) + CRUD FAQ (ordre, catégories).
4. Intégration : slider témoignages sur la home ; FAQ extrait sur fiches services.

## Étapes (PDAR)
1. **Plan** : schémas, composants (slider/accordion), points d'intégration.
2. **Do** : modèles → composants → pages → admin → intégrations.
3. **Assess** : tests (pages 200, non publié masqué, CRUD protégé, accordion un-ouvert-à-la-fois en
   a11y). Pint/Larastan. Rendu mobile (swipe) + desktop.
4. **Review** : a11y accordéon (ARIA), charte, JSON-LD `FAQPage`.

## Tests (Pest)
- `/temoignages` et `/faq` 200 ; item non publié absent.
- CRUD interdit sans permission.

## Definition of Done
DoD du maître + : témoignages & FAQ éditables, slider + accordion accessibles, JSON-LD FAQ, tests verts.
