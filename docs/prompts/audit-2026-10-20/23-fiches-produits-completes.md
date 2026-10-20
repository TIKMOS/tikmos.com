# 23 — Structurer les informations des fiches produit

Priorité : **P2** · Référence : **Visuel et UX : fiches peu détaillées**.

Dépendances : [13](13-frais-livraison.md), [22](22-photos-et-cartes-catalogue.md).

Lire le [cadre commun](CADRE-COMMUN.md). Utiliser A, vérifier le résultat, puis utiliser B. La publication vérifiée précède le passage à la suite.

## Prompt A — réaliser et valider

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Lis docs/prompts/audit-2026-10-20/CADRE-COMMUN.md, les instructions applicables et docs/audits/2026-10-20/README.md. Exécute uniquement la correction 23 — Structurer les informations des fiches produit.

Constat de référence : Visuel et UX : fiches peu détaillées. Priorité : P2.
Dépendances techniques : 13 — Annoncer les frais de livraison avant confirmation; 22 — Remplir les médias et compacter les cartes produit

Avant de modifier, confirme l’état actuel du défaut et les checkpoints Git précédents. Les points d’entrée ci-dessous sont à vérifier dans le dépôt :
- `app/Models/Product.php`
- `resources/views/livewire/pages/public/shop-show.blade.php`
- `resources/views/livewire/pages/admin/boutique/products.blade.php`
- `resources/views/components/tk/media-gallery.blade.php`
- `tests/Feature/ShopTest.php`

Travail limité à cette fiche :
1. Ajouter ou réutiliser des champs éditables adaptés aux caractéristiques, disponibilité et informations d’achat utiles.
2. Présenter galerie, prix, variantes, stock et conditions connues dans une hiérarchie claire.
3. Préserver les anciennes fiches et masquer proprement les informations non renseignées.
4. N’inventer ni modèle technique, garantie, délai de livraison ni origine ; identifier les données métier à fournir.

Critères d’acceptation :
- Une fiche complète est compréhensible avant ajout au panier.
- Une fiche ancienne ou incomplète reste utilisable sans promesse commerciale inventée.
- Le CRUD, la sélection de variante et l’achat conservent leur fonctionnement.

Validation attendue :
- Tester les changements de données/validation s’il y en a.
- Recette fiche complète/incomplète/rupture/variantes et tests boutique existants.
- Applique également les contrôles transversaux du cadre commun pertinents à cette modification.
- Actualise la ligne 23 de docs/prompts/audit-2026-10-20/JOURNAL.md avec les résultats réels et les limites.

Termine par un compte rendu clair et les preuves utiles. Ne commence aucune autre correction. Ne fais ni commit ni push ; attends le prompt B de cette fiche.
````

## Prompt B — commit et push de cette seule correction

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Publie uniquement la correction 23 — Structurer les informations des fiches produit, réalisée avec le prompt A de docs/prompts/audit-2026-10-20/23-fiches-produits-completes.md.

Lis le cadre commun et son protocole « Publier B ». Vérifie que A est validé, que les dépendances et checkpoints précédents sont satisfaits et que le code n’a pas changé sans revalidation. Une recette externe non effectuée doit rester signalée ; ne transforme pas une limite en résultat positif.

Relis le diff et les fichiers non suivis. Sélectionne explicitement les seuls fichiers de cette correction, sa documentation, sa ligne de journal et les assets compilés nécessaires. Écarte notamment le changement de mode préexistant d’artisan, les secrets, bases et données privées. Ne mélange pas une autre correction ou l’archive initiale de l’audit dans ce commit.

Crée un commit descriptif, par défaut :
feat(shop): structure useful product information

Pousse normalement sur origin, vers la branche de travail autorisée de même nom (main si le lot est toujours sur main). Vérifie le SHA publié sur https://github.com/TIKMOS/tikmos.com et rapporte les checks CI réellement accessibles.

Si le push échoue, résous uniquement le blocage autorisé sans force-push ni perte de travail ; sinon explique précisément pourquoi la correction reste non publiée. Si le commit existe déjà, vérifie-le plutôt que créer un doublon.

Termine avec le numéro 23, la branche, le SHA, le lien du commit GitHub, les validations et les limites. Arrête-toi : ne commence pas la correction suivante.
````
