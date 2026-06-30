# Lot 07 — Boutique : catalogue

> Hérite du **prompt maître**. Réf : `05` (products, product_categories), `03` (P2), `01`.

## Objectif
Construire le **catalogue boutique** de chaussures de luxe : catégories, produits (galerie, tailles,
prix, stock) et fiches produit désirables. (La commande/panier = lot 08.)

## Périmètre / livrables
1. **Modèles `ProductCategory`** + **`Product`** (+ migrations, factories) : slug, name, brand,
   excerpt, body (HTML), price/compare_at, currency, sku, **sizes (json)**, **stock_status** (enum),
   position, status, **galerie médias** (conversions).
2. **Vitrine boutique** : `/boutique` (grille filtrable par catégorie/marque, tri prix), pagination ;
   `/boutique/{slug}` (galerie, sélection **taille**, prix, dispo, **CTA commander** — relié au lot 08).
   `x-tk.product-card`, `x-tk.price-tag`.
3. **Admin boutique — produits** : CRUD produits + catégories (ordre, publication, médias, stock,
   tailles), prix barré/promo.
4. **Feature flag** : la boutique s'active via `FeatureSettings` (masquable si non prête).

## Étapes (PDAR)
1. **Plan** : schéma produit/catégorie, filtres, écran fiche, composants.
2. **Do** : modèles/migrations → vitrine (liste + fiche) → admin CRUD → flag.
3. **Assess** : tests (liste 200, filtres, produit épuisé affiché correctement, fiche 200, CRUD
   protégé, flag off masque la boutique). Pint/Larastan. Rendu mobile + desktop.
4. **Review** : charte (images couleur, élégance luxe), perf (lazy galerie), SEO (JSON-LD `Product`).

## Tests (Pest)
- `/boutique` 200 + pagination ; filtre catégorie fonctionne.
- Produit `epuise` : CTA commander désactivé/adapté.
- `FeatureSettings.shop = false` → routes boutique masquées (404/redirect).

## Definition of Done
DoD du maître + : catalogue boutique éditable et filtrable, fiches premium, stock/tailles gérés,
JSON-LD produit, tests verts.
