# Prompts de correction — audit Tikmos du 20 octobre 2026

Cette bibliothèque découpe les constats de l’[audit](../../audits/2026-10-20/README.md) en **36 fiches**, chacune avec **deux prompts prêts à copier** :
- **A : corriger et valider**, puis s’arrêter avant commit ;
- **B : relire, créer un commit limité à cette correction, pousser et vérifier GitHub**, puis s’arrêter.

Les étapes 33 à 36 sont des recettes et leur compte rendu ; 35 est optionnelle. Leur existence ne signifie pas que les services externes, la PWA ou la production sont déjà validés.

## Mode d’emploi

1. Commencer par [00 — publication de l’audit et du plan](00-publier-audit-et-plan.md).
2. Lire le [cadre commun](CADRE-COMMUN.md), puis utiliser le prompt A de la première fiche.
3. Examiner les résultats, captures et limites. Une correction partielle reste partielle.
4. Utiliser le prompt B de **la même fiche**. Le SHA doit être retrouvé sur GitHub.
5. Passer à la fiche suivante seulement après cette publication vérifiée et sur instruction de l’utilisateur.
6. Conserver les décisions et validations dans le [journal](JOURNAL.md). Git et le reçu final font foi pour les publications ; ne pas créer un commit supplémentaire uniquement pour y noter son propre SHA.

La préparation de cette bibliothèque n’applique ni ne publie les corrections. Le nombre de tests et l’état des constats datent de l’audit ; chaque fiche commence par vérifier la situation actuelle.

## Demandes courtes utilisables dans cette conversation

Pour commencer la première correction :

```text
Dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com, lis docs/prompts/audit-2026-10-20/01-transaction-externe-unique.md et le cadre commun. Exécute uniquement le prompt A de la fiche 01, puis arrête-toi avant commit et push.
```

Pour publier cette correction après validation :

```text
Dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com, exécute uniquement le prompt B de la fiche 01 (docs/prompts/audit-2026-10-20/01-transaction-externe-unique.md). Vérifie le commit sur GitHub, puis arrête-toi avant toute autre correction.
```

Pour les fiches suivantes, remplacer le numéro et le chemin par ceux du tableau. Les prompts complets sont dans les fichiers ; il n’est pas nécessaire de les regrouper dans une même demande.

## Ordre recommandé

Le checkpoint de publication s’applique après **chaque** ligne, pas seulement à la fin d’une phase.

