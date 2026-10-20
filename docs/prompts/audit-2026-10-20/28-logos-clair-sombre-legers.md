# 28 — Optimiser les logos et leur rendu sur fond sombre

Priorité : **P2** · Référence : **Visuel logo + performance 809 Ko**.

Dépendances : aucune dépendance technique supplémentaire.

Lire le [cadre commun](CADRE-COMMUN.md). Utiliser A, vérifier le résultat, puis utiliser B. La publication vérifiée précède le passage à la suite.

## Prompt A — réaliser et valider

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Lis docs/prompts/audit-2026-10-20/CADRE-COMMUN.md, les instructions applicables et docs/audits/2026-10-20/README.md. Exécute uniquement la correction 28 — Optimiser les logos et leur rendu sur fond sombre.

Constat de référence : Visuel logo + performance 809 Ko. Priorité : P2.
Dépendances techniques : Aucune dépendance technique supplémentaire ; respecter les checkpoints de l’ordre choisi.

Avant de modifier, confirme l’état actuel du défaut et les checkpoints Git précédents. Les points d’entrée ci-dessous sont à vérifier dans le dépôt :
- `public/img/tikmos-logo.png`
- `public/img/tikmos-logo-dark.jpg`
- `resources/views/components/tk/logo.blade.php`
- `brand`

Travail limité à cette fiche :
1. Examiner les assets officiels et produire des variantes adaptées aux fonds clairs/sombres sans recréer arbitrairement la marque.
2. Préférer le vectoriel existant ; sinon optimiser les dimensions et le format des sources autorisées.
3. Remplacer la dépendance au mélange CSS par une variante réellement lisible sur la sidebar sombre.
4. Stabiliser dimensions intrinsèques et affichées pour éviter les décalages ; préserver les variantes utilisées par les emails si concernées.

Critères d’acceptation :
- Logo lisible dans header, footer, auth et sidebar.
- Poids nettement inférieur aux 808 965 octets du PNG initial, avec mesure avant/après.
- Pas de déformation, de texte flou ou de nouvelle identité graphique.

Validation attendue :
- Comparer visuellement à taille réelle sur fonds clair/sombre.
- Vérifier URLs d’assets, dimensions, poids et build.
- Applique également les contrôles transversaux du cadre commun pertinents à cette modification.
- Actualise la ligne 28 de docs/prompts/audit-2026-10-20/JOURNAL.md avec les résultats réels et les limites.

Termine par un compte rendu clair et les preuves utiles. Ne commence aucune autre correction. Ne fais ni commit ni push ; attends le prompt B de cette fiche.
````

## Prompt B — commit et push de cette seule correction

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Publie uniquement la correction 28 — Optimiser les logos et leur rendu sur fond sombre, réalisée avec le prompt A de docs/prompts/audit-2026-10-20/28-logos-clair-sombre-legers.md.

Lis le cadre commun et son protocole « Publier B ». Vérifie que A est validé, que les dépendances et checkpoints précédents sont satisfaits et que le code n’a pas changé sans revalidation. Une recette externe non effectuée doit rester signalée ; ne transforme pas une limite en résultat positif.

Relis le diff et les fichiers non suivis. Sélectionne explicitement les seuls fichiers de cette correction, sa documentation, sa ligne de journal et les assets compilés nécessaires. Écarte notamment le changement de mode préexistant d’artisan, les secrets, bases et données privées. Ne mélange pas une autre correction ou l’archive initiale de l’audit dans ce commit.

Crée un commit descriptif, par défaut :
perf(brand): optimize light and dark logo assets

Pousse normalement sur origin, vers la branche de travail autorisée de même nom (main si le lot est toujours sur main). Vérifie le SHA publié sur https://github.com/TIKMOS/tikmos.com et rapporte les checks CI réellement accessibles.

Si le push échoue, résous uniquement le blocage autorisé sans force-push ni perte de travail ; sinon explique précisément pourquoi la correction reste non publiée. Si le commit existe déjà, vérifie-le plutôt que créer un doublon.

Termine avec le numéro 28, la branche, le SHA, le lien du commit GitHub, les validations et les limites. Arrête-toi : ne commence pas la correction suivante.
````
