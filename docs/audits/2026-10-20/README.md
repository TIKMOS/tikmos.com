# Tikmos — audit UI, UX et modules fonctionnels

**Date : 20 octobre 2026.** Version examinée : `20ec1b5`, avec les données locales disponibles. Application Laravel exécutée sous PHP 8.4.26 et Node 24.13.0.

Pour appliquer les recommandations une par une, utiliser la [bibliothèque de 36 fiches de correction et de publication](../../prompts/audit-2026-10-20/README.md). Chaque fiche sépare la correction/validation du commit/push, avec vérification de GitHub avant la suite.

## Verdict

La plateforme dispose d'un socle fonctionnel solide et d'une identité visuelle cohérente. Les principaux écrans se chargent, les parcours connectés de commande et de devis fonctionnent et la suite existante passe. **Elle ne peut toutefois pas être considérée comme entièrement validée pour la production** : des cas importants de paiement, de stock et de devis restent incorrects malgré les tests existants.

Le meilleur gain visuel viendra des photos produit, de contenus plus concrets et d'une meilleure lisibilité. Une refonte complète de la charte n'est pas nécessaire pour traiter les problèmes observés.

## 1. Méthode et résultats vérifiés

| Contrôle | Résultat |
|---|---|
| Suite Pest existante | **227 tests réussis, 627 assertions**, environ 104 secondes |
| Larastan, niveau configuré 6 | **0 erreur** |
| Compilation Vite de production | Réussie, sortie isolée dans `storage/framework/testing/audit-build` |
| Pages publiques | **18 pages répondent HTTP 200** |
| Accès visiteur à `/admin`, `/app`, `/commander` | Redirection vers la connexion vérifiée |
| URL inexistante | Réponse HTTP 404 et page dédiée |
| Administration avec compte de recette | **22 écrans chargés**, détails dans [admin-pages.json](admin-pages.json) |
| Tests complémentaires de reproduction | **5 cas confirmés**, détaillés ci-dessous |
| Formats inspectés visuellement | Public : 390, 768 et 1440 px ; client : 360, 390 et 1280 px ; administration : 360 et 1440 px |

Les mesures HTTP sont conservées dans [public-pages.json](public-pages.json). Les contrôles aux différentes largeurs sont des échantillons de pages, pas une certification de chaque écran à chaque résolution.

La recette avec écritures a utilisé une **copie SQLite distincte**, deux comptes factices et le port local 8010. Les emails y étaient interceptés par le transport mémoire, sans livraison externe. La base utilisée par l'application sur le port 8000 n'a pas servi aux commandes et devis factices.

### Parcours réalisés dans le navigateur

- Accueil, menu mobile, catalogue et filtrage par catégorie.
- Connexion client ; tableau de bord, profil et demandes.
- Produit → ajout au panier → augmentation de quantité → commande à la livraison → détail et suivi client.
- Fiche service → demande de devis → apparition dans l'espace client et dans le pipeline admin.
- Connexion administrateur, challenge 2FA et renvoi du code dans l'environnement local.
- Création d'un devis de 10 000 XOF ; mise à jour du pipeline et de l'historique.
- Commande ouverte dans l'administration, puis passage de « En attente » à « Confirmée ».
- Ouverture du formulaire de service et chargement de l'éditeur riche ; annulation sans enregistrer.

L'acceptation et le refus des devis, les contrôles de droits, les CRUD et les médias sont également couverts par les tests existants. Tous leurs cas n'ont pas été rejoués manuellement.

## 2. Défauts fonctionnels à corriger en priorité

**P1 : à traiter avant une validation de production.** Les reproductions utilisent des paiements simulés ; aucune transaction bancaire réelle n'a été effectuée.

### F01 — Une transaction externe peut payer deux commandes — P1, reproduit

Deux commandes de même montant, deux paiements locaux distincts, une seule référence externe : les deux vérifications sont acceptées et les deux commandes passent à « paid ».

Le verrou de `PaymentService` protège chaque paiement local, mais pas l'utilisation de la même transaction entre plusieurs paiements. `provider_reference` possède un index simple, sans unicité par fournisseur.