| N° | Correction / recette | Priorité | Dépendances techniques |
|---|---|---|---|
| 01 | [Empêcher une transaction externe de payer plusieurs commandes](01-transaction-externe-unique.md) | P1 | — |
| 02 | [Refuser un paiement en ligne sans montant confirmé](02-montant-paiement-obligatoire.md) | P1 | 01 |
| 03 | [Empêcher le stock négatif entre variantes](03-stock-global-variantes.md) | P1 | — |
| 04 | [Appliquer la validité des devis côté serveur](04-expiration-devis.md) | P1 | — |
| 05 | [Permettre au visiteur de retrouver sa demande en sécurité](05-rattachement-demande-invite.md) | P1 | — |
| 06 | [Supprimer les identifiants administrateur de repli](06-creation-administrateur.md) | P1 | — |
| 07 | [Gérer l’abandon et la reprise du paiement](07-reprise-paiement-reservation.md) | P1 | 01, 02, 03 |
| 08 | [Rendre la numérotation des commandes atomique](08-numerotation-commandes.md) | P1 | 03 |
| 09 | [Limiter les abus des formulaires publics](09-anti-abus-formulaires.md) | P2 | — |
| 10 | [Sécuriser et clarifier le challenge 2FA](10-otp-delai-et-experience.md) | P2 | — |
| 11 | [Aligner la suppression de compte avec son fonctionnement réel](11-suppression-compte-texte.md) | P2 | — |
| 12 | [Unifier le bouton Demander un devis et le suivi client](12-parcours-devis-unifie.md) | P1 | 05 |
| 13 | [Annoncer les frais de livraison avant confirmation](13-frais-livraison.md) | P2 | 07 |
| 14 | [Clarifier les statuts de commande et le vocabulaire](14-libelles-statuts-navigation.md) | P2 | — |
| 15 | [Supprimer ou relier la carte Alertes inerte](15-carte-alertes-client.md) | P2 | — |
| 16 | [Ajouter un retour clair du client vers la vitrine](16-retour-vers-vitrine.md) | P2 | — |
| 17 | [Organiser les réglages longs en sections](17-reglages-admin-sections.md) | P2 | — |
| 18 | [Relier les libellés, aides et erreurs aux champs](18-libelles-champs-accessibles.md) | P2 | — |
| 19 | [Nommer les actions à icône et le tri du catalogue](19-boutons-et-selecteurs-accessibles.md) | P2 | — |
| 20 | [Gérer le focus des menus et panneaux](20-focus-menus-panneaux.md) | P2 | — |
| 21 | [Corriger les contrastes sans changer l’identité](21-contrastes-theme.md) | P2 | — |
| 22 | [Remplir les médias et compacter les cartes produit](22-photos-et-cartes-catalogue.md) | P2 | 21 |
| 23 | [Structurer les informations des fiches produit](23-fiches-produits-completes.md) | P2 | 13, 22 |
| 24 | [Présenter des réalisations et chiffres vérifiables](24-realisations-et-preuves.md) | P2 | — |
| 25 | [Améliorer la présentation de l’équipe](25-portraits-equipe.md) | P2 | — |
| 26 | [Rendre les offres visibles sur l’accueil](26-hierarchie-accueil.md) | P2 | 12, 21, 24, 25 |
| 27 | [Alléger l’en-tête aux largeurs intermédiaires](27-entete-tablette.md) | P2 | — |
| 28 | [Optimiser les logos et leur rendu sur fond sombre](28-logos-clair-sombre-legers.md) | P2 | — |
| 29 | [Corriger l’image Open Graph manquante](29-image-partage-social.md) | P2 | — |
| 30 | [Donner un titre explicite à chaque page privée](30-titres-pages-privees.md) | P3 | — |
| 31 | [Ajouter les contrôles automatiques GitHub](31-ci-github.md) | P2 | 01, 02, 03, 04, 05, 06, 07, 08 |
| 32 | [Mesurer et améliorer la performance compilée](32-performance-production.md) | P2 | 22, 28 |
| 33 | [Valider les emails sans contacter de vrais destinataires par défaut](33-recette-emails.md) | Recette | 07, 10, 12 |
| 34 | [Valider le cycle KKiaPay en sandbox](34-recette-kkiapay-sandbox.md) | Recette | 01, 02, 07, 13 |
| 35 | [Vérifier la PWA sans l’activer en production](35-recette-pwa.md) | Optionnel | — |
| 36 | [Actualiser l’audit après toutes les corrections publiées](36-recette-finale-modules.md) | Recette | 01, 02, 03, 04, 05, 06, 07, 08, 09, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32 |

## Lecture du plan

- **01–12 :** fiabilité métier, sécurité et parcours de demande.
- **13–17 :** clarté des parcours client et administration.
- **18–21 :** accessibilité des composants, focus et contrastes.
- **22–30 :** contenu, présentation, médias et SEO.
- **31–32 :** CI GitHub et performance mesurée.
- **33–36 :** recettes emails, sandbox, PWA optionnelle et validation finale.

Les vrais médias, textes métier et accès sandbox manquants sont identifiés dans leurs fiches. Ils ne doivent pas être remplacés par des informations inventées. Aucun prompt n’autorise un paiement réel, un email vers un destinataire non autorisé ou un déploiement en production.

## Source des constats

Les références F01 à F07 correspondent aux défauts nommés du rapport. Les autres fiches couvrent ses tableaux UX, accessibilité, visuel, performance et vérifications restantes. Les anciennes bibliothèques de construction restent disponibles dans [l’index des prompts](../README.md).
