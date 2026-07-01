# TIKMOS — Audit & nettoyage (retrait Sezy, boutique, QA, configurabilité)

> Plan d'audit complet : **éliminer tout le domaine hérité Sezy**, **repositionner la boutique**,
> **fiabiliser les espaces client & admin** (zéro bug), et **rendre chaque zone configurable** depuis
> l'admin. Dernière mise à jour : 2026-07-01.

---

## 1. Audit « Sezy » — inventaire (55 fichiers)

### A. Domaine métier hérité à **supprimer** (colis / vols / livraison / achat)
- **Modèles** : `Parcel`, `ParcelEvent`, `ParcelRequest`, `Flight`, `Courier`, `Delivery`,
  `PreferredSite`, (à trancher : `Lead` = messages contact → **garder**, renommer sans « sezy »).
- **Enums** : `ParcelStatus`, `ParcelOrigin`, `ParcelType`, `FlightStatus`, `FlightDirection`,
  `DeliveryStatus`, `CommissionType`.
- **Services** : `ParcelService`, `FlightService`, `DeliveryService`, `CommissionCalculator`,
  `ReviewSolicitationService` (à recibler : sollicitation d'avis post-commande, pas post-colis).
- **Notifications** : `ParcelReady`, `ParcelStatusChanged` (+ `ReviewRequested` à recibler).
- **Routes** : `web` (`achat`, `vols`, `suivi`, `suivi/{ref}`) ; `app` (`colis`, `colis/{ref}`,
  `demandes/nouvelle`) ; `routes/livreur.php` entier.
- **Vues** : `public/achat`, `public/flights`, `public/track`, `app/colis-*`, `app/demande-create`,
  `app/alertes` (à recibler en notifications Tikmos), `livewire/pages/livreur/*`, layout `livreur`.
- **Migrations** : tables `parcels`, `parcel_events`, `parcel_requests`, `flights`, `couriers`,
  `deliveries`, `preferred_sites`, colonnes `courier_id` sur orders/users.
- **Rôles** : `agent_cotonou`, `agent_paris`, `conseiller_mobilite`, `livreur` (enum + permissions
  `colis.*`, `vol.*`, `couriers.*`, `deliveries.*`).
- **Tests** : `DeliveryTest`, `E2E/ParcelLifecycleTest` (+ nettoyer refs dans Auth/UserManagement).
- **Divers** : `config/tikmos.php` (géographie Cotonou/Paris → neutraliser), `AdminSeeder`/`DemoSeeder`/
  `ShowcaseSeeder` (retirer le seed colis/vols), `EnsureUserApproved` (commentaire), `.htaccess`.

### B. Résidus « sz / SZ » (marque, à normaliser en « tk »)
- **Numéro de commande** `SZ-CMD-…` → **`TKM-CMD-…`** (OrderService).
- **Événement toast** `sz-toast` / `sz-open-cookie-prefs` → `tk-toast` / `tk-open-cookie-prefs`
  (app.js + toast + cookie-consent + tous les `dispatch`).
- **PWA** : `public/sw.js`, `public/offline.html` (noms de cache + textes « SEZY »).
- **design-demo.blade** : contenu d'exemple Sezy → neutre.
- **Docs** : mentions « dérivé de Sezy » → note d'historique interne assumée (ou retirées).
- **Fichier** `database/settings/..._create_sezy_settings.php` : le nom contient « sezy » (groupe =
  `general`, pas de rename possible sans risque migration) → **laisser** ou ajouter migration neutre.

> **Stratégie** : suppression du domaine A **en un lot dédié** (`19-nettoyage-sezy`), avec
> `migrate:fresh` et suite verte. Normalisation B en passe mécanique dans le même lot.

---

## 2. Repositionnement boutique
- **Avant** : « chaussures de luxe ». **Après** : **« Équipements informatiques & accessoires de luxe
  (homme) »**.
- Formulation retenue partout : titre boutique **« Équipements & accessoires de luxe »**, sous-titre
  « Matériel informatique et accessoires haut de gamme, sélectionnés avec exigence. »
- **Modèle** : `Product` déjà générique (name, brand, price, category, `sizes` **optionnel**). Les
  « tailles » deviennent optionnelles/renommables en « variantes » (ex. capacité, coloris) — évolutif.
- **Catégories** cibles : *Ordinateurs & périphériques*, *Audio & accessoires*, *Objets & accessoires
  de luxe*. **Produits** de démo : laptop pro, souris/clavier premium, casque, sacoche cuir, montre,
  stylo, etc.
- Impacts : seed boutique, `shop-index`/`shop-show` (textes, icône `footprints`→`laptop`/`package`),
  carte métier accueil (HomepageSettings.services), `00-cadrage` §D, CGV.

---

## 3. Espace client — QA (complet, sans bug)
Checklist de revue de bout en bout :
- [ ] Tableau de bord : KPIs justes, liens valides, états vides.
- [ ] Mes devis : liste scoppée, détail, accepter/refuser, timeline, notifications reçues.
- [ ] Mes commandes : suivi, statuts, taille/variante, paiement (COD/en ligne selon config).
- [ ] Profil : édition, préférences notifications, RGPD (export/effacement), sécurité (appareils).
- [ ] Nav (bottom + top), responsive 360→1440, zéro erreur console/log.
- [ ] Retrait total des vues colis/alertes héritées.

## 4. Espace admin — QA (complet, solide)
- [ ] Chaque section derrière la bonne permission (RBAC vérifié).
- [ ] CRUD complets : services, réalisations, blog, produits, catégories, commandes, devis, équipe,
      témoignages, FAQ, partenaires, pages légales.
- [ ] Dashboard KPIs + alertes actionnables.
- [ ] Journal d'activité (voir `32-`) ; paiements (voir `30-`) ; médias (voir `31-`).
- [ ] Zéro écran Sezy résiduel (colis, vols, transporteurs, livreur).

## 5. Tout configurable depuis l'admin (structuré)
Objectif : **aucune valeur en dur** côté vitrine ; réglages **structurés** et simples.
- **Hub Réglages** réorganisé : *Général* (identité, contacts, réseaux, WhatsApp), *Accueil* (hero,
  sections, ordre, activation), *SEO*, *Paiements* (voir `30-`), *Médias* (voir `31-`),
  *Fonctionnalités* (flags : boutique, blog, PWA, paiement en ligne), *Légales*, *Équipe/Partenaires*.
- Chaque **zone de page** (hero, sections d'accueil, blocs) éditable ; **ordre & visibilité** des
  sections pilotés (déjà en place pour l'accueil, à étendre).
- **Navigation & footer** éditables (liens, ordre, visibilité) depuis l'admin.

---

## 5 bis. État lot 23 (QA + configurabilité) — ✅ livré
- [x] **Configurabilité** : nouvel écran **Réglages → Fonctionnalités** (boutique, blog, PWA)
      pilotable sans déploiement. → `reglages/features.blade.php`
- [x] **Blog** réellement conditionné : middleware `feature:blog` (404 si off) + lien retiré du
      menu et du pied de page. → `EnsureFeatureEnabled`
- [x] **PWA** conditionnée : le service worker ne s'enregistre que si la PWA est activée
      (méta `tk-pwa`) ; icônes/manifeste toujours servis.
- [x] **Boutique** : liens nav/footer déjà gardés par `ShopSettings::enabled` (404 si off).
- [x] **Nettoyage** : suppression des factories mortes (Parcel/Flight/…), neutralisation de la
      galerie `design-demo` (contenu Sezy → Tikmos), correction de la description SEO
      (« chaussures » → « équipements informatiques & accessoires de luxe »).
- [x] **Route integrity** : audit automatique — zéro `route()` mort dans les vues.
- [x] Tests Pest (6) : blog on/off, méta PWA on/off, boutique off, pilotage admin, RBAC de l'écran.
- [ ] **Restant (itératif)** : passage QA manuel responsive 360→1440 ; export CSV audit (voir `32-`) ;
      étendre l'ordre/visibilité des sections à toutes les pages (aujourd'hui : accueil).

## 6. Lots d'exécution (voir `prompts/`)
| Lot | Objet | Doc de réf |
|---|---|---|
| 19 | Nettoyage Sezy (suppression domaine + normalisation sz→tk) + repositionnement boutique | ce doc §1–2 |
| 20 | Module de gestion des images | `31-module-images.md` |
| 21 | Système de paiement (abstraction + KKiaPay) | `30-systeme-paiement.md` |
| 22 | Journal d'activité & audit granulaire | `32-journal-activite.md` |
| 23 | QA espaces client/admin + configurabilité complète | ce doc §3–5 |

Chaque lot suit le **prompt maître** (PDAR, tests de A à Z, DoD).
