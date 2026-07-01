# TIKMOS — Module de gestion des images

> Module **complet, rapide, interactif et solide** pour gérer les images partout sur le site :
> upload glisser-déposer, galeries réordonnables, recadrage, texte alternatif, conversions
> automatiques (webp/avif), et une **médiathèque réutilisable** dans chaque CRUD admin.
> Socle : **Spatie MediaLibrary** (déjà installé). Rendu : lazy + srcset + zéro CLS.
> Dernière mise à jour : 2026-07-01.
> Bonnes pratiques 2026 : formats next-gen (AVIF/WebP), responsive `srcset`/`sizes`, `loading="lazy"`,
> `fetchpriority` pour le LCP, dimensions réservées, compression maîtrisée.

---

## 1. Objectifs
- **Une seule expérience d'upload** partout (services, réalisations, produits, blog, équipe, CMS
  accueil, hero, partenaires) — cohérente, rapide, robuste.
- **Interactif** : glisser-déposer multiple, **aperçu instantané**, **barre de progression**,
  **réordonnancement** (drag), suppression, **recadrage** au ratio attendu, **alt text** par image.
- **Solide** : validation stricte (type/poids/dimensions), conversions non bloquantes, purge des
  fichiers orphelins, accès sécurisé aux médias privés (URLs signées).
- **Performant** : génération **WebP/AVIF** + tailles responsives, rendu `srcset`/`sizes`, lazy-load,
  placeholders blur-up (LQIP), zéro décalage de mise en page.

---

## 2. Socle technique
- **Spatie MediaLibrary** : collections par modèle, conversions, responsive images, associations
  polymorphes (`media` table). Déjà utilisé (Product, Project, Service, Post, TeamMember…).
- **Conversions** standard centralisées (trait `HasStandardImages`) : `thumb` (160), `card` (800),
  `full` (1600), toutes en **WebP** (option AVIF), `->nonQueued()` en dev / **queue** en prod.
- **Responsive images** : `->withResponsiveImages()` sur les collections vitrine → `srcset` auto.

---

## 3. Composants UI (`x-tk.*`)
| Composant | Rôle |
|---|---|
| `x-tk.media-input` (existe) | upload **simple** (1 image) + aperçu — à enrichir (progress, recadrage). |
| `x-tk.media-gallery` (**nouveau**) | upload **multiple** + grille réordonnable (drag) + suppression + alt par image + définir la couverture. |
| `x-tk.media-picker` (**nouveau**) | **médiathèque** : parcourir/rechercher les médias déjà uploadés et en réutiliser un (évite les doublons). |
| `x-tk.image` (**nouveau**) | rendu front normalisé : `srcset`/`sizes`, `loading`, `width/height`, blur-up. |

Interactions : `wire:model` + `Livewire\WithFileUploads` (upload temporaire → validation → média),
Alpine + `sortable` (déjà présent via `x-tk.sortable-list`) pour l'ordre, `croppie/cropperjs`
(léger) pour le recadrage optionnel côté client avant envoi.

---

## 4. Service & modèle
```
app/Media/
  HasStandardImages.php     # trait : collections + conversions homogènes
  ImageService.php          # upload validé, recadrage serveur (Intervention), purge orphelins, alt
  Data/UploadedImage.php    # DTO
```
- **Validation** : mimes png/jpg/jpeg/webp/avif, poids max (config), dimensions min/max, nombre max
  par collection.
- **Optimisation** : `spatie/image` (Intervention/Imagick) → conversions WebP/AVIF, qualité réglable.
- **Alt text & légende** : stockés dans `media.custom_properties` (`alt`, `caption`).
- **Sécurité** : médias publics via disque `public` (URLs directes + cache) ; médias privés
  (justificatifs devis) via disque privé + **URLs signées** temporaires + policy.

---

## 5. Configuration admin
- **Réglages → Médias** : formats acceptés, poids max, qualité de compression, activer AVIF,
  activer les responsive images, quota par collection.
- Chaque écran CRUD utilise `x-tk.media-gallery` / `x-tk.media-picker` → expérience identique.

---

## 6. Rendu front (performance)
`<x-tk.image :media="$m" sizes="(max-width:640px) 100vw, 33vw" :priority="false" />` génère :
```html
<img src="…-card.webp" srcset="…-thumb.webp 160w, …-card.webp 800w, …-full.webp 1600w"
     sizes="…" width="800" height="600" loading="lazy" decoding="async" alt="…">
```
- Hero/LCP : `:priority="true"` → `loading="eager" fetchpriority="high"`.
- Placeholder **blur-up** (base64 LQIP) en `background` pendant le chargement → zéro CLS.

---

## 7. Robustesse & maintenance
- **Purge des orphelins** : commande `media:prune` planifiée (supprime médias sans modèle).
- **Idempotence upload** : re-générer les conversions manquantes (`media:regenerate`).
- **Migration douce** : les modèles existants adoptent `HasStandardImages` sans casser l'existant.

---

## 8. Definition of Done (lot d'implémentation)
- [ ] Trait `HasStandardImages` + conversions WebP/AVIF + responsive images.
- [ ] `x-tk.media-gallery` (multi + drag + alt + couverture) et `x-tk.media-picker` (médiathèque).
- [ ] `x-tk.image` (srcset/sizes/lazy/blur-up) utilisé sur toute la vitrine.
- [ ] `ImageService` (validation, recadrage, purge) + `MediaSettings` + écran admin.
- [ ] Branché sur services, réalisations, produits, blog, équipe, CMS accueil, partenaires.
- [ ] Tests : upload valide/refusé, conversions générées, alt persisté, URLs signées privées,
      réordonnancement, purge orphelins. Lighthouse images ≥ 90.
