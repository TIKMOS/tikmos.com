# Lot 05 — Services & devis

> Hérite du **prompt maître**. Réf : `03` (P1, états), `05` (services/service_requests/quotes), `01`.

## Objectif
Construire le **cœur acquisition services** : catalogue de **services** éditable (formation,
conception, installation, assistance, orientation, autres) + moteur de **demande de devis** avec
**pipeline** côté admin. Un seul moteur générique couvre tous les métiers A/B/C.

## Périmètre / livrables
1. **Modèle `Service`** (+ migration, factory, enum `Publication`) : category, slug, title, excerpt,
   body (HTML purifié), price_label, icon (Lucide), position, status, médias, SEO.
2. **Vitrine services** : `/services` (catalogue filtrable par catégorie) + `/services/{slug}`
   (détail : bénéfices, déroulé, FAQ, **CTA Demander un devis**). `x-tk.service-card`.
3. **Modèle `ServiceRequest`** + **`Quote`** + **`request_events`** (timeline), enums
   `ServiceRequestStatus`/quote status. Génération de **référence** (`TKM-DEV-…`).
4. **Formulaire de demande** (Volt) : depuis une fiche service ou `/contact`. **Envoi possible sans
   compte** ; proposition de création de compte pour suivre. Pièces jointes (uploads sécurisés).
   Validation (Form Request / règles Livewire), anti-spam (honeypot + throttle).
5. **Admin — pipeline** (préfigure le lot 10) : liste demandes (filtres/statuts), détail, **affectation**,
   **changement de statut** (→ event + notification), **création/édition de devis**, envoi.
6. **Notifications** (stubs reliés au lot 13) : accusé client, alerte équipe, devis envoyé.

## Étapes (PDAR)
1. **Plan** : schéma services/requests/quotes, transitions d'état, écrans.
2. **Do** : modèles/migrations → vitrine → formulaire → pipeline admin → events/notifs.
3. **Assess** : tests (création demande avec/sans compte, validation, upload, transition de statut
   crée un event, devis envoyé change le statut, permissions admin). Pint/Larastan. Parcours réel.
4. **Review** : scoping (client voit sa demande), sécurité uploads, charte.

## Tests (Pest)
- Soumission d'une demande (invité) → enregistrée + référence + accusé.
- Transition `envoyee → en_etude` journalisée (request_event).
- `quotes.send` interdit à un rôle non autorisé.
- Un client ne voit que **ses** demandes.

## Definition of Done
DoD du maître + : catalogue services éditable en ligne, demande→devis fonctionnelle de bout en bout,
pipeline admin minimal, timeline d'événements, tests verts.
