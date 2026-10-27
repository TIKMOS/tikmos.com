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

### Formulaires publics — correction 09, 27 octobre 2026

Le contact et toutes les fiches service partagent les quotas suivants :

| Dimension | Plafond | Fenêtre fixe depuis la première tentative admise |
|---|---|---|
| Visiteur (session serveur) ou compte connecté | 3 | 10 minutes |
| Adresse IP réseau | 20 | 10 minutes |
| Chaque email ou téléphone déclaré | 5 | 30 minutes |

Un utilisateur connecté est identifié par son compte serveur, jamais par l'email
déclaré dans le formulaire. L'adresse de contact reste une donnée non vérifiée :
son quota protège les destinataires, il ne donne aucun droit d'accès. Deux visiteurs
distincts partageant une IP ont leur propre quota de session, dans la limite réseau.
Changer d'email, de fiche service ou passer au contact ne réinitialise pas ce quota.
Supprimer les cookies peut changer le visiteur invité ; le plafond réseau reste actif.

Les validations et le honeypot existant précèdent le contrôle. Une tentative valide
admise réserve ses quotas avant toute écriture ou notification ; un échec métier
ultérieur peut donc consommer cette tentative. Aucun remboursement automatique de
quota. Un refus ne consomme pas d'autre quota et ne prolonge pas la fenêtre.

Le délai affiché correspond au plus long quota actuellement bloquant. Les champs
restent renseignés, l'alerte reçoit le focus et le formulaire peut être soumis à
nouveau après le délai, y compris si la page était déjà ouverte. Les appels Livewire
directs passent par le même contrôle serveur. Aucun CAPTCHA ni service tiers ajouté.
Le contact continue à créer un `Lead` sans nouvel email ; les demandes conservent
leur événement et leurs notifications équipe/client, ainsi que la récupération
sécurisée introduite en 05.

