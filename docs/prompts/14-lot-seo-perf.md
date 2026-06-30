# Lot 14 — SEO & performance

> Hérite du **prompt maître**. Réf : `04` §7-8, `01` §9.

## Objectif
Rendre Tikmos **trouvable et rapide** : SEO complet (meta, JSON-LD, sitemap) et performance
(Lighthouse mobile ≥ 90), sur toutes les pages publiques.

## Périmètre / livrables
1. **SEO par page** : `x-tk.seo` (titre < 60c, description < 160c, canonical, OG/Twitter) alimenté
   par contenu/Settings ; valeurs par défaut depuis `SeoSettings`.
2. **JSON-LD** (`spatie/schema-org`) : `Person` ([le Professeur]) + `ProfessionalService` /
   `Organization` (home/à propos), `Product` (boutique), `FAQPage` (FAQ), `BreadcrumbList`.
3. **Sitemap** auto (`spatie/laravel-sitemap`) + `robots.txt` ; URLs propres (slugs kebab).
4. **Performance** : fonts self-host `swap`, images webp/avif lazy + dimensions (medialibrary
   conversions, srcset), `wire:navigate`, caches prod, élimination N+1.
5. **Mesure** : passer Lighthouse mobile sur home/service/boutique, corriger jusqu'à ≥ 90.

## Étapes (PDAR)
1. **Plan** : inventaire pages → meta/JSON-LD requis ; budget perf par page.
2. **Do** : composant SEO → JSON-LD → sitemap/robots → optimisations perf.
3. **Assess** : tests (balises meta présentes, canonical correct, sitemap liste les URLs publiées,
   robots ok). Lighthouse ≥ 90. Pint/Larastan.
4. **Review** : zéro CLS, LCP < 2.5 s, alt partout, Hn structurés.

## Tests (Pest)
- Home : title/description/canonical/OG présents.
- `/sitemap.xml` 200 et contient les services/produits/projets publiés.
- JSON-LD `Person` présent sur `/a-propos`.

## Definition of Done
DoD du maître + : SEO complet par page, JSON-LD valides (Rich Results), sitemap/robots, Lighthouse
mobile ≥ 90, tests verts.
