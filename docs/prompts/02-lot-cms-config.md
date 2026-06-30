# Lot 02 — CMS & configuration globale

> Hérite du **prompt maître**. Réf : `03` (CMS §6), `05` (§6-7), `04`.

## Objectif
Mettre en place le **socle de contenu éditable** : configuration globale (Spatie Settings), médias
(MediaLibrary), éditeur riche (TipTap + purifier), et l'ossature CRUD générique réutilisée par les
lots de contenu. **Rien d'« en dur » sur la vitrine.**

## Périmètre / livrables
1. **Settings** (`app/Settings`) : `GeneralSettings` (nom, baseline, logo, devise, fuseau),
   `ContactSettings` (email, téléphones, **WhatsApp**, adresse, horaires, réseaux), `SeoSettings`
   (titre/description/og par défaut, JSON-LD Person/Organization), `FeatureSettings` (flags :
   boutique, blog, PWA, paiement en ligne). Migrations settings + valeurs par défaut seedées.
2. **Médias** : config MediaLibrary (conversions thumb/web webp), composant `x-tk.media-input`
   repalettisé, helper d'affichage responsive (srcset).
3. **Éditeur riche** : `x-tk.editor` (TipTap) + **purification** systématique (`mews/purifier`) à
   l'enregistrement.
4. **Helpers contenu** : accès aux settings dans les vues (ex. numéro WhatsApp, réseaux) via un
   composant/partial unique (source unique de vérité).

## Étapes (PDAR)
1. **Plan** : classes settings + champs ; politique de conversions médias ; règles de purification.
2. **Do** : settings + migrations + seed ; médias ; éditeur ; helpers.
3. **Assess** : tests settings (lecture/écriture, défauts), upload média (validation type/taille,
   conversion), purification (script injecté → nettoyé). Pint/Larastan. Rendu d'un champ éditeur.
4. **Review** : sécurité uploads (hors webroot, signés si privés), cohérence charte.

## Tests (Pest)
- Settings : valeurs par défaut présentes ; mise à jour persistée.
- Médias : upload valide accepté, type/poids invalides rejetés, conversion générée.
- Purifier : HTML malveillant nettoyé avant stockage.

## Definition of Done
DoD du maître + : settings opérationnels et seedés, médias + éditeur sécurisés, WhatsApp/coordonnées
pilotés par les settings (zéro valeur en dur).
