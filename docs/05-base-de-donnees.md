# TIKMOS — Base de données

> Schéma cible : tables, colonnes clés, relations, enums, index, settings. SQLite (dev) / MySQL (prod).
> Les migrations sont écrites **au fil des lots** ; ce doc est la **carte de référence**.
> Dernière mise à jour : 2026-06-30.

---

## 1. Conventions
- Tables **pluriel snake_case**, modèles **singulier**. Clés `id` (bigint auto). `created_at/updated_at`.
- **Enums PHP** castés (statuts/publication). **Slugs** uniques sur contenus publics.
- **Montants** : `unsigned integer` en **plus petite unité** (ex. centimes XOF/EUR — fixer la devise
  dans les settings) **ou** `decimal(12,2)` — choisir et documenter ici à l'implémentation.
- **Médias** via `spatie/medialibrary` (table `media`, polymorphe) — pas de colonnes chemins en dur.
- **Soft deletes** sur entités métier sensibles (clients, commandes, demandes).
- **Index** sur FK, `slug`, `status`, colonnes de tri/filtre fréquentes.

---

## 2. Identité & accès

### users
| Colonne | Type | Notes |
|---|---|---|
| id | bigint | |
| name | string | |
| email | string unique | |
| email_verified_at | timestamp null | |
| password | string | |
| phone | string null | format international (drapeaux) |
| is_active | bool | comptes équipe activés par admin |
| two_factor_* | — | OTP email (secret/expiry/confirmé) |
| last_login_at | timestamp null | |
- Rôles/permissions via **Spatie** (`roles`, `permissions`, `model_has_*`, `role_has_permissions`).
- **trusted_devices** (device-remember signé), **login_logs** (IP, UA, résultat) — hérités.

---

## 3. Offre de services (A, B, C)

### services
| Colonne | Type | Notes |
|---|---|---|
| id, slug | | slug unique |
| category | enum/string | formation · conception · installation · assistance · orientation · autre |
| title, excerpt | string | |
| body | text (HTML purifié) | description riche |
| price_label | string null | « à partir de … » ou « sur devis » |
| price_amount | int null | optionnel |
| icon | string null | nom Lucide |
| position | int | ordre d'affichage |
| status | enum Publication | brouillon/publié/archivé |
| seo_* | — | titre/description/og (ou table seo polymorphe) |
- Médias : illustrations (medialibrary). Relation : `hasMany serviceRequests`.

### service_requests (demandes de devis)
| Colonne | Type | Notes |
|---|---|---|
| id, reference | | référence lisible (ex. `TKM-DEV-AB12CD`) |
| user_id | FK null | null si envoyée sans compte (puis rattachable) |
| service_id | FK null | service concerné |
| name, email, phone | | coordonnées (si pas de compte) |
| company | string null | |
| message | text | besoin |
| budget_hint | string null | |
| status | enum ServiceRequestStatus | voir 03 §4 |
| assigned_to | FK users null | membre équipe affecté |
| created_at… | | |
- Relations : `belongsTo user/service`, `hasOne quote`, `hasMany messages`, `morphMany media`
  (pièces jointes), `hasMany events` (historique de statut).

### quotes (devis)
| Colonne | Type | Notes |
|---|---|---|
| id | | |
| service_request_id | FK | |
| reference | | `TKM-Q-…` |
| amount | int | montant proposé |
| currency | string | |
| valid_until | date null | |
| body | text null | détail/conditions |
| status | enum | brouillon/envoyé/accepté/refusé/expiré |
| sent_at, decided_at | timestamp null | |

### request_events (timeline)
`id, service_request_id (FK), from_status, to_status, note, user_id (auteur), created_at`.

---

## 4. Réalisations & équipe

### projects (réalisations)
`id, slug, title, excerpt, body(HTML), client_name(null), category, results(text/json), year,
position, status(Publication), seo_*`. Médias (galerie) via medialibrary.

### team_members
`id, name, role_title, expertise(string/json), bio(text), socials(json null), position,
is_published(bool)`. Photo via medialibrary. *(Hérité de sezy, à repalettiser.)*

---

## 5. Boutique (D)

### product_categories
`id, slug, name, position, is_published`.

### products
| Colonne | Type | Notes |
|---|---|---|
| id, slug | | |
| product_category_id | FK null | |
| name, excerpt | | |
| body | text (HTML) | description |
| brand | string null | marque (luxe) |
| price_amount | int | prix |
| currency | string | |
| compare_at_amount | int null | prix barré (promo) |
| sku | string null | référence |
| stock_status | enum StockStatus | en_stock/bientôt/épuisé |
| sizes | json | tailles disponibles (ex. [40,41,42]) |
| position | int | |
| status | enum Publication | |
- Galerie via medialibrary (plusieurs images + conversions). `hasMany orderItems`.

