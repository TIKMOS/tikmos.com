# TIKMOS — Cadrage produit

> Document de cadrage — phase 0. Source de vérité produit.
> Statut : proposition initiale, en attente de validation client / arbitrages.
> Dernière mise à jour : 2026-06-30.

---

## 1. Le client en bref

**[le Professeur]** — *(nom & titre exacts à confirmer)* — **professeur certifié des sciences
mathématiques** et **ingénieur en data science & intelligence artificielle**. Il **conçoit et
pilote des projets** (solutions informatiques, data/IA), **forme** des entreprises, et opère en
parallèle une **boutique de chaussures de luxe en ligne** (aujourd'hui pilotée via **WhatsApp**).

Il **ne travaille pas seul** : une **équipe réelle** l'accompagne (technique, commercial, support,
logistique boutique). Le site doit refléter cette force collective tout en gardant **l'homme** comme
visage et caution de l'offre.

> **Logo** retenu + **portrait** fournis (`brand/`). **Contacts / réseaux / photos d'équipe / visuels
> boutique** : encore à fournir (voir `brand/README.md`).

---

## 2. Ce que propose Tikmos (4 lignes de métier, 1 socle)

L'erreur serait d'en faire « un site de prof » ou « une boutique ». Tikmos est une **plateforme
personnelle multi-offres** où chaque métier est un **module** posé sur un socle commun (compte
client, devis/commandes, notifications, équipe, contenu éditable).

### A. Expertise & formation
- **Formations entreprises** : informatique, **suites logicielles** (bureautique, métiers), data,
  IA appliquée, initiation/perfectionnement.
- Interventions, ateliers, accompagnement de montée en compétence des équipes.

### B. Conception de solutions informatiques
- **Solutions sur-mesure complexes** pour entreprises et sociétés : applications métier, plateformes,
  automatisation, intégration data, projets IA.
- Pilotage de projet de bout en bout (cadrage → conception → livraison → support).

