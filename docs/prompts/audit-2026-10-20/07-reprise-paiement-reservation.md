# 07 — Gérer l’abandon et la reprise du paiement

Priorité : **P1** · Référence : **F07**.

Dépendances : [01](01-transaction-externe-unique.md), [02](02-montant-paiement-obligatoire.md), [03](03-stock-global-variantes.md).

Lire le [cadre commun](CADRE-COMMUN.md). Utiliser A, vérifier le résultat, puis utiliser B. La publication vérifiée précède le passage à la suite.

## Prompt A — réaliser et valider

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Lis docs/prompts/audit-2026-10-20/CADRE-COMMUN.md, les instructions applicables et docs/audits/2026-10-20/README.md. Exécute uniquement la correction 07 — Gérer l’abandon et la reprise du paiement.

Constat de référence : F07. Priorité : P1.
Dépendances techniques : 01 — Empêcher une transaction externe de payer plusieurs commandes; 02 — Refuser un paiement en ligne sans montant confirmé; 03 — Empêcher le stock négatif entre variantes

Avant de modifier, confirme l’état actuel du défaut et les checkpoints Git précédents. Les points d’entrée ci-dessous sont à vérifier dans le dépôt :
- `app/Services/OrderService.php`
- `app/Services/PaymentService.php`
- `app/Models/Order.php`
- `resources/views/livewire/pages/public/checkout.blade.php`
- `resources/views/livewire/pages/app/orders-show.blade.php`
- `routes/console.php`

Travail limité à cette fiche :
1. Définir un cycle simple de réservation, succès, échec et expiration du stock pour les commandes en ligne.
2. Permettre de reprendre le paiement d’une commande existante depuis son détail ; ne pas recréer une commande à chaque essai.
3. Gérer fermeture du widget, échec, retour tardif du webhook et rafraîchissement de page ; restituer le stock une seule fois selon la règle documentée.
4. Ne proposer le paiement en ligne que si sa configuration est exploitable ; préserver le paiement à la livraison.

Critères d’acceptation :
- Abandon ou échec : prochaine étape visible et stock traité selon le cycle défini.
- Réessai : même commande, pas de double réservation ni double débit applicatif.
- Expiration et webhook tardif sont cohérents ; aucun paiement réel n’est déclenché pendant les tests.

Validation attendue :
- Tester le cycle avec passerelle simulée, horloge figée et rejeux.
- Exécuter les tests paiement/commande/stock et la suite ; l’étape 34 traite la recette fournisseur sandbox.
- Applique également les contrôles transversaux du cadre commun pertinents à cette modification.
- Actualise la ligne 07 de docs/prompts/audit-2026-10-20/JOURNAL.md avec les résultats réels et les limites.

Termine par un compte rendu clair et les preuves utiles. Ne commence aucune autre correction. Ne fais ni commit ni push ; attends le prompt B de cette fiche.
````

## Prompt B — commit et push de cette seule correction

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Publie uniquement la correction 07 — Gérer l’abandon et la reprise du paiement, réalisée avec le prompt A de docs/prompts/audit-2026-10-20/07-reprise-paiement-reservation.md.

Lis le cadre commun et son protocole « Publier B ». Vérifie que A est validé, que les dépendances et checkpoints précédents sont satisfaits et que le code n’a pas changé sans revalidation. Une recette externe non effectuée doit rester signalée ; ne transforme pas une limite en résultat positif.

Relis le diff et les fichiers non suivis. Sélectionne explicitement les seuls fichiers de cette correction, sa documentation, sa ligne de journal et les assets compilés nécessaires. Écarte notamment le changement de mode préexistant d’artisan, les secrets, bases et données privées. Ne mélange pas une autre correction ou l’archive initiale de l’audit dans ce commit.

Crée un commit descriptif, par défaut :
fix(checkout): support payment recovery and stock expiry

Pousse normalement sur origin, vers la branche de travail autorisée de même nom (main si le lot est toujours sur main). Vérifie le SHA publié sur https://github.com/TIKMOS/tikmos.com et rapporte les checks CI réellement accessibles.

Si le push échoue, résous uniquement le blocage autorisé sans force-push ni perte de travail ; sinon explique précisément pourquoi la correction reste non publiée. Si le commit existe déjà, vérifie-le plutôt que créer un doublon.

Termine avec le numéro 07, la branche, le SHA, le lien du commit GitHub, les validations et les limites. Arrête-toi : ne commence pas la correction suivante.
````
