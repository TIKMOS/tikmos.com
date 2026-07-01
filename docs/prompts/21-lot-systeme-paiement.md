# Lot 21 — Système de paiement (abstraction + KKiaPay)

> Hérite du **prompt maître**. Réf : `30-systeme-paiement.md`.

## Objectif
Construire un **système de paiement portable et évolutif** : couche d'abstraction « passerelles » +
driver **KKiaPay** (mobile money / carte / Wave), avec bascule **en ligne ↔ à la livraison**
configurable en admin. Conçu pour être **copié sur d'autres projets** (dossier autonome).

## Périmètre / livrables
1. **`app/Payments/`** (autonome) : interface `PaymentGateway`, DTO (`ChargeRequest/Result`,
   `WebhookResult`), enums (`PaymentProvider/State`), `PaymentManager` (driver manager),
   drivers **`KkiapayGateway`** + **`CodGateway`**, `config/payments.php`.
2. **Table `payments`** (polymorphe `payable`) + `payment_events` (idempotence, `markSucceeded()`).
3. **Widget KKiaPay** au checkout (`k.js`, `openKkiapayWidget`, listeners) + action serveur
   **`verify()`** (`verifyTransaction`).
4. **Webhook** signé (`x-kkiapay-secret`) : route hors CSRF, vérif signature + re-`verify` + transition
   idempotente, réponse 200.
5. **`PaymentSettings`** + écran **Réglages → Paiements** (activation online/COD, clés chiffrées,
   sandbox/prod, passerelle par canal).
6. **Doc de copie** (< 15 min) pour réutiliser sur un autre projet.

## Sécurité (impératif)
Signature webhook vérifiée, re-vérification serveur, montant contrôlé, idempotence, secrets en `.env`,
HTTPS, rate limiting. **Ne jamais faire confiance au client.**

## Étapes (PDAR) + DoD
Voir `30-` §10. Tests : verify SUCCESS/échec, signature valide/invalide, idempotence (widget+webhook),
écart de montant refusé, bascule online/COD, mode sandbox de bout en bout. Suite verte, Pint/Larastan.
