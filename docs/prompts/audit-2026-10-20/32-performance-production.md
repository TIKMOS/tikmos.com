# 32 — Mesurer et améliorer la performance compilée

Priorité : **P2** · Référence : **Performance : mesures réelles restantes**.

Dépendances : [22](22-photos-et-cartes-catalogue.md), [28](28-logos-clair-sombre-legers.md).

Lire le [cadre commun](CADRE-COMMUN.md). Utiliser A, vérifier le résultat, puis utiliser B. La publication vérifiée précède le passage à la suite.

## Prompt A — réaliser et valider

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Lis docs/prompts/audit-2026-10-20/CADRE-COMMUN.md, les instructions applicables et docs/audits/2026-10-20/README.md. Exécute uniquement la correction 32 — Mesurer et améliorer la performance compilée.

Constat de référence : Performance : mesures réelles restantes. Priorité : P2.
Dépendances techniques : 22 — Remplir les médias et compacter les cartes produit; 28 — Optimiser les logos et leur rendu sur fond sombre

Avant de modifier, confirme l’état actuel du défaut et les checkpoints Git précédents. Les points d’entrée ci-dessous sont à vérifier dans le dépôt :
- `vite.config.js`
- `resources/js/app.js`
- `resources/js/tiptap-editor.js`
- `resources/css/app.css`
- `resources/views/components/tk/image.blade.php`

Travail limité à cette fiche :
1. Établir une mesure sur une version compilée isolée, hors Vite/debug, avant toute optimisation.
2. Inspecter les ressources réellement chargées : images, polices et scripts ; vérifier que l’éditeur riche reste chargé seulement où nécessaire.
3. Corriger uniquement les coûts démontrés, sans retirer de fonctionnalités ou dégrader les médias.
4. Documenter appareil/profil réseau/environnement et limites ; ne pas inventer de score Lighthouse ou de Core Web Vitals.

Critères d’acceptation :
- Comparaison avant/après reproductible sur les mêmes pages et conditions.
- Aucune régression fonctionnelle, de lisibilité ou de stabilité de mise en page.
- Seules les ressources utiles sont chargées selon la page, avec budgets documentés.

Validation attendue :
- Build de production, mesures disponibles et recette visuelle après optimisation.
- Si Lighthouse n’est pas disponible, livrer les mesures réellement obtenues et préciser cette limite.
- Applique également les contrôles transversaux du cadre commun pertinents à cette modification.
- Actualise la ligne 32 de docs/prompts/audit-2026-10-20/JOURNAL.md avec les résultats réels et les limites.

Termine par un compte rendu clair et les preuves utiles. Ne commence aucune autre correction. Ne fais ni commit ni push ; attends le prompt B de cette fiche.
````

## Prompt B — commit et push de cette seule correction

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Publie uniquement la correction 32 — Mesurer et améliorer la performance compilée, réalisée avec le prompt A de docs/prompts/audit-2026-10-20/32-performance-production.md.

Lis le cadre commun et son protocole « Publier B ». Vérifie que A est validé, que les dépendances et checkpoints précédents sont satisfaits et que le code n’a pas changé sans revalidation. Une recette externe non effectuée doit rester signalée ; ne transforme pas une limite en résultat positif.

Relis le diff et les fichiers non suivis. Sélectionne explicitement les seuls fichiers de cette correction, sa documentation, sa ligne de journal et les assets compilés nécessaires. Écarte notamment le changement de mode préexistant d’artisan, les secrets, bases et données privées. Ne mélange pas une autre correction ou l’archive initiale de l’audit dans ce commit.

Crée un commit descriptif, par défaut :
perf: optimize measured production page costs

Pousse normalement sur origin, vers la branche de travail autorisée de même nom (main si le lot est toujours sur main). Vérifie le SHA publié sur https://github.com/TIKMOS/tikmos.com et rapporte les checks CI réellement accessibles.

Si le push échoue, résous uniquement le blocage autorisé sans force-push ni perte de travail ; sinon explique précisément pourquoi la correction reste non publiée. Si le commit existe déjà, vérifie-le plutôt que créer un doublon.

Termine avec le numéro 32, la branche, le SHA, le lien du commit GitHub, les validations et les limites. Arrête-toi : ne commence pas la correction suivante.
````
