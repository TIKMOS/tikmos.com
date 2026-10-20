# 02 — Refuser un paiement en ligne sans montant confirmé

Priorité : **P1** · Référence : **F02**.

Dépendances : [01](01-transaction-externe-unique.md).

Lire le [cadre commun](CADRE-COMMUN.md). Utiliser A, vérifier le résultat, puis utiliser B. La publication vérifiée précède le passage à la suite.

## Prompt A — réaliser et valider

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Lis docs/prompts/audit-2026-10-20/CADRE-COMMUN.md, les instructions applicables et docs/audits/2026-10-20/README.md. Exécute uniquement la correction 02 — Refuser un paiement en ligne sans montant confirmé.

Constat de référence : F02. Priorité : P1.
Dépendances techniques : 01 — Empêcher une transaction externe de payer plusieurs commandes

Avant de modifier, confirme l’état actuel du défaut et les checkpoints Git précédents. Les points d’entrée ci-dessous sont à vérifier dans le dépôt :
- `app/Models/Payment.php`
- `app/Payments/Gateways/KkiapayGateway.php`
- `app/Payments/Data/ChargeResult.php`
- `tests/Feature/PaymentTest.php`

Travail limité à cette fiche :
1. Transformer la reproduction F02 en test de refus d’une réponse SUCCESS sans montant.
2. Exiger pour le paiement en ligne un montant confirmé valide et égal au montant dû, sans traiter une valeur absente comme une réussite.
3. Vérifier la devise lorsqu’elle est fournie et son format dans le contrat officiel de la passerelle ; documenter ce que le fournisseur ne permet pas de vérifier.
4. Distinguer réponse incomplète, montant incohérent et paiement réellement échoué ; journaliser sans enregistrer de secrets.

Critères d’acceptation :
- Montant absent, non numérique ou incohérent : commande non payée.
- Un succès complet et cohérent continue à fonctionner ; le paiement à la livraison n’est pas affecté.
- Aucun montant client ne devient la source de vérité.

Validation attendue :
- Tester valeurs absentes, zéro selon le montant dû, valeur incohérente et réussite nominale.
- Exécuter PaymentTest puis la suite Pest.
- Applique également les contrôles transversaux du cadre commun pertinents à cette modification.
- Actualise la ligne 02 de docs/prompts/audit-2026-10-20/JOURNAL.md avec les résultats réels et les limites.

Termine par un compte rendu clair et les preuves utiles. Ne commence aucune autre correction. Ne fais ni commit ni push ; attends le prompt B de cette fiche.
````

## Prompt B — commit et push de cette seule correction

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Publie uniquement la correction 02 — Refuser un paiement en ligne sans montant confirmé, réalisée avec le prompt A de docs/prompts/audit-2026-10-20/02-montant-paiement-obligatoire.md.

Lis le cadre commun et son protocole « Publier B ». Vérifie que A est validé, que les dépendances et checkpoints précédents sont satisfaits et que le code n’a pas changé sans revalidation. Une recette externe non effectuée doit rester signalée ; ne transforme pas une limite en résultat positif.

Relis le diff et les fichiers non suivis. Sélectionne explicitement les seuls fichiers de cette correction, sa documentation, sa ligne de journal et les assets compilés nécessaires. Écarte notamment le changement de mode préexistant d’artisan, les secrets, bases et données privées. Ne mélange pas une autre correction ou l’archive initiale de l’audit dans ce commit.

Crée un commit descriptif, par défaut :
fix(payments): require a verified online amount

Pousse normalement sur origin, vers la branche de travail autorisée de même nom (main si le lot est toujours sur main). Vérifie le SHA publié sur https://github.com/TIKMOS/tikmos.com et rapporte les checks CI réellement accessibles.

Si le push échoue, résous uniquement le blocage autorisé sans force-push ni perte de travail ; sinon explique précisément pourquoi la correction reste non publiée. Si le commit existe déjà, vérifie-le plutôt que créer un doublon.

Termine avec le numéro 02, la branche, le SHA, le lien du commit GitHub, les validations et les limites. Arrête-toi : ne commence pas la correction suivante.
````
