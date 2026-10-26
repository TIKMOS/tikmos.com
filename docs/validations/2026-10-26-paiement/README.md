# Correction 07 — Réservation du stock et reprise du paiement

Validation locale du 26 octobre 2026, phase A, sans commit ni push.
Checkpoint 06 `e002883f59801b49fd50bdc7d59e445de528958e` vérifié sur GitHub/main.
Les corrections 01, 02 et 03 sont conservées et leurs tests relancés.
L'[audit historique F07](../../audits/2026-10-20/README.md) reste inchangé.

## Cause et reproduction avant correction

La création de commande décrémentait le stock et vidait le panier avant le succès
du paiement. La commande n'avait aucune échéance de réservation ; le checkout
ouvrait directement le widget et son détail ne permettait pas de reprendre.
Une fermeture ou un rafraîchissement ne fournissait pas de parcours de sortie utile.

Le nouveau test `F07 limite la réservation du stock en ligne à quinze minutes`
échouait avant modification sur SQLite en mémoire : après décrément du stock de
2 à 1, `reservation_expires_at` était **NULL**, au lieu de l'échéance à +15 minutes.
Il passe après correction. Il s'agit d'une preuve applicative isolée, sans
observation du fonctionnement réel de KKiaPay.

## Règles et garanties obtenues

- Réservation en ligne de **15 minutes**, non prolongée par une reprise. Limite
  exclusive, contrôlée à l'action serveur même sur une page ouverte auparavant.
- Quantités effectivement retirées mémorisées par produit, cumul des variantes,
  stocks `NULL` préservés. Succès avant échéance : réservation consommée sans
  second décrément. Échéance/annulation avant paiement : restitution unique.
- Checkout dirigé vers le détail persistant, paiement repris sur la même commande
  et le même paiement local, y compris après une réponse serveur `FAILED`.
- Jeton de checkout unique, verrou utilisateur contre son rejeu ; pas de commande,
  notification de réception ou retrait de stock supplémentaire, et conservation
  d'un nouveau panier constitué après le premier envoi.
- Verrous commande puis paiement, ou commande puis produits ordonnés, dans les
  transactions de règlement/restitution. Expiration et succès relisent l'état.
  Rollback testé si l'écriture de stock échoue. SQLite ne prouve pas la concurrence MySQL.
- Fermeture/erreur du navigateur : aucune conclusion sur le résultat bancaire.
  `addFailedListener` documenté ; bouton explicite « J’ai fermé la fenêtre de
  paiement » faute de callback de fermeture garanti par la documentation consultée.
  Messages invitant un client déjà débité à contacter l'équipe avant tout réessai.
- Vérification réseau limitée à 15 secondes ; interruption réseau : état non
  confirmé et événement `verification_unavailable`, sans faux échec bancaire.
- Montants serveur et corrections F01/F02 conservés ; aucune clé privée envoyée
  au navigateur. Mode en ligne masqué/refusé si mode, devise ou clés locales
  nécessaires sont incompatibles. Choix devenu indisponible refusé sans changer
  silencieusement le mode. COD indépendant des clés et de l'expiration.

### Paiement tardif et anciennes commandes

Un succès confirmé après libération du stock ou annulation reste **enregistré
comme paiement reçu**, avec `payment_review_required` et événement
`stock_review_required`. Aucun stock n'est repris automatiquement, même s'il est
encore disponible. Pas de confirmation automatique de préparation ; la gestion
bloque les avances de statut et l'encaissement manuel COD pour ce dossier en ligne.
Le client voit une invitation à contacter l'équipe et à ne pas payer une seconde fois.
Aucun remboursement automatique n'est effectué ou annoncé.

L'opérateur doit vérifier la transaction fournisseur, la disponibilité et convenir
de la suite avec le client. La résolution nécessite une intervention technique
autorisée et auditée ; aucun bouton de contournement du drapeau n'est fourni.

