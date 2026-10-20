# 16 — Ajouter un retour clair du client vers la vitrine

Priorité : **P2** · Référence : **UX : navigation espace client**.

Dépendances : aucune dépendance technique supplémentaire.

Lire le [cadre commun](CADRE-COMMUN.md). Utiliser A, vérifier le résultat, puis utiliser B. La publication vérifiée précède le passage à la suite.

## Prompt A — réaliser et valider

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Lis docs/prompts/audit-2026-10-20/CADRE-COMMUN.md, les instructions applicables et docs/audits/2026-10-20/README.md. Exécute uniquement la correction 16 — Ajouter un retour clair du client vers la vitrine.

Constat de référence : UX : navigation espace client. Priorité : P2.
Dépendances techniques : Aucune dépendance technique supplémentaire ; respecter les checkpoints de l’ordre choisi.

Avant de modifier, confirme l’état actuel du défaut et les checkpoints Git précédents. Les points d’entrée ci-dessous sont à vérifier dans le dépôt :
- `resources/views/components/layouts/app.blade.php`
- `resources/views/components/tk/nav-bottom.blade.php`

Travail limité à cette fiche :
1. Ajouter un accès explicite Voir le site depuis l’espace client sur ordinateur et mobile.
2. Conserver le logo ou Accueil comme accès au tableau de bord si c’est la convention retenue ; éviter deux destinations ambiguës sous le même nom.
3. Fournir un nom accessible et un état de focus lisible, sans encombrer la barre mobile.
4. Préserver la session et les liens actuels vers devis, commandes et compte.

Critères d’acceptation :
- Le client retrouve la vitrine en une action identifiable.
- Le retour ne déconnecte pas l’utilisateur et ne vide pas son panier.
- Le lien est utilisable au clavier et aux largeurs 360 et 1440 px.

Validation attendue :
- Recette navigateur ciblée ; tests existants de navigation si concernés.
- Applique également les contrôles transversaux du cadre commun pertinents à cette modification.
- Actualise la ligne 16 de docs/prompts/audit-2026-10-20/JOURNAL.md avec les résultats réels et les limites.

Termine par un compte rendu clair et les preuves utiles. Ne commence aucune autre correction. Ne fais ni commit ni push ; attends le prompt B de cette fiche.
````

## Prompt B — commit et push de cette seule correction

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Publie uniquement la correction 16 — Ajouter un retour clair du client vers la vitrine, réalisée avec le prompt A de docs/prompts/audit-2026-10-20/16-retour-vers-vitrine.md.

Lis le cadre commun et son protocole « Publier B ». Vérifie que A est validé, que les dépendances et checkpoints précédents sont satisfaits et que le code n’a pas changé sans revalidation. Une recette externe non effectuée doit rester signalée ; ne transforme pas une limite en résultat positif.

Relis le diff et les fichiers non suivis. Sélectionne explicitement les seuls fichiers de cette correction, sa documentation, sa ligne de journal et les assets compilés nécessaires. Écarte notamment le changement de mode préexistant d’artisan, les secrets, bases et données privées. Ne mélange pas une autre correction ou l’archive initiale de l’audit dans ce commit.

Crée un commit descriptif, par défaut :
fix(client): expose a clear return to the website

Pousse normalement sur origin, vers la branche de travail autorisée de même nom (main si le lot est toujours sur main). Vérifie le SHA publié sur https://github.com/TIKMOS/tikmos.com et rapporte les checks CI réellement accessibles.

Si le push échoue, résous uniquement le blocage autorisé sans force-push ni perte de travail ; sinon explique précisément pourquoi la correction reste non publiée. Si le commit existe déjà, vérifie-le plutôt que créer un doublon.

Termine avec le numéro 16, la branche, le SHA, le lien du commit GitHub, les validations et les limites. Arrête-toi : ne commence pas la correction suivante.
````