### orders (commandes boutique)
| Colonne | Type | Notes |
|---|---|---|
| id, number | identifiant + string unique | `TKM-CMD-YYYY-00001`, numéros historiques conservés |
| user_id | FK null | invité possible |
| name, email, phone, address | | livraison |
| channel | enum | whatsapp · site |
| status | enum OrderStatus | voir 03 §4 |
| payment_status | enum PaymentStatus | défaut `en_attente` → `payé` **à la livraison** (modèle Sezy) |
| payment_method | enum/string null | cash · mobile_money (à la remise) ; en ligne = phase ultérieure |
| subtotal, shipping, total | int | |
| currency | string | |
| note | text null | |
- Relations : `hasMany items`, `hasMany events` (timeline statut).

### order_number_sequences — correction 08

`year` (entier, clé primaire) et `last_number` (entier non négatif). Le compteur
annuel est verrouillé et incrémenté dans la transaction de création de commande.
L'année provient du fuseau `app.timezone` ; le compteur ne dépend ni du nombre de
commandes restantes ni de `created_at`. Une suppression ne diminue jamais ce compteur.
Un rollback annule aussi l'allocation non validée ; la séquence n'est pas garantie
sans trous. La contrainte unique sur `orders.number` reste présente.

Nouvelle migration : `2026_10_26_010000_create_order_number_sequences_table.php`.
Sur une installation historique, elle exige des plafonds annuels explicitement
vérifiés, incluant les numéros supprimés connus, dans `ORDER_NUMBER_SEQUENCE_FLOORS`.
Elle refuse les données incompatibles avant de créer la table et ne réécrit aucune
commande. Les références des autres modules ne changent pas.

Voir les [conditions de déploiement](DEPLOIEMENT.md#numérotation-atomique-des-commandes--correction-08)
et les [preuves et limites](validations/2026-10-26-numerotation/README.md).

### order_items
`id, order_id(FK), product_id(FK null), name(snapshot), size, unit_price(int), qty, line_total(int)`.

> **Panier** : maison, en **session** (invité) → fusion à la **connexion**. Pas de table `carts`
> obligatoire au lancement (session) ; l'ajouter si panier persistant requis.

---

## 6. Contenu & social proof (CMS)

### testimonials
`id, author_name, author_role(null), quote(text), rating(int null), is_published, position`. Photo
via medialibrary. (Hérité — à repalettiser.)

### faq_items
`id, question, answer(HTML), category(null), position, is_published`. (Hérité.)

### legal_documents
`id, slug(mentions-legales/confidentialite/cgv/cookies), title, body(HTML), updated_at`. (Hérité.)

### blog — post_categories / posts
- **post_categories** : `id, slug, name, position, is_published`.
- **posts** : `id, slug, post_category_id(FK null), title, excerpt, body(HTML purifié),
  author_id(FK users null), reading_time(int null), published_at(timestamp null),
  status(enum Publication), seo_*`. Image de couverture + galerie via medialibrary.
- Index : `posts(status, published_at)`, `posts.slug` unique.

### content blocks / pages (accueil & pages éditables)
Selon besoin : table `content_blocks` (`key, title, body(HTML), image, position, is_published`) **ou**
sections pilotées par **Settings**. Le **hero** et les sections d'accueil = Settings + blocs.

---

## 7. Configuration globale (Spatie Settings)
- **GeneralSettings** : nom affiché, baseline, logo, devise, fuseau.
- **ContactSettings** : email, téléphones, **numéro WhatsApp**, adresse, horaires, réseaux (json).
- **SeoSettings** : titre/description par défaut, og image, JSON-LD `Person`/`Organization`.
- **FeatureSettings** (groupe `features`) : flags `blog` **on**, `pwa` off, `online_payment` off
  (paiement à la livraison au lancement). **L'activation boutique reste portée par
  `ShopSettings::$enabled`** (source unique boutique, + `cod_enabled`/`currency`).
- **SeoSettings** (groupe `seo`) : `default_title/description`, `og_image`, JSON-LD
  `person_name/person_job_title/person_url`, `organization_name`, `twitter_handle`.

---

## 8. Médias, audit, RGPD
- **media** (medialibrary, polymorphe) — toutes images/fichiers, avec conversions (thumb/web).
- **activity_log** (spatie/activitylog) — audit des actions sensibles (devis, commandes, rôles, CMS).
- **login_logs**, **trusted_devices** — sécurité auth.
- RGPD : effacement client = anonymisation/suppression de `users` + dépendances (à cadrer).

---

## 9. Index & intégrité (rappels)
- FK avec `cascade`/`nullOnDelete` selon le cas (ex. supprimer un service ne supprime pas les demandes
  → `nullOnDelete`).
- Index uniques : `users.email`, tous les `slug` publics, `*.reference`.
- Index composés utiles : `service_requests(status, assigned_to)`, `orders(status, created_at)`,
  `products(status, product_category_id)`.

---

## 10. Tables héritées à trancher (nettoyage progressif)
Le socle copié contient des tables/métier **sezy** non utilisés ici : `parcels`, `parcel_events`,
`parcel_requests`, `flights`, `couriers`, `deliveries`, `leads`, `preferred_sites`, `partners`,
`review_requests`… → **migrations de suppression** au lot concerné (ou ignorer si non migrées).
Conserver : users/auth, spatie (permission/settings/medialibrary/activitylog), testimonials,
faq_items, legal_documents, team_members (repalettisés).
