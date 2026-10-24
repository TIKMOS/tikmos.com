# Journal des corrections de l’audit

État initial : **aucune correction applicative effectuée dans le cadre de cette bibliothèque**.

Pour chaque A, remplacer seulement sa ligne et ajouter une note courte datée avec : changement, fichiers, commandes/résultats, recette et limites. États possibles : À traiter, En cours, Partiel, Validé localement.

La publication est contrôlée par le prompt B et attestée par son reçu (branche, SHA, lien GitHub, vérification distante). Ce journal décrit la validation locale ; ne pas annoncer une publication avant le push et ne pas créer une modification non publiée juste pour y écrire un SHA.

| Fiche | Sujet | État local | Preuves et limites |
|---|---|---|---|
| [01](01-transaction-externe-unique.md) | Empêcher une transaction externe de payer plusieurs commandes | Validé localement | 24/10/2026 : F01 reproduit avant correction, puis refus du second paiement avec événement explicite ; unicité fournisseur/référence et règlement atomique. Tests ciblés : 32 réussis, 138 assertions ; suite complète : 242 réussis, 717 assertions ; Larastan : 0 erreur ; Pint conforme. Migration existant/doublons/rollback : SQLite en mémoire validé ; SQL MySQL compilé uniquement, concurrence MySQL et KKiaPay sandbox non validées. Base locale inchangée ; aucun commit/push. Voir la note 01 ci-dessous. |
| [02](02-montant-paiement-obligatoire.md) | Refuser un paiement en ligne sans montant confirmé | Validé localement | 24/10/2026 : F02 reproduit avant correction ; montant serveur entier, valide et égal au dû désormais requis. Devise contrôlée si fournie, paiement KKiaPay local limité à XOF ; absence du champ de devise documentée comme limite. PaymentTest : 59 réussis, 283 assertions ; suite complète : 282 réussis, 894 assertions ; Larastan : 0 erreur ; Pint conforme. F01, idempotence et COD préservés. HTTP/notifications simulés, SQLite en mémoire ; MySQL et KKiaPay sandbox non validés. Aucune migration locale, aucun commit/push lors de A. Voir note 02. |
| [03](03-stock-global-variantes.md) | Empêcher le stock négatif entre variantes | Validé localement | 24/10/2026 : F03 reproduit (stock 2 → −2 avant correction), puis cumul par produit contrôlé dans le panier et sous verrou en transaction à la commande. Refus explicite, panier corrigeable et rollback validés. CartSizeTest + OrderFlowTest : 24 tests, 125 assertions ; suite : 297 tests, 995 assertions ; Larastan : 0 erreur ; Pint conforme ; build réussi. Recette isolée à 360/1440 px validée, stocks NULL préservés. Concurrence MySQL non validée ; base de l'application inchangée. Aucun commit/push lors de A. Voir note 03. |
| [04](04-expiration-devis.md) | Appliquer la validité des devis côté serveur | Validé localement | 24/10/2026 : F04 reproduit avant correction ; validité contrôlée à la décision, fin du jour incluse dans le fuseau applicatif (UTC), absence de date préservée. État Expiré et renouvellement visibles ; envoi avec date passée refusé ; décisions historiques préservées. Tests ciblés : 45 tests, 244 assertions ; suite : 326 tests, 1 192 assertions ; Larastan : 0 erreur ; Pint conforme ; build réussi. Recette navigateur 360/1440 px et clavier réalisée ; utilisateur ayant confirmé l'état Expiré et les boutons sur l'instance fictive 8012, autres cas manuels disponibles. MySQL/concurrence et SMTP réel non validés. Aucune migration de la base habituelle, aucun commit/push lors de A. Voir note 04. |
| [05](05-rattachement-demande-invite.md) | Permettre au visiteur de retrouver sa demande en sécurité | Validé localement | 24/10/2026 : F05 reproduit (lien email attendu 200, obtenu 403). Récupération par lien signé 24 h, compte destinataire vérifié, confirmation et rattachement atomique unique ; accès croisés toujours interdits. Renouvellement neutre et limité, demandes historiques conservées. Nouveaux tests : 29 tests, 164 assertions ; ciblés : 107 tests, 501 assertions ; suite : 355 tests, 1 356 assertions ; ciblés sur vue finale : 51 tests, 282 assertions. Larastan : 0 erreur ; Pint conforme ; build réussi. Recette navigateur mobile/bureau/clavier réalisée ; utilisateur ayant confirmé le devis de 10 000 XOF après rattachement et le message « déjà utilisé » à la réouverture du lien sur l'instance fictive 8013. MySQL/concurrence, migration MySQL et SMTP réel non validés. Nouvelle migration non appliquée à la base habituelle. Aucun commit/push lors de A. Voir note 05. |
| [06](06-creation-administrateur.md) | Supprimer les identifiants administrateur de repli | À traiter | — |
| [07](07-reprise-paiement-reservation.md) | Gérer l’abandon et la reprise du paiement | À traiter | — |
| [08](08-numerotation-commandes.md) | Rendre la numérotation des commandes atomique | À traiter | — |
| [09](09-anti-abus-formulaires.md) | Limiter les abus des formulaires publics | À traiter | — |
| [10](10-otp-delai-et-experience.md) | Sécuriser et clarifier le challenge 2FA | À traiter | — |
| [11](11-suppression-compte-texte.md) | Aligner la suppression de compte avec son fonctionnement réel | À traiter | — |
| [12](12-parcours-devis-unifie.md) | Unifier le bouton Demander un devis et le suivi client | À traiter | — |
| [13](13-frais-livraison.md) | Annoncer les frais de livraison avant confirmation | À traiter | — |
| [14](14-libelles-statuts-navigation.md) | Clarifier les statuts de commande et le vocabulaire | À traiter | — |
| [15](15-carte-alertes-client.md) | Supprimer ou relier la carte Alertes inerte | À traiter | — |
| [16](16-retour-vers-vitrine.md) | Ajouter un retour clair du client vers la vitrine | À traiter | — |
| [17](17-reglages-admin-sections.md) | Organiser les réglages longs en sections | À traiter | — |
| [18](18-libelles-champs-accessibles.md) | Relier les libellés, aides et erreurs aux champs | À traiter | — |
| [19](19-boutons-et-selecteurs-accessibles.md) | Nommer les actions à icône et le tri du catalogue | À traiter | — |
| [20](20-focus-menus-panneaux.md) | Gérer le focus des menus et panneaux | À traiter | — |
| [21](21-contrastes-theme.md) | Corriger les contrastes sans changer l’identité | À traiter | — |
| [22](22-photos-et-cartes-catalogue.md) | Remplir les médias et compacter les cartes produit | À traiter | — |
| [23](23-fiches-produits-completes.md) | Structurer les informations des fiches produit | À traiter | — |
| [24](24-realisations-et-preuves.md) | Présenter des réalisations et chiffres vérifiables | À traiter | — |
| [25](25-portraits-equipe.md) | Améliorer la présentation de l’équipe | À traiter | — |
| [26](26-hierarchie-accueil.md) | Rendre les offres visibles sur l’accueil | À traiter | — |
| [27](27-entete-tablette.md) | Alléger l’en-tête aux largeurs intermédiaires | À traiter | — |
| [28](28-logos-clair-sombre-legers.md) | Optimiser les logos et leur rendu sur fond sombre | À traiter | — |
| [29](29-image-partage-social.md) | Corriger l’image Open Graph manquante | À traiter | — |
| [30](30-titres-pages-privees.md) | Donner un titre explicite à chaque page privée | À traiter | — |
| [31](31-ci-github.md) | Ajouter les contrôles automatiques GitHub | À traiter | — |
| [32](32-performance-production.md) | Mesurer et améliorer la performance compilée | À traiter | — |
| [33](33-recette-emails.md) | Valider les emails sans contacter de vrais destinataires par défaut | À traiter | — |
| [34](34-recette-kkiapay-sandbox.md) | Valider le cycle KKiaPay en sandbox | À traiter | — |
| [35](35-recette-pwa.md) | Vérifier la PWA sans l’activer en production | À traiter | — |
| [36](36-recette-finale-modules.md) | Actualiser l’audit après toutes les corrections publiées | À traiter | — |

