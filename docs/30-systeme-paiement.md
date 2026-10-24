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

1. **Init (serveur)** — au checkout, on crée un `Payment` (`pending`, montant recalculé serveur,
   `reference` unique) via `PaymentManager::active('shop')->initialize(...)`.
2. **Widget (client)** — la page charge `k.js` et appelle `openKkiapayWidget({ amount, key,
   sandbox, data: {reference}, callback, paymentmethod, countries:['BJ'] })`. `data` porte **notre**
   référence pour le rapprochement.
3. **Succès côté client** — `addSuccessListener(r => …)` renvoie un `transactionId`. On l'envoie au
   serveur (Livewire action) qui appelle **`verify(transactionId)`** (SDK PHP `verifyTransaction`) →
   si `status = SUCCESS` et `amount` conforme → transition `succeeded` + marque la commande payée.
4. **Webhook (asynchrone, source de vérité)** — KKiaPay POST sur `/webhooks/kkiapay`. On **vérifie la
   signature** (`x-kkiapay-secret` = hash secret), on normalise, on `verify()` par sécurité, puis on
   applique la **même** transition idempotente. Réponse **HTTP 200** obligatoire (sinon retries).
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

### Widget (extrait, à intégrer au checkout)
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

## Sources
- [Documentation KKiaPay](https://docs.kkiapay.me/v1) · [SDK PHP](https://docs.kkiapay.me/v1/en-1.0.0/plugin-et-sdk/admin-sdks-server-side/php-admin-sdk) · [SDK JS](https://docs.kkiapay.me/v1/en-1.0.0/plugin-et-sdk/sdk-javascript) · [Webhooks](https://docs.kkiapay.me/v1/en-1.0.0/tableau-de-bord/webhook) · [php-sdk (GitHub)](https://github.com/kkiapay/php-sdk)
