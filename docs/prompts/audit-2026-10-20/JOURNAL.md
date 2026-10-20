# Journal des corrections de l’audit

État initial : **aucune correction applicative effectuée dans le cadre de cette bibliothèque**.

Pour chaque A, remplacer seulement sa ligne et ajouter une note courte datée avec : changement, fichiers, commandes/résultats, recette et limites. États possibles : À traiter, En cours, Partiel, Validé localement.

La publication est contrôlée par le prompt B et attestée par son reçu (branche, SHA, lien GitHub, vérification distante). Ce journal décrit la validation locale ; ne pas annoncer une publication avant le push et ne pas créer une modification non publiée juste pour y écrire un SHA.

| Fiche | Sujet | État local | Preuves et limites |
|---|---|---|---|
| [01](01-transaction-externe-unique.md) | Empêcher une transaction externe de payer plusieurs commandes | À traiter | — |
| [02](02-montant-paiement-obligatoire.md) | Refuser un paiement en ligne sans montant confirmé | À traiter | — |
| [03](03-stock-global-variantes.md) | Empêcher le stock négatif entre variantes | À traiter | — |
| [04](04-expiration-devis.md) | Appliquer la validité des devis côté serveur | À traiter | — |
| [05](05-rattachement-demande-invite.md) | Permettre au visiteur de retrouver sa demande en sécurité | À traiter | — |
| [06](06-creation-administrateur.md) | Supprimer les identifiants administrateur de repli | À traiter | — |
| [07](07-reprise-paiement-reservation.md) | Gérer l’abandon et la reprise du paiement | À traiter | — |
| [08](08-numerotation-commandes.md) | Rendre la numérotation des commandes atomique | À traiter | — |
| [09](09-anti-abus-formulaires.md) | Limiter les abus des formulaires publics | À traiter | — |
| [10](10-otp-delai-et-experience.md) | Sécuriser et clarifier le challenge 2FA | À traiter | — |
| [11](11-suppression-compte-texte.md) | Aligner la suppression de compte avec son fonctionnement réel | À traiter | — |
| [12](12-parcours-devis-unifie.md) | Unifier le bouton Demander un devis et le suivi client | À traiter | — |
| [13](13-frais-livraison.md) | Annoncer les frais de livraison avant confirmation | À traiter | — |
| [14](14-libelles-statuts-navigation.md) | Clarifier les statuts de commande et le vocabulaire | À traiter | — |
| [15](15-carte-alertes-client.md) | Supprimer ou relier la carte Alertes inerte | À traiter | — |
| [16](16-retour-vers-vitrine.md) | Ajouter un retour clair du client vers la vitrine | À traiter | — |
| [17](17-reglages-admin-sections.md) | Organiser les réglages longs en sections | À traiter | — |
| [18](18-libelles-champs-accessibles.md) | Relier les libellés, aides et erreurs aux champs | À traiter | — |
| [19](19-boutons-et-selecteurs-accessibles.md) | Nommer les actions à icône et le tri du catalogue | À traiter | — |
| [20](20-focus-menus-panneaux.md) | Gérer le focus des menus et panneaux | À traiter | — |
| [21](21-contrastes-theme.md) | Corriger les contrastes sans changer l’identité | À traiter | — |
| [22](22-photos-et-cartes-catalogue.md) | Remplir les médias et compacter les cartes produit | À traiter | — |
| [23](23-fiches-produits-completes.md) | Structurer les informations des fiches produit | À traiter | — |
| [24](24-realisations-et-preuves.md) | Présenter des réalisations et chiffres vérifiables | À traiter | — |
| [25](25-portraits-equipe.md) | Améliorer la présentation de l’équipe | À traiter | — |
| [26](26-hierarchie-accueil.md) | Rendre les offres visibles sur l’accueil | À traiter | — |
| [27](27-entete-tablette.md) | Alléger l’en-tête aux largeurs intermédiaires | À traiter | — |
| [28](28-logos-clair-sombre-legers.md) | Optimiser les logos et leur rendu sur fond sombre | À traiter | — |
| [29](29-image-partage-social.md) | Corriger l’image Open Graph manquante | À traiter | — |
| [30](30-titres-pages-privees.md) | Donner un titre explicite à chaque page privée | À traiter | — |
| [31](31-ci-github.md) | Ajouter les contrôles automatiques GitHub | À traiter | — |
| [32](32-performance-production.md) | Mesurer et améliorer la performance compilée | À traiter | — |
| [33](33-recette-emails.md) | Valider les emails sans contacter de vrais destinataires par défaut | À traiter | — |
| [34](34-recette-kkiapay-sandbox.md) | Valider le cycle KKiaPay en sandbox | À traiter | — |
| [35](35-recette-pwa.md) | Vérifier la PWA sans l’activer en production | À traiter | — |
| [36](36-recette-finale-modules.md) | Actualiser l’audit après toutes les corrections publiées | À traiter | — |

## Modèle de note à remplir après une intervention

```text
Fiche :
Date :
Comportement obtenu :
Fichiers principaux :
Tests et contrôles réellement exécutés :
Recette navigateur / services externes :
Limites ou données manquantes :
État local : Partiel / Validé localement
```