**Preuves :** `app/Services/PaymentService.php:68`, `app/Models/Payment.php:91`, `database/migrations/2026_07_15_000100_create_payments_tables.php:21`, reproduction 1.

**Correction attendue :** unicité fournisseur + référence externe, vérification du rattachement à la commande attendue et traitement atomique. Une même transaction ne doit jamais créditer deux commandes, y compris lors d'appels concurrents. La vérification du contrat réel KKiaPay reste à faire en sandbox.

### F02 — Un paiement « SUCCESS » sans montant est accepté — P1, reproduit

Une réponse simulée contenant uniquement `status=SUCCESS` suffit à payer une commande. Le contrôle du montant est ignoré quand il vaut `null`.

**Preuve :** `app/Models/Payment.php:97`, reproduction 2.

**Correction attendue :** exiger un montant confirmé pour les paiements en ligne, le comparer strictement au montant dû et vérifier la devise lorsque la passerelle la fournit. Une réponse incomplète doit rester non validée et produire un événement explicite.

### F03 — Deux variantes peuvent rendre le stock négatif — P1, reproduit

Pour un produit avec un stock global de 2, ajouter 2 unités de variante 41 et 2 de variante 42 permet de commander 4 unités. Le stock final devient **−2**. Le plafond est appliqué par ligne de panier, alors que le stock du modèle est global.

**Preuves :** `app/Services/CartService.php`, `app/Services/OrderService.php:66`, reproduction 3.

**Correction attendue :** agréger les quantités par produit, verrouiller et revérifier le stock dans la transaction. Si les variantes doivent avoir des stocks indépendants, les modéliser explicitement. Ajouter également un test de concurrence sur MySQL.

### F04 — La date d'expiration d'un devis n'empêche pas son acceptation — P1, reproduit

Un devis encore au statut « Envoyé », mais dont `valid_until` est passé, peut être accepté. La date est affichée au client, sans être appliquée dans `acceptQuote()`.

**Preuve :** `resources/views/livewire/pages/app/requests-show.blade.php:21`, reproduction 4.

**Correction attendue :** contrôler la validité côté serveur au moment de la décision et afficher un état « Expiré » avec une demande de renouvellement. Définir clairement si une date de validité inclut toute la journée indiquée.

### F05 — Une demande déposée sans compte n'a pas de parcours de récupération — P1, parcours incomplet confirmé

La fiche service accepte les visiteurs et crée une demande avec `user_id=null`. L'email de devis dirige vers l'espace client, mais celui-ci exige que `user_id` corresponde au compte connecté. Même un compte vérifié utilisant le même email reçoit une **403**. Ce refus protège correctement la confidentialité ; le parcours de rattachement manque.

**Preuves :** `resources/views/livewire/pages/public/services-show.blade.php:70`, `app/Notifications/QuoteSent.php`, `resources/views/livewire/pages/app/requests-show.blade.php:17`, reproduction 5.

**Correction attendue :** proposer une invitation ou un lien signé à usage limité permettant de revendiquer la demande après vérification d'identité, ou demander une connexion au moment approprié. Ne pas rattacher automatiquement une demande sur la seule égalité d'un email non vérifié.

### F06 — Le seeder conserve un mot de passe administrateur de repli faible — P1, constat de code

`AdminSeeder` crée trois comptes SuperAdmin avec le repli `password` si la variable dédiée est absente. `firstOrCreate` conserve les mots de passe des comptes déjà créés. Cela ne démontre pas que les comptes actuellement déployés utilisent ce mot de passe ; leur configuration réelle n'a pas été testée.

**Preuves :** `database/seeders/AdminSeeder.php:20`, `:37`, `:49`.

**Correction attendue :** création explicite d'un administrateur, secret obligatoire ou invitation, comptes secondaires opt-in. Vérifier séparément les comptes présents en production.

### F07 — Le cycle d'échec ou d'abandon d'un paiement manque de reprise — P1, constat de code à compléter en sandbox

La commande est créée, le stock décrémenté et le panier vidé **avant** le succès du widget. Le checkout gère son retour positif, mais n'offre pas de parcours complet d'annulation, d'expiration ou de reprise depuis le détail de commande. Le retour d'échec suggère de réessayer alors que le panier a déjà été vidé.

