# 14 — Clarifier les statuts de commande et le vocabulaire

Priorité : **P2** · Référence : **UX : COD + vocabulaire**.

Dépendances : aucune dépendance technique supplémentaire.

Lire le [cadre commun](CADRE-COMMUN.md). Utiliser A, vérifier le résultat, puis utiliser B. La publication vérifiée précède le passage à la suite.

## Prompt A — réaliser et valider

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Lis docs/prompts/audit-2026-10-20/CADRE-COMMUN.md, les instructions applicables et docs/audits/2026-10-20/README.md. Exécute uniquement la correction 14 — Clarifier les statuts de commande et le vocabulaire.

Constat de référence : UX : COD + vocabulaire. Priorité : P2.
Dépendances techniques : Aucune dépendance technique supplémentaire ; respecter les checkpoints de l’ordre choisi.

Avant de modifier, confirme l’état actuel du défaut et les checkpoints Git précédents. Les points d’entrée ci-dessous sont à vérifier dans le dépôt :
- `app/Enums/OrderStatus.php`
- `app/Enums/OrderPaymentStatus.php`
- `resources/views/livewire/pages/app/orders-show.blade.php`
- `resources/views/components/layouts/app.blade.php`
- `resources/views/components/layouts/admin.blade.php`

Travail limité à cette fiche :
1. Afficher À régler à la livraison pour une commande COD non encaissée, en gardant des libellés exacts pour les paiements en ligne.
2. Distinguer commande reçue, confirmée commercialement et payée dans les messages et les écrans.
3. Choisir un vocabulaire cohérent pour Mes commandes/Mes achats et distinguer équipe publique et personnel disposant d’un accès.
4. Modifier les libellés affichés, pas les valeurs persistées des enums ni les URLs sans nécessité.

Critères d’acceptation :
- Le COD normal n’apparaît pas comme une erreur de paiement.
- Les statuts réels et les actions disponibles restent exacts.
- Les termes sont cohérents dans la navigation, les listes, le détail et les messages concernés.

Validation attendue :
- Vérifier visuellement COD, paiement en attente et paiement confirmé.
- Exécuter les tests de parcours existants ; n’ajouter un test que si une règle de présentation conditionnelle le justifie.
- Applique également les contrôles transversaux du cadre commun pertinents à cette modification.
- Actualise la ligne 14 de docs/prompts/audit-2026-10-20/JOURNAL.md avec les résultats réels et les limites.

Termine par un compte rendu clair et les preuves utiles. Ne commence aucune autre correction. Ne fais ni commit ni push ; attends le prompt B de cette fiche.
````

## Prompt B — commit et push de cette seule correction

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Publie uniquement la correction 14 — Clarifier les statuts de commande et le vocabulaire, réalisée avec le prompt A de docs/prompts/audit-2026-10-20/14-libelles-statuts-navigation.md.

Lis le cadre commun et son protocole « Publier B ». Vérifie que A est validé, que les dépendances et checkpoints précédents sont satisfaits et que le code n’a pas changé sans revalidation. Une recette externe non effectuée doit rester signalée ; ne transforme pas une limite en résultat positif.

Relis le diff et les fichiers non suivis. Sélectionne explicitement les seuls fichiers de cette correction, sa documentation, sa ligne de journal et les assets compilés nécessaires. Écarte notamment le changement de mode préexistant d’artisan, les secrets, bases et données privées. Ne mélange pas une autre correction ou l’archive initiale de l’audit dans ce commit.

Crée un commit descriptif, par défaut :
fix(ux): clarify order statuses and navigation labels

Pousse normalement sur origin, vers la branche de travail autorisée de même nom (main si le lot est toujours sur main). Vérifie le SHA publié sur https://github.com/TIKMOS/tikmos.com et rapporte les checks CI réellement accessibles.

Si le push échoue, résous uniquement le blocage autorisé sans force-push ni perte de travail ; sinon explique précisément pourquoi la correction reste non publiée. Si le commit existe déjà, vérifie-le plutôt que créer un doublon.

Termine avec le numéro 14, la branche, le SHA, le lien du commit GitHub, les validations et les limites. Arrête-toi : ne commence pas la correction suivante.
````
