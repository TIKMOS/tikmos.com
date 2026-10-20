# 34 — Valider le cycle KKiaPay en sandbox

Priorité : **Recette** · Référence : **Exploitation : passerelle réelle non testée**.

Dépendances : [01](01-transaction-externe-unique.md), [02](02-montant-paiement-obligatoire.md), [07](07-reprise-paiement-reservation.md), [13](13-frais-livraison.md).

Lire le [cadre commun](CADRE-COMMUN.md). Utiliser A, vérifier le résultat, puis utiliser B. La publication vérifiée précède le passage à la suite.

## Prompt A — réaliser et valider

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Lis docs/prompts/audit-2026-10-20/CADRE-COMMUN.md, les instructions applicables et docs/audits/2026-10-20/README.md. Exécute uniquement la correction 34 — Valider le cycle KKiaPay en sandbox.

Constat de référence : Exploitation : passerelle réelle non testée. Priorité : Recette.
Dépendances techniques : 01 — Empêcher une transaction externe de payer plusieurs commandes; 02 — Refuser un paiement en ligne sans montant confirmé; 07 — Gérer l’abandon et la reprise du paiement; 13 — Annoncer les frais de livraison avant confirmation

Avant de modifier, confirme l’état actuel du défaut et les checkpoints Git précédents. Les points d’entrée ci-dessous sont à vérifier dans le dépôt :
- `app/Payments/Gateways/KkiapayGateway.php`
- `app/Services/PaymentService.php`
- `app/Http/Controllers/PaymentWebhookController.php`
- `docs/30-systeme-paiement.md`

Travail limité à cette fiche :
1. Relire la documentation officielle actuelle pour les signatures, statuts, montants et références ; confronter l’implémentation au contrat réel.
2. Utiliser seulement un compte sandbox et des transactions fictives, sans mouvement de fonds réels ni bascule en mode live.
3. Tester succès, refus, abandon, reprise, rechargement, répétition de webhook, montant manquant et tentative de rattachement croisé.
4. Ne pas exposer de secrets ni créer de tunnel public vers une application avec données réelles ; si le callback nécessite une URL autorisée indisponible, documenter ce point au lieu de contourner la protection.

Critères d’acceptation :
- Chaque résultat rapproche une commande et un paiement uniques, pour le bon montant.
- Les événements sont idempotents et l’abandon n’immobilise pas indéfiniment le stock.
- Une clé ou un endpoint manquant produit une limite explicite, pas une fausse validation.

Validation attendue :
- Conserver les preuves non sensibles de sandbox ; tests simulés restent verts.
- Ne corriger dans ce lot que les écarts de protocole confirmés, sans refonte des corrections précédentes.
- Applique également les contrôles transversaux du cadre commun pertinents à cette modification.
- Actualise la ligne 34 de docs/prompts/audit-2026-10-20/JOURNAL.md avec les résultats réels et les limites.

Termine par un compte rendu clair et les preuves utiles. Ne commence aucune autre correction. Ne fais ni commit ni push ; attends le prompt B de cette fiche.
````

## Prompt B — commit et push de cette seule correction

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Publie uniquement la correction 34 — Valider le cycle KKiaPay en sandbox, réalisée avec le prompt A de docs/prompts/audit-2026-10-20/34-recette-kkiapay-sandbox.md.

Lis le cadre commun et son protocole « Publier B ». Vérifie que A est validé, que les dépendances et checkpoints précédents sont satisfaits et que le code n’a pas changé sans revalidation. Une recette externe non effectuée doit rester signalée ; ne transforme pas une limite en résultat positif.

Relis le diff et les fichiers non suivis. Sélectionne explicitement les seuls fichiers de cette correction, sa documentation, sa ligne de journal et les assets compilés nécessaires. Écarte notamment le changement de mode préexistant d’artisan, les secrets, bases et données privées. Ne mélange pas une autre correction ou l’archive initiale de l’audit dans ce commit.

Crée un commit descriptif, par défaut :
test(payments): verify documented sandbox scenarios

Pousse normalement sur origin, vers la branche de travail autorisée de même nom (main si le lot est toujours sur main). Vérifie le SHA publié sur https://github.com/TIKMOS/tikmos.com et rapporte les checks CI réellement accessibles.

Si le push échoue, résous uniquement le blocage autorisé sans force-push ni perte de travail ; sinon explique précisément pourquoi la correction reste non publiée. Si le commit existe déjà, vérifie-le plutôt que créer un doublon.

Termine avec le numéro 34, la branche, le SHA, le lien du commit GitHub, les validations et les limites. Arrête-toi : ne commence pas la correction suivante.
````
