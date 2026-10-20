# 29 — Corriger l’image Open Graph manquante

Priorité : **P2** · Référence : **SEO : og-default.png en 404**.

Dépendances : aucune dépendance technique supplémentaire.

Lire le [cadre commun](CADRE-COMMUN.md). Utiliser A, vérifier le résultat, puis utiliser B. La publication vérifiée précède le passage à la suite.

## Prompt A — réaliser et valider

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Lis docs/prompts/audit-2026-10-20/CADRE-COMMUN.md, les instructions applicables et docs/audits/2026-10-20/README.md. Exécute uniquement la correction 29 — Corriger l’image Open Graph manquante.

Constat de référence : SEO : og-default.png en 404. Priorité : P2.
Dépendances techniques : Aucune dépendance technique supplémentaire ; respecter les checkpoints de l’ordre choisi.

Avant de modifier, confirme l’état actuel du défaut et les checkpoints Git précédents. Les points d’entrée ci-dessous sont à vérifier dans le dépôt :
- `resources/views/components/tk/seo.blade.php`
- `app/Settings/SeoSettings.php`
- `public/icons`
- `tests/Feature/SeoTest.php`

Travail limité à cette fiche :
1. Fournir une vraie image de partage autorisée, cohérente avec la marque, et une URL de fallback existante.
2. Préserver l’image configurée depuis les réglages SEO lorsqu’elle est renseignée.
3. Vérifier OG/Twitter sur l’accueil et les contenus disposant d’un visuel spécifique.
4. Définir les informations utiles de l’image sans prétendre valider les caches des réseaux sociaux depuis localhost.

Critères d’acceptation :
- L’image par défaut répond HTTP 200 et est une image exploitable.
- Les personnalisations SEO restent prioritaires.
- Les URL produites sont correctes en local et selon APP_URL en production.

Validation attendue :
- Test de fallback et de personnalisation SEO ; vérification HTTP et contenu de l’image.
- Inspection des métadonnées et tests SeoTest.
- Applique également les contrôles transversaux du cadre commun pertinents à cette modification.
- Actualise la ligne 29 de docs/prompts/audit-2026-10-20/JOURNAL.md avec les résultats réels et les limites.

Termine par un compte rendu clair et les preuves utiles. Ne commence aucune autre correction. Ne fais ni commit ni push ; attends le prompt B de cette fiche.
````

## Prompt B — commit et push de cette seule correction

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Publie uniquement la correction 29 — Corriger l’image Open Graph manquante, réalisée avec le prompt A de docs/prompts/audit-2026-10-20/29-image-partage-social.md.

Lis le cadre commun et son protocole « Publier B ». Vérifie que A est validé, que les dépendances et checkpoints précédents sont satisfaits et que le code n’a pas changé sans revalidation. Une recette externe non effectuée doit rester signalée ; ne transforme pas une limite en résultat positif.

Relis le diff et les fichiers non suivis. Sélectionne explicitement les seuls fichiers de cette correction, sa documentation, sa ligne de journal et les assets compilés nécessaires. Écarte notamment le changement de mode préexistant d’artisan, les secrets, bases et données privées. Ne mélange pas une autre correction ou l’archive initiale de l’audit dans ce commit.

Crée un commit descriptif, par défaut :
fix(seo): provide a working social preview image

Pousse normalement sur origin, vers la branche de travail autorisée de même nom (main si le lot est toujours sur main). Vérifie le SHA publié sur https://github.com/TIKMOS/tikmos.com et rapporte les checks CI réellement accessibles.

Si le push échoue, résous uniquement le blocage autorisé sans force-push ni perte de travail ; sinon explique précisément pourquoi la correction reste non publiée. Si le commit existe déjà, vérifie-le plutôt que créer un doublon.

Termine avec le numéro 29, la branche, le SHA, le lien du commit GitHub, les validations et les limites. Arrête-toi : ne commence pas la correction suivante.
````
