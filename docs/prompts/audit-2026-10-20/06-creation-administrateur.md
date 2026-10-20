# 06 — Supprimer les identifiants administrateur de repli

Priorité : **P1** · Référence : **F06**.

Dépendances : aucune dépendance technique supplémentaire.

Lire le [cadre commun](CADRE-COMMUN.md). Utiliser A, vérifier le résultat, puis utiliser B. La publication vérifiée précède le passage à la suite.

## Prompt A — réaliser et valider

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Lis docs/prompts/audit-2026-10-20/CADRE-COMMUN.md, les instructions applicables et docs/audits/2026-10-20/README.md. Exécute uniquement la correction 06 — Supprimer les identifiants administrateur de repli.

Constat de référence : F06. Priorité : P1.
Dépendances techniques : Aucune dépendance technique supplémentaire ; respecter les checkpoints de l’ordre choisi.

Avant de modifier, confirme l’état actuel du défaut et les checkpoints Git précédents. Les points d’entrée ci-dessous sont à vérifier dans le dépôt :
- `database/seeders/AdminSeeder.php`
- `database/seeders/DatabaseSeeder.php`
- `.env.example`
- `docs/DEPLOIEMENT.md`
- `tests/Feature/Admin/UserManagementTest.php`

Travail limité à cette fiche :
1. Supprimer le repli password et la création implicite de plusieurs SuperAdmin.
2. Fournir un mécanisme explicite de création initiale exigeant les données nécessaires ; échouer avec un message utile si elles manquent.
3. Ne pas réinitialiser les mots de passe existants, créer de comptes réels ni révoquer de rôles sur la base courante.
4. Documenter le provisionnement et les vérifications à effectuer sur les comptes déployés sans exposer de secret.

Critères d’acceptation :
- Une installation ne crée aucun SuperAdmin avec un secret devinable par défaut.
- Le provisionnement explicite crée le compte attendu et reste idempotent.
- Aucun secret réel ni identifiant personnel de démonstration n’est ajouté au dépôt.

Validation attendue :
- Tester absence de configuration, création explicite et compte déjà présent sur une base isolée.
- Exécuter les tests de gestion utilisateur et la suite.
- Applique également les contrôles transversaux du cadre commun pertinents à cette modification.
- Actualise la ligne 06 de docs/prompts/audit-2026-10-20/JOURNAL.md avec les résultats réels et les limites.

Termine par un compte rendu clair et les preuves utiles. Ne commence aucune autre correction. Ne fais ni commit ni push ; attends le prompt B de cette fiche.
````

## Prompt B — commit et push de cette seule correction

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Publie uniquement la correction 06 — Supprimer les identifiants administrateur de repli, réalisée avec le prompt A de docs/prompts/audit-2026-10-20/06-creation-administrateur.md.

Lis le cadre commun et son protocole « Publier B ». Vérifie que A est validé, que les dépendances et checkpoints précédents sont satisfaits et que le code n’a pas changé sans revalidation. Une recette externe non effectuée doit rester signalée ; ne transforme pas une limite en résultat positif.

Relis le diff et les fichiers non suivis. Sélectionne explicitement les seuls fichiers de cette correction, sa documentation, sa ligne de journal et les assets compilés nécessaires. Écarte notamment le changement de mode préexistant d’artisan, les secrets, bases et données privées. Ne mélange pas une autre correction ou l’archive initiale de l’audit dans ce commit.

Crée un commit descriptif, par défaut :
fix(auth): require explicit administrator provisioning

Pousse normalement sur origin, vers la branche de travail autorisée de même nom (main si le lot est toujours sur main). Vérifie le SHA publié sur https://github.com/TIKMOS/tikmos.com et rapporte les checks CI réellement accessibles.

Si le push échoue, résous uniquement le blocage autorisé sans force-push ni perte de travail ; sinon explique précisément pourquoi la correction reste non publiée. Si le commit existe déjà, vérifie-le plutôt que créer un doublon.

Termine avec le numéro 06, la branche, le SHA, le lien du commit GitHub, les validations et les limites. Arrête-toi : ne commence pas la correction suivante.
````
