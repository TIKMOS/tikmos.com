# Lot 16 — Internationalisation (FR → EN)

> Hérite du **prompt maître**. Réf : `04` (i18n-ready), `03`.

## Objectif
Activer le **multilingue** (FR par défaut, **EN** en second) proprement : extraction des chaînes,
sélecteur de langue, contenu CMS traduisible. i18n pensée dès le départ → ce lot la **réalise**.

## Périmètre / livrables
1. **Chaînes UI** : toutes via `lang/fr/` (+ `lang/en/`). Audit : zéro texte en dur dans les vues.
2. **Sélecteur de langue** (header) + middleware de locale (URL préfixe `/en` ou session — choisir
   et documenter), persistance.
3. **Contenu CMS traduisible** : stratégie pour les modèles éditables (champs traduisibles ou table
   de traductions). Décider le niveau (UI seule vs contenu) selon le besoin client et **documenter**.
4. **Formats** : dates/nombres/devise localisés.

## Étapes (PDAR)
1. **Plan** : périmètre (UI seule d'abord ?), mécanisme de locale, modèles à traduire.
2. **Do** : extraction chaînes → sélecteur/middleware → (contenu traduisible) → formats.
3. **Assess** : tests (locale switch change la langue, fallback FR, pas de clé manquante). Pint/Larastan.
4. **Review** : SEO multilingue (hreflang) si URLs par langue.

## Tests (Pest)
- Bascule EN : une page clé rend les libellés EN ; clé absente → fallback FR.

## Definition of Done
DoD du maître + : UI traduisible FR/EN, sélecteur fonctionnel, formats localisés, (contenu CMS selon
décision), tests verts.
