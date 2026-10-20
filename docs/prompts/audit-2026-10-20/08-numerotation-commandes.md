# 08 — Rendre la numérotation des commandes atomique

Priorité : **P1** · Référence : **Autres risques : numérotation**.

Dépendances : [03](03-stock-global-variantes.md).

Lire le [cadre commun](CADRE-COMMUN.md). Utiliser A, vérifier le résultat, puis utiliser B. La publication vérifiée précède le passage à la suite.

## Prompt A — réaliser et valider

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Lis docs/prompts/audit-2026-10-20/CADRE-COMMUN.md, les instructions applicables et docs/audits/2026-10-20/README.md. Exécute uniquement la correction 08 — Rendre la numérotation des commandes atomique.

Constat de référence : Autres risques : numérotation. Priorité : P1.
Dépendances techniques : 03 — Empêcher le stock négatif entre variantes

Avant de modifier, confirme l’état actuel du défaut et les checkpoints Git précédents. Les points d’entrée ci-dessous sont à vérifier dans le dépôt :
- `app/Services/OrderService.php`
- `app/Support/ReferenceGenerator.php`
- `database/migrations`
- `tests/Feature/OrderFlowTest.php`

Travail limité à cette fiche :
1. Remplacer count()+1 par une stratégie atomique compatible avec le format lisible actuel.
2. Conserver tous les numéros déjà attribués et la contrainte d’unicité.
3. Gérer les créations simultanées et le changement d’année sans dépendre du nombre de lignes restantes.
4. Utiliser la solution la plus simple compatible SQLite/MySQL et documenter le choix.

Critères d’acceptation :
- Deux créations concurrentes ne produisent jamais le même numéro.
- Les commandes existantes et leurs URLs continuent à fonctionner.
- Le mécanisme ne nécessite aucune suppression ou renumérotation historique.

Validation attendue :
- Tester unicité, changement d’année et existence de numéros antérieurs.
- Exécuter OrderFlowTest, les tests de paiement impactés et la suite ; signaler les limites des tests MySQL.
- Applique également les contrôles transversaux du cadre commun pertinents à cette modification.
- Actualise la ligne 08 de docs/prompts/audit-2026-10-20/JOURNAL.md avec les résultats réels et les limites.

Termine par un compte rendu clair et les preuves utiles. Ne commence aucune autre correction. Ne fais ni commit ni push ; attends le prompt B de cette fiche.
````

## Prompt B — commit et push de cette seule correction

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Publie uniquement la correction 08 — Rendre la numérotation des commandes atomique, réalisée avec le prompt A de docs/prompts/audit-2026-10-20/08-numerotation-commandes.md.

Lis le cadre commun et son protocole « Publier B ». Vérifie que A est validé, que les dépendances et checkpoints précédents sont satisfaits et que le code n’a pas changé sans revalidation. Une recette externe non effectuée doit rester signalée ; ne transforme pas une limite en résultat positif.

Relis le diff et les fichiers non suivis. Sélectionne explicitement les seuls fichiers de cette correction, sa documentation, sa ligne de journal et les assets compilés nécessaires. Écarte notamment le changement de mode préexistant d’artisan, les secrets, bases et données privées. Ne mélange pas une autre correction ou l’archive initiale de l’audit dans ce commit.

Crée un commit descriptif, par défaut :
fix(orders): allocate order numbers atomically

Pousse normalement sur origin, vers la branche de travail autorisée de même nom (main si le lot est toujours sur main). Vérifie le SHA publié sur https://github.com/TIKMOS/tikmos.com et rapporte les checks CI réellement accessibles.

Si le push échoue, résous uniquement le blocage autorisé sans force-push ni perte de travail ; sinon explique précisément pourquoi la correction reste non publiée. Si le commit existe déjà, vérifie-le plutôt que créer un doublon.

Termine avec le numéro 08, la branche, le SHA, le lien du commit GitHub, les validations et les limites. Arrête-toi : ne commence pas la correction suivante.
````
