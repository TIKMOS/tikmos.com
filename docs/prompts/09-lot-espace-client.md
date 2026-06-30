# Lot 09 — Espace client

> Hérite du **prompt maître**. Réf : `03` (espace B), `05`, `01`.

## Objectif
Offrir au client un **espace** clair (`/espace`) pour suivre ses **demandes/devis** et **commandes
boutique**, échanger et gérer son profil. Scoping strict (chacun ne voit que ses données).

## Périmètre / livrables
1. **Layout app** (header + bottom-nav mobile, filet or actif) finalisé.
2. **Tableau de bord client** : aperçu (demandes en cours, dernières commandes, messages).
3. **Mes demandes / devis** : liste + détail (statut/timeline, **devis** à accepter/refuser,
   **échanges**, documents). Action **accepter/refuser un devis** (→ event + notif).
4. **Mes commandes boutique** : liste + détail (timeline statuts, articles, total).
5. **Mes factures / paiements** : suivi simple (documents, statut paiement).
6. **Mon profil** : coordonnées, mot de passe, **préférences de notification**.
7. **Messagerie/échanges** légère sur demandes (thread).

## Étapes (PDAR)
1. **Plan** : écrans, scoping par Policy, composants réutilisés (stepper, timeline, empty-state).
2. **Do** : layout → dashboard → demandes → commandes → factures → profil → messagerie.
3. **Assess** : tests (accès `auth`+`verified`, scoping 403 sur ressource d'autrui, accepter un devis
   change le statut, préférences notif persistées). Pint/Larastan. Parcours réel mobile + desktop.
4. **Review** : a11y, états vides soignés, cohérence charte.

## Tests (Pest)
- Client non connecté → redirigé ; non vérifié → bloqué.
- Client A ne peut pas voir une commande/demande de client B (403).
- Accepter un devis : `devis_envoye → acceptee` + event + notif.

## Definition of Done
DoD du maître + : espace client complet et scoppé, devis acceptables, suivi commandes, profil &
préférences, tests verts.
