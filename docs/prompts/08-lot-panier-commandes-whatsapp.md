# Lot 08 — Panier, commandes & WhatsApp

> Hérite du **prompt maître**. Réf : `03` (P2, états), `05` (orders, order_items), `01`.

## Objectif
Permettre de **commander** une chaussure selon le **modèle Sezy** : **panier maison** (session →
fusion à la connexion) + checkout léger + **paiement à la livraison** (cash / mobile money à la
remise), avec **commande** suivie (timeline statuts). **WhatsApp** disponible comme canal de
commande/contact. Friction minimale.

## Périmètre / livrables
1. **Panier maison** en **session** (ajout par produit + taille + qty), fusion à la **connexion**.
   Composant Livewire panier + page `/panier`. Mini-panier dans le header.
2. **Commande WhatsApp** : depuis la fiche produit ou le panier → message `wa.me` pré-rempli
   (produit(s), taille(s), réf, total) vers le **numéro des settings**. Création d'une `Order`
   `channel=whatsapp` `status=enregistree` pour traçabilité (option : à la confirmation).
3. **Checkout site** (léger) : coordonnées + adresse de livraison → `Order` `channel=site`,
   `payment_status=en_attente`, `payment_method` (cash/mobile_money). **Paiement à la livraison**
   (pas de paiement en ligne au lancement — flag `FeatureSettings`). À la remise : statut `livrée` +
   `payment_status=payé`.
4. **Modèles `Order` / `OrderItem`** (+ migrations, factories), enums `OrderStatus`/`PaymentStatus`,
   référence `TKM-CMD-…`, snapshot prix/nom dans les items, **timeline** (order_events).
5. **Confirmation** : page + email d'accusé (relié lot 13).

## Étapes (PDAR)
1. **Plan** : structure panier session→DB, calcul totaux, format message WhatsApp, schéma order.
2. **Do** : panier → WhatsApp → checkout → modèles order → confirmation.
3. **Assess** : tests (ajout/maj/suppression panier, calcul total, fusion à la connexion, création
   order + items snapshot, génération du lien WhatsApp correct, transition de statut). Pint/Larastan.
4. **Review** : sécurité (revalider prix serveur, jamais le total client), charte, états vides.

## Tests (Pest)
- Ajout au panier (produit+taille) → total recalculé serveur.
- Lien WhatsApp contient produit/taille/réf et le **numéro des settings**.
- Création d'`Order` avec items **snapshotés** (nom/prix figés).
- Panier invité **fusionné** après connexion.

## Definition of Done
DoD du maître + : commander via WhatsApp et via site fonctionne, commande tracée, totaux fiables
(serveur), tests verts.