**Preuves :** `app/Services/OrderService.php:27`, `resources/views/livewire/pages/public/checkout.blade.php:50`, `:209`, `:221`.

**Correction attendue :** réservation de stock avec expiration, reprise du paiement depuis la commande, gestion des échecs/fermetures du widget et restitution du stock selon les règles métier. Désactiver l'option en ligne si sa configuration est incomplète. Valider les rechargements, doubles clics, délais réseau et webhooks concurrents en sandbox.

### Autres risques à couvrir

- **Numérotation des commandes :** `count()+1` dans `OrderService::generateNumber()` peut produire une collision sous concurrence. Prévoir une séquence ou une génération atomique. Risque statique, non reproduit sous charge MySQL.
- **Anti-abus :** le contact public n'a pas de limitation métier visible ; la demande de service dispose d'un honeypot mais pas de limitation de fréquence dédiée ; le renvoi OTP n'a pas de délai serveur dédié visible. Ajouter une protection adaptée et des erreurs compréhensibles.
- **Suppression de compte :** l'interface annonce une suppression définitive, tandis que `User` utilise `SoftDeletes`. Clarifier le mécanisme réel de conservation/anonymisation et le texte affiché. Aucune suppression de compte n'a été effectuée pendant cet audit.

## 3. Audit UX : rendre les parcours plus clairs

| Sujet | Observation | Amélioration proposée | Priorité |
|---|---|---|---|
| Demande de devis depuis l'accueil | Le CTA principal mène à `/contact`, qui crée un `Lead`, alors que la fiche service crée un `ServiceRequest` suivi dans l'espace client | Unifier l'entrée « Demander un devis » et son pipeline, ou distinguer clairement message et demande suivie | P1 |
| Livraison | « À convenir » apparaît à côté d'un total présenté comme définitif | Calculer les frais avant confirmation ou afficher clairement « total hors livraison » et les modalités de validation | P2 |
| Commande à la livraison | L'écran client affiche « Non payée », bien que ce soit le mode choisi | Utiliser « À régler à la livraison » ; distinguer réception de la commande et confirmation commerciale | P2 |
| Alertes client | Une carte compte les notifications non lues, mais aucun centre de lecture n'est exposé ; les notifications inspectées passent par email | Relier la carte à un centre utile ou retirer cette métrique tant que le parcours n'existe pas | P2 |
| Profil | Le champ téléphone n'a pas de nom accessible ; le retour vers la vitrine est peu évident dans l'espace client | Corriger le composant téléphone et ajouter un lien clair « Voir le site » | P2 |
| Réglages admin | 27 contrôles dans Général, 41 dans Accueil lors du relevé | Sections repliables ou onglets, aide contextuelle, aperçu et avertissement de modifications non enregistrées | P2 |
| Connexion et 2FA | La confiance de 30 jours est cochée par défaut ; l'erreur de code reste visible après renvoi | Confiance opt-in, délai de renvoi affiché, distinction entre code invalide et expiré, remise à zéro du message d'erreur | P2 |
| Statuts et navigation | « Mes achats » / « Mes commandes », « Personnel » / « Équipe » désignent parfois des notions proches | Stabiliser le vocabulaire et distinguer personnel avec accès et équipe publique | P3 |

## 4. Audit visuel et responsive

### Ce qui mérite d'être conservé

- Palette encre, ivoire et or, avec une hiérarchie typographique reconnaissable.
- Portrait et promesse professionnelle clairs sur l'accueil.
- CTA principaux bien visibles et états vides qui proposent une suite.
- Navigation basse client adaptée au mobile et suivi de commande lisible.
- Administration sobre ; pipeline et historique rendent les demandes compréhensibles.
- Absence de débordement horizontal sur les pages principales échantillonnées, après chargement, aux largeurs contrôlées.

### Les améliorations qui changeront le plus le rendu

