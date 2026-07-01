# Lot 20 — Module de gestion des images

> Hérite du **prompt maître**. Réf : `31-module-images.md`.

## Objectif
Livrer un **module images complet, rapide, interactif et solide** réutilisé partout : upload
glisser-déposer, galeries réordonnables, alt/couverture, médiathèque, conversions WebP/AVIF, rendu
`srcset`/lazy/blur-up.

## Périmètre / livrables
1. Trait **`HasStandardImages`** (collections + conversions homogènes WebP/AVIF + responsive images).
2. Composants **`x-tk.media-gallery`** (multi + drag + alt + couverture), **`x-tk.media-picker`**
   (médiathèque), **`x-tk.image`** (srcset/sizes/lazy/blur-up). Enrichir `x-tk.media-input` (progress,
   recadrage).
3. **`ImageService`** (validation stricte, recadrage serveur `spatie/image`, purge orphelins, alt),
   **`MediaSettings`** + écran admin (formats, poids, qualité, AVIF).
4. Branchement sur services, réalisations, produits, blog, équipe, CMS accueil, partenaires.
5. Sécurité médias privés (URLs signées), commandes `media:prune` / `media:regenerate`.

## Étapes (PDAR) + DoD
Voir `31-` §8. Tests : upload valide/refusé, conversions, alt, réordonnancement, URLs signées, purge ;
Lighthouse images ≥ 90. Suite verte, Pint/Larastan.
