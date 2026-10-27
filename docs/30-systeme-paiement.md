# TIKMOS — Système de paiement (abstraction + KKiaPay)

> Module **costaud, évolutif et portable** : une couche d'abstraction « passerelles de paiement »
> réutilisable sur d'autres projets, avec un premier driver **KKiaPay** (mobile money / carte / Wave,
> zone UEMOA). Bascule **paiement en ligne ↔ paiement à la livraison** configurable depuis l'admin.
> Dernière mise à jour : 2026-07-01.
> Recherche : docs officielles KKiaPay (SDK PHP `kkiapay/kkiapay-php`, widget `cdn.kkiapay.me/k.js`,
> `verifyTransaction`, webhooks signés `x-kkiapay-secret`).

---

## 1. Objectifs
- **Encaisser en ligne** (KKiaPay : MTN/Moov MoMo, carte, Wave) **et/ou à la livraison** (COD).
- **Configurable par l'admin** : activer/désactiver chaque mode et chaque passerelle, par canal
  (boutique, acomptes de devis, futurs modules).
- **Évolutif** : ajouter une passerelle (FedaPay, Stripe, PayPal…) = 1 classe driver, zéro refonte.
- **Portable** : conçu comme un **package interne** (`app/Payments/`) copiable tel quel sur un autre
  projet Laravel. Aucune dépendance au métier Tikmos dans le cœur.
- **Sûr** : vérification serveur systématique, **webhooks signés**, idempotence, montants recalculés
  serveur, jamais confiance au client.

---

## 2. Architecture (couche d'abstraction)

```
app/Payments/
  Contracts/
    PaymentGateway.php        # interface commune à toutes les passerelles
  Gateways/
    KkiapayGateway.php        # driver KKiaPay (1er)
    // FedapayGateway.php, StripeGateway.php … (à venir)
  Data/
    ChargeRequest.php         # DTO : montant, devise, référence, client, metadata, callback
    ChargeResult.php          # DTO : statut, référence passerelle, brut
    WebhookResult.php         # DTO normalisé d'un événement webhook
  Enums/
    PaymentProvider.php       # kkiapay | fedapay | stripe | cod …
    PaymentState.php          # pending | succeeded | failed | refunded | cancelled
  PaymentManager.php          # résout la passerelle active (driver manager, façon Laravel)
  Exceptions/PaymentException.php
config/payments.php           # passerelles, clés, mode online/COD, activation par canal
```

### Interface `PaymentGateway`
```php
interface PaymentGateway
{
    public function provider(): PaymentProvider;

    /** Prépare/initie une charge et retourne de quoi rendre le widget ou rediriger. */
    public function initialize(ChargeRequest $request): ChargeResult;

    /** Vérifie une transaction côté serveur (source de vérité). */
    public function verify(string $reference): ChargeResult;

    /** Valide la signature d'un webhook et normalise l'événement. */
    public function handleWebhook(\Illuminate\Http\Request $request): WebhookResult;
}
```

> **Règle d'or** : le résultat d'un paiement n'est **jamais** décidé côté client. On confirme via
> `verify()` (poll après widget) **et** via `handleWebhook()` (asynchrone). Les deux chemins
> convergent vers une **transition idempotente** de la commande/du paiement.

### `PaymentManager` (driver manager)
Étend le pattern `Illuminate\Support\Manager`. `PaymentManager::driver('kkiapay')` ou
`PaymentManager::active($channel)` qui lit `config/payments.php` + les réglages admin (feature flags)
pour retourner la passerelle active d'un canal.

---

## 3. Modèle de données

### payments
| Colonne | Type | Notes |
|---|---|---|
| id | | |
| payable_type / payable_id | morph | commande, devis, … (polymorphe → portable) |
| provider | string | kkiapay / cod / … |
| reference | string unique | notre référence (`TKM-PAY-…`) |
| provider_reference | string null | id transaction passerelle |
| amount | unsigned int | plus petite unité (XOF entier) |
| currency | string(8) | XOF |
| state | string | PaymentState |
| method | string null | momo / card / wallet / cash |
| meta | json null | payload brut normalisé (audit) |
| paid_at | timestamp null | |
| timestamps | | |
- Index : `reference`, `provider_reference`, `(payable_type, payable_id)`, `state`.
- Depuis la correction F01 du 24 octobre 2026 : index unique
  `payments_provider_reference_unique` sur `(provider, provider_reference)`.
  Les références `NULL` restent autorisées pour les paiements en attente et le COD.