1. **Remplir les médias du catalogue.** Les six produits affichés utilisent des cadres de substitution. Sur mobile, une grande partie de la première carte est vide et le nom/prix arrive tard dans le défilement. Ajouter des photos homogènes, une galerie utile et un ratio de carte plus compact. Ne pas présenter des fiches incomplètes comme une sélection premium achevée.
2. **Enrichir les fiches.** Le casque inspecté ne propose qu'une courte phrase. Ajouter caractéristiques, marque/modèle précis, disponibilité, garantie, livraison et conditions utiles à la décision.
3. **Montrer des preuves concrètes.** Illustrations de réalisations, contexte, livrables et résultats vérifiables ; portraits de l'équipe plutôt que les seules initiales. Le compteur « 1 200 clients et projets » provient de réglages : documenter son périmètre et sa source.
4. **Mieux organiser l'accueil.** Proposition : promesse + CTA → trois offres principales → réalisations avec résultats → méthode en trois étapes → équipe/témoignages → contact. Les services doivent être identifiables sans obliger à quitter immédiatement l'accueil.
5. **Alléger l'en-tête tablette.** À 768 px, « À propos » passe sur deux lignes et l'ensemble est dense, même sans débordement. Conserver le menu compact jusqu'à une largeur où les liens et CTA tiennent confortablement.
6. **Rendre le logo lisible sur fond sombre.** La version admin est petite et très sombre. Prévoir une version monochrome claire, sans dépendre d'un mode de fusion pour obtenir le contraste visuel souhaité.

### Cible de présentation proposée

| Zone | Cible concrète |
|---|---|
| Accueil | Une promesse plus spécifique, deux CTA maximum par zone, preuves documentées et raccourcis vers les offres |
| Boutique mobile | Introduction plus courte, photos homogènes, nom/prix/stock faciles à repérer, filtres avec état sélectionné explicite |
| Fiche produit | Galerie, données techniques structurées et bloc achat indiquant paiement et livraison |
| Espace client | Actions correspondant au statut, prochaine étape visible, accès direct au support et à la vitrine |
| Administration | Actions contextuelles nommées, filtres cohérents, panneaux accessibles et aperçu avant publication |

## 5. Accessibilité : corrections transversales

### Noms et associations manquants — P2

- **Checkout : cinq champs de saisie sans association programmatique au libellé** lors de la mesure DOM, malgré les textes visibles.
- Téléphone du profil et des demandes : libellé non relié à l'input.
- Tri du catalogue : sélecteur sans nom accessible.
- Navigation basse client : le bouton central « + » est un lien sans nom accessible.
- Services admin : 12 actions d'édition/suppression et le bouton de fermeture du formulaire sont sans nom accessible dans l'écran inspecté.

Corriger les composants `field`, `phone`, `nav-bottom` et les actions de listes : `id`/`for`, `aria-label` contextuels, association des aides/erreurs par `aria-describedby`, et `aria-invalid` lorsque nécessaire. Ces corrections bénéficieront à plusieurs modules à la fois.

### Focus et navigation clavier — P2

La sidebar admin mobile est déplacée hors écran par transformation CSS, mais ses liens restent focusables et exposés. Dans le formulaire « Nouveau service », aucun rôle de dialogue n'est présent et le focus reste sur le bouton d'ouverture derrière le panneau. Le menu public ne fournit pas `aria-expanded`/`aria-controls`.

Utiliser `inert` ou un masquage approprié lorsque le panneau est fermé, gérer l'entrée/sortie du focus, la touche Échap et les dialogues modaux. Ajouter un lien d'évitement vers le contenu principal.

### Contrastes mesurés — P2

Les ratios ci-dessous sont calculés à partir des couleurs du thème et des styles relevés. Les petits textes ordinaires demandent un contraste d'au moins **4,5:1** selon [WCAG 2.2 — contraste minimum](https://www.w3.org/WAI/WCAG22/Understanding/contrast-minimum.html). Il s'agit de constats ciblés, pas d'une certification WCAG complète.

