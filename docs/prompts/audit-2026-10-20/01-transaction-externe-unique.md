# 01 — Empêcher une transaction externe de payer plusieurs commandes

Priorité : **P1** · Référence : **F01**.

Dépendances : aucune dépendance technique supplémentaire.

Lire le [cadre commun](CADRE-COMMUN.md). Utiliser A, vérifier le résultat, puis utiliser B. La publication vérifiée précède le passage à la suite.

## Prompt A — réaliser et valider

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Lis docs/prompts/audit-2026-10-20/CADRE-COMMUN.md, les instructions applicables et docs/audits/2026-10-20/README.md. Exécute uniquement la correction 01 — Empêcher une transaction externe de payer plusieurs commandes.

Constat de référence : F01. Priorité : P1.
Dépendances techniques : Aucune dépendance technique supplémentaire ; respecter les checkpoints de l’ordre choisi.

Avant de modifier, confirme l’état actuel du défaut et les checkpoints Git précédents. Les points d’entrée ci-dessous sont à vérifier dans le dépôt :
- `app/Services/PaymentService.php`
- `app/Models/Payment.php`
- `database/migrations`
- `tests/Feature/PaymentTest.php`

Travail limité à cette fiche :
1. Reproduire le cas F01 avec deux commandes et une seule référence externe, appels réseau simulés.
2. Garantir l’unicité du couple fournisseur/référence externe et la cohérence avec le paiement local, dans une opération atomique.
3. Prévoir une nouvelle migration pour les installations existantes. Détecter les doublons avant ajout de contrainte ; ne supprimer ni ne réaffecter silencieusement des paiements historiques.
4. Préserver l’idempotence du retour widget et du webhook ; vérifier le contrat KKiaPay dans ses sources officielles si des métadonnées sont nécessaires.

Critères d’acceptation :
- Une référence ne crédite qu’une commande ; le second rattachement est refusé avec un événement explicite.
- Les appels répétés sur la même commande ne doublent ni transition ni notification.
- L’ajout de contrainte est compatible SQLite/MySQL ; le test concurrent MySQL reste explicitement non validé si ce moteur est indisponible.

Validation attendue :
- Adapter la reproduction 1 en vrai test qui attend le refus du second paiement.
- Exécuter PaymentTest, les tests de commandes impactés et la suite Pest complète.
- Applique également les contrôles transversaux du cadre commun pertinents à cette modification.
- Actualise la ligne 01 de docs/prompts/audit-2026-10-20/JOURNAL.md avec les résultats réels et les limites.

Termine par un compte rendu clair et les preuves utiles. Ne commence aucune autre correction. Ne fais ni commit ni push ; attends le prompt B de cette fiche.
````

## Prompt B — commit et push de cette seule correction

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Publie uniquement la correction 01 — Empêcher une transaction externe de payer plusieurs commandes, réalisée avec le prompt A de docs/prompts/audit-2026-10-20/01-transaction-externe-unique.md.

Lis le cadre commun et son protocole « Publier B ». Vérifie que A est validé, que les dépendances et checkpoints précédents sont satisfaits et que le code n’a pas changé sans revalidation. Une recette externe non effectuée doit rester signalée ; ne transforme pas une limite en résultat positif.

Relis le diff et les fichiers non suivis. Sélectionne explicitement les seuls fichiers de cette correction, sa documentation, sa ligne de journal et les assets compilés nécessaires. Écarte notamment le changement de mode préexistant d’artisan, les secrets, bases et données privées. Ne mélange pas une autre correction ou l’archive initiale de l’audit dans ce commit.

Crée un commit descriptif, par défaut :
fix(payments): enforce unique external transaction

Pousse normalement sur origin, vers la branche de travail autorisée de même nom (main si le lot est toujours sur main). Vérifie le SHA publié sur https://github.com/TIKMOS/tikmos.com et rapporte les checks CI réellement accessibles.

Si le push échoue, résous uniquement le blocage autorisé sans force-push ni perte de travail ; sinon explique précisément pourquoi la correction reste non publiée. Si le commit existe déjà, vérifie-le plutôt que créer un doublon.

Termine avec le numéro 01, la branche, le SHA, le lien du commit GitHub, les validations et les limites. Arrête-toi : ne commence pas la correction suivante.
````