### payment_events (traçabilité fine — voir aussi `33-journal-activite.md`)
`id, payment_id, from_state, to_state, source(widget|webhook|admin|verify), note, created_at`.

> Le paiement est **découplé** de la commande via `payable` (morph) : le même module encaisse une
> commande boutique **ou** un acompte de devis **ou** tout futur objet, sans le savoir.

---

## 4. Flux « paiement en ligne » (KKiaPay)

1. **Init (serveur)** — le checkout crée une commande avec réservation de 15 minutes et un
   `Payment` en attente via `PaymentService::open()`, puis dirige vers le détail de la commande.
   Montant recalculé côté serveur, référence locale unique, jeton de checkout unique contre le rejeu.
2. **Widget (client)** — « Reprendre le paiement » sur le détail obtient le paiement existant
   via `resumeOrder()`, charge `k.js` à la demande et ouvre le widget avec la seule clé publique.
   `data` contient notre référence et le numéro de commande pour le rapprochement.
3. **Succès côté client** — `addSuccessListener(r => …)` renvoie un `transactionId`. On l'envoie au
   serveur (Livewire action) qui appelle **`verify(transactionId)`** (driver HTTP KKiaPay) →
   si `status = SUCCESS` et `amount` conforme → transition `succeeded` + marque la commande payée.
4. **Webhook (asynchrone)** — endpoint `/webhooks/payments/kkiapay`. Le driver contrôle
   `x-kkiapay-secret` selon l'intégration existante, puis revérifie la transaction auprès du serveur
   KKiaPay avant la même transition idempotente. Le contrat réel, notamment les métadonnées,
   reste à confirmer en sandbox ; aucune validation fournisseur n'est déduite de nos simulations.
5. **Idempotence** — toute transition passe par `Payment::markSucceeded()` protégé (no-op si déjà
   `succeeded`). Widget et webhook peuvent arriver dans n'importe quel ordre / en double.

### Correction F01 — unicité et cohérence du rattachement (24 octobre 2026)

`PaymentService::settle()` recharge le paiement sous verrou dans une transaction, pour les
résultats réussis comme échoués. L'enregistrement de la référence externe, le succès du paiement,
son événement et la réaction de la commande sont atomiques. Un échec tardif sur une instance
périmée ne peut plus écraser un succès.

La contrainte unique en base arbitre la réutilisation d'une transaction entre deux paiements,
y compris lorsque leurs lignes sont différentes. Après rollback, le service retourne `false`
et conserve un événement `provider_reference_conflict` sur le paiement refusé. Il ne masque pas
les violations d'autres contraintes. Les autres refus de rattachement sont journalisés :

- `provider_mismatch` : fournisseur du webhook différent de celui du paiement local ;
- `provider_reference_mismatch` : remplacement d'une référence déjà renseignée ;
- `provider_reference_missing` : succès KKiaPay sans identité externe exploitable.

Les rejeux sur un paiement réussi ne répètent ni sa transition ni sa réaction métier. Les
références externes restent propres à chaque fournisseur. Aucun changement du format des
métadonnées du widget n'est introduit : les [sources officielles du SDK JS](https://docs.kkiapay.me/v1/plugin-et-sdk/sdk-javascript)
documentent `data`, mais l'[exemple de vérification du SDK PHP](https://docs.kkiapay.me/v1/plugin-et-sdk/admin-sdks-server-side/php-admin-sdk)
ne garantit pas son retour. La correspondance de ces métadonnées dans le contrat réel KKiaPay
reste à vérifier lors de la recette sandbox ; elle n'est pas présentée comme validée ici.

**Migration d'une installation existante :** la nouvelle migration
`2026_10_24_000000_add_unique_provider_reference_to_payments.php` détecte les doublons non `NULL`
par fournisseur, quel que soit leur statut. Elle s'arrête avant tout changement si elle en trouve.
L'opérateur doit alors réconcilier les paiements concernés avec les justificatifs du fournisseur ;
aucun paiement historique n'est supprimé ou réaffecté automatiquement. Lors du déploiement,
effectuer la migration sans écritures concurrentes de paiement. Son rollback retire seulement
l'index unique et conserve les données ainsi que l'index simple préexistant.