| Couleurs | Ratio | Constat |
|---|---:|---|
| Or `#a07f44` sur blanc | 3,74:1 | Insuffisant pour les petits textes observés |
| Or `#a07f44` sur ivoire `#faf8f3` | 3,52:1 | Insuffisant pour les petits textes observés |
| Gris `#9a958a` sur blanc | 2,98:1 | Trop faible pour du texte informatif courant |
| Gris de texte `#6f6f76` sur blanc | 4,99:1 | Bon candidat pour les textes secondaires sur blanc |
| Blanc sur vert WhatsApp `#25d366` | 1,98:1 | Insuffisant pour un libellé textuel normal |

Conserver l'or pour les accents et utiliser un ton plus foncé pour les textes ; revoir également les états de focus. Les libellés doivent être identifiables par les technologies d'assistance : voir [W3C — libellés et instructions](https://www.w3.org/WAI/WCAG22/Understanding/labels-or-instructions.html).

## 6. Performance, SEO et exploitation

- **Image Open Graph manquante — P2.** L'accueil référence `/icons/og-default.png`, dont la réponse vérifiée est **404**. Fournir une image de partage existante et vérifier son rendu sur une URL publique.
- **Logo très lourd pour sa taille affichée — P2.** `public/img/tikmos-logo.png` pèse **808 965 octets**, alors qu'il est affiché autour de 111 × 64 px sur desktop. Préférer un SVG officiel ou une image optimisée. À l'inverse, le portrait d'accueil actuellement chargé pèse environ 36,6 Ko : ce fichier n'est pas le premier problème à optimiser.
- **Titres privés génériques — P3.** Plusieurs pages client utilisent « Tikmos » et les pages admin « Tikmos Admin ». Ajouter des titres par module pour faciliter la navigation entre onglets.
- **CI GitHub absente — P2.** La CI versionnée concerne GitLab. Ajouter une vérification GitHub de Pest, Larastan et du build ; transformer les reproductions en vrais tests de non-régression après correction.
- **Emails, paiements et hébergement : vérifications restantes.** Le succès des tests avec transports simulés ne démontre pas la délivrabilité SMTP, la validité des clés, le fonctionnement réel du widget ou le comportement des transactions MySQL.
- **Performance réelle non chiffrée.** Aucun score Lighthouse de production ou Core Web Vitals n'est revendiqué. Mesurer les pages publiques sur une version compilée, hors Vite/debug, puis contrôler le chargement des polices, images et scripts.

## 7. Matrice des modules

| Module | Niveau de vérification | État / réserve principale |
|---|---|---|
| Accueil, vitrine, équipe, réalisations | HTTP + navigateur + tests | Fonctionnel ; contenus de démonstration et médias à enrichir |
| Blog | HTTP + ouverture admin + tests | Fonctionnel dans les cas couverts ; qualité éditoriale à compléter |
| Services et demandes | Parcours navigateur complet + tests | Connecté : opérationnel ; invité et CTA générique : parcours à compléter |
| Devis | Création admin et historique dans le navigateur + tests | Expiration non appliquée ; récupération invité manquante |
| Catalogue et panier | Navigateur + tests | Filtres, ajout et quantité fonctionnent ; stock multi-variantes incorrect |
| Commande à la livraison | Parcours client + confirmation admin + tests | Fonctionne ; livraison et libellés à clarifier |
| Paiement en ligne | Tests simulés + revue de code | Défauts de validation et de reprise ; recette KKiaPay réelle non faite |
| Authentification et 2FA | Connexions navigateur + tests | Fonctionne avec cache persistant ; seeder et anti-abus à renforcer |
| Clients, personnel, rôles | Écrans admin + tests de droits/gestion | Cas existants verts ; revue exhaustive de tous les rôles non effectuée manuellement |
| CMS, médias, éditeur riche | Chargement du formulaire/éditeur + tests CRUD/upload/purification | Fonctionnel dans les cas testés ; accessibilité et données média à améliorer |
| Réglages, FAQ, pages, partenaires, avis | 22 écrans admin + tests existants | Chargement correct ; formulaires longs et actions à mieux expliquer |
| Emails et notifications | Tests avec transports simulés + lecture des canaux | SMTP réel non validé ; carte Alertes sans parcours de lecture |
| PWA et hors-ligne | Tests existants + configuration examinée | Fonctionnalité désactivée dans les données observées ; recette d'installation/hors-ligne non réalisée |
| Données personnelles et pages légales | Tests existants + code et réponses HTTP | Texte de suppression à aligner avec le mécanisme ; pas d'audit juridique |

## 8. Plan d'amélioration recommandé

### Lot 1 — Fiabilité métier et sécurité

Corriger F01 à F07 ; ajouter les tests qui refusent les comportements défaillants ; sécuriser la numérotation ; tester stock et paiements concurrents sur MySQL. Terminer par une recette KKiaPay sandbox et SMTP de test.

**Critères de sortie :** une transaction ne paie qu'une commande ; tout paiement possède un montant confirmé ; stock jamais négatif ; devis expiré non acceptable ; demande invitée récupérable de façon sûre ; paiement interrompu reprenable sans perte de stock ni double commande.

### Lot 2 — Clarté des parcours et accessibilité

Unifier le parcours de devis, expliciter la livraison, corriger les composants de formulaire et de navigation, gérer le focus et les contrastes, simplifier les réglages.

**Critères de sortie :** parcours essentiels utilisables au clavier ; champs et boutons nommés ; erreurs reliées aux champs ; panneaux sans focus caché ; montant et prochaine étape compréhensibles avant confirmation.

### Lot 3 — Finition visuelle et validation de production

Ajouter les photos et contenus réels, optimiser les logos, corriger l'Open Graph, ajuster l'en-tête tablette et l'accueil, puis mesurer les pages compilées et mettre en place la CI GitHub.

**Critères de sortie :** fiches publiées complètes ; absence de cadres média vides sur les produits proposés ; variantes claire/sombre du logo lisibles ; carte de partage fonctionnelle ; recette mobile, navigateur et services externes documentée.

## 9. Preuves et reproductibilité

Les [5 tests d'audit](AuditRisksTest.php) sont volontairement des **preuves des comportements actuels**, et non des tests garantissant leur correction. Leurs assertions réussissent lorsque le défaut ou le manque de parcours est présent. Après correction, il faudra remplacer ces assertions par les règles métier attendues avant intégration à la CI.

Depuis la racine du projet, avec le `phpunit.xml` actuel ciblant SQLite en mémoire :

```powershell
php artisan test --compact
php vendor/bin/pest docs/audits/2026-10-20/AuditRisksTest.php --compact
php -d memory_limit=1G vendor/bin/phpstan analyse --no-progress --memory-limit=1G
npm run build -- --outDir storage/framework/testing/audit-build
```

| Reproduction | Observation confirmée |
|---|---|
| 1 | Deux commandes payées avec `SAME-EXTERNAL-TXN` |
| 2 | Commande payée avec une réponse `SUCCESS` sans montant |
| 3 | Stock initial 2, commande de 4 unités sur deux variantes, stock final −2 |
| 4 | Devis expiré la veille passant au statut « Accepté » |
| 5 | Demande sans propriétaire, compte du même email, accès au détail renvoyant 403 |

### Captures de référence

Les captures ne représentent pas une proposition de refonte. Elles montrent l'état observé et les données factices utilisées pendant la recette.

- [Accueil desktop, 1440 px](images/accueil-desktop-1440.jpg)
- [Accueil mobile, 390 px](images/accueil-mobile-390.jpg)
- [Boutique mobile : médias absents](images/boutique-mobile-390.jpg)
- [Boutique tablette, 768 px](images/boutique-tablette-768.jpg)
- [Formulaire de commande](images/checkout-desktop.jpg)
- [Suivi client de la commande de test](images/commande-client.jpg)
- [Gestion admin de la commande](images/commande-admin.jpg)
- [Demandes client sur mobile, 360 px](images/devis-client-mobile-360.jpg)
- [Profil mobile, 390 px](images/profil-mobile-390.jpg)
- [Dashboard admin desktop](images/admin-desktop.jpg)
- [Dashboard admin mobile, 360 px](images/admin-mobile-360.jpg)
- [Panneau CMS et éditeur riche](images/editeur-service-desktop.jpg)

![Accueil desktop observé](images/accueil-desktop-1440.jpg)

![Catalogue mobile et cadres sans photo](images/boutique-mobile-390.jpg)
