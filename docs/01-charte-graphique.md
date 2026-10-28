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
canvas   #faf8f3   fond principal, alias de l'ivoire ; paper pour les cartes/formulaires
neutres  tk-50…950 (texte muted #54514a, bordures décoratives #e4e1d9)
```

### Accents de marque
```
gold     #b9975b   Or champagne — filets, soulignés, puces, hover, séparateurs, numéros
gold-600 #a07f44   Accent décoratif — ne garantit pas AA pour les petits textes
gold-ink #6e5524   Texte or sur blanc/ivoire (7,03:1 / 6,62:1)
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
success #157f52 · warning #b5840a · danger #c0362c · info #1e2a52  (+ variantes soft/ink)
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
  --color-canvas: #faf8f3;
  --color-gold: #b9975b;
  --color-gold-600: #a07f44;
  --color-gold-ink: #6e5524;
  --color-gold-100: #f4ecdd;
  --color-indigo: #1e2a52;
  --color-indigo-600: #2b3a6b;
  --color-indigo-50: #eef1f8;
  --color-tk-50:#fbfaf7; --color-tk-100:#f3f1ea; --color-tk-200:#e4e1d9;
  --color-tk-300:#cfcabd; --color-tk-400:#9a958a; --color-tk-500:#6f6f76;
  --color-tk-600:#54514a; --color-tk-700:#3a3a40;
  --color-tk-900:#161618; --color-tk-950:#0b0b0c;
  --color-muted:#54514a;
  --color-control-line: #8a857a; /* contours des contrôles, distincts des filets décoratifs */
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
blanc : `gold-600` n'atteint que 3,74:1 ; employer `gold-ink` pour les petits textes
sur fonds clairs), focus visibles (indigo sur clair, blanc sur encre), cibles tactiles ≥ 44px.

---

## 10. Checklist charte
- [ ] Clair/aéré, **noir & blanc + filets or + indigo** ; aucune couleur vive dans le chrome.
- [ ] Rayons modérés cohérents (boutons = champs = 10px ; cartes 16px).
- [ ] Logo officiel partout ; **vraie icône WhatsApp** ; kicker mono+or.
- [ ] Lien/onglet actif = filet or ; CTA encre parcimonieux ; emphase premium = variante `gold`.
- [ ] Images « mix » (hero/boutique couleur, secondaires n/b doux + hover).
- [ ] Interactions présentes & sobres ; toasts sur actions ; responsive 360→1440 ; a11y AA.
- [ ] Contraste du texte mesuré sur son fond réel ; `gold-ink` sur clair, focus discernable.

### Ajustement ciblé d'accessibilité — 28 octobre 2026

Les variantes `gold` des boutons et les kickers utilisent `gold-ink`, sans modifier
l'or décoratif, les logos ou les fonds de marque. Les libellés et glyphes des
boutons WhatsApp deviennent encre sur le vert imposé : **9,92:1**, contre 1,98:1
pour le blanc. Les placeholders utiles utilisent `muted` sur leurs surfaces
blanches (**4,99:1**). Les contours de champs emploient `control-line` : **3,67:1**
sur blanc et **3,46:1** sur ivoire, contre 1,64:1 / 1,54:1 pour `tk-300`.
Les filets de carte décoratifs gardent le token `line`.

Le focus utilise l'indigo sur fond clair (**13,92:1** sur blanc) et une bordure
blanche dans les navigations encre (**19,67:1**). Les choix de paiement masqués
visuellement exposent leur focus sur la carte qui les contient. Les états inactifs
ne sont pas assimilés aux états actifs. Les tons opt-in `.dark` sont préservés,
mais cette recette ne valide pas globalement le mode sombre.

Ces mesures concernent les parcours essentiels, pas une certification WCAG de
toutes les vues ou de tous les contenus CMS. Selon les références W3C :
[texte](https://www.w3.org/WAI/WCAG22/Understanding/contrast-minimum.html),
[contrôles et états nécessaires](https://www.w3.org/WAI/WCAG22/Understanding/non-text-contrast.html).

### Harmonisation complémentaire de l'interface — 28 octobre 2026

Le texte secondaire `muted` reprend désormais le neutre existant `tk-600`
(`#54514a`) : **7,92:1** sur blanc, **7,46:1** sur ivoire et **7,04:1** sur sable.
Les valeurs 4,99:1 indiquées dans le lot précédent décrivent son état historique.
Les dates, durées et libellés informatifs sur clair utilisent ce texte secondaire,
les catégories et rôles utilisent `gold-ink`. Ne pas foncer globalement `tk-400` :
ce gris reste utile sur encre (**6,60:1**), notamment dans les navigations et le footer.

Les liens des contenus riches sont indigo, soulignés en permanence et indigo-600
au survol. Le rendu produit historique `prose-sz` reçoit le même traitement des
liens sans changement de sa mise en page. Les titres du footer passent de tk-500
à tk-400 (**3,95 → 6,60:1**). Les focus sur les surfaces encre sont blancs.
Les étapes de l'accueil utilisent les pastels existants et des numéros or foncé.

Le panneau photo d'authentification garde son image avec un voile plus opaque
(encre à 75 % minimum), texte blanc à 85 %, petit texte à 80 %. Les glyphes et
textes WhatsApp sont encre sur le vert existant, y compris sur la carte de contact.
Les couleurs de statut et leurs libellés sont conservés. Aucun mode sombre
automatique, nouveau logo ou contenu réel en base n'est introduit.

Le [rapport couleurs](validations/2026-10-28-couleurs-interface/README.md) détaille
les mesures, la recette et les réserves CMS/production. Les couleurs intégrées
aux contenus peuvent primer sur les styles du thème et demandent une revue
éditoriale séparée. Aucune conformité globale n'est déclarée.