## Note 01 — 24 octobre 2026

- **Comportement obtenu :** une transaction externe ne crédite qu'un paiement pour un fournisseur.
  Le second rattachement retourne `false`, conserve la commande impayée et journalise
  `provider_reference_conflict` après rollback. Référence déjà renseignée non remplaçable,
  fournisseur du webhook contrôlé, identité externe obligatoire pour un succès KKiaPay.
  Rejeux widget/webhook sans deuxième transition ; retour d'échec tardif sans écrasement d'un succès.
- **Fichiers :** `app/Services/PaymentService.php`, `app/Models/Payment.php`,
  `database/migrations/2026_10_24_000000_add_unique_provider_reference_to_payments.php`,
  `tests/Feature/PaymentTest.php`, `tests/Feature/PaymentReferenceMigrationTest.php`,
  `docs/30-systeme-paiement.md` et cette ligne/note du journal.
- **Preuve avant/après :** le nouveau test `deux commandes` échouait avant correction :
  seconde commande attendue `unpaid`, obtenue `paid`. Il passe après correction.
  L'audit historique est conservé ; son exécution directe sous Windows a rencontré un problème
  de namespace Pest lié au dossier numérique. La reproduction 1 a donc été reprise dans `tests/`.
- **Contrôles :** `php vendor/bin/pest tests/Feature/PaymentTest.php tests/Feature/PaymentReferenceMigrationTest.php tests/Feature/OrderFlowTest.php tests/Feature/NotificationsTest.php --compact`
  → **32 tests, 138 assertions** ; `php artisan test --compact` → **242 tests, 717 assertions** ;
  `php -d memory_limit=1G vendor/bin/phpstan analyse --no-progress --memory-limit=1G` → **0 erreur** ;
  Pint `--test` sur les cinq fichiers PHP du lot → conforme. Tests exécutés avec
  `APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`, `DB_URL` vide,
  `MAIL_MAILER=array`, `QUEUE_CONNECTION=sync`. HTTP/notifications interceptés dans PaymentTest.
  Après ajustement du SELECT groupé pour MySQL, les trois tests de migration ont également été
  relancés : **3 tests, 11 assertions**. Migration, doublons historiques et rollback validés sur
  une connexion SQLite en mémoire dédiée, sans effacement ni réaffectation de paiements.
