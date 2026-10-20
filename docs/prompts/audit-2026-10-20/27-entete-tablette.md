# 27 — Alléger l’en-tête aux largeurs intermédiaires

Priorité : **P2** · Référence : **Visuel : navigation à 768 px**.

Dépendances : aucune dépendance technique supplémentaire.

Lire le [cadre commun](CADRE-COMMUN.md). Utiliser A, vérifier le résultat, puis utiliser B. La publication vérifiée précède le passage à la suite.

## Prompt A — réaliser et valider

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Lis docs/prompts/audit-2026-10-20/CADRE-COMMUN.md, les instructions applicables et docs/audits/2026-10-20/README.md. Exécute uniquement la correction 27 — Alléger l’en-tête aux largeurs intermédiaires.

Constat de référence : Visuel : navigation à 768 px. Priorité : P2.
Dépendances techniques : Aucune dépendance technique supplémentaire ; respecter les checkpoints de l’ordre choisi.

Avant de modifier, confirme l’état actuel du défaut et les checkpoints Git précédents. Les points d’entrée ci-dessous sont à vérifier dans le dépôt :
- `resources/views/components/layouts/public.blade.php`

Travail limité à cette fiche :
1. Conserver le menu compact jusqu’à la largeur où les liens et actions tiennent confortablement sur une ligne.
2. Rendre visibles les actions utiles sans comprimer le logo ni casser À propos sur deux lignes.
3. Préserver navigation active, compte connecté, panier et menu clavier.
4. Réutiliser le comportement accessible de l’étape 20 si elle est déjà livrée.

Critères d’acceptation :
- Pas de chevauchement ou débordement aux largeurs 360, 390, 640, 768, 1024 et 1440 px.
- Les états visiteur/connecté et panier avec compteur restent utilisables.
- Le changement de breakpoint ne laisse pas un menu incohérent ou un fond bloqué.

Validation attendue :
- Recette navigateur et captures aux principaux breakpoints ; build si nécessaire.
- Ne pas ajouter de tests reproduisant les classes CSS.
- Applique également les contrôles transversaux du cadre commun pertinents à cette modification.
- Actualise la ligne 27 de docs/prompts/audit-2026-10-20/JOURNAL.md avec les résultats réels et les limites.

Termine par un compte rendu clair et les preuves utiles. Ne commence aucune autre correction. Ne fais ni commit ni push ; attends le prompt B de cette fiche.
````

## Prompt B — commit et push de cette seule correction

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Publie uniquement la correction 27 — Alléger l’en-tête aux largeurs intermédiaires, réalisée avec le prompt A de docs/prompts/audit-2026-10-20/27-entete-tablette.md.

Lis le cadre commun et son protocole « Publier B ». Vérifie que A est validé, que les dépendances et checkpoints précédents sont satisfaits et que le code n’a pas changé sans revalidation. Une recette externe non effectuée doit rester signalée ; ne transforme pas une limite en résultat positif.

Relis le diff et les fichiers non suivis. Sélectionne explicitement les seuls fichiers de cette correction, sa documentation, sa ligne de journal et les assets compilés nécessaires. Écarte notamment le changement de mode préexistant d’artisan, les secrets, bases et données privées. Ne mélange pas une autre correction ou l’archive initiale de l’audit dans ce commit.

Crée un commit descriptif, par défaut :
fix(layout): simplify tablet header navigation

Pousse normalement sur origin, vers la branche de travail autorisée de même nom (main si le lot est toujours sur main). Vérifie le SHA publié sur https://github.com/TIKMOS/tikmos.com et rapporte les checks CI réellement accessibles.

Si le push échoue, résous uniquement le blocage autorisé sans force-push ni perte de travail ; sinon explique précisément pourquoi la correction reste non publiée. Si le commit existe déjà, vérifie-le plutôt que créer un doublon.

Termine avec le numéro 27, la branche, le SHA, le lien du commit GitHub, les validations et les limites. Arrête-toi : ne commence pas la correction suivante.
````
