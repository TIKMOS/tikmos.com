# TIKMOS — État d'avancement

Dernière mise à jour : 2026-07-01. Suite de tests : **213 verts** · Pint · Larastan · prod-ready.

## Lots livrés
| Lot | Sujet | État |
|---|---|---|
| 01 | Fondations & design system (`x-tk.*`, charte, layouts) | ✅ |
| 02 | CMS & configuration (Settings SEO/Features, médias, purifier) | ✅ |
| 03 | Auth & RBAC (rôles commercial/editor, 2FA, scoping) | ✅ |
| 04 | Vitrine — accueil & à propos | ✅ |
| 05 | Services & devis (catalogue + pipeline) | ✅ |
| 06 | Réalisations & équipe | ✅ |
| 07 | Boutique — catalogue (chaussures de luxe, tailles) | ✅ |
| 08 | Panier, commandes & WhatsApp (paiement à la livraison) | ✅ |
| 09 | Espace client (devis, commandes, RGPD) | ✅ |
| 10 | Back-office (dashboard KPIs, pilotage) | ✅ |
| 11 | Témoignages & FAQ (pages + accordéon) | ✅ |
| 11b | Blog & actualités | ✅ |
| 12 | Pages légales & RGPD (CGU/CGV, export/effacement) | ✅ |
| 13 | Notifications & emails (queue) | ✅ |
| 14 | SEO (sitemap, JSON-LD, robots) | ✅ |
| 17 | Tests & QA (CI GitLab) | ✅ |
| 18 | Mise en production (guide + prod caches OK) | ✅ |
| 15 | PWA | ⏸️ **différé** — infra présente, flag `features.pwa` = off |
| 16 | Internationalisation (EN) | ⏸️ **différé** — lancement FR d'abord |

## Différés (décision de priorité)
- **PWA (15)** : manifest + service worker + bouton d'install déjà scaffoldés (hérités). Activation via
  `FeatureSettings.pwa` + finalisation du SW quand souhaité. Faible valeur immédiate (site surtout vitrine).
- **i18n EN (16)** : gros chantier d'extraction de chaînes. À faire si une audience anglophone est visée.
  Le socle est i18n-ready (dossier `lang/`).

## Identité & contenu
- Charte : **noir + or champagne** (le noir de départ, après essais bleu roi/bleu nuit).
- Logo : header agrandi + détouré ; footer sur fond blanc arrondi.
- Fondateur : **Moïse TIKO** (intégré à « À propos »). Équipe : Moïse TIKO, Pesquidox AKODAZOUN,
  Carlos LEGBAGA.
- Nav : À propos · Services · Blog · Boutique. Contact via CTA « Demander un devis ».
- Partenaires : Alinkka, LegTik Se. Numéro : +229 01 97 44 06 15.

## Points ouverts / à confirmer
- Contenus réels (photos équipe, visuels boutique, textes légaux avec juriste, articles blog).
- « LegTik Se » : un ou deux partenaires ? (pris comme un seul).
- Activer la PWA et/ou l'anglais ?
- Domaines annexes autour de `tikmos.com`.
