# TIKMOS — Cadre fonctionnel

> Acteurs, espaces, pages, parcours, états, notifications. Spec fonctionnelle de référence.
> Dernière mise à jour : 2026-06-30.

---

## 1. Acteurs
- **Visiteur** (non connecté) : découvre, consulte services/boutique/réalisations, agit (devis,
  WhatsApp, commande).
- **Client** (connecté) : suit ses **demandes/devis** et **commandes boutique**, échange, paie.
- **Équipe** (commercial, support, boutique, editor) : gère selon son rôle.
- **Admin / [le Professeur]** : tout, dont le **CMS** et les **paramètres**.

---

## 2. Espaces & arborescence des pages

### A. Vitrine publique (`web`)
| Page | URL | Contenu / but |
|---|---|---|
| Accueil | `/` | Hero « Je suis [le Professeur] », promesse, métiers, preuves (chiffres, logos, témoignages), aperçu boutique, CTA devis/WhatsApp. |
| À propos | `/a-propos` | Storytelling (parcours, valeurs, méthode), photo, **pas un CV**. |
| Services | `/services` | Catalogue éditable (formation, conception, installation, assistance, orientation, …). |
| Détail service | `/services/{slug}` | Description, bénéfices, déroulé, FAQ, **CTA demander un devis**. |
| Réalisations | `/realisations` | Portfolio (études de cas / projets). |
| Détail réalisation | `/realisations/{slug}` | Contexte, solution, résultats, médias. |
| Équipe | `/equipe` | Membres (photo, rôle, expertise) — l'effet « équipe derrière ». |
| Boutique | `/boutique` | Catalogue chaussures de luxe (filtres, catégories). |
| Détail produit | `/boutique/{slug}` | Galerie, tailles, prix, **commander (WhatsApp / panier)**. |
| Panier | `/panier` | Panier léger (session → login). |
| Témoignages | `/temoignages` | Avis clients (slider + page). |
| FAQ | `/faq` | Questions fréquentes (accordéon). |
| Contact | `/contact` | Formulaire + coordonnées + **WhatsApp** + carte (option). |
| Légales | `/mentions-legales`, `/confidentialite`, `/cgv`, `/cookies` | Pages éditables. |
| Blog | `/blog`, `/blog/{slug}` | Articles (autorité, SEO) — éditables admin. ✅ |

### B. Espace client (`auth`, `verified`) — préfixe `/espace`
| Page | But |
|---|---|
| Tableau de bord | Aperçu : demandes en cours, commandes, messages. |
| Mes demandes / devis | Liste + détail (statut, devis, échanges, documents). |
| Nouvelle demande | Formulaire guidé (service concerné, besoin, pièces jointes). |
| Mes commandes boutique | Liste + suivi (timeline statuts). |
| Mes factures / paiements | Documents financiers (suivi simple). |
| Mon profil | Coordonnées, mot de passe, préférences notifs. |

### C. Back-office (`admin`, `role`, 2FA) — préfixe `/admin`
| Section | But |
|---|---|
| Tableau de bord | KPIs : demandes du mois, devis envoyés/acceptés, commandes boutique, CA estimé. |
| Demandes / devis | Pipeline (statuts), affectation, édition de devis, conversion. |
| Services | CRUD catalogue, ordre, mise en avant, médias. |
| Réalisations | CRUD portfolio. |
| Boutique — produits | CRUD produits, catégories, tailles, prix, stock, médias. |
| Boutique — commandes | Pipeline commandes (statuts), notes, contact client. |
| Clients (CRM léger) | Fiches, historique multi-offres. |
| Équipe & rôles | Invitations, rôles, permissions. |
| Témoignages / avis | Modération, mise en avant. |
| FAQ | CRUD. |
| CMS / Pages | Hero, à propos, blocs d'accueil, pages légales, blog (option). |
| Paramètres | Coordonnées, WhatsApp, réseaux, SEO global, cookies, feature flags. |

---

## 3. Parcours clés

### P1 — Prospect → demande de devis (cœur acquisition services)
1. Visiteur lit un **service** → clic **« Demander un devis »**.
2. Formulaire (service, besoin, budget indicatif, coordonnées, pièces jointes). Pas besoin de compte
   pour **envoyer** ; un compte est **créé/proposé** pour **suivre**.
3. Demande créée (`status = envoyée`) → **notif équipe** (email) + accusé client.
4. Équipe qualifie (`en étude`), prépare un **devis** (`devis envoyé`) → notif client.
5. Client **accepte/refuse** → `acceptée` → projet `en cours` → `livrée`. Échanges + documents.

**Validité des devis :** une date `valid_until` inclut toute la journée indiquée
jusqu'à 23:59:59.999999, dans le fuseau `config('app.timezone')` (actuellement UTC).
Sans date, aucune échéance automatique n'est appliquée. La validité est relue côté
serveur au moment de la décision ; un devis expiré ne peut plus être accepté ou
refusé. L'espace client affiche « Expiré » et propose de contacter l'équipe pour
un renouvellement, en précisant la référence du devis. Cette expiration affichée
ne réécrit ni les devis historiques ni les décisions déjà prises. Un nouvel envoi
exige une date du jour ou future, ou aucune date. Un devis déjà accepté ou refusé
reste en lecture seule : son montant, sa décision et sa date de décision sont conservés.

