# Lot 12 — Pages légales & RGPD

> Hérite du **prompt maître**. Réf : `04` §6 (RGPD), `05` (legal_documents), `02`.

## Objectif
Mettre le site en **conformité** : pages légales éditables (mentions, confidentialité, CGV, cookies),
**consentement cookies**, et **droits RGPD** clients (accès/effacement).

## Périmètre / livrables
1. **Modèle `LegalDocument`** (repalettiser) : slug, title, body (HTML), updated_at ; pages
   `/mentions-legales`, `/confidentialite`, `/cgv`, `/cookies` (éditables admin).
2. **Bandeau de consentement cookies** (`x-tk.cookie-consent`) : opt-in, mémorisation, lien
   politique. Pas de traceurs non essentiels avant consentement.
3. **Droits RGPD** : depuis le profil client — **export de mes données** + **demande d'effacement**
   (anonymisation/suppression cadrée). Journalisation.
4. **Politique de rétention** documentée (devis/commandes/logs).

## Étapes (PDAR)
1. **Plan** : contenus, modèle de consentement, flux export/effacement.
2. **Do** : pages légales + admin → bandeau cookies → export/effacement.
3. **Assess** : tests (pages 200, consentement mémorisé, export génère le fichier, effacement
   anonymise et révoque l'accès). Pint/Larastan.
4. **Review** : sécurité (export authentifié + signé), cohérence charte.

## Tests (Pest)
- Pages légales 200 et éditables (admin).
- Effacement client : ressources anonymisées, login impossible ensuite.

## Definition of Done
DoD du maître + : légales éditables, cookies opt-in conforme, droits RGPD opérationnels, tests verts.
