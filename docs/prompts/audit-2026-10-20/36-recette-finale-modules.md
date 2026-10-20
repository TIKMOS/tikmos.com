# 36 — Actualiser l’audit après toutes les corrections publiées

Priorité : **Recette** · Référence : **Validation finale**.

Dépendances : [01](01-transaction-externe-unique.md), [02](02-montant-paiement-obligatoire.md), [03](03-stock-global-variantes.md), [04](04-expiration-devis.md), [05](05-rattachement-demande-invite.md), [06](06-creation-administrateur.md), [07](07-reprise-paiement-reservation.md), [08](08-numerotation-commandes.md), [09](09-anti-abus-formulaires.md), [10](10-otp-delai-et-experience.md), [11](11-suppression-compte-texte.md), [12](12-parcours-devis-unifie.md), [13](13-frais-livraison.md), [14](14-libelles-statuts-navigation.md), [15](15-carte-alertes-client.md), [16](16-retour-vers-vitrine.md), [17](17-reglages-admin-sections.md), [18](18-libelles-champs-accessibles.md), [19](19-boutons-et-selecteurs-accessibles.md), [20](20-focus-menus-panneaux.md), [21](21-contrastes-theme.md), [22](22-photos-et-cartes-catalogue.md), [23](23-fiches-produits-completes.md), [24](24-realisations-et-preuves.md), [25](25-portraits-equipe.md), [26](26-hierarchie-accueil.md), [27](27-entete-tablette.md), [28](28-logos-clair-sombre-legers.md), [29](29-image-partage-social.md), [30](30-titres-pages-privees.md), [31](31-ci-github.md), [32](32-performance-production.md).

Lire le [cadre commun](CADRE-COMMUN.md). Utiliser A, vérifier le résultat, puis utiliser B. La publication vérifiée précède le passage à la suite.

## Prompt A — réaliser et valider

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Lis docs/prompts/audit-2026-10-20/CADRE-COMMUN.md, les instructions applicables et docs/audits/2026-10-20/README.md. Exécute uniquement la correction 36 — Actualiser l’audit après toutes les corrections publiées.

Constat de référence : Validation finale. Priorité : Recette.
Dépendances techniques : 01 — Empêcher une transaction externe de payer plusieurs commandes; 02 — Refuser un paiement en ligne sans montant confirmé; 03 — Empêcher le stock négatif entre variantes; 04 — Appliquer la validité des devis côté serveur; 05 — Permettre au visiteur de retrouver sa demande en sécurité; 06 — Supprimer les identifiants administrateur de repli; 07 — Gérer l’abandon et la reprise du paiement; 08 — Rendre la numérotation des commandes atomique; 09 — Limiter les abus des formulaires publics; 10 — Sécuriser et clarifier le challenge 2FA; 11 — Aligner la suppression de compte avec son fonctionnement réel; 12 — Unifier le bouton Demander un devis et le suivi client; 13 — Annoncer les frais de livraison avant confirmation; 14 — Clarifier les statuts de commande et le vocabulaire; 15 — Supprimer ou relier la carte Alertes inerte; 16 — Ajouter un retour clair du client vers la vitrine; 17 — Organiser les réglages longs en sections; 18 — Relier les libellés, aides et erreurs aux champs; 19 — Nommer les actions à icône et le tri du catalogue; 20 — Gérer le focus des menus et panneaux; 21 — Corriger les contrastes sans changer l’identité; 22 — Remplir les médias et compacter les cartes produit; 23 — Structurer les informations des fiches produit; 24 — Présenter des réalisations et chiffres vérifiables; 25 — Améliorer la présentation de l’équipe; 26 — Rendre les offres visibles sur l’accueil; 27 — Alléger l’en-tête aux largeurs intermédiaires; 28 — Optimiser les logos et leur rendu sur fond sombre; 29 — Corriger l’image Open Graph manquante; 30 — Donner un titre explicite à chaque page privée; 31 — Ajouter les contrôles automatiques GitHub; 32 — Mesurer et améliorer la performance compilée

Avant de modifier, confirme l’état actuel du défaut et les checkpoints Git précédents. Les points d’entrée ci-dessous sont à vérifier dans le dépôt :
- `docs/audits/2026-10-20/README.md`
- `tests`
- `docs/DEPLOIEMENT.md`

Travail limité à cette fiche :
1. Vérifier les commits publiés des corrections précédentes et relire les limites des recettes 33, 34 et 35 ; elles ne sont pas implicitement considérées comme réalisées.
2. Rejouer les parcours publics, invités, client et admin avec données factices et transport de test.
3. Vérifier 360/390/768/1440 px, clavier, validation, états vides/erreurs et navigation Livewire.
4. Rédiger un nouvel état de validation daté, avec lien vers l’audit historique conservé ; pas de déploiement ou de correction indépendante supplémentaire dans cette recette.

Critères d’acceptation :
- Chaque défaut de l’audit possède une preuve de correction ou une réserve explicite.
- Tests locaux, build et contrôles GitHub disponibles sont rapportés avec leur état réel.
- La préparation de production reste distincte d’un déploiement effectif et d’une certification juridique/accessibilité.

Validation attendue :
- Suite Pest complète, Larastan, Pint selon baseline, build et recette navigateur.
- Consolider les preuves des services externes sans répéter une transaction réelle ni envoyer d’emails non autorisés.
- Applique également les contrôles transversaux du cadre commun pertinents à cette modification.
- Actualise la ligne 36 de docs/prompts/audit-2026-10-20/JOURNAL.md avec les résultats réels et les limites.

Termine par un compte rendu clair et les preuves utiles. Ne commence aucune autre correction. Ne fais ni commit ni push ; attends le prompt B de cette fiche.
````

## Prompt B — commit et push de cette seule correction

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Publie uniquement la correction 36 — Actualiser l’audit après toutes les corrections publiées, réalisée avec le prompt A de docs/prompts/audit-2026-10-20/36-recette-finale-modules.md.

Lis le cadre commun et son protocole « Publier B ». Vérifie que A est validé, que les dépendances et checkpoints précédents sont satisfaits et que le code n’a pas changé sans revalidation. Une recette externe non effectuée doit rester signalée ; ne transforme pas une limite en résultat positif.

Relis le diff et les fichiers non suivis. Sélectionne explicitement les seuls fichiers de cette correction, sa documentation, sa ligne de journal et les assets compilés nécessaires. Écarte notamment le changement de mode préexistant d’artisan, les secrets, bases et données privées. Ne mélange pas une autre correction ou l’archive initiale de l’audit dans ce commit.

Crée un commit descriptif, par défaut :
docs(qa): record final module validation

Pousse normalement sur origin, vers la branche de travail autorisée de même nom (main si le lot est toujours sur main). Vérifie le SHA publié sur https://github.com/TIKMOS/tikmos.com et rapporte les checks CI réellement accessibles.

Si le push échoue, résous uniquement le blocage autorisé sans force-push ni perte de travail ; sinon explique précisément pourquoi la correction reste non publiée. Si le commit existe déjà, vérifie-le plutôt que créer un doublon.

Termine avec le numéro 36, la branche, le SHA, le lien du commit GitHub, les validations et les limites. Arrête-toi : ne commence pas la correction suivante.
````