- **Limites :** génération SQL MySQL vérifiée en mode `pretend`, sans connexion ni exécution ;
  aucun serveur MySQL en écoute locale sur 3306, donc concurrence MySQL **non validée**.
  Aucun paiement réel ni recette KKiaPay sandbox ; retour réel des métadonnées non validé
  (sources officielles et décision dans `docs/30-systeme-paiement.md`). Aucun changement d'UI.
  La migration n'a pas été exécutée sur la base de l'application ; elle devra accompagner
  le déploiement et s'arrête si des doublons historiques nécessitent une réconciliation manuelle.
- **État :** validé localement, non publié. Checkpoint documentaire `ae5bc81` vérifié sur
  GitHub/main avant intervention. Changement de mode préexistant d'`artisan` préservé.
  Aucune autre fiche commencée.

## Note 02 — 24 octobre 2026

- **Cause et preuve avant/après :** le modèle ignorait le contrôle d'un montant `NULL` ; la
  passerelle appliquait `(int)` avant validation, avec troncature de fractions et conversion
  de types invalides. Le nouveau test `sans montant confirmé` échouait avant correction :
  commande attendue `unpaid`, obtenue `paid`. Il passe après correction ; l'audit historique
  reste inchangé.
- **Comportement obtenu :** succès en ligne soumis à un montant entier non négatif confirmé
  côté serveur, strictement égal au montant du paiement local. Zéro distingué d'une absence.
  Devise éventuelle contrôlée ; configuration locale KKiaPay autre que XOF refusée. Réponses
  HTTP/JSON incomplètes et statut en attente distingués d'un échec réel. Rejeux sans double
  transition, contrainte F01 conservée et confirmation COD sans montant de passerelle préservée.
  Les journaux KKiaPay reçoivent uniquement un petit ensemble de champs normalisés, sans
  recopier secrets, données client ou valeurs mal formées.
- **Fichiers :** `app/Models/Payment.php`, `app/Payments/Gateways/KkiapayGateway.php`,
  `app/Payments/Data/ChargeResult.php`, `app/Services/PaymentService.php`,
  `tests/Feature/PaymentTest.php`, `docs/30-systeme-paiement.md` et cette ligne/note du journal.
- **Contrôles :** `php vendor/bin/pest tests/Feature/PaymentTest.php --compact` →
  **59 tests, 283 assertions** ; `php artisan test --compact` → **282 tests, 894 assertions** ;
  `php -d memory_limit=1G vendor/bin/phpstan analyse --no-progress --memory-limit=1G` →
  **0 erreur** ; Pint `--test` sur les cinq fichiers PHP concernés → conforme.
  Environnement : `APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`,
  `DB_URL` vide, `MAIL_MAILER=array`, `QUEUE_CONNECTION=sync`. Appels HTTP et notifications
  simulés dans PaymentTest. Cas couverts : absence et valeur nulle, chaînes invalides,
  booléens, structures, négatifs, fractions, dépassement entier, montants nominaux et zéro,
  devises, HTTP non réussi, JSON/statut inexploitable, webhook avec montant client contradictoire,
  nouvelle vérification après réponse incomplète, non-divulgation dans les événements et COD.
