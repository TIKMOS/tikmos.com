# 04 — Appliquer la validité des devis côté serveur

Priorité : **P1** · Référence : **F04**.

Dépendances : aucune dépendance technique supplémentaire.

Lire le [cadre commun](CADRE-COMMUN.md). Utiliser A, vérifier le résultat, puis utiliser B. La publication vérifiée précède le passage à la suite.

## Prompt A — réaliser et valider

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Lis docs/prompts/audit-2026-10-20/CADRE-COMMUN.md, les instructions applicables et docs/audits/2026-10-20/README.md. Exécute uniquement la correction 04 — Appliquer la validité des devis côté serveur.

Constat de référence : F04. Priorité : P1.
Dépendances techniques : Aucune dépendance technique supplémentaire ; respecter les checkpoints de l’ordre choisi.

Avant de modifier, confirme l’état actuel du défaut et les checkpoints Git précédents. Les points d’entrée ci-dessous sont à vérifier dans le dépôt :
- `app/Models/Quote.php`
- `app/Enums/QuoteStatus.php`
- `resources/views/livewire/pages/app/requests-show.blade.php`
- `resources/views/livewire/pages/admin/service-request-show.blade.php`
- `tests/Feature/App/EspaceClientDevisTest.php`

Travail limité à cette fiche :
1. Définir la date limite comme valable jusqu’à la fin du jour indiqué, dans le fuseau configuré de l’application ; documenter cette règle.
2. Contrôler la validité au moment de l’acceptation, y compris si l’écran a été ouvert avant expiration.
3. Afficher un devis expiré avec une prochaine étape claire pour demander son renouvellement ; conserver les devis sans date selon la règle existante.
4. Empêcher les dates de validité déjà passées lors d’un nouvel envoi, sans modifier silencieusement les devis historiques.

Critères d’acceptation :
- Un devis expiré ne peut pas être accepté par un appel Livewire direct.
- Le jour limite, le lendemain et l’absence de date sont couverts.
- Montant, historique et décisions déjà enregistrées restent cohérents.

Validation attendue :
- Inverser la reproduction 4 et utiliser une horloge figée pour les limites de journée.
- Exécuter EspaceClientDevisTest, ServicesDevisTest et la suite.
- Applique également les contrôles transversaux du cadre commun pertinents à cette modification.
- Actualise la ligne 04 de docs/prompts/audit-2026-10-20/JOURNAL.md avec les résultats réels et les limites.

Termine par un compte rendu clair et les preuves utiles. Ne commence aucune autre correction. Ne fais ni commit ni push ; attends le prompt B de cette fiche.
````

## Prompt B — commit et push de cette seule correction

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Publie uniquement la correction 04 — Appliquer la validité des devis côté serveur, réalisée avec le prompt A de docs/prompts/audit-2026-10-20/04-expiration-devis.md.

Lis le cadre commun et son protocole « Publier B ». Vérifie que A est validé, que les dépendances et checkpoints précédents sont satisfaits et que le code n’a pas changé sans revalidation. Une recette externe non effectuée doit rester signalée ; ne transforme pas une limite en résultat positif.

Relis le diff et les fichiers non suivis. Sélectionne explicitement les seuls fichiers de cette correction, sa documentation, sa ligne de journal et les assets compilés nécessaires. Écarte notamment le changement de mode préexistant d’artisan, les secrets, bases et données privées. Ne mélange pas une autre correction ou l’archive initiale de l’audit dans ce commit.

Crée un commit descriptif, par défaut :
fix(quotes): enforce validity when accepting

Pousse normalement sur origin, vers la branche de travail autorisée de même nom (main si le lot est toujours sur main). Vérifie le SHA publié sur https://github.com/TIKMOS/tikmos.com et rapporte les checks CI réellement accessibles.

Si le push échoue, résous uniquement le blocage autorisé sans force-push ni perte de travail ; sinon explique précisément pourquoi la correction reste non publiée. Si le commit existe déjà, vérifie-le plutôt que créer un doublon.

Termine avec le numéro 04, la branche, le SHA, le lien du commit GitHub, les validations et les limites. Arrête-toi : ne commence pas la correction suivante.
````
