# TIKMOS — Interface d'accueil & interactions

> Spec UI de la page d'accueil (vitrine) et des interactions transverses. Applique la charte
> **Encre + Or champagne** (`01`). Objectif : **premium, orienté offre, action immédiate**.
> Dernière mise à jour : 2026-06-30.

---

## 1. Intention
La home doit, en un défilement, faire comprendre **qui** ([le Professeur]), **ce qu'il offre**
(services + boutique), **pourquoi lui** (preuves, équipe), et **inviter à agir** (devis / WhatsApp /
boutique). Ton **éditorial-luxe**, sobre, confiant. **Pas un CV** : une vitrine d'offre.

---

## 2. Structure des sections (ordre proposé)

1. **Header** — logo, nav (Accueil · Services · Réalisations · Boutique · Équipe · Contact), CTA
   **Demander un devis** + **Connexion**. Lien actif = **filet or**.
2. **Hero** — kicker mono+or (« Mathématiques · Data · IA »), titre fort « **Je suis [le Professeur].
   Je conçois, je forme, je livre.** », sous-titre (promesse concrète), **2 CTA** (primary « Demander
   un devis » + gold « Découvrir mes services »), visuel réel (portrait premium). Bouton **WhatsApp**
   flottant dès le hero.
3. **Bandeau preuve** — chiffres clés (années d'expérience, projets livrés, clients formés…) en
   **compteurs animés** + éventuel **marquee** de logos clients/marques.
4. **Métiers en bref** — 4 cartes (Formation · Conception de solutions · Services d'appui · Boutique
   de luxe), chacune avec icône Lucide, 1 phrase, lien. Survol = élévation + filet or.
5. **Services (aperçu)** — 3–6 `service-card` issues du CMS (les mises en avant), CTA « Tous les
   services ».
6. **Approche / méthode** — courte narration (« cadrage → conception → livraison → support ») en
   stepper horizontal, ton rassurant.
7. **Réalisations (aperçu)** — 3 `project-card` (étude de cas, résultat chiffré), CTA « Voir les
   réalisations ».
8. **Boutique (teaser)** — bande premium : 3–4 `product-card` chaussures de luxe (image couleur),
   CTA gold « Voir la boutique ».
9. **Équipe (aperçu)** — « Une équipe derrière chaque projet » + 3–5 avatars, CTA « Rencontrer
   l'équipe ».
10. **Témoignages** — slider (`testimonial-slider`) + note. Crédibilité.
11. **FAQ (extrait)** — 3–4 items en accordéon, CTA « Toutes les questions ».
12. **Bloc CTA final** — fond ivory/canvas, filet or, filigrane logo : « Un projet, une formation,
    une paire en vue ? » → **Demander un devis** + **WhatsApp**.
13. **Footer encre** — colonnes (offre, liens, légales, réseaux), filet or, mentions.

> Toutes les sections **éditables** (CMS/Settings). Aucune n'est figée en dur.

---

## 3. Composants mobilisés (`x-tk.*`)
`section`, `kicker`, `button` (primary/gold/secondary/ghost/whatsapp), `card`, `service-card`,
`project-card`, `product-card`, `team-card`, `stat` (compteur), `marquee`, `stepper`,
`testimonial-slider`, `accordion/faq`, `divider-gold`, `whatsapp-button`, `logo`, `seo`.

---

## 4. Interactions
- **Reveals au scroll** (fade/translate doux) — respect `prefers-reduced-motion`.
- **Hovers** : cartes (élévation + filet or), images secondaires (n/b → couleur).
- **Compteurs** animés à l'entrée en viewport.
- **Marquee** logos (pause au survol).
- **Slider** témoignages (auto + flèches + swipe mobile).
- **FAQ** accordéon (un ouvert à la fois).
- **Transitions** `wire:navigate` entre pages (pas de full reload).
- **Toasts** sur actions (devis envoyé, ajouté au panier…).

---

## 5. Chargement & perf
- **Pas de spinner plein écran** après le 1er rendu : **skeletons** ciblés.
- Hero **LCP-friendly** : image dimensionnée, `fetchpriority=high`, fonts `swap`.
- Images **lazy + blur-up**, ratios réservés (zéro CLS).
- Au-dessous de la ligne de flottaison : chargement différé des sliders/marquee.

---

## 6. Responsive
- **Mobile-first** : hero lisible, CTA pleine largeur < `sm`, nav en **bottom-nav** ou menu.
- Grilles : 1 col (mobile) → 2/3 (tablette) → 3/4 (desktop). Testé **360 → 1440 px**.
- **Touch ≥ 44px**, safe-area (si PWA).

---

## 7. SEO & a11y de la home
- `<h1>` unique (hero), Hn structurés, `alt` sur visuels, meta + OG + JSON-LD `Person`/
  `ProfessionalService`.
- Contraste AA (or texte = `gold-600`), focus visibles (indigo), ordre de tabulation logique.

---

## 8. Checklist accueil
- [ ] Hero clair « je suis / je propose », 2 CTA + WhatsApp.
- [ ] Preuves (chiffres/logos) + équipe visibles « above the fold » ou juste après.
- [ ] Aperçus Services / Réalisations / Boutique alimentés par le CMS.
- [ ] Interactions sobres présentes ; toasts ; skeletons.
- [ ] Responsive 360→1440, a11y AA, LCP < 2.5 s, zéro CLS.
- [ ] Tout le contenu éditable (rien en dur).