- **Limites :** la documentation officielle indique XOF et montre `amount`, mais ne garantit
  ni présence ni format d'un champ de devise dans la réponse. La lecture défensive de `currency`
  est documentée ; son absence n'est pas présentée comme une confirmation indépendante de devise.
  Sources et décisions dans `docs/30-systeme-paiement.md`. Aucun paiement réel ni recette
  KKiaPay sandbox ; MySQL/concurrence non testés. Aucun changement d'UI, build frontend ou schéma.
  Aucune migration exécutée sur la base de l'application ; la migration de la fiche 01 reste
  à appliquer lors du déploiement de cette correction précédente.
- **État :** validé localement, sans commit ni push lors de A. Dépendance 01 vérifiée sur
  GitHub/main : `9da1d3c1ae30cd75feb0c6897108cb6c08a56c4e`. Changement de mode d'`artisan` préservé.
  Aucune autre fiche commencée.

## Note 03 — 24 octobre 2026

- **Cause et preuve avant/après :** le plafond de stock s'appliquait à chaque ligne
  séparément ; le décrément ne contrôlait pas leur cumul. Le nouveau test du panier
  historique (deux variantes × deux unités, stock global deux) échouait avant
  correction avec un stock de −2. Il passe désormais avec refus, stock inchangé,
  aucune commande ni notification et panier conservé. Audit historique inchangé.
- **Comportement obtenu :** cumul par produit contrôlé lors des ajouts/augmentations,
  puis revérifié sur les produits relus sous `lockForUpdate()` dans la transaction
  de commande. Vérification de tous les produits avant création, un décrément par
  produit, rollback intégral en cas d'erreur. Panier et notifications modifiés après
  réussite de la transaction. Quantités conservées après baisse de stock, diminutions
  progressives et retrait possibles ; stocks `NULL`, tailles valides et COD préservés.
  Erreur accessible avec quantité disponible, lien de retour au panier et tailles
  distinctes au récapitulatif. Disposition mobile du panier ajustée pour le rendre corrigeable.
- **Fichiers :** services `CartService`/`OrderService`, composant `tk/cart-error`,
  vues publiques `cart`, `checkout`, `shop-show`, tests `CartSizeTest`/`OrderFlowTest`,
  CSS compilé `app-t2tCysDR.css` remplaçant `app-dfHp-K84.css`, manifeste de build,
  présente ligne/note et [preuves de validation](../../validations/2026-10-24-stock/README.md).
- **Contrôles :** tests ciblés → **24 tests, 125 assertions** ; suite Pest complète →
  **297 tests, 995 assertions** ; Larastan → **0 erreur** ; Pint `--test` sur les quatre
  fichiers PHP → conforme ; `npm.cmd run build` → réussi. SQLite en mémoire, HTTP
  imprévu interdit et notifications simulées dans les tests ciblés. Cas de cumul,
  modification des quantités, baisse du stock à 1/0, lecture périmée, refus de toute
  la commande, rollback pendant le décrément et succès nominal couverts. Suite complète
  exécutée avant les derniers ajustements de présentation ; tests ciblés relancés
  avec les vues finales.
- **Recette :** nouvelle base SQLite temporaire et données fictives, serveur séparé
  sur 8011, mail `array` et COD. À 360 px : refus d'ajout/augmentation, panier conservé,
  pas de débordement horizontal. À 1440 px : stock abaissé avant confirmation, refus
  explicite, puis retrait d'une variante et commande réussie (une unité, stock final 0,
  panier vide). Captures dans la note liée. Console sans erreur/avertissement capturé,
  sélection/ajout/quantités/refus/retrait testés avec Entrée et tabulation. Viewport
  restauré, serveurs temporaires arrêtés ; serveurs habituels préservés.
