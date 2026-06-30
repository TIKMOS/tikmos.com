# Lot 01 — Fondations & design system

> Hérite du **prompt maître** (`00-prompt-maitre.md`). Charte : `01`. Conventions : `04`.

## Objectif
Transformer le socle copié de Sezy en **base Tikmos** : identité d'app, tokens de charte
**« Encre + Or champagne »**, bibliothèque de composants **`x-tk.*`**, layouts, et premier nettoyage
du métier sezy non réutilisé — **sans casser la suite de tests**.

## Pré-requis
- Projet copié, `composer install` / `npm install` OK, `php artisan test` au vert sur le socle.

## Périmètre / livrables
1. **Identité app** : `.env` (`APP_NAME="Tikmos"`, `APP_URL`), `config/app.php`, titres par défaut,
   favicon placeholder, mise à jour `README.md` racine.
2. **Tokens charte** dans `resources/css/app.css` (`@theme`) : encre, paper, ivory, canvas, **gold/
   gold-600/gold-100**, **indigo/indigo-600/indigo-50**, neutres `tk-*`, rayons (btn 10 / card 16).
   Importer/auto-héberger les polices (Display serif + Inter + mono), `font-display: swap`.
3. **Renommage composants** : `resources/views/components/sz/` → `components/tk/` ; namespace
   `x-sz.*` → **`x-tk.*`** partout (vues, layouts). Repalettiser `button` (variantes primary/gold/
   secondary/ghost/danger/whatsapp), `card`, `badge`, `field/input/textarea/select`, `section`
   (ivory/paper/canvas/ink), `kicker` (mono + or), `stat`, `toast`, `stepper`, `skeleton`,
   `empty-state`, `avatar`, `logo`, `nav-bottom`, `whatsapp-button`.
4. **Nouveau composant** : `x-tk.divider-gold` (filet or réutilisable).
5. **Layouts** : `components/layouts/{public,app,admin,auth}.blade.php` repalettisés (header + footer
   encre + filet or pour public).
6. **Page démo design system** (`/design-demo`, non liée en prod) montrant tous les `x-tk.*`.
7. **Nettoyage léger** : retirer les composants/vues purement sezy inutiles (sans toucher au métier
   tant que ses tests existent) ; lister ce qui reste à supprimer pour les lots suivants.

## Étapes (PDAR)
1. **Plan** : inventaire des `x-sz.*` et de leurs usages ; liste des renommages et repalettisations.
2. **Do** : tokens → renommage → repalettisation → layouts → page démo.
3. **Assess** : `php artisan test` (adapter les tests qui référencent `sz`), Pint, Larastan ; rendu
   `/design-demo` (tous états : default/hover/focus/disabled), mobile + desktop.
4. **Review** : conformité charte (contraste or, focus indigo), zéro `x-sz.*` résiduel.

## Tests (Pest)
- La page `/design-demo` répond 200 et rend chaque composant.
- Test de non-régression : les layouts publics/app/admin/auth se montent sans erreur.

## Definition of Done
DoD du maître + : aucun `x-sz.*` restant, tokens charte appliqués, `/design-demo` complet, suite verte.
