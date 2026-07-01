# TIKMOS — Documentation

Plateforme web personnelle **premium** de **[le Professeur]** — professeur certifié en sciences
mathématiques, ingénieur data science & IA. Une seule adresse pour **présenter l'homme et son
équipe**, **vendre ses services** (formation, conception de solutions informatiques, installation,
assistance administrative, orientation professionnelle…) et sa **boutique de chaussures de luxe**,
avec **espace client** (suivi des commandes & devis) et **espace administration** (back-office).

Domaine cible : **tikmos.com**. Stack **Laravel 13 / TALL**. Démarche **docs-first, phasée** —
on écrit la spec avant le code, on teste de A à Z.

> Base technique : ce projet est **dérivé de l'installation Sezy** (copie complète Laravel + vendor +
> node_modules) puis **repalettisé et reciblé** vers Tikmos. Le code hérité (composants `x-sz.*`,
> modèles colis/vols, etc.) sert de **socle réutilisable** : on garde l'infrastructure (auth, RBAC,
> medialibrary, settings, CMS) et on remplace le métier.

## Index
| Doc | Contenu |
|---|---|
| [00-cadrage-tikmos](00-cadrage-tikmos.md) | Cadrage produit : client, métiers, vision, périmètre, personas, phasage |
| [01-charte-graphique](01-charte-graphique.md) | Identité visuelle **Encre + Or champagne** : tokens, typo, composants `x-tk.*` |
| [02-stack-et-auth](02-stack-et-auth.md) | Stack Laravel 13/TALL, auth + RBAC (Spatie), 2FA, espaces |
| [03-cadre-fonctionnel](03-cadre-fonctionnel.md) | Acteurs, espaces, pages, parcours, états, notifications |
| [04-conventions-architecture](04-conventions-architecture.md) | Arborescence, code, composants, API-like, sécurité, SEO, perf, PWA, déploiement |
| [05-base-de-donnees](05-base-de-donnees.md) | Schéma, tables, relations, enums, index, settings |
| [06-interface-accueil](06-interface-accueil.md) | UI accueil premium, sections, interactions, chargement |
| **[30-systeme-paiement](30-systeme-paiement.md)** | **V3** — abstraction paiement portable + **KKiaPay** (online↔COD, évolutif) |
| **[31-module-images](31-module-images.md)** | **V3** — module images complet (upload/galerie/médiathèque, WebP/AVIF) |
| **[32-journal-activite](32-journal-activite.md)** | **V3** — journal d'activité & audit granulaire par acteur |
| **[33-audit-et-nettoyage](33-audit-et-nettoyage.md)** | **V3** — plan : retrait Sezy, boutique, QA espaces, configurabilité |
| **[prompts/](prompts/README.md)** | **Bibliothèque de prompts 360** : prompt maître + 19 lots (fondations → vitrine → commerce → blog → back-office → SEO/i18n/PWA → prod) |

## Décisions verrouillées
- **Positionnement** : site **personnel premium orienté offre** — « *Je suis [le Professeur], voici
  mes services et mes produits.* » Met l'homme en avant, donne l'impression (vraie) d'une **équipe**
  derrière (page dédiée). Pro, simple, concret, interactif, responsive. **Pas un CV en ligne.**
- **Métiers** : (A) Expertise & **formation** · (B) **Conception de solutions informatiques** ·
  (C) Services d'appui (**installation info**, **assistance gestion administrative**, **orientation
  professionnelle**, autres) · (D) **Boutique chaussures de luxe**.
- **Services 100 % gérés depuis l'admin** : création/édition de nouveaux services sans dév (CMS).
- **Boutique « à la manière de Sezy »** : panier maison, commande, **paiement à la livraison**, suivi
  ; WhatsApp comme canal.
- **Blog / actualités** inclus (autorité + SEO).
- **Logo** retenu : `brand/logo-tikmos-dark-gold.jpeg` (marine + or champagne).
- **Espaces** : Vitrine publique · **Espace client** (suivi devis/commandes) · **Espace admin** (CMS
  + gestion). RBAC dès le départ.
- **Stack** : Laravel 13 · Livewire 3 + **Volt** · Tailwind 4 (`@theme`) · Alpine.js · MySQL (prod) /
  SQLite (dev) · **PHP 8.3**. Spatie Permission/Settings/MediaLibrary, `mews/purifier`, TipTap.
- **Charte** : **Encre + Or champagne** — voir `01-charte-graphique.md`. Clair, aéré, noir & blanc
  dominant + **filets or** (luxe) + **indigo nuit** (data/IA). Vert WhatsApp imposé. **Non négociable.**
- **Langue** : **FR** au lancement (EN prévu, i18n-ready).

## Questions ouvertes (à confirmer avec le client)
1. **Nom & titre exacts** à afficher (actuellement `[le Professeur]`) + coordonnées/réseaux/WhatsApp.
2. **Liste finale et libellés des services** (+ tarification affichée ou « sur devis »).
3. **Domaines** à sécuriser autour de `tikmos.com` (ex. `.bj`, `.africa`).
4. **PWA** : installable dès V1 ou phase ultérieure ?

> Résolu (2026-06-30) : **logo** (dark-gold), **services gérés en admin**, **boutique modèle Sezy**
> (paiement à la livraison), **blog inclus**. Détail : [00-cadrage §10](00-cadrage-tikmos.md).
