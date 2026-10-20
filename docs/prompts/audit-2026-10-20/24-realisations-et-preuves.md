# 24 — Présenter des réalisations et chiffres vérifiables

Priorité : **P2** · Référence : **Visuel : cas clients et compteur**.

Dépendances : aucune dépendance technique supplémentaire.

Lire le [cadre commun](CADRE-COMMUN.md). Utiliser A, vérifier le résultat, puis utiliser B. La publication vérifiée précède le passage à la suite.

## Prompt A — réaliser et valider

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Lis docs/prompts/audit-2026-10-20/CADRE-COMMUN.md, les instructions applicables et docs/audits/2026-10-20/README.md. Exécute uniquement la correction 24 — Présenter des réalisations et chiffres vérifiables.

Constat de référence : Visuel : cas clients et compteur. Priorité : P2.
Dépendances techniques : Aucune dépendance technique supplémentaire ; respecter les checkpoints de l’ordre choisi.

Avant de modifier, confirme l’état actuel du défaut et les checkpoints Git précédents. Les points d’entrée ci-dessous sont à vérifier dans le dépôt :
- `resources/views/livewire/pages/public/projects-index.blade.php`
- `resources/views/livewire/pages/public/projects-show.blade.php`
- `resources/views/livewire/pages/admin/projects.blade.php`
- `app/Settings/GeneralSettings.php`
- `resources/views/home.blade.php`

Travail limité à cette fiche :
1. Structurer les réalisations autour du contexte, livrables et résultats que les données disponibles permettent de prouver.
2. Rendre les visuels et textes éditables avec les modules existants.
3. Associer aux compteurs une définition et une source documentables ; masquer un chiffre non justifié plutôt que le remplacer par une invention.
4. Conserver les slugs, liens et contenu historique ; préparer les emplacements si les études de cas réelles manquent.

Critères d’acceptation :
- Chaque preuve publiée correspond à des informations réellement fournies.
- Aucun résultat, client, témoignage ou compteur n’est inventé.
- Les anciens projets restent accessibles et les états sans image sont soignés.

Validation attendue :
- Tests réalisations/SEO existants et recette navigateur.
- Documenter explicitement les contenus réels encore attendus.
- Applique également les contrôles transversaux du cadre commun pertinents à cette modification.
- Actualise la ligne 24 de docs/prompts/audit-2026-10-20/JOURNAL.md avec les résultats réels et les limites.

Termine par un compte rendu clair et les preuves utiles. Ne commence aucune autre correction. Ne fais ni commit ni push ; attends le prompt B de cette fiche.
````

## Prompt B — commit et push de cette seule correction

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Publie uniquement la correction 24 — Présenter des réalisations et chiffres vérifiables, réalisée avec le prompt A de docs/prompts/audit-2026-10-20/24-realisations-et-preuves.md.

Lis le cadre commun et son protocole « Publier B ». Vérifie que A est validé, que les dépendances et checkpoints précédents sont satisfaits et que le code n’a pas changé sans revalidation. Une recette externe non effectuée doit rester signalée ; ne transforme pas une limite en résultat positif.

Relis le diff et les fichiers non suivis. Sélectionne explicitement les seuls fichiers de cette correction, sa documentation, sa ligne de journal et les assets compilés nécessaires. Écarte notamment le changement de mode préexistant d’artisan, les secrets, bases et données privées. Ne mélange pas une autre correction ou l’archive initiale de l’audit dans ce commit.

Crée un commit descriptif, par défaut :
feat(portfolio): present verifiable project evidence

Pousse normalement sur origin, vers la branche de travail autorisée de même nom (main si le lot est toujours sur main). Vérifie le SHA publié sur https://github.com/TIKMOS/tikmos.com et rapporte les checks CI réellement accessibles.

Si le push échoue, résous uniquement le blocage autorisé sans force-push ni perte de travail ; sinon explique précisément pourquoi la correction reste non publiée. Si le commit existe déjà, vérifie-le plutôt que créer un doublon.

Termine avec le numéro 24, la branche, le SHA, le lien du commit GitHub, les validations et les limites. Arrête-toi : ne commence pas la correction suivante.
````
