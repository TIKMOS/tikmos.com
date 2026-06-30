# TIKMOS — Bibliothèque de prompts (360)

Bibliothèque de prompts **complète et cadrée** pour construire Tikmos de A à Z, module par module,
interface par interface, **avec tests**. Tout est encadré par le **prompt maître**.

## Comment l'utiliser
1. **Toujours** charger d'abord `00-prompt-maitre.md` (rôle, doc de référence, règles, DoD).
2. Lancer **un lot à la fois**, dans l'ordre. Chaque lot suppose les précédents **au vert**.
3. Appliquer **PDAR** (Plan → Do → Assess → Review) et la **Definition of Done** du maître.
4. Ne passer au lot suivant qu'après : tests verts (Pest), Pint clean, Larastan OK, rendu vérifié.

## Ordre des lots
| # | Lot | Objet |
|---|---|---|
| 00 | [Prompt maître](00-prompt-maitre.md) | Cadre non négociable (rôle, doc, règles, DoD). |
| 01 | [Fondations & design system](01-lot-fondations-design-system.md) | Rebrand `x-tk.*`, tokens charte, layouts, nettoyage socle. |
| 02 | [CMS & configuration globale](02-lot-cms-config.md) | Settings (contact/SEO/features), médias, contenu éditable. |
| 03 | [Auth & RBAC](03-lot-auth-rbac.md) | Login/2FA, rôles/permissions, espaces & gardes. |
| 04 | [Vitrine — accueil & profil](04-lot-vitrine-accueil-profil.md) | Home premium + à propos (storytelling, pas un CV). |
| 05 | [Services & devis](05-lot-services-devis.md) | Catalogue services + demande/devis (pipeline). |
| 06 | [Réalisations & équipe](06-lot-realisations-equipe.md) | Portfolio + page équipe. |
| 07 | [Boutique — catalogue](07-lot-boutique-catalogue.md) | Produits, catégories, fiches chaussures de luxe. |
| 08 | [Panier, commandes & WhatsApp](08-lot-panier-commandes-whatsapp.md) | Panier léger, checkout, commande WhatsApp, suivi. |
| 09 | [Espace client](09-lot-espace-client.md) | Tableau de bord client, demandes, commandes, profil. |
| 10 | [Back-office](10-lot-back-office.md) | Dashboard + gestion (demandes, boutique, CRM, équipe). |
| 11 | [Témoignages & FAQ](11-lot-temoignages-faq.md) | Avis (slider) + FAQ (accordéon), modération. |
| 11b | [Blog & actualités](11b-lot-blog-actualites.md) | Articles d'autorité (maths/data/IA), SEO, CRUD admin. |
| 12 | [Pages légales & RGPD](12-lot-legales-rgpd.md) | Mentions, confidentialité, CGV, cookies, droits. |
| 13 | [Notifications & emails](13-lot-notifications-emails.md) | Mails transactionnels (queue) + WhatsApp. |
| 14 | [SEO & performance](14-lot-seo-perf.md) | Meta, JSON-LD, sitemap, perf, Lighthouse. |
| 15 | [PWA (option)](15-lot-pwa.md) | Manifest, SW, install, push (si activée). |
| 16 | [Internationalisation](16-lot-i18n.md) | FR → EN, extraction des chaînes, sélecteur. |
| 17 | [Tests & QA](17-lot-tests-qa.md) | Couverture critique, factories, CI, polish. |
| 18 | [Mise en production](18-lot-production.md) | Build, caches, cron/queue, HTTPS, checklist live. |

## Conventions communes à tous les prompts
- **Charte** : `x-tk.*`, tokens « Encre + Or champagne », filets or parcimonieux.
- **Tests d'abord pensés, toujours exécutés** (`php artisan test`).
- **Contenu éditable** (CMS/Settings) — jamais de texte en dur sur la vitrine.
- **Sécurité & a11y & SEO** par défaut (voir maître §5).
- **Nettoyage progressif** du métier sezy hérité, sans casser la suite.
