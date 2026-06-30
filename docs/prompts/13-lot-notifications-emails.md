# Lot 13 — Notifications & emails

> Hérite du **prompt maître**. Réf : `03` §5 (matrice notifs), `02`, `04` §8 (queue).

## Objectif
Centraliser les **notifications** transactionnelles (email en queue) et préparer le canal
**WhatsApp**, sur tous les événements client-visibles (demandes, devis, commandes, 2FA).

## Périmètre / livrables
1. **Notifications Laravel** (queue `database`) : `RequestReceived` (équipe + accusé client),
   `QuoteSent`, `RequestStatusChanged`, `OrderReceived`, `OrderStatusChanged`, `NewMessage`,
   `LoginCode` (2FA). Templates mail à la charte (logo, filet or).
2. **Déclencheurs** : brancher sur les transitions d'état (events) des lots 05 / 08 / 09 / 10.
3. **Préférences** : respect des préférences notif du profil client (transactionnel toujours).
4. **WhatsApp** : helper de lien `wa.me` (lancement) + **abstraction** prête pour Cloud API
   (interface `WhatsappChannel`) à activer en phase auto (non bloquant).
5. **File d'attente** : configurer/queue, retries, logs ; commande `queue:work` planifiée.

## Étapes (PDAR)
1. **Plan** : liste des notifs ↔ événements, templates, canal WhatsApp (abstraction).
2. **Do** : notifications → templates → branchements → préférences → queue.
3. **Assess** : tests (`Notification::fake()` : la bonne notif part au bon acteur sur chaque
   transition ; respect des préférences ; mail en queue). Pint/Larastan.
4. **Review** : pas d'envoi synchrone bloquant, contenu mail à la charte, désinscription marketing.

## Tests (Pest)
- `Notification::fake()` : transition `devis_envoye` → `QuoteSent` au client.
- Nouvelle commande → `OrderReceived` à l'admin + accusé client.
- Préférence notif off (non transactionnel) respectée.

## Definition of Done
DoD du maître + : notifications en queue branchées sur tous les événements, templates charte,
abstraction WhatsApp prête, tests verts.
