# TIKMOS — Charte graphique & design system

> **Direction validée (2026-06-30) : « Encre + Or champagne ».**
> Base **claire, aérée, premium**, **noir & blanc dominant + bordures** (héritée de l'esprit Sezy),
> rehaussée d'un **accent Or champagne** (prestige : luxe chaussures + autorité académique) et d'un
> **accent Indigo nuit** (data / IA / liens). Premium, sobre, **pro et interactif**.
> Vert WhatsApp **imposé**. **Non négociable** — toute déviation se documente d'abord ici.

---

## 1. Principes
- **Clair et aéré** : blanc + **ivoire** très doux, beaucoup d'espace. Le noir est un **trait**, pas
  une masse.
- **Noir & blanc dominant** : encre = texte, **bordures**, CTA principal. On **joue les bordures et
  les filets** plutôt que les aplats.
- **Or champagne = signature de luxe**, par **petites touches** : filets, soulignés, séparateurs,
  hovers, puces, numéros de section. **Jamais** en aplat large ni en fond saturé.
- **Indigo nuit = signal « expertise tech/data »** : liens, focus, badges « IA/Data », graphiques.
- **Pas d'arc-en-ciel** dans le chrome ; la couleur vient de l'**or discret**, des **photos** et des
  pastels très légers. Exceptions : **vert WhatsApp** (imposé) et couleurs de **statut**.
- **Interactif** : reveals au scroll, hovers (élévation/filet or), compteurs, marquee, accordéons,
  slider témoignages, transitions `wire:navigate`. Toujours **élégant, jamais clinquant**.
- **Mobile-first**, responsive parfait (360 → 1440), a11y AA.

---

## 2. Couleur

### Socle
```
ink      #0b0b0c   texte, bordures fortes, CTA principal (aplat noir, parcimonieux)
paper    #ffffff   surfaces / cartes
ivory    #faf8f3   fond d'app premium (ivoire très doux, plus chaud que le gris)
canvas   #f5f4f1   alternative de section (légèrement plus marquée que l'ivoire)
neutres  tk-50…950 (texte muted #6f6f76, bordures #e4e1d9, lignes #ece9e1)
```

### Accents de marque
```
gold     #b9975b   Or champagne — filets, soulignés, puces, hover, séparateurs, numéros
gold-600 #a07f44   Or foncé — texte sur fond clair quand contraste nécessaire (AA)
gold-100 #f4ecdd   Or pâle — fond de carte/zone « premium » très léger
indigo   #1e2a52   Indigo nuit — liens, focus, badges data/IA, accents tech
indigo-600 #2b3a6b indigo plus clair (hover/lien)
indigo-50  #eef1f8 fond très léger zones « tech »
```

### Pastels doux (fonds de zones / cartes, très légers)
```
sand   #f6f1e8   ·  mist  #eef1f6   ·  sage #eef3ee
```

### Couleurs de signal (statut système uniquement)
```
success #16a34a · warning #d97706 · danger #dc2626 · info #2b3a6b  (+ variantes soft)
whatsapp #25D366  (icône & bouton officiels)
```

> ⛔ Pas d'or en gros aplat, pas de dégradés criards, pas de couleur d'accent vive hors statut.
> Le « foncé » se limite au **footer encre** et au **CTA encre** ponctuel, soulignés d'un **filet or**.

### Tokens Tailwind 4 (`@theme`, indicatif)
```css
@theme {
  --color-ink: #0b0b0c;
  --color-paper: #ffffff;
  --color-ivory: #faf8f3;
  --color-canvas: #f5f4f1;
  --color-gold: #b9975b;
  --color-gold-600: #a07f44;
  --color-gold-100: #f4ecdd;
  --color-indigo: #1e2a52;
  --color-indigo-600: #2b3a6b;
  --color-indigo-50: #eef1f8;
  --color-tk-50:#fbfaf7; --color-tk-100:#f3f1ea; --color-tk-200:#e4e1d9;
  --color-tk-300:#cfcabd; --color-tk-500:#9a958a; --color-tk-700:#54514a;
  --color-tk-900:#1c1b18; --color-tk-950:#0f0e0c;
  --radius-btn: 10px;
  --radius-card: 16px;
}
```

---

## 3. Typographie
| Rôle | Police | Usage |
|---|---|---|
| Display | **Fraunces** (ou Playfair Display) 600–900 | Titres, hero, gros chiffres — **caractère éditorial-luxe**. |
| Texte | **Inter** (400–600) | UI, corps, formulaires. |
| Mono | **Space Mono** / **JetBrains Mono** (500–700) | Références, codes, chiffres data, « touches » tech. |

- **Display à empattements (serif)** = signal premium/académique ; **Inter** = lisibilité moderne ;
  **mono** = clin d'œil data/IA. Sur-titre (**kicker**) en **mono majuscules + or**.
- Titres soignés : retours à la ligne calculés, échelle généreuse (hero 48→72px desktop).
- Fonts en `font-display: swap` (self-host via Vite/fontsource pour la perf).

> Alternative « 100 % géométrique » si le client préfère sans-serif partout : Display = **Archivo**
> (700–900). À trancher au design system (lot 01).

---

## 4. Formes & profondeur
- **Rayons modérés cohérents** : boutons = champs = **10px** ; cartes **16px** ; pilule réservée aux
  badges. **Pas de gros arrondis** (le luxe est anguleux/net).
- **Profondeur sans foncé** : ombres douces neutres, **layering** (cartes flottantes), **filets fins**
  (1px tk-200, ou 1px **or** sur éléments premium).
- **Filet or** = signature : trait de 1–2px sous les kickers, en séparateur de section, au survol des
  cartes, en soulignement de lien actif.
- **Images** : hero & boutique en **couleur** (chaleur, désir) ; visuels secondaires en **n/b doux**
  avec **retour couleur au survol**. Ratios réservés (zéro CLS), `loading="lazy"`, blur-up.

---

## 5. Boutons (formes & variantes)
Rayon `radius-btn` (10px). Hauteurs : sm 40 / md 48 / lg 52. Mêmes radius que les champs.
- **primary** : aplat **encre** + texte blanc — CTA principal, **un par zone** (ex. « Demander un devis »).
- **gold** : encre + **filet or** (ou texte/bordure or) — emphase **premium** (ex. « Voir la boutique »).
- **secondary** : **blanc + bordure** tk-200 (hover bordure encre) — action secondaire.
- **ghost** : sans fond, texte encre/indigo — actions tertiaires.
- **danger** : selon contexte (suppressions admin).
- **WhatsApp** : vert `#25D366`, **vraie icône** (glyphe officiel) — toujours reconnaissable.

---

## 6. Composants `x-tk.*`
> On **renomme** la bibliothèque héritée `x-sz.*` → `x-tk.*` et on la **repalettise** (lot 01).

Socle : `button`, `card`, `badge`, `field`, `input`, `textarea`, `select`, `phone` (drapeaux),
`section` (ivory/paper/canvas/ink), `kicker` (mono + or), `stat`, `avatar`, `toast`, `stepper`,
`skeleton`, `empty-state`, `nav-bottom`, `whatsapp-button`, `logo`, `seo`, `cookie-consent`,
`media-input`, `editor` (TipTap), `rating`, `toggle`, `flag`, `legal`.
À venir (backlog) : `accordion/faq`, `service-card`, `project-card`, `product-card`, `team-card`,
`testimonial-slider`, `price-tag`, `marquee`, `divider-gold`.

**États actifs** (nav, étapes, onglets) : **fond clair** (tk-100 / sand) + texte encre + **filet or**
sous l'actif — jamais de couleur vive.

---

## 6 bis. Logo officiel
Logo retenu : **`brand/logo-tikmos-dark-gold.jpeg`** — wordmark « tikmos » marine, le « i » est une
**silhouette à tête or champagne** (élévation / transmission). Il **valide la charte** : or = luxe +
prestige, marine = expertise data/IA. **Le marine du logo = token `indigo` (#1e2a52).**
- **Header** (fond ivoire/blanc) : version **claire** (marine + or), fond transparent.
- **Footer encre** & fonds sombres : version **inversée** (blanc + or).
- **Mark seul** (la silhouette) = favicon / icône app.
- ⚠️ Production : produire **SVG** (lockup + mark) + variantes transparentes (le JPEG fourni est
  basse-déf sur fond noir). Voir `brand/README.md`.

---

## 7. Layouts
- **Top-bar** mince *(optionnelle)* : baseline + WhatsApp + langue.
- **Header** : logo officiel (variante claire), nav (lien actif = filet or), CTA **Connexion** +
  **Demander un devis**.
- **Sections** : alternance **paper / ivory / canvas** + cartes pastel très légères ; **séparateurs
  filet or** ; **footer encre** (colonnes, réseaux, filigrane logo).
- **Bloc CTA** distinct sur fond ivory/canvas, **séparé du footer**, filet or + filigrane.
- **Auth** : panneau image (l'homme / l'équipe / produits) + overlay ; **inscription simple**.
- **App / Admin** : header + **bottom-nav** (mobile) / **sidebar** (admin), style clair, filet or
  sur l'item actif.

---

## 8. Interactions (cap)
Reveals au scroll, hovers (élévation + filet or), compteurs animés (chiffres clés), slider
témoignages, **FAQ accordéon**, **marquee** (logos clients / marques), transitions `wire:navigate`,
**toasts sur chaque action**, respect `prefers-reduced-motion`. **Sobriété** : l'animation sert la
lecture, jamais le spectacle.

---

## 9. PWA / perf / a11y
Manifest standalone + SW offline (si PWA activée), icônes du logo officiel, safe-area. Fonts `swap`,
images lazy/blur-up, **zéro CLS**, Lighthouse mobile **≥ 90**. Contraste **AA** (attention à l'or sur
blanc : utiliser `gold-600` pour le texte), focus visibles (anneau indigo), cibles tactiles ≥ 44px.

---

## 10. Checklist charte
- [ ] Clair/aéré, **noir & blanc + filets or + indigo** ; aucune couleur vive dans le chrome.
- [ ] Rayons modérés cohérents (boutons = champs = 10px ; cartes 16px).
- [ ] Logo officiel partout ; **vraie icône WhatsApp** ; kicker mono+or.
- [ ] Lien/onglet actif = filet or ; CTA encre parcimonieux ; emphase premium = variante `gold`.
- [ ] Images « mix » (hero/boutique couleur, secondaires n/b doux + hover).
- [ ] Interactions présentes & sobres ; toasts sur actions ; responsive 360→1440 ; a11y AA.
- [ ] Or texte = `gold-600` (contraste AA) ; focus indigo visible.
