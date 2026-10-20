# 15 — Supprimer ou relier la carte Alertes inerte

Priorité : **P2** · Référence : **UX : compteur sans parcours**.

Dépendances : aucune dépendance technique supplémentaire.

Lire le [cadre commun](CADRE-COMMUN.md). Utiliser A, vérifier le résultat, puis utiliser B. La publication vérifiée précède le passage à la suite.

## Prompt A — réaliser et valider

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Lis docs/prompts/audit-2026-10-20/CADRE-COMMUN.md, les instructions applicables et docs/audits/2026-10-20/README.md. Exécute uniquement la correction 15 — Supprimer ou relier la carte Alertes inerte.

Constat de référence : UX : compteur sans parcours. Priorité : P2.
Dépendances techniques : Aucune dépendance technique supplémentaire ; respecter les checkpoints de l’ordre choisi.

Avant de modifier, confirme l’état actuel du défaut et les checkpoints Git précédents. Les points d’entrée ci-dessous sont à vérifier dans le dépôt :
- `resources/views/livewire/pages/app/dashboard.blade.php`
- `app/Notifications`
- `routes/app.php`
- `tests/Feature/ClientSpaceSmokeTest.php`

Travail limité à cette fiche :
1. Vérifier les canaux de notification réellement actifs et l’existence d’un parcours de lecture.
2. Si aucun centre exploitable n’existe, retirer la carte Alertes inerte et rééquilibrer le tableau de bord ; ne pas construire un nouveau centre complet dans cette correction.
3. Si un parcours réel existe, relier la carte à ce parcours et rendre le compteur compréhensible.
4. Conserver les emails existants et les vrais indicateurs de demandes, devis et commandes.

Critères d’acceptation :
- Aucun indicateur ne promet une fonctionnalité inaccessible.
- Le tableau de bord reste équilibré en état vide et avec des données.
- Aucune notification fictive ni métrique inventée n’est introduite.

Validation attendue :
- Vérifier les deux états au navigateur et les tests existants du dashboard client.
- Applique également les contrôles transversaux du cadre commun pertinents à cette modification.
- Actualise la ligne 15 de docs/prompts/audit-2026-10-20/JOURNAL.md avec les résultats réels et les limites.

Termine par un compte rendu clair et les preuves utiles. Ne commence aucune autre correction. Ne fais ni commit ni push ; attends le prompt B de cette fiche.
````

## Prompt B — commit et push de cette seule correction

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Publie uniquement la correction 15 — Supprimer ou relier la carte Alertes inerte, réalisée avec le prompt A de docs/prompts/audit-2026-10-20/15-carte-alertes-client.md.

Lis le cadre commun et son protocole « Publier B ». Vérifie que A est validé, que les dépendances et checkpoints précédents sont satisfaits et que le code n’a pas changé sans revalidation. Une recette externe non effectuée doit rester signalée ; ne transforme pas une limite en résultat positif.

Relis le diff et les fichiers non suivis. Sélectionne explicitement les seuls fichiers de cette correction, sa documentation, sa ligne de journal et les assets compilés nécessaires. Écarte notamment le changement de mode préexistant d’artisan, les secrets, bases et données privées. Ne mélange pas une autre correction ou l’archive initiale de l’audit dans ce commit.

Crée un commit descriptif, par défaut :
fix(client): remove inert notification indicator

Pousse normalement sur origin, vers la branche de travail autorisée de même nom (main si le lot est toujours sur main). Vérifie le SHA publié sur https://github.com/TIKMOS/tikmos.com et rapporte les checks CI réellement accessibles.

Si le push échoue, résous uniquement le blocage autorisé sans force-push ni perte de travail ; sinon explique précisément pourquoi la correction reste non publiée. Si le commit existe déjà, vérifie-le plutôt que créer un doublon.

Termine avec le numéro 15, la branche, le SHA, le lien du commit GitHub, les validations et les limites. Arrête-toi : ne commence pas la correction suivante.
````
