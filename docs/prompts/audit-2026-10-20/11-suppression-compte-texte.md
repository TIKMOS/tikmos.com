# 11 — Aligner la suppression de compte avec son fonctionnement réel

Priorité : **P2** · Référence : **Autres risques : SoftDeletes**.

Dépendances : aucune dépendance technique supplémentaire.

Lire le [cadre commun](CADRE-COMMUN.md). Utiliser A, vérifier le résultat, puis utiliser B. La publication vérifiée précède le passage à la suite.

## Prompt A — réaliser et valider

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Lis docs/prompts/audit-2026-10-20/CADRE-COMMUN.md, les instructions applicables et docs/audits/2026-10-20/README.md. Exécute uniquement la correction 11 — Aligner la suppression de compte avec son fonctionnement réel.

Constat de référence : Autres risques : SoftDeletes. Priorité : P2.
Dépendances techniques : Aucune dépendance technique supplémentaire ; respecter les checkpoints de l’ordre choisi.

Avant de modifier, confirme l’état actuel du défaut et les checkpoints Git précédents. Les points d’entrée ci-dessous sont à vérifier dans le dépôt :
- `app/Models/User.php`
- `resources/views/livewire/pages/app/compte.blade.php`
- `resources/views/public/privacy.blade.php`
- `tests/Feature/LegalRgpdTest.php`

Travail limité à cette fiche :
1. Examiner les données réellement conservées par SoftDeletes et les accès après désactivation.
2. Appliquer par défaut une correction réversible : expliquer précisément la désactivation et la conservation existantes, sans promettre une suppression physique immédiate.
3. Documenter le traitement distinct d’une demande d’effacement/anonymisation et les décisions métier encore nécessaires.
4. Ne supprimer physiquement aucune donnée réelle et ne définir aucune durée légale inventée.

Critères d’acceptation :
- Le texte correspond à l’action réellement exécutée.
- Les accès d’un compte désactivé sont bloqués selon les règles existantes.
- L’export et les comptes actifs restent fonctionnels ; les décisions juridiques non validées sont identifiées.

Validation attendue :
- Vérifier les cas existants sur comptes factices ; tester tout changement de comportement d’accès.
- Exécuter LegalRgpdTest et les tests auth impactés.
- Applique également les contrôles transversaux du cadre commun pertinents à cette modification.
- Actualise la ligne 11 de docs/prompts/audit-2026-10-20/JOURNAL.md avec les résultats réels et les limites.

Termine par un compte rendu clair et les preuves utiles. Ne commence aucune autre correction. Ne fais ni commit ni push ; attends le prompt B de cette fiche.
````

## Prompt B — commit et push de cette seule correction

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Publie uniquement la correction 11 — Aligner la suppression de compte avec son fonctionnement réel, réalisée avec le prompt A de docs/prompts/audit-2026-10-20/11-suppression-compte-texte.md.

Lis le cadre commun et son protocole « Publier B ». Vérifie que A est validé, que les dépendances et checkpoints précédents sont satisfaits et que le code n’a pas changé sans revalidation. Une recette externe non effectuée doit rester signalée ; ne transforme pas une limite en résultat positif.

Relis le diff et les fichiers non suivis. Sélectionne explicitement les seuls fichiers de cette correction, sa documentation, sa ligne de journal et les assets compilés nécessaires. Écarte notamment le changement de mode préexistant d’artisan, les secrets, bases et données privées. Ne mélange pas une autre correction ou l’archive initiale de l’audit dans ce commit.

Crée un commit descriptif, par défaut :
fix(account): clarify deactivation and data handling

Pousse normalement sur origin, vers la branche de travail autorisée de même nom (main si le lot est toujours sur main). Vérifie le SHA publié sur https://github.com/TIKMOS/tikmos.com et rapporte les checks CI réellement accessibles.

Si le push échoue, résous uniquement le blocage autorisé sans force-push ni perte de travail ; sinon explique précisément pourquoi la correction reste non publiée. Si le commit existe déjà, vérifie-le plutôt que créer un doublon.

Termine avec le numéro 11, la branche, le SHA, le lien du commit GitHub, les validations et les limites. Arrête-toi : ne commence pas la correction suivante.
````
