# 22 — Remplir les médias et compacter les cartes produit

Priorité : **P2** · Référence : **Visuel : catalogue sans photos**.

Dépendances : [21](21-contrastes-theme.md).

Lire le [cadre commun](CADRE-COMMUN.md). Utiliser A, vérifier le résultat, puis utiliser B. La publication vérifiée précède le passage à la suite.

## Prompt A — réaliser et valider

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Lis docs/prompts/audit-2026-10-20/CADRE-COMMUN.md, les instructions applicables et docs/audits/2026-10-20/README.md. Exécute uniquement la correction 22 — Remplir les médias et compacter les cartes produit.

Constat de référence : Visuel : catalogue sans photos. Priorité : P2.
Dépendances techniques : 21 — Corriger les contrastes sans changer l’identité

Avant de modifier, confirme l’état actuel du défaut et les checkpoints Git précédents. Les points d’entrée ci-dessous sont à vérifier dans le dépôt :
- `resources/views/components/tk/product-card.blade.php`
- `resources/views/components/tk/image.blade.php`
- `resources/views/livewire/pages/public/shop-index.blade.php`
- `resources/views/livewire/pages/admin/boutique/products.blade.php`
- `app/Models/Product.php`

Travail limité à cette fiche :
1. Inventorier les visuels réellement disponibles et leur association aux produits ; utiliser seulement des assets autorisés et pertinents.
2. Améliorer le chargement et la présentation des photos via le module média existant, avec alt et ratio cohérents.
3. Réduire l’introduction et les cadres de substitution excessifs sur mobile pour rendre nom et prix accessibles plus rapidement.
4. Si les photos réelles manquent, livrer l’amélioration technique et lister les assets manquants ; ne pas inventer de photographie ou de caractéristique d’un produit commercial.

Critères d’acceptation :
- Les produits disposant d’images les affichent sans déformation ni saut de mise en page évitable.
- Les cartes restent utiles et explicites sans média ; aucune fausse photo de produit n’est publiée.
- Filtres, prix, promotions et navigation restent utilisables sur 360/390/768/1440 px.

Validation attendue :
- Vérifier avec et sans média, chargement lent et catalogue vide.
- Utiliser les tests catalogue/média existants et le build ; documenter les données encore nécessaires.
- Applique également les contrôles transversaux du cadre commun pertinents à cette modification.
- Actualise la ligne 22 de docs/prompts/audit-2026-10-20/JOURNAL.md avec les résultats réels et les limites.

Termine par un compte rendu clair et les preuves utiles. Ne commence aucune autre correction. Ne fais ni commit ni push ; attends le prompt B de cette fiche.
````

## Prompt B — commit et push de cette seule correction

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Publie uniquement la correction 22 — Remplir les médias et compacter les cartes produit, réalisée avec le prompt A de docs/prompts/audit-2026-10-20/22-photos-et-cartes-catalogue.md.

Lis le cadre commun et son protocole « Publier B ». Vérifie que A est validé, que les dépendances et checkpoints précédents sont satisfaits et que le code n’a pas changé sans revalidation. Une recette externe non effectuée doit rester signalée ; ne transforme pas une limite en résultat positif.

Relis le diff et les fichiers non suivis. Sélectionne explicitement les seuls fichiers de cette correction, sa documentation, sa ligne de journal et les assets compilés nécessaires. Écarte notamment le changement de mode préexistant d’artisan, les secrets, bases et données privées. Ne mélange pas une autre correction ou l’archive initiale de l’audit dans ce commit.

Crée un commit descriptif, par défaut :
fix(shop): improve product media and mobile cards

Pousse normalement sur origin, vers la branche de travail autorisée de même nom (main si le lot est toujours sur main). Vérifie le SHA publié sur https://github.com/TIKMOS/tikmos.com et rapporte les checks CI réellement accessibles.

Si le push échoue, résous uniquement le blocage autorisé sans force-push ni perte de travail ; sinon explique précisément pourquoi la correction reste non publiée. Si le commit existe déjà, vérifie-le plutôt que créer un doublon.

Termine avec le numéro 22, la branche, le SHA, le lien du commit GitHub, les validations et les limites. Arrête-toi : ne commence pas la correction suivante.
````