La nouvelle migration ajoute échéance, date de restitution, quantités réservées,
drapeau de revue et jeton de checkout. Les anciens champs/montants/paiements ne
sont pas réécrits. Sans métadonnées historiques, aucune restitution fondée sur
l'âge et aucun nouveau lancement de paiement depuis le détail : revue manuelle.
Une ancienne confirmation fournisseur valide reste traitée selon le comportement
antérieur. Un article supprimé ou devenu non géré n'est pas recréé lors de la
libération ; avertissement technique sans données client pour revue manuelle.

### Expiration et exploitation

Commande `orders:expire-reservations`, planifiée chaque minute en processus par
`Schedule::call()`, compatible avec les restrictions `proc_open` du projet.
Le détail, une reprise et le règlement contrôlent aussi l'échéance. Un cron en
retard peut retarder la remise en vente, mais ne prolonge pas le droit de payer.
[Règles détaillées](../../30-systeme-paiement.md#correction-f07--réservation-et-reprise-26-octobre-2026)
et [déploiement](../../DEPLOIEMENT.md#réservations-de-stock-en-ligne--correction-07).

## Tests et contrôles réellement exécutés

| Contrôle | Résultat |
|---|---|
| PaymentRecovery + migration + PaymentTest + OrderFlowTest + CartSizeTest | **116 tests, 597 assertions**, réussis |
| Suite Pest complète | **428 tests, 1 719 assertions**, réussis |
| JavaScript du widget simulé | **5 tests**, réussis |
| Larastan niveau 6 : app, nouvelle migration et routes console, séquentiel | **0 erreur** |
| Pint et syntaxe des 12 fichiers PHP concernés | Conformes |
| Build Vite et références du manifeste | Réussis, fichiers référencés présents |

La suite complète a été exécutée sur le comportement serveur et les contrôles de
formulaire finaux. Un dernier ajustement de présentation a ensuite supprimé un
débordement du checkout à 360 px (`min-w-0` local au formulaire et au téléphone) ;
les 116 tests ciblés ont été relancés sur cette vue finale, puis le rendu et le
bundle final revérifiés. Aucun fichier PHP fonctionnel n'a changé depuis Larastan.

Environnement forcé : `APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`,
`DB_URL` vide, cache/session `array`, mail mémoire et queue synchrone. Notifications
simulées et appels HTTP imprévus interdits dans les nouveaux tests.

```powershell
php vendor/bin/pest tests/Feature/PaymentRecoveryTest.php tests/Feature/OrderReservationMigrationTest.php tests/Feature/PaymentTest.php tests/Feature/OrderFlowTest.php tests/Feature/CartSizeTest.php --compact
php vendor/bin/pest --compact
node --test tests/Frontend/order-payment.test.mjs
php -d memory_limit=1G vendor/bin/phpstan analyse app database/migrations/2026_10_26_000000_add_stock_reservation_to_orders.php routes/console.php --debug --no-progress --memory-limit=1G
npm.cmd run build
```

Cas couverts : limite exacte, page périmée, réessais et rechargement, fermeture/
échec navigateur, échec fournisseur puis succès, erreurs réseau, accès croisés,
clés absentes/non textuelles/espacées, devise incompatible, refus d'une conversion
silencieuse en COD, succès nominal, rejeu webhook, paiement tardif avec et sans
expiration préalable par le job, stock devenu indisponible, cumul de variantes,
stocks non gérés, rollback de restitution, annulation et protection de la gestion.
La migration est exécutée sur une ancienne structure SQLite fictive et conserve
ses commandes et paiements sans mutation. Aucun test ne lance une opération bancaire.

## Recette navigateur et confirmation de l'utilisateur

Nouvelle base SQLite temporaire distincte sur le port **8014**, comptes fictifs,
horloge figée au 26/10/2026 à 17:00 UTC. Le faux SDK est servi exclusivement par
le routeur temporaire hors dépôt, avec HTTP/notifications interceptés. Il ne fait
aucun appel au widget marchand. La 2FA de gestion reste active, avec code local,
sans appareil de confiance ; son cache de recette est isolé dans le dossier temporaire.

Vérifications effectuées : checkout → détail persistant, ouverture par Entrée,
fermeture puis reprise, succès simulé, rafraîchissement conservant l'état payé,
réservation expirée sans bouton de paiement, paiement tardif visible côté client
et préparation bloquée en gestion. Formats CSS 360 et 1440 px, absence de
débordement observée sur les vues finales. Les mesures initiales ont révélé puis
permis de corriger le débordement du téléphone au checkout. Aucune erreur ou
avertissement console capturé sur les parcours finaux inspectés.

L'utilisateur confirme personnellement **fermeture, reprise et succès simulé**
sur la même commande après rafraîchissement. Les autres cas ne sont pas présentés
comme ayant été personnellement validés par lui. Le contrôle clavier porte sur
les actions de paiement et l'envoi du formulaire ; il ne certifie pas l'ensemble
des panneaux historiques de gestion ou leur piégeage du focus.

Les dimensions ont été restaurées, l'onglet de contrôle fermé. L'instance isolée
8014 et l'onglet de recette sont conservés temporairement pour les essais de
l'utilisateur. Identifiants, guide privé, routeur, SDK fictif et base restent
hors dépôt. Aucun secret ou identifiant de connexion ne figure dans les captures.

Captures des données fictives :

- [Reprise après fermeture, mobile](reprise-mobile-360.jpg)
- [Paiement confirmé, bureau](paiement-confirme-desktop-1440.jpg)
- [Réservation expirée, bureau](reservation-expiree-desktop-1440.jpg)
- [Paiement tardif, bureau](paiement-tardif-desktop-1440.jpg)
- [Paiement tardif, mobile](paiement-tardif-mobile-360.jpg)
- [Gestion bloquée pour revue, mobile](admin-verification-mobile-360.jpg)
- [Gestion bloquée pour revue, bureau](admin-verification-desktop-1440.jpg)
- [Checkout final, mobile](checkout-mobile-360.jpg)
- [Checkout final, bureau](checkout-desktop-1440.jpg)

## Fichiers du lot

- `app/Models/Order.php`
- `app/Services/OrderService.php`, `PaymentService.php`, nouveau `OrderReservationService.php`
- `app/Settings/PaymentSettings.php`
- `app/Payments/Gateways/KkiapayGateway.php`
- `app/Console/Commands/ExpireOrderReservations.php`
- `database/migrations/2026_10_26_000000_add_stock_reservation_to_orders.php`
- `routes/console.php`
- Vues `pages/public/checkout`, `pages/app/orders-show`, `pages/admin/boutique/orders`
- `resources/js/app.js` et nouveau `resources/js/order-payment.js`
- Tests `PaymentTest`, `PaymentRecoveryTest`, `OrderReservationMigrationTest`, `Frontend/order-payment.test.mjs`
- `docs/30-systeme-paiement.md`, `docs/DEPLOIEMENT.md`, ligne/note 07 du journal, ce rapport et les captures
- Manifeste et bundles compilés : CSS `app-Cz4owPm2.css`, JS `app-BEZyNR1Z.js`,
  remplaçant respectivement `app-CV94gIVv.css` et `app-SJZ69oV_.js`

## Limites et état

- **MySQL/concurrence réelle et migration MySQL non validés** ; SQLite seul ne
  démontre pas les verrous InnoDB ou les courses entre plusieurs processus.
- **KKiaPay sandbox non validée** ; payloads, callbacks réels, signatures,
  rapprochement des métadonnées et débits bancaires multiples restent à vérifier
  en fiche 34. Le code évite les doublons applicatifs locaux, sans garantir
  l'idempotence bancaire de plusieurs fenêtres/appareils.
- **SMTP réel, délivrabilité et cron de production non validés**.
- La migration doit accompagner un déploiement autorisé avant usage sur la base
  habituelle. **Aucune migration ni commande d'expiration sur cette base** durant A.
- Aucun email/paiement réel, compte réel modifié, déploiement, commit ou push.
- Mode étranger d'`artisan` préservé, index non modifié. Correction 08 non commencée.

Source officielle consultée pour les appels JS :
[SDK Javascript KKiaPay](https://docs.kkiapay.me/v1/plugin-et-sdk/sdk-javascript).