### C. Services d'appui
- **Installation informatique** (postes, réseaux, déploiement de logiciels).
- **Assistance en gestion administrative**.
- **Orientation professionnelle** (conseil, accompagnement de parcours).
- **Autres services** (le catalogue de services est **éditable** depuis l'admin — extensible sans dév).

### D. Boutique — équipements informatiques & accessoires de luxe (homme)
- Catalogue d'**équipements informatiques** (ordinateurs, périphériques, audio) et d'**accessoires de
  luxe** présenté avec soin (photos, marque, variantes, références).
- **Modèle calqué sur Sezy** (décision client) : **panier maison** (session → connexion), commande,
  **paiement à la livraison** (cash / mobile money à la remise), **suivi de commande** dans l'espace
  client. **WhatsApp** reste un canal de commande/contact. Paiement en ligne = phase ultérieure.

> **Implication produit :** plateforme **modulaire**. Un **catalogue de services** générique
> (titre, description, image, prix/« sur devis », CTA) + un **moteur de demandes/devis** couvrent A,
> B, C sans coder un module par métier. La boutique (D) est un module e-commerce léger dédié.

---

## 3. Vision produit

> **Donner à [le Professeur] une plateforme premium qui dit en une visite : voici qui je suis, voici
> mon équipe, voici ce que je peux faire pour vous — et permet de passer à l'action tout de suite
> (devis, commande, WhatsApp), puis de suivre l'avancement dans un espace dédié.**

Trois promesses :

1. **Pour le prospect** — comprendre en 30 secondes la valeur (expertise rare : maths + data/IA +
   exécution), être rassuré (équipe, réalisations, témoignages), et **agir** (demander un devis,
   commander, écrire sur WhatsApp). Vitrine **éditoriale-luxe**, pas un CV.
2. **Pour le client** — un **espace** clair pour suivre ses **devis/demandes de services** et ses
   **commandes boutique**, échanger, retrouver ses **documents/factures**.
3. **Pour [le Professeur] & l'équipe** — un **back-office** qui pilote tout : **contenu du site
   (CMS)**, services, demandes, boutique, clients, équipe, KPIs. Le site se met à jour **sans dév**.

**Principe directeur :** *orienté offre et action*. Chaque page répond à « et concrètement, pour
vous ? » et propose un pas suivant. WhatsApp reste un canal de proximité ; la plateforme le
**structure** (suivi, traçabilité, mémoire client).

---

## 4. Positionnement de marque & domaine

- **Nom de plateforme :** **Tikmos** (domaine **tikmos.com**). Court, neutre, mémorable.
- **Essence :** *l'expertise qui exécute* — rigueur académique (maths), modernité (data/IA),
  exigence du **luxe** (boutique), fiabilité d'une **équipe**.
- **Ton :** premium, sobre, confiant, accessible (« compréhensible même par un non-informaticien »).
  Ni jargon, ni esbroufe : **du concret**.
- **Direction créative :** éditorial-luxe clair, **Encre + Or champagne** + indigo nuit (data/IA),
  typographie forte, photos réelles (l'homme, l'équipe, les produits), micro-interactions soignées.
  Détail : `01-charte-graphique.md`.

**Domaines :** `tikmos.com` (principal). Réserver au besoin des variantes locales (`.bj`, `.africa`)
en redirection. Espace connecté possible sur sous-domaine `app.tikmos.com` (à trancher).

---

## 5. Personas & rôles

**Côté public / clients**
- **Le décideur entreprise** — cherche un partenaire fiable pour une **solution informatique** ou
  une **formation** ; veut des **preuves** (réalisations, équipe) et un **devis** rapide.
- **Le particulier / pro en accompagnement** — **orientation professionnelle**, **assistance
  administrative**, **installation** ; veut simplicité et contact humain.
- **Le client boutique** — achète des **chaussures de luxe** ; veut belles photos, tailles claires,
  confiance et **commande facile (WhatsApp)** + suivi.

**Côté équipe (gestion via l'app)**
- **[le Professeur] — Admin / Fondateur** : tout voir, tout piloter, éditer le site, KPIs.
- **Commercial / Chargé de clientèle** : devis, demandes, relances, fiches clients.
- **Support** : réponses, suivi des commandes et demandes.
- **Gestionnaire boutique** : produits, stock, commandes chaussures.
- **Technicien / Chef de projet** *(optionnel)* : suivi des projets/installations.

→ **Rôles & permissions (RBAC)** dès le départ (Spatie). Détail : `03-cadre-fonctionnel.md`.

---

## 6. Périmètre fonctionnel (modules)

### Module 0 — Socle commun
Auth (email + mot de passe, **2FA OTP email** pour la gestion), profil client, **notifications
(email + WhatsApp)**, **CMS/paramètres globaux** (contenu éditable), médias (uploads), FR
(i18n-ready). PWA **optionnelle** (phase ultérieure ou V1 selon arbitrage).

### Module 1 — Vitrine publique (acquisition)
- **Accueil** premium : hero « Je suis [le Professeur] », promesse, preuves, CTA (devis / WhatsApp).
- **À propos** : storytelling (parcours, valeurs, approche) — **pas un CV**, une narration crédible.
- **Services** : catalogue éditable (formation, conception, installation, assistance, orientation,
  autres) → fiche service → **demande de devis**.
- **Réalisations / Projets** : portfolio (études de cas, captures, résultats).
- **Équipe** : page dédiée (l'effet « équipe derrière »).
- **Boutique** : catalogue chaussures de luxe → fiche produit → **commande WhatsApp** (+ panier léger).
- **Témoignages / avis**, **FAQ**, **contact** + **WhatsApp omniprésent**, pages légales.
- **Blog / actualités** ✅ (décision client) : articles d'autorité (maths, data/IA, conseils), SEO,
  éditable depuis l'admin.

### Module 2 — Espace client
- **Mes demandes de services / devis** — statuts (envoyée → en étude → devis envoyé → acceptée →
  en cours → livrée), échanges, documents.
- **Mes commandes boutique** — suivi (enregistrée → confirmée → préparée → expédiée → livrée).
- **Mes factures / paiements** (suivi simple ; paiement en ligne ultérieur).
- **Mon profil**, messagerie/échanges, documents.

### Module 3 — Back-office (admin & équipe)
- **CMS** : tous les contenus de la vitrine (hero, à propos, services, projets, équipe, témoignages,
  FAQ, pages légales) éditables.
- **Services** : catalogue (CRUD), mise en avant, ordre.
- **Demandes / devis** : pipeline, affectation, devis, statuts → notifications.
- **Boutique** : produits, catégories, stock, commandes (pipeline), médias.
- **CRM léger** : fiches clients, historique multi-offres.
- **Équipe** : invitations, rôles, permissions.
- **Tableau de bord** : KPIs (demandes du mois, devis, commandes boutique, CA estimé).
- **Paramètres globaux** : coordonnées, WhatsApp, réseaux, SEO, cookies.

### Module 4 — Automatisation & évolutions
- **Notifications** auto (email + WhatsApp) sur changements de statut.
- **Paiement en ligne** (boutique / acomptes devis) — phase ultérieure.
- **Prise de rendez-vous** (consultation/orientation) — option.
- **Multilingue EN** — i18n-ready dès le départ.

---

## 7. Stack technique (résumé)

> Détail complet et stratégie d'auth : `02-stack-et-auth.md`. Stack **standard Alinkka = Laravel/TALL**.

- **Backend** : **Laravel 13** (sorti 17/03/2026, PHP **8.3** min), **MySQL 8+** (prod) / **SQLite** (dev).
- **Front** : **Livewire 3 + Volt** + **Alpine.js** + **Tailwind CSS 4** (`@theme`) + **Vite**.
  *(Livewire 4 disponible — chemin d'upgrade documenté, non requis au lancement.)*
- **Auth / RBAC** : **Spatie Laravel Permission** + rôles enum + permissions `config/permissions.php`
  + **2FA email (OTP)** + device-remember + login log.
- **Contenu** : **Spatie Settings** (config globale), **Spatie MediaLibrary** (médias),
  **mews/purifier** (HTML riche), **TipTap** (éditeur).
- **Notifications** : Mail (queue `database`) ; **WhatsApp** (lien `wa.me` au lancement, Cloud API
  en phase auto).
- **Déploiement** : serveur PHP/MySQL (Hostinger shared compatible, VPS-ready). Doc root `public/`.

---

## 8. Phasage (docs-first, par paliers)

| Phase | Livrable | Cœur |
|---|---|---|
| **0 — Cadrage** *(ce doc)* | Recherche + périmètre + charte | ✅ |
| **1 — Marque & design system** | Tokens charte, composants `x-tk.*`, layouts, accueil | Direction validée avant prod |
| **2 — Vitrine** | Accueil + à propos + services + réalisations + équipe + contact | Le « 100× mieux », met Tikmos en ligne |
| **3 — Auth, espace client, back-office** | RBAC, devis/demandes bout-en-bout, CMS, dashboard | Cœur opérationnel |
| **4 — Boutique** | Catalogue chaussures + panier + commande WhatsApp + suivi | Élargit la valeur |
| **5 — Automatisation & i18n** | Notifs auto, paiement (option), EN, PWA (option) | Vers l'autonomie |

Chaque phase : spec dédiée dans `docs/` **avant** le code (working style Alinkka).

---

## 9. Risques & points d'attention
- **Confiance = produit** : la crédibilité visuelle est le 1er argument (clients entreprises + luxe).
  Toute approximation UI/UX coûte des conversions.
- **Effet « one-man-show »** vs **équipe** : équilibrer l'homme (caution) et le collectif (capacité).
- **Catalogue de services mouvant** : tout passe par le **CMS** (zéro dév pour ajouter un service).
- **Boutique vs services** : deux logiques de commande distinctes — ne pas les mélanger.
- **Données clients** (devis, documents) : stockage sécurisé, permissions strictes, RGPD.

---

## 10. Décisions validées (2026-06-30)
- **Logo** : retenu **`brand/logo-tikmos-dark-gold.jpeg`** (marine + or champagne) — valide la charte.
  *(À vectoriser en SVG + variantes transparentes pour la prod.)* **Portrait** fourni (`portrait-prof.jpg`).
- **Services** : entièrement **créés & gérés depuis l'espace admin** (CRUD CMS) — ajout d'un service
  sans dév. *(cf. Module 3 + lots 05/10.)*
- **Boutique** : **à la manière de Sezy** — panier maison, commande, **paiement à la livraison**,
  suivi ; WhatsApp comme canal. Paiement en ligne plus tard.
- **Blog / actualités** : **inclus** au périmètre.

## 10 bis. Questions encore ouvertes
1. **Nom & titre exacts** à afficher + coordonnées/réseaux + numéro WhatsApp.
2. **Liste finale des services** et politique de prix (affiché vs « sur devis »).
3. **Domaines** annexes à sécuriser autour de `tikmos.com` + sous-domaine espace connecté.
4. **PWA** dès V1 ou phase ultérieure ?
