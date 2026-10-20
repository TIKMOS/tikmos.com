# 21 — Corriger les contrastes sans changer l’identité

Priorité : **P2** · Référence : **Accessibilité : ratios du thème**.

Dépendances : aucune dépendance technique supplémentaire.

Lire le [cadre commun](CADRE-COMMUN.md). Utiliser A, vérifier le résultat, puis utiliser B. La publication vérifiée précède le passage à la suite.

## Prompt A — réaliser et valider

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Lis docs/prompts/audit-2026-10-20/CADRE-COMMUN.md, les instructions applicables et docs/audits/2026-10-20/README.md. Exécute uniquement la correction 21 — Corriger les contrastes sans changer l’identité.

Constat de référence : Accessibilité : ratios du thème. Priorité : P2.
Dépendances techniques : Aucune dépendance technique supplémentaire ; respecter les checkpoints de l’ordre choisi.

Avant de modifier, confirme l’état actuel du défaut et les checkpoints Git précédents. Les points d’entrée ci-dessous sont à vérifier dans le dépôt :
- `resources/css/app.css`
- `resources/views/components/tk/button.blade.php`
- `resources/views/components/tk/kicker.blade.php`
- `docs/01-charte-graphique.md`
- `docs/prompts/00-prompt-maitre.md`

Travail limité à cette fiche :
1. Mesurer les combinaisons réellement utilisées : gold-600 sur blanc/ivoire, tk-400 informatif et texte blanc sur vert WhatsApp.
2. Utiliser des tons de texte assez foncés, en conservant l’or décoratif et le vert de marque.
3. Corriger les états de focus, erreur, hover et désactivé selon leur usage ; respecter prefers-reduced-motion.
4. Actualiser les commentaires et documents affirmant à tort que gold-600 garantit AA ; s’appuyer sur les ratios mesurés et les références W3C officielles.

Critères d’acceptation :
- Les petits textes informatifs ciblés atteignent 4,5:1 sur leurs fonds réels.
- Les indicateurs nécessaires et le focus restent discernables.
- L’identité encre/ivoire/or demeure cohérente ; aucune certification globale non mesurée n’est revendiquée.

Validation attendue :
- Mesures de contraste documentées et captures des écrans concernés.
- Build frontend et recette visuelle ; éviter des tests de simple recopie des couleurs.
- Applique également les contrôles transversaux du cadre commun pertinents à cette modification.
- Actualise la ligne 21 de docs/prompts/audit-2026-10-20/JOURNAL.md avec les résultats réels et les limites.

Termine par un compte rendu clair et les preuves utiles. Ne commence aucune autre correction. Ne fais ni commit ni push ; attends le prompt B de cette fiche.
````

## Prompt B — commit et push de cette seule correction

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Publie uniquement la correction 21 — Corriger les contrastes sans changer l’identité, réalisée avec le prompt A de docs/prompts/audit-2026-10-20/21-contrastes-theme.md.

Lis le cadre commun et son protocole « Publier B ». Vérifie que A est validé, que les dépendances et checkpoints précédents sont satisfaits et que le code n’a pas changé sans revalidation. Une recette externe non effectuée doit rester signalée ; ne transforme pas une limite en résultat positif.

Relis le diff et les fichiers non suivis. Sélectionne explicitement les seuls fichiers de cette correction, sa documentation, sa ligne de journal et les assets compilés nécessaires. Écarte notamment le changement de mode préexistant d’artisan, les secrets, bases et données privées. Ne mélange pas une autre correction ou l’archive initiale de l’audit dans ce commit.

Crée un commit descriptif, par défaut :
fix(a11y): improve theme text and focus contrast

Pousse normalement sur origin, vers la branche de travail autorisée de même nom (main si le lot est toujours sur main). Vérifie le SHA publié sur https://github.com/TIKMOS/tikmos.com et rapporte les checks CI réellement accessibles.

Si le push échoue, résous uniquement le blocage autorisé sans force-push ni perte de travail ; sinon explique précisément pourquoi la correction reste non publiée. Si le commit existe déjà, vérifie-le plutôt que créer un doublon.

Termine avec le numéro 21, la branche, le SHA, le lien du commit GitHub, les validations et les limites. Arrête-toi : ne commence pas la correction suivante.
````
