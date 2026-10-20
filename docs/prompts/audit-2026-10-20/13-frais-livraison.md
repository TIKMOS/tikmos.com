# 13 — Annoncer les frais de livraison avant confirmation

Priorité : **P2** · Référence : **UX : livraison à convenir**.

Dépendances : [07](07-reprise-paiement-reservation.md).

Lire le [cadre commun](CADRE-COMMUN.md). Utiliser A, vérifier le résultat, puis utiliser B. La publication vérifiée précède le passage à la suite.

## Prompt A — réaliser et valider

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Lis docs/prompts/audit-2026-10-20/CADRE-COMMUN.md, les instructions applicables et docs/audits/2026-10-20/README.md. Exécute uniquement la correction 13 — Annoncer les frais de livraison avant confirmation.

Constat de référence : UX : livraison à convenir. Priorité : P2.
Dépendances techniques : 07 — Gérer l’abandon et la reprise du paiement

Avant de modifier, confirme l’état actuel du défaut et les checkpoints Git précédents. Les points d’entrée ci-dessous sont à vérifier dans le dépôt :
- `app/Settings/ShopSettings.php`
- `app/Services/OrderService.php`
- `resources/views/livewire/pages/public/cart.blade.php`
- `resources/views/livewire/pages/public/checkout.blade.php`
- `resources/views/livewire/pages/app/orders-show.blade.php`

Travail limité à cette fiche :
1. Distinguer livraison gratuite, tarif connu et tarif non encore déterminé sans confondre ces états.
2. Si le tarif reste à convenir, afficher total hors livraison et une validation commerciale explicite ; ne pas inventer de grille tarifaire.
3. Ne déclencher aucun paiement en ligne présenté comme définitif lorsque le montant final reste inconnu.
4. Conserver le calcul serveur et une présentation cohérente entre panier, checkout et commande.

Critères d’acceptation :
- Le client comprend avant confirmation ce qui est inclus et ce qui reste à valider.
- Zéro signifie explicitement gratuit seulement si la configuration le définit ainsi.
- Le paiement utilise un montant définitif et validé, ou reste indisponible avec une explication utile.

Validation attendue :
- Tester livraison gratuite/connue/inconnue et protection du paiement en ligne.
- Vérifier les trois écrans au navigateur et exécuter les tests de commandes/paiements.
- Applique également les contrôles transversaux du cadre commun pertinents à cette modification.
- Actualise la ligne 13 de docs/prompts/audit-2026-10-20/JOURNAL.md avec les résultats réels et les limites.

Termine par un compte rendu clair et les preuves utiles. Ne commence aucune autre correction. Ne fais ni commit ni push ; attends le prompt B de cette fiche.
````

## Prompt B — commit et push de cette seule correction

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Publie uniquement la correction 13 — Annoncer les frais de livraison avant confirmation, réalisée avec le prompt A de docs/prompts/audit-2026-10-20/13-frais-livraison.md.

Lis le cadre commun et son protocole « Publier B ». Vérifie que A est validé, que les dépendances et checkpoints précédents sont satisfaits et que le code n’a pas changé sans revalidation. Une recette externe non effectuée doit rester signalée ; ne transforme pas une limite en résultat positif.

Relis le diff et les fichiers non suivis. Sélectionne explicitement les seuls fichiers de cette correction, sa documentation, sa ligne de journal et les assets compilés nécessaires. Écarte notamment le changement de mode préexistant d’artisan, les secrets, bases et données privées. Ne mélange pas une autre correction ou l’archive initiale de l’audit dans ce commit.

Crée un commit descriptif, par défaut :
fix(checkout): distinguish unknown delivery charges

Pousse normalement sur origin, vers la branche de travail autorisée de même nom (main si le lot est toujours sur main). Vérifie le SHA publié sur https://github.com/TIKMOS/tikmos.com et rapporte les checks CI réellement accessibles.

Si le push échoue, résous uniquement le blocage autorisé sans force-push ni perte de travail ; sinon explique précisément pourquoi la correction reste non publiée. Si le commit existe déjà, vérifie-le plutôt que créer un doublon.

Termine avec le numéro 13, la branche, le SHA, le lien du commit GitHub, les validations et les limites. Arrête-toi : ne commence pas la correction suivante.
````
