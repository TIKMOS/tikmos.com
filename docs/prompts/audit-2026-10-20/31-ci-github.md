# 31 — Ajouter les contrôles automatiques GitHub

Priorité : **P2** · Référence : **Exploitation : CI GitLab seulement**.

Dépendances : [01](01-transaction-externe-unique.md), [02](02-montant-paiement-obligatoire.md), [03](03-stock-global-variantes.md), [04](04-expiration-devis.md), [05](05-rattachement-demande-invite.md), [06](06-creation-administrateur.md), [07](07-reprise-paiement-reservation.md), [08](08-numerotation-commandes.md).

Lire le [cadre commun](CADRE-COMMUN.md). Utiliser A, vérifier le résultat, puis utiliser B. La publication vérifiée précède le passage à la suite.

## Prompt A — réaliser et valider

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Lis docs/prompts/audit-2026-10-20/CADRE-COMMUN.md, les instructions applicables et docs/audits/2026-10-20/README.md. Exécute uniquement la correction 31 — Ajouter les contrôles automatiques GitHub.

Constat de référence : Exploitation : CI GitLab seulement. Priorité : P2.
Dépendances techniques : 01 — Empêcher une transaction externe de payer plusieurs commandes; 02 — Refuser un paiement en ligne sans montant confirmé; 03 — Empêcher le stock négatif entre variantes; 04 — Appliquer la validité des devis côté serveur; 05 — Permettre au visiteur de retrouver sa demande en sécurité; 06 — Supprimer les identifiants administrateur de repli; 07 — Gérer l’abandon et la reprise du paiement; 08 — Rendre la numérotation des commandes atomique

Avant de modifier, confirme l’état actuel du défaut et les checkpoints Git précédents. Les points d’entrée ci-dessous sont à vérifier dans le dépôt :
- `.gitlab-ci.yml`
- `composer.json`
- `package.json`
- `phpunit.xml`
- `phpstan.neon`

Travail limité à cette fiche :
1. Créer des workflows GitHub Actions adaptés au dépôt pour Pest, Larastan, Pint selon la baseline et build frontend.
2. Utiliser les lockfiles, PHP compatible et les extensions requises ; tests sur SQLite isolée et aucune connexion production.
3. Préserver la CI GitLab et ne pas envoyer les cinq reproductions historiques avec leurs attentes défaillantes comme preuve de correction.
4. Limiter les permissions du workflow, éviter les secrets applicatifs et documenter les contrôles de concurrence MySQL éventuellement séparés.

Critères d’acceptation :
- Les contrôles se déclenchent sur push et pull request sans secret de production.
- Les commandes correspondent à celles validées localement et aux exigences réelles du projet.
- Après publication, les résultats GitHub sont consultés si accessibles ; un état inconnu n’est pas déclaré vert.

Validation attendue :
- Exécuter les commandes du workflow et vérifier la structure YAML.
- À la publication B, consulter les checks du commit poussé et rapporter leur état réel.
- Applique également les contrôles transversaux du cadre commun pertinents à cette modification.
- Actualise la ligne 31 de docs/prompts/audit-2026-10-20/JOURNAL.md avec les résultats réels et les limites.

Termine par un compte rendu clair et les preuves utiles. Ne commence aucune autre correction. Ne fais ni commit ni push ; attends le prompt B de cette fiche.
````

## Prompt B — commit et push de cette seule correction

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Publie uniquement la correction 31 — Ajouter les contrôles automatiques GitHub, réalisée avec le prompt A de docs/prompts/audit-2026-10-20/31-ci-github.md.

Lis le cadre commun et son protocole « Publier B ». Vérifie que A est validé, que les dépendances et checkpoints précédents sont satisfaits et que le code n’a pas changé sans revalidation. Une recette externe non effectuée doit rester signalée ; ne transforme pas une limite en résultat positif.

Relis le diff et les fichiers non suivis. Sélectionne explicitement les seuls fichiers de cette correction, sa documentation, sa ligne de journal et les assets compilés nécessaires. Écarte notamment le changement de mode préexistant d’artisan, les secrets, bases et données privées. Ne mélange pas une autre correction ou l’archive initiale de l’audit dans ce commit.

Crée un commit descriptif, par défaut :
ci: validate Laravel and frontend on GitHub

Pousse normalement sur origin, vers la branche de travail autorisée de même nom (main si le lot est toujours sur main). Vérifie le SHA publié sur https://github.com/TIKMOS/tikmos.com et rapporte les checks CI réellement accessibles.

Si le push échoue, résous uniquement le blocage autorisé sans force-push ni perte de travail ; sinon explique précisément pourquoi la correction reste non publiée. Si le commit existe déjà, vérifie-le plutôt que créer un doublon.

Termine avec le numéro 31, la branche, le SHA, le lien du commit GitHub, les validations et les limites. Arrête-toi : ne commence pas la correction suivante.
````
