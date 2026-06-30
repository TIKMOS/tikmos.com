# Lot 11b — Blog & actualités

> Hérite du **prompt maître**. Réf : `05` (posts/post_categories), `03`, `14` (SEO).

## Objectif
Donner à [le Professeur] un **blog d'autorité** (maths, data/IA, conseils, actualités) : asset SEO et
preuve d'expertise. Entièrement éditable depuis l'admin.

## Périmètre / livrables
1. **Modèles `Post`** + **`PostCategory`** (+ migrations, factories) : slug, title, excerpt, body
   (HTML purifié, TipTap), couverture (medialibrary), auteur, `published_at`, `reading_time`,
   catégorie, status (Publication), SEO.
2. **Vitrine blog** : `/blog` (liste paginée, filtres catégorie, mise en avant), `/blog/{slug}`
   (article : couverture, contenu riche, date, temps de lecture, partage, articles liés, CTA).
   `x-tk.post-card`.
3. **Admin blog** : CRUD articles + catégories (brouillon/planifié/publié via `published_at`, ordre,
   médias, SEO par article).
4. **Intégrations** : 3 derniers articles sur la home (option) ; flux/`feed` (option).
5. **Feature flag** : `FeatureSettings.blog` (on).

## Étapes (PDAR)
1. **Plan** : schéma, écrans vitrine + admin, planification (published_at).
2. **Do** : modèles/migrations → vitrine (liste + article) → admin CRUD → intégrations.
3. **Assess** : tests (liste 200, article publié visible, brouillon/futur masqué, CRUD protégé,
   purification du body). Pint/Larastan. Rendu mobile + desktop.
4. **Review** : SEO (JSON-LD `BlogPosting`/`Article`, canonical), a11y, perf (images lazy).

## Tests (Pest)
- `/blog` 200 + pagination ; un post `published_at` futur ou `brouillon` est masqué en public.
- `/blog/{slug}` 200 pour un post publié ; HTML malveillant nettoyé (purifier).
- CRUD interdit sans permission `cms.manage`.

## Definition of Done
DoD du maître + : blog éditable et planifiable, articles premium, JSON-LD `Article`, intégration home,
tests verts.