**Récupération d'une demande invitée :** les emails de réception, de devis et de
suivi dirigent une demande sans propriétaire vers un lien signé personnel valable
24 heures, avec jeton aléatoire dont seul le hachage est stocké. Le destinataire
se connecte ou s'inscrit avec la même adresse, vérifie l'adresse de son compte
(email de vérification signé valable une heure), puis confirme le rattachement.
La vérification d'adresse est demandée dans ce parcours ; elle ne rend pas
obligatoire une vérification globale pour tous les autres écrans du site.

Un affichage GET ne rattache rien. Le POST avec protection CSRF revérifie sous
verrou l'échéance, le destinataire, le compte actif et vérifié, et l'absence de
propriétaire. Le rattachement, l'invalidation de tous les jetons de la demande
et l'événement d'historique forment une transaction unique. Une tentative refusée
ne consomme pas le lien ; une demande déjà liée n'est jamais transférée. Montants,
statuts, validité et décisions du devis sont conservés. L'accès au détail reste
strictement limité au propriétaire.

Pour une ancienne demande ou un lien expiré, « Retrouver une demande » dans la
liste client permet de demander un email avec référence et adresse initiale.
La réponse publique est neutre, sans confirmer l'existence de la demande.
Le lien est envoyé uniquement à l'adresse enregistrée d'une demande encore sans
propriétaire. Le renvoi est limité par IP et par couple référence/adresse ; une
nouvelle émission ne révoque pas les liens encore valides, afin qu'un tiers ne
puisse pas bloquer le destinataire. Le premier rattachement les consomme tous.
Sans email ou sans référence, le client contacte l'équipe pour une vérification
manuelle d'identité ; aucune réaffectation automatique par simple email.

### P2 — Boutique → commande chaussures (modèle Sezy, paiement à la livraison)
1. Visiteur parcourt la **boutique**, ouvre une **fiche produit** (taille, prix), **ajoute au panier**.
2. **Panier maison** (session → fusion à la connexion) → **checkout léger** (coordonnées, adresse de
   livraison) → commande `enregistrée`. **Paiement à la livraison** (cash / mobile money à la remise).
   **WhatsApp** disponible comme canal de commande/contact (message pré-rempli : produit, taille, réf).
3. Admin confirme (`confirmée`) → `préparée` → `expédiée` → `livrée`. Notifs (email/WhatsApp).
   `payment_status` passe à `payé` à la remise.
4. Client suit la **timeline** dans son espace.

### P3 — Équipe : pilotage
1. Admin/équipe se connecte (**2FA**) → tableau de bord.
2. Traite demandes/devis, gère boutique, met à jour le **contenu** (CMS), invite des membres.

---

## 4. États (enums)
- **ServiceRequestStatus** : `envoyee` → `en_etude` → `devis_envoye` → `acceptee` → `en_cours` →
  `livree` · `refusee` · `annulee`.
- **OrderStatus** (boutique) : `enregistree` → `confirmee` → `preparee` → `expediee` → `livree` ·
  `annulee`.
- **PaymentStatus** : `en_attente` · `partiel` · `paye` · `rembourse`.
- **StockStatus** (produit) : `en_stock` · `bientot` · `epuise`.
- **Publication** (contenu/service/projet/produit) : `brouillon` · `publie` · `archive`.

> Chaque transition d'état **client-visible** déclenche une **notification** (voir §5) et est
> **journalisée** (audit).

---

## 5. Notifications
| Événement | Canal | Destinataire |
|---|---|---|
| Nouvelle demande de devis | Email | Équipe (commercial) + accusé client |
| Devis envoyé | Email (+ WhatsApp option) | Client |
| Statut demande change | Email | Client |
| Nouvelle commande boutique | Email | Boutique/Admin + accusé client |
| Statut commande change | Email (+ WhatsApp option) | Client |
| Nouveau message/échange | Email | Destinataire |
| Code 2FA | Email | Équipe/Admin |

- Envois en **queue** (`database`). WhatsApp : lien `wa.me` (manuel) au lancement, **Cloud API**
  (automatique) en phase auto.
- Préférences de notification dans le profil client (opt-out non-transactionnel).

---

## 6. Contenu éditable (CMS) — principe
Tout ce qui « parle » sur la vitrine doit être **éditable sans dév** : hero, sections d'accueil,
à-propos, **liste des services**, réalisations, **membres d'équipe**, témoignages, FAQ, pages
légales, coordonnées, liens réseaux, numéro WhatsApp, SEO par page. Implémentation : modèles CRUD
(services, projets, équipe, témoignages, FAQ, produits) + **Spatie Settings** pour la config globale
+ blocs de contenu pour l'accueil.

---

## 7. Règles transverses
- **WhatsApp omniprésent** mais **non intrusif** (bouton flottant + CTA contextuels).
- **Un CTA principal par zone** ; le reste en secondaire.
- **États vides/erreur/chargement** soignés partout (composants partagés).
- **Scoping client** : un client ne voit jamais les données d'un autre (Policies).
- **i18n-ready** : tous les textes via `lang/fr/` (clés), prêts pour l'EN.
