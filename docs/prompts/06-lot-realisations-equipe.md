# Lot 06 — Réalisations & équipe

> Hérite du **prompt maître**. Réf : `05` (projects, team_members), `03`, `01`.

## Objectif
Renforcer la **crédibilité** : portfolio de **réalisations** (études de cas) et page **Équipe**
(l'effet « équipe derrière chaque projet »). Contenu éditable.

## Périmètre / livrables
1. **Modèle `Project`** (+ migration, factory) : slug, title, excerpt, body (HTML), client_name,
   category, results (chiffres/texte), year, position, status, galerie médias, SEO.
2. **Vitrine réalisations** : `/realisations` (grille filtrable) + `/realisations/{slug}` (contexte,
   solution, **résultats chiffrés**, galerie). `x-tk.project-card`.
3. **Modèle `TeamMember`** (repalettiser l'existant) : name, role_title, expertise, bio, socials,
   photo, position, is_published.
4. **Page Équipe** : `/equipe` (grille `x-tk.team-card`) + mise en avant du fondateur.
5. **Admin** : CRUD réalisations + CRUD équipe (ordre, publication, médias).

## Étapes (PDAR)
1. **Plan** : schémas, écrans vitrine + admin, composants cartes.
2. **Do** : modèles/migrations → vitrine → admin CRUD.
3. **Assess** : tests (pages 200, slug unique, brouillon non visible en public, CRUD admin protégé).
   Pint/Larastan. Rendu mobile + desktop.
4. **Review** : charte (images mix n/b→couleur au survol), SEO (JSON-LD CreativeWork/Person), a11y.

## Tests (Pest)
- `/realisations` et `/equipe` répondent 200 ; un projet `brouillon` n'apparaît pas en public.
- CRUD réalisations interdit sans permission `cms.manage`/dédiée.

## Definition of Done
DoD du maître + : portfolio et équipe éditables et publiés, cartes premium, tests verts.