Cache, verrous et conditions de partage : [déploiement](DEPLOIEMENT.md#protection-des-formulaires-publics--correction-09).
Preuves et limites : [validation 09](validations/2026-10-27-formulaires/README.md).

### Fermeture du compte — correction 11, 27 octobre 2026

« Fermer mon compte » ferme l'accès après vérification du mot de passe actuel et
confirmation explicite. L'annulation et les confirmations incomplètes ne ferment
pas le compte. Le mot de passe saisi est effacé du composant après refus,
annulation ou succès ; il n'est pas changé. La fermeture utilise `User::SoftDeletes`
(`deleted_at`) : elle ne supprime physiquement ni n'anonymise le profil.
Le champ métier `status`, les rôles et le mot de passe restent conservés.

Les demandes, devis et décisions, événements, commandes, articles et paiements
restent conservés avec leurs liens. Les enregistrements d'appareils approuvés ne
sont pas purgés. La session courante est invalidée, son jeton CSRF renouvelé et la
déconnexion applique le comportement Laravel du jeton « se souvenir de moi ».
Les autres sessions ne sont pas toutes physiquement supprimées : aux requêtes
suivantes, le fournisseur d'authentification ne retrouve plus l'utilisateur fermé.
Les identifiants et les cookies d'appareils approuvés ne permettent pas de se
reconnecter. Les actions du compte revérifient aussi un utilisateur actif en base,
au lieu de se fier à un objet authentifié chargé avant fermeture.
Aucun parcours de réactivation n'est proposé par cette correction.

Avant fermeture, l'export existant reste disponible : profil, demandes avec devis,
commandes et articles. Il ne constitue pas un export exhaustif de tous les
stockages, journaux, pièces jointes ou sauvegardes.

Une demande d'effacement ou d'anonymisation suit un examen distinct via la page
existante `/contact`. Aucun traitement automatique, délai de
conservation ou procédure de décision juridique n'est créé ici. Restent à définir
et valider par les responsables : identité du demandeur, périmètre des données et
copies, conservation par catégorie, traitement des documents et transactions,
responsabilités, modalités de réponse et exécution de la décision. Aucun effacement
ni anonymisation n'est réputé accompli par le bouton de fermeture.

La page `/confidentialite` lit le document CMS publié `LegalDocument.key=privacy`.
Un encadré technique partagé avec « Mon compte » précise ce fonctionnement avant
le corps éditorial. Le corps, la publication et la version CMS ne sont pas
réécrits. Les anciennes formulations du contenu CMS, notamment la suppression
depuis « Mon compte » et la suppression/anonymisation après conservation, doivent
être examinées séparément dans l'éditeur existant `/admin/reglages/pages`, puis
validées avant publication. Leur présence dans les données initiales ne prouve
pas le contenu effectivement déployé. Les comptes et contenus réels n'ont pas été
modifiés ni audités dans cette intervention.

Cette description est technique et ne constitue pas une validation juridique.
Preuves, parcours et limites : [validation 11](validations/2026-10-27-compte/README.md).

### Devis suivi et contact général — correction 12, 27 octobre 2026

Les boutons « Demander un devis » de l'accueil, du menu public, d'À propos,
des témoignages, des catalogues services/réalisations et de l'espace client mènent
à `/demander-un-devis` (`quote-request`). Ce parcours réutilise le formulaire et
le pipeline des fiches service. Le visiteur peut choisir un service publié ou
garder « Besoin sur mesure / Je ne sais pas encore » (`service_id=null`, déjà
autorisé par le schéma). Une fiche service garde son service présélectionné et sa
publication est revérifiée à l'envoi. Aucun service artificiel n'est créé.

Un devis produit uniquement une `ServiceRequest` avec son événement et les
notifications équipe/client existantes. La confirmation affiche sa référence,
puis « Suivre ma demande » pour son propriétaire connecté. Un visiteur reçoit les
instructions du rattachement signé de la correction 05 : email destinataire,
connexion/inscription, vérification de cette adresse et confirmation. Aucun droit
n'est accordé par simple égalité d'emails déclarés. Avec téléphone seul, la
référence reste visible et l'équipe recontacte le visiteur ; pas de promesse de
suivi automatique par email. Les validations et le honeypot sont conservés.

`/contact` est un « Contact général », avec le sujet initial « Question générale ».
Il continue à créer uniquement un `Lead`, sans notification nouvelle. Un lien
distinct propose le parcours de devis avant de remplir le contact. Les messages
et demandes historiques ne sont ni convertis ni réaffectés.

Les deux intentions partagent les quotas de la correction 09. Chaque formulaire
possède aussi un jeton serveur verrouillé et un reçu persistant dans
`public_form_submissions`. Le reçu, la donnée métier et l'événement de demande
sont validés dans une même transaction. Un rejeu du même jeton par le même acteur
retourne le résultat existant, sans réécriture, nouveau quota ni notification.
Le compte ou la session de l'acteur est vérifié, jamais l'email déclaré. Un autre
acteur ne peut pas récupérer ce reçu. Après succès, un nouveau jeton permet une
nouvelle demande volontaire ; ce mécanisme ne déduplique pas des textes identiques
envoyés depuis de nouveaux formulaires. Les comptes fermés ou inactifs sont refusés
même si leur identité était déjà chargée.

Les liens explicites des vues sont corrigés sans modifier le contenu CMS réel.
Les titres/textes de la section finale restent issus de `HomepageSettings` ; les
corps riches et liens éditoriaux éventuellement présents en base doivent être
revus séparément s'ils annoncent un devis tout en menant à `/contact`.
Le contenu effectivement déployé n'a pas été examiné.

Installation et limites : [déploiement](DEPLOIEMENT.md#devis-suivi-et-recus-denvoi--correction-12).
Preuves : [validation 12](validations/2026-10-27-parcours-devis/README.md).

### Livraison annoncée avant confirmation — correction 13, 27 octobre 2026

Dans **Réglages → Livraison**, un compte disposant de `settings.manage` choisit
explicitement l'un des trois états. « Livraison gratuite » annonce zéro frais ;
« Tarif fixe connu » exige un entier strictement positif en FCFA ; « Frais à
déterminer » annonce **Total hors livraison**, explique la validation commerciale
et propose le contact existant. Aucune zone ou grille tarifaire n'est créée.
Une configuration incohérente est traitée comme inconnue, sans gratuité supposée.

Le panier, le checkout, les commandes client/admin et l'email de commande reprennent
ces indications. Sur mobile, le récapitulatif précède le formulaire. Lorsque les
frais sont inconnus, le bouton transmet une commande pour validation ; le paiement
en ligne est indisponible, y compris par appel direct ou reprise. Le paiement à
la livraison reste proposé s'il est activé. Si seul le paiement en ligne est
configuré, le formulaire explique l'indisponibilité et propose de contacter l'équipe.

Les prix proviennent du serveur. Un changement des conditions entre ouverture et
confirmation interrompt la création et demande de relire le nouveau récapitulatif,
puis de confirmer à nouveau. Chaque nouvelle commande conserve son état de
livraison, ses frais et son total ; un changement de réglage ne les recalcule pas.
Les commandes historiques sans cette information restent « Montant enregistré »
et « livraison à vérifier » : aucun zéro historique n'est déclaré gratuit.

Pour une commande à livraison inconnue, **l'équipe doit encore communiquer les
frais et le montant final, puis recueillir l'accord du client avant paiement**.
Cette étape reste manuelle. Aucun parcours d'approbation de frais par commande,
de conversion automatique vers le paiement en ligne ou de facturation complémentaire
n'est ajouté. La gestion COD et le stock associés restent ceux du fonctionnement
existant. Les montants et paiements historiques sont conservés.

Voir [déploiement](DEPLOIEMENT.md#livraison-et-total-final--correction-13),
[paiements](30-systeme-paiement.md#livraison-et-paiement--correction-13) et
[preuves de validation](validations/2026-10-27-livraison/README.md).