- **Limites et état :** concurrence MySQL **non validée** ; SQLite ne prouve pas les
  verrous InnoDB. Aucun paiement ni email réel, recette clavier/accessibilité non
  exhaustive. Aucun changement de schéma ni migration de la base de l'application.
  Checkpoint 02 `6ad360bcda5d778c93d3c70a166cc9a3e86da654` vérifié sur GitHub/main.
  Validé localement, sans commit ni push lors de A ; mode étranger d'`artisan` préservé.
  Aucune autre fiche commencée.

## Note 04 — 24 octobre 2026

- **Cause et reproduction :** l'acceptation contrôlait uniquement le statut Envoyé,
  sans `valid_until`. Le nouvel appel Livewire direct sur un devis expiré échouait
  avant correction : statut attendu Sent, obtenu Accepted. L'audit historique est inchangé.
- **Règle :** validité jusqu'à 23:59:59.999999 du jour indiqué dans `app.timezone`
  (UTC actuellement), refus dès le lendemain ; sans date, aucune échéance automatique.
  Expiration calculée à l'affichage et à la décision sans réécriture historique.
- **Comportement :** `QuoteService` relit demande/devis sous verrou, vérifie propriétaire,
  permissions, statut et date ; décision/événement atomiques et rejeux sans doublon.
  Les décisions acceptées/refusées restent conservées et en lecture seule. Envoi avec
  date passée ou mal formée refusé, erreur visible puis effacée après correction.
  État Expiré et lien de contact pour demander explicitement le renouvellement.
- **Fichiers :** modèle `Quote`, nouveau `QuoteService`, vues client `requests-show`
  et admin `service-request-show`, tests `EspaceClientDevisTest`/`ServicesDevisTest`,
  cadre fonctionnel, présente ligne/note, [rapport et captures](../../validations/2026-10-24-devis/README.md),
  CSS `app-CV94gIVv.css` remplaçant `app-t2tCysDR.css` et manifeste de build.
- **Contrôles :** tests ciblés → **45 tests, 244 assertions** ; suite complète →
  **326 tests, 1 192 assertions** ; Larastan → **0 erreur** ; Pint sur les quatre
  fichiers PHP → conforme ; `npm.cmd run build` → réussi. Tests ciblés relancés avec
  les vues finales après les derniers ajustements de présentation/formulaire.
  SQLite en mémoire, HTTP imprévu interdit et notifications simulées. Horloge figée :
  limites de journée et fuseaux UTC/Lagos/New York, page périmée, date changée en base,
  absence de date, dates d'envoi, décisions/historique, permissions, rejeu et rollback.
- **Recette :** nouvelle base SQLite temporaire et comptes fictifs sur 8012, mail
  `array`, double authentification commerciale conservée sans appareil de confiance.
  Client : expiration, renouvellement au clavier, refus sur page devenue périmée,
  devis sans date et décision historique. Admin : date passée refusée, correction
  puis envoi fictif par tabulation/Entrée ; devis accepté en lecture seule. Formats
  360/1440 px sans débordement observé, console sans erreur/avertissement capturé,
  viewport restauré et onglets automatisés fermés. L'utilisateur confirme l'état
  Expiré, l'absence du bouton Accepter et la présence du renouvellement. Instance 8012
  conservée pour la suite des essais manuels. Plateforme 8000
  redémarrée à sa demande et vérifiée HTTP 200.
- **Limites/état :** concurrence MySQL **non validée**, aucun email réel ni recette
  SMTP. Aucun changement de schéma ou migration de la base habituelle. Validé localement,
  sans commit ni push lors de A. Checkpoint 03 `88cc961ff7a8050d64932c3b646817a58e2ee419`
  vérifié sur origin/main avant intervention ; mode étranger d'`artisan` préservé.
  Aucune autre fiche commencée.

## Note 05 — 24 octobre 2026

- **Cause et reproduction :** demande invitée sans propriétaire, email de devis
  vers le détail exigeant à juste titre un propriétaire connecté. Le nouveau test
  attendait HTTP 200 et obtenait 403 avant correction ; il passe avec récupération
  légitime et conserve le refus d'accès d'un autre compte. Audit historique inchangé.
- **Parcours :** lien signé 24 heures avec jeton aléatoire haché en base, connexion
  ou inscription avec retour au lien, vérification d'adresse par email signé d'une
  heure puis confirmation POST avec CSRF. Aucune simple égalité d'emails non vérifiés,
  aucune donnée privée révélée avant rattachement et aucune mutation GET.
