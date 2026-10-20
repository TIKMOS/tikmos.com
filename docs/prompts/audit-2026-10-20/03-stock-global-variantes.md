# 03 — Empêcher le stock négatif entre variantes

Priorité : **P1** · Référence : **F03**.

Dépendances : aucune dépendance technique supplémentaire.

Lire le [cadre commun](CADRE-COMMUN.md). Utiliser A, vérifier le résultat, puis utiliser B. La publication vérifiée précède le passage à la suite.

## Prompt A — réaliser et valider

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Lis docs/prompts/audit-2026-10-20/CADRE-COMMUN.md, les instructions applicables et docs/audits/2026-10-20/README.md. Exécute uniquement la correction 03 — Empêcher le stock négatif entre variantes.

Constat de référence : F03. Priorité : P1.
Dépendances techniques : Aucune dépendance technique supplémentaire ; respecter les checkpoints de l’ordre choisi.

Avant de modifier, confirme l’état actuel du défaut et les checkpoints Git précédents. Les points d’entrée ci-dessous sont à vérifier dans le dépôt :
- `app/Services/CartService.php`
- `app/Services/OrderService.php`
- `app/Models/Product.php`
- `tests/Feature/CartSizeTest.php`
- `tests/Feature/OrderFlowTest.php`

Travail limité à cette fiche :
1. Reproduire le stock initial 2 avec deux lignes de variantes demandant chacune 2 unités.
2. Conserver le stock global actuel : agréger les quantités par produit et revérifier atomiquement au passage de commande.
3. Utiliser un verrouillage ou une mise à jour conditionnelle adaptés à MySQL et compatibles avec les tests SQLite ; éviter les commandes partiellement créées.
4. Expliquer au client la quantité réellement disponible et préserver un panier corrigeable en cas de refus.

Critères d’acceptation :
- La somme des variantes ne dépasse jamais le stock global.
- Le cas 2 → commande de 4 est refusé proprement et ne produit pas de stock négatif.
- Les stocks non gérés, variantes valides et commandes nominales restent utilisables.

Validation attendue :
- Ajouter un test de non-régression issu de la reproduction 3 et des cas de modification de stock avant confirmation.
- Exécuter CartSizeTest, OrderFlowTest et la suite ; documenter la concurrence MySQL non exécutée si indisponible.
- Applique également les contrôles transversaux du cadre commun pertinents à cette modification.
- Actualise la ligne 03 de docs/prompts/audit-2026-10-20/JOURNAL.md avec les résultats réels et les limites.

Termine par un compte rendu clair et les preuves utiles. Ne commence aucune autre correction. Ne fais ni commit ni push ; attends le prompt B de cette fiche.
````

## Prompt B — commit et push de cette seule correction

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Publie uniquement la correction 03 — Empêcher le stock négatif entre variantes, réalisée avec le prompt A de docs/prompts/audit-2026-10-20/03-stock-global-variantes.md.

Lis le cadre commun et son protocole « Publier B ». Vérifie que A est validé, que les dépendances et checkpoints précédents sont satisfaits et que le code n’a pas changé sans revalidation. Une recette externe non effectuée doit rester signalée ; ne transforme pas une limite en résultat positif.

Relis le diff et les fichiers non suivis. Sélectionne explicitement les seuls fichiers de cette correction, sa documentation, sa ligne de journal et les assets compilés nécessaires. Écarte notamment le changement de mode préexistant d’artisan, les secrets, bases et données privées. Ne mélange pas une autre correction ou l’archive initiale de l’audit dans ce commit.

Crée un commit descriptif, par défaut :
fix(stock): validate aggregate variant quantities

Pousse normalement sur origin, vers la branche de travail autorisée de même nom (main si le lot est toujours sur main). Vérifie le SHA publié sur https://github.com/TIKMOS/tikmos.com et rapporte les checks CI réellement accessibles.

Si le push échoue, résous uniquement le blocage autorisé sans force-push ni perte de travail ; sinon explique précisément pourquoi la correction reste non publiée. Si le commit existe déjà, vérifie-le plutôt que créer un doublon.

Termine avec le numéro 03, la branche, le SHA, le lien du commit GitHub, les validations et les limites. Arrête-toi : ne commence pas la correction suivante.
````