Les tests utilisent SQLite en mémoire, HTTP et notifications simulés. Ils couvrent le refus du
second paiement, les deux ordres d'arrivée widget/webhook, le rollback métier et les données
historiques. La concurrence MySQL et les paiements KKiaPay réels ne sont pas validés par ces tests.
La migration n'a pas été exécutée sur la base locale utilisée par l'application.

### Correction F02 — montant confirmé obligatoire (24 octobre 2026)

Un succès en ligne exige un montant confirmé égal au `Payment.amount` enregistré côté serveur.
Le widget et le webhook ne fournissent que les identifiants à revérifier ; leurs montants et
devises éventuels ne peuvent pas remplacer la réponse de vérification serveur.

La passerelle valide le montant avant toute conversion : entier non négatif, chaîne composée
uniquement de chiffres dans la plage des entiers PHP, ou nombre JSON sans fraction représentable
comme entier PHP. Booléens, tableaux, objets, fractions, chaînes non numériques et dépassements
sont refusés. Zéro est une valeur présente : il n'est accepté que si zéro est dû. Le modèle
refuse également un succès en ligne sans montant lorsqu'il reçoit directement un `ChargeResult`.
La confirmation interne du COD conserve son fonctionnement sans montant de passerelle.

Les [devises supportées par KKiaPay](https://docs.kkiapay.me/v1/paiements/devises-supportees)
se limitent à XOF dans la documentation consultée le 24 octobre 2026. L'[exemple officiel de
vérification PHP](https://docs.kkiapay.me/v1/plugin-et-sdk/admin-sdks-server-side/php-admin-sdk)
contient `amount`, mais ne garantit aucun champ de devise ni son format. Le
[SDK PHP officiel](https://github.com/kkiapay/php-sdk/blob/master/src/Kkiapay.php) restitue le JSON
de l'API sans ajouter de confirmation de devise. En conséquence :

- le paiement local KKiaPay doit être en XOF, même si aucune devise n'est renvoyée ;
- si la réponse contient un champ `currency`, sa lecture défensive exige un code ISO 4217
  de trois lettres majuscules, identique à la devise locale ; ce champ n'est pas présenté comme
  garanti par le contrat documenté ;
- l'absence du champ reste une **limite de vérification indépendante de la devise**. La recette
  sandbox devra confirmer le payload réel ; aucune confirmation fournie par le client n'est utilisée.

Les réponses HTTP non réussies, JSON non exploitable, statut inconnu et transaction en attente
ne deviennent pas un échec bancaire confirmé. Elles retournent `false` et conservent le paiement
non confirmé. Un statut `FAILED` reçu par HTTP réussi conserve son événement `failed`. Les refus
sont distingués par `amount_missing`, `amount_invalid`, `amount_mismatch`, `currency_invalid`,
`currency_mismatch`, `currency_unsupported`, `verification_unavailable`, `verification_incomplete`
et `verification_pending`. Le premier succès et sa réaction métier restent atomiques et idempotents.

Le DTO transporte la devise éventuelle et le motif interne de validation. Les données d'audit
KKiaPay sont limitées au statut HTTP, au statut de transaction reconnu et au montant/devise
normalisés : ni corps brut, ni clés, jetons ou données client. Les valeurs mal formées ne sont pas
recopiées dans le journal. Aucun changement de schéma ou de frontend n'est nécessaire pour F02.

### Correction F07 — réservation et reprise (26 octobre 2026)

Une nouvelle commande en ligne réserve le stock pendant **15 minutes** à compter de sa
création. La limite est exclusive : à l'instant de l'échéance, aucun nouveau paiement ne peut
être lancé. Les réessais ne prolongent pas le délai. `reservation_expires_at` porte l'échéance,
`stock_reservation` mémorise les quantités effectivement retirées par produit, cumulées entre
variantes, et `reservation_released_at` empêche une deuxième restitution. Un stock non géré
(`NULL`) n'est jamais transformé en stock géré.

| Situation | Paiement et commande | Stock et prochaine action |
|---|---|---|
| Création en ligne | Commande en attente, paiement local en attente | Réservation 15 minutes ; payer depuis son détail |
| Fermeture/erreur du widget | Aucune conclusion bancaire tirée du navigateur | Commande conservée ; reprise avant échéance si aucun débit constaté |
| Réponse serveur `FAILED` | Paiement échoué, commande non payée | Même paiement local réutilisé, sans nouveau retrait ni prolongation |
| Réponse réseau/incomplète | Aucun succès ni échec bancaire inventé | Consulter la commande ; contacter l'équipe si déjà débité |
| Succès confirmé avant échéance | Paiement réussi, commande payée et confirmée | Réservation consommée, aucun nouveau décrément |
| Expiration ou annulation avant paiement | Paiement non confirmé ; reprise refusée | Quantités libérées une seule fois, contact pour une nouvelle disponibilité |
| Succès confirmé après libération/annulation | Paiement enregistré comme réussi, commande payée avec `payment_review_required` | Aucune nouvelle consommation automatique, préparation bloquée, vérification humaine |

Ouverture/reprise, règlement et expiration verrouillent d'abord la commande ; le paiement
vient ensuite pour le règlement, les produits dans un ordre stable pour la restitution.
Les transitions, quantités et événements métier sont transactionnels. Les contraintes F01,
le contrôle du montant F02 et le cumul du stock F03 restent en place. Les rejeux ne créent
pas de nouvelle commande, notification de commande ou réaction de succès. Un jeton de
checkout rejoué ne vide pas un nouveau panier ajouté entre-temps.

`orders:expire-reservations` est planifiée chaque minute par `Schedule::call()` (pas de
sous-processus, compatible avec le cron existant). Elle ignore les commandes payées et les
anciennes commandes sans métadonnées. L'affichage du détail et une tentative de reprise
appliquent également l'expiration ; le règlement revérifie l'échéance même avant le prochain
passage du cron. Le cron peut restituer physiquement le stock avec jusqu'à un intervalle de
planification de retard, sans prolonger le droit de payer.

Le mode en ligne nécessite `online` ou `both`, XOF et trois clés publiques/privées/secret
non vides et sans espaces, après surcharge par les réglages. Cela vérifie la configuration
locale, **pas la validité réelle du compte marchand ou des clés**. Seule la clé publique est
transmise au widget. Si la configuration change pendant un checkout, le choix devenu
indisponible est refusé, sans conversion silencieuse en COD. Le COD reste indépendant des
clés et n'a pas d'expiration automatique de stock.

Le [SDK Javascript officiel](https://docs.kkiapay.me/v1/plugin-et-sdk/sdk-javascript) documente
`openKkiapayWidget`, `addSuccessListener` et `addFailedListener`. Il ne documente pas de callback
de fermeture dans la page consultée : le bouton « J’ai fermé la fenêtre de paiement » permet
de sortir de cet état sans inventer d'événement fournisseur. Aucune fermeture ou erreur JS
ne marque la banque en échec et aucun payload brut de ces callbacks n'est journalisé.

**Paiement tardif :** l'événement `stock_review_required` conserve la nécessité de revue.
Le détail client et la gestion signalent le paiement reçu. Les avances de préparation et
l'encaissement manuel COD sont refusés pour cette commande en ligne. L'opérateur doit
vérifier le justificatif fournisseur, les stocks réellement disponibles et contacter le
client pour convenir d'une exécution ou d'un remboursement par un canal autorisé. Aucun
remboursement ni déblocage automatique n'est implémenté ou annoncé. Un traitement technique
explicite et audité reste nécessaire pour solder ce cas ; ne pas effacer le drapeau pour
contourner le contrôle.

**Installations existantes :** migration additive
`2026_10_26_000000_add_stock_reservation_to_orders.php`, sans modification des anciennes
migrations ni des montants/statuts historiques. Les anciennes commandes gardent les trois
champs de réservation et le jeton à `NULL`, sans restitution déduite de leur âge. Une ancienne
confirmation de paiement valide reste traitée ; aucun nouveau lancement depuis le détail
n'est autorisé sans revue préalable. Réconcilier ces dossiers manuellement, sans réaffectation
silencieuse. Un produit supprimé ou devenu non géré lors d'une libération n'est pas recréé :
un avertissement contenant seulement les identifiants techniques demande une revue.

Les tests simulés ne garantissent ni l'idempotence bancaire de plusieurs fenêtres ou appareils,
ni l'arrêt d'une opération déjà engagée chez le fournisseur. Le client déjà débité est invité
à contacter l'équipe avant un réessai. Concurrence MySQL, signature/payloads et fonctionnement
réel du widget KKiaPay, SMTP et cron de production restent non validés. Recette fournisseur
complète en fiche 34. [Rapport de validation F07](validations/2026-10-26-paiement/README.md).

### Widget (illustration du contrat ; intégration effective dans `resources/js/order-payment.js`)
```html
<script src="https://cdn.kkiapay.me/k.js"></script>
<script>
  function payer() {
    openKkiapayWidget({
      amount: @js($payment->amount),
      key: @js(config('payments.gateways.kkiapay.public_key')),
      sandbox: @js(config('payments.gateways.kkiapay.sandbox')),
      paymentmethod: 'momo', // momo | card | wallet (optionnel)
      countries: ['BJ'],
      data: JSON.stringify({ reference: @js($payment->reference) }),
      name: @js($order->contact_name), phone: @js($order->contact_phone),
    });
  }
  addSuccessListener(r => Livewire.dispatch('kkiapay-success', { transactionId: r.transactionId }));
  addFailedListener(() => Livewire.dispatch('kkiapay-failed'));
</script>
```

### Vérification serveur (driver)
```php
$kkiapay = new \Kkiapay\Kkiapay($public, $private, $secret, $sandbox);
$tx = $kkiapay->verifyTransaction($transactionId); // status, amount, transactionId, client…
// state = $tx->status === 'SUCCESS' ? Succeeded : Failed ; contrôler amount == payment->amount
```

---

## 5. Flux « paiement à la livraison » (COD)
- Driver `cod` (interne) : `initialize()` crée un `Payment` `pending` (méthode `cash`), aucune
  interaction passerelle. La commande suit son pipeline ; à la remise, l'admin **marque payé**
  (`succeeded`, `paid_at`). Comportement actuel de la boutique — repris dans le module unifié.

---

## 6. Configuration & activation (admin)

`config/payments.php` (valeurs par défaut) + surcharge via **`PaymentSettings`** (Spatie Settings,
éditable en admin) :
```php
return [
  'default_mode' => env('PAYMENTS_MODE', 'cod'),      // 'online' | 'cod' | 'both'
  'channels' => [
    'shop'   => ['modes' => ['cod', 'online'], 'gateway' => 'kkiapay'],
    'quotes' => ['modes' => ['cod'], 'gateway' => 'kkiapay'],
  ],
  'gateways' => [
    'kkiapay' => [
      'public_key'  => env('KKIAPAY_PUBLIC_KEY'),
      'private_key' => env('KKIAPAY_PRIVATE_KEY'),
      'secret'      => env('KKIAPAY_SECRET'),
      'sandbox'     => (bool) env('KKIAPAY_SANDBOX', true),
      'enabled'     => (bool) env('KKIAPAY_ENABLED', false),
    ],
  ],
];
```
Écran admin **Réglages → Paiements** : activer online/COD, choisir la passerelle par canal, saisir les
clés (chiffrées), basculer sandbox/production. Clés **jamais** commit — `.env` uniquement.

---

## 7. Sécurité (défense en profondeur)
- **Signature webhook** vérifiée (`x-kkiapay-secret`) avant tout traitement ; rejet 401 sinon.
- **Re-vérification** systématique via `verifyTransaction` (ne jamais faire confiance au payload).
- **Montant** comparé au `Payment` serveur ; refus si écart.
- **Idempotence** (référence unique + transitions no-op) contre doublons/retries.
- **Rate limiting** sur l'endpoint webhook ; logs d'audit (voir `33-journal-activite.md`).
- Secrets en `.env`, HTTPS obligatoire, endpoint webhook hors CSRF (route dédiée sans session).

---

## 8. Évolutivité (ajouter une passerelle)
1. Créer `app/Payments/Gateways/FedapayGateway.php implements PaymentGateway`.
2. L'enregistrer dans `PaymentManager` + `config/payments.php`.
3. Aucun changement dans la boutique/devis (ils parlent à l'interface).
Idem pour Stripe/PayPal (redirection au lieu de widget → `initialize()` renvoie une URL).

---

## 9. Portabilité (copier sur un autre projet)
- Le dossier **`app/Payments/`** + `config/payments.php` + la migration `payments` + le contrôleur
  webhook sont **autonomes** (aucune référence au métier Tikmos ; couplage via `payable` morph).
- Procédure de copie documentée : copier le dossier, publier la config, la migration, ajouter la
  route webhook, brancher `PaymentManager` sur son objet `payable`. Objectif : **< 15 min** pour
  réutiliser sur un nouveau projet.

---

## 10. Definition of Done (lot d'implémentation) — ✅ livré (lot 21)
- [x] Interface + `KkiapayGateway` + `CodGateway` + `PaymentManager` + DTO/enums. → `app/Payments/`
- [x] Table `payments` (+ `payment_events`), modèle polymorphe, `markSucceeded()` idempotent. → `app/Models/Payment.php`
- [x] Endpoint webhook signé + `verify()` ; checkout boutique branché (online + COD). → `PaymentWebhookController`, `checkout.blade.php`
- [x] `PaymentSettings` + écran admin (activation, clés, sandbox). → `reglages/payments.blade.php`
- [x] Tests Pest : verify SUCCESS/echec, signature webhook valide/invalide, idempotence
      (widget+webhook), écart de montant refusé, bascule online/COD. → `tests/Feature/PaymentTest.php` (7 tests)
- [x] Mode **sandbox** par défaut ; secrets chiffrés (`PaymentSettings::encrypted`) & hors repo (`.env`) ; §9 = guide de copie.

## Livraison et paiement — correction 13

Pour une commande, l'ouverture d'un paiement en ligne exige une livraison
explicitement `free` avec frais zéro ou `fixed` avec frais positifs, et un total
cohérent avec sous-total + frais enregistrés. Le checkout contrôle les réglages
serveur et leur empreinte verrouillée ; `OrderService::place()`,
`PaymentService::open()` et `resumeOrder()` refusent un montant incomplet avant
toute nouvelle initialisation. Masquer les boutons n'est pas la protection.
Les frais/total provenant du client sont ignorés. Un tarif changé demande une
nouvelle confirmation ; un tarif déjà enregistré dans une commande reste inchangé.

Une commande `unknown` annonce « Total hors livraison » et nécessite un accord
commercial manuel avant paiement. Les commandes historiques sans métadonnée
annoncent « Montant enregistré » et demandent une vérification. Aucune gratuité
ni supplément n'est déduit rétroactivement. Leur reprise en ligne est bloquée.
Un paiement ancien réellement confirmé par le fournisseur doit néanmoins être
enregistré : montant et référence restent vérifiés par les protections 01/02,
puis `payment_review_required` et l'événement `delivery_review_required` empêchent
une confirmation/préparation automatique en l'absence de livraison documentée.
Les rejeux ne répètent pas la transition. Aucun remboursement automatique.

La réservation, l'expiration et la restitution du stock de la correction 07
restent actives. Le contrôle après expiration conserve la restitution nécessaire.
Le COD et ses actions administratives existantes restent inchangés ; l'accord
commercial sur les frais inconnus demeure une procédure manuelle à définir et
tracer par l'équipe, sans nouveau système de tarification par commande.
SMTP réel, KKiaPay sandbox, MySQL et concurrence non validés.

## Sources

- [Documentation KKiaPay](https://docs.kkiapay.me/v1) · [SDK PHP](https://docs.kkiapay.me/v1/en-1.0.0/plugin-et-sdk/admin-sdks-server-side/php-admin-sdk) · [SDK JS](https://docs.kkiapay.me/v1/en-1.0.0/plugin-et-sdk/sdk-javascript) · [Webhooks](https://docs.kkiapay.me/v1/en-1.0.0/tableau-de-bord/webhook) · [php-sdk (GitHub)](https://github.com/kkiapay/php-sdk)