- **Garanties :** demande, compte et jeton relus sous verrou en transaction, mise
  à jour conditionnelle du propriétaire absent, consommation de tous les liens et
  événement atomiques. Rejeu et transfert refusés ; mauvais compte et adresse non
  vérifiée ne consomment rien. Statuts, montants, décisions et historique conservés.
  Le suivi distingue une mise à jour d'informations d'un nouvel envoi de devis.
- **Renouvellement :** formulaire accessible depuis la liste client, les liens
  refusés et la confirmation publique ; référence et adresse initiale requises,
  réponse neutre, destination serveur uniquement. Limites par IP et destination.
  Les liens valides antérieurs restent utilisables jusqu'au premier rattachement.
  Sans email/référence : contact de l'équipe et vérification humaine, aucun transfert
  automatique d'une ancienne demande.
- **Fichiers :** nouveau `GuestRequestRecoveryController`, `GuestRequestRecoveryService`,
  notifications `GuestRequestRecoveryLink` et `GuestRequestEmailVerification`, nouvelle
  migration `2026_10_24_010000_create_guest_request_claims_table.php`,
  `GuestRequestRecoveryTest`, routes web, en-têtes de sécurité, trois notifications
  client existantes, inscription, fiche service, liste/détail client et vues
  `request-recovery`. Cadre fonctionnel, présente ligne/note et
  [rapport avec captures](../../validations/2026-10-24-rattachement/README.md).
  Le bundle initial `app-BOXzao7U.css` est remplacé au contrôle final par le bundle
  `app-CV94gIVv.css` déjà versionné en 04 ; aucun changement d'asset à publier.
- **Contrôles :** nouveaux tests → **29 tests, 164 assertions** ; récupération,
  devis, auth et notifications → **107 tests, 501 assertions** ; suite complète →
  **355 tests, 1 356 assertions** ; ciblés récupération/détail client relancés sur
  la vue finale → **51 tests, 282 assertions** ; Larastan → **0 erreur** ; Pint
  sur 11 fichiers PHP conforme ; build réussi. Première suite complète lancée avant
  le dernier ajustement Blade ; suite entière réexécutée sur les vues finales avant
  publication à la demande de l'utilisateur : **355 tests, 1 356 assertions**,
  Larastan **0 erreur**, Pint conforme. Après les tests, build final et build isolé
  identiques, manifeste et assets vérifiés ; `public/build` inchangé depuis 04.
  Bases SQLite en mémoire,
  notifications simulées et HTTP imprévu interdit dans les nouveaux tests.
  Succès, signatures, expiration en base, page périmée, vérification d'adresse,
  comptes croisés, inscription, rejeu, plusieurs liens, rollback, historique et
  renouvellement couverts ; aucune requête réelle de paiement.
- **Recette :** base SQLite temporaire distincte sur 8013, mail mémoire et boîte
  locale interceptée ; comptes fictifs. Connexion, confirmation au clavier, devis
  retrouvé, rejeu, expiration, mauvais compte, vérification puis rattachement,
  renouvellement par Tab/Entrée réalisés à 360/1280/1440 px. Aucun débordement observé,
  aucune erreur/avertissement console capturé ; viewport restauré, onglets de
  test fermés. Cas manuel distinct ouvert dans Chrome ; l'utilisateur confirme
  le devis de 10 000 XOF après rattachement et le message « déjà utilisé » à la
  réouverture du même lien. Les autres cas ne sont pas présentés comme personnellement
  validés par l'utilisateur. Renouvellement mobile et lien utilisé bureau revérifiés
  avec le bundle final, captures actualisées ; aucun débordement ni erreur console.
  Identifiants, liens à jeton et fichiers de
  recette hors dépôt ; instance 8013 conservée temporairement pour l'essai demandé.
- **Limites/état :** concurrence réelle et migration MySQL **non validées** ; SMTP
  réel/délivrabilité et worker de queue réel **non validés**. Nouvelle migration
  testée sur SQLite isolé uniquement, non appliquée à la base habituelle. Le nouveau
  parcours nécessite cette migration avant usage sur cette base. Aucun déploiement,
  commit ou push lors de A. Checkpoint 04 `c37fb7b7720612f674dca4fc9145ba2676709ce2`
  vérifié sur origin/main ; mode étranger d'`artisan` préservé. Aucune autre fiche
  commencée.

## Modèle de note à remplir après une intervention

```text
Fiche :
Date :
Comportement obtenu :
Fichiers principaux :
Tests et contrôles réellement exécutés :
Recette navigateur / services externes :
Limites ou données manquantes :
État local : Partiel / Validé localement
```
