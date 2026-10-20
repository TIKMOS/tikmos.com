# 05 — Permettre au visiteur de retrouver sa demande en sécurité

Priorité : **P1** · Référence : **F05**.

Dépendances : aucune dépendance technique supplémentaire.

Lire le [cadre commun](CADRE-COMMUN.md). Utiliser A, vérifier le résultat, puis utiliser B. La publication vérifiée précède le passage à la suite.

## Prompt A — réaliser et valider

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Lis docs/prompts/audit-2026-10-20/CADRE-COMMUN.md, les instructions applicables et docs/audits/2026-10-20/README.md. Exécute uniquement la correction 05 — Permettre au visiteur de retrouver sa demande en sécurité.

Constat de référence : F05. Priorité : P1.
Dépendances techniques : Aucune dépendance technique supplémentaire ; respecter les checkpoints de l’ordre choisi.

Avant de modifier, confirme l’état actuel du défaut et les checkpoints Git précédents. Les points d’entrée ci-dessous sont à vérifier dans le dépôt :
- `app/Models/ServiceRequest.php`
- `app/Notifications/QuoteSent.php`
- `resources/views/livewire/pages/public/services-show.blade.php`
- `resources/views/livewire/pages/app/requests-show.blade.php`
- `routes/web.php`
- `routes/auth.php`

Travail limité à cette fiche :
1. Concevoir un parcours minimal de revendication : lien signé à durée limitée, vérification de l’adresse destinataire et rattachement unique au compte autorisé.
2. Conserver le contrôle strict de propriété ; une simple égalité entre deux emails non vérifiés ne suffit pas.
3. Rendre les cas de connexion, inscription, lien expiré, lien déjà utilisé et compte différent compréhensibles.
4. Mettre à jour les emails et conserver les anciennes demandes ; prévoir un renouvellement sûr du lien.

Critères d’acceptation :
- Le demandeur légitime peut retrouver son devis après vérification d’identité.
- Un autre compte, une signature altérée ou un lien expiré ne révèle aucune donnée.
- Le rattachement n’est pas rejouable et ne transfère pas une demande déjà attribuée.

Validation attendue :
- Compléter la reproduction 5 : conserver le refus de l’accès non autorisé et ajouter le parcours de récupération légitime.
- Tester liens valides/invalides/expirés et accès croisés ; exécuter les tests devis, auth et la suite.
- Applique également les contrôles transversaux du cadre commun pertinents à cette modification.
- Actualise la ligne 05 de docs/prompts/audit-2026-10-20/JOURNAL.md avec les résultats réels et les limites.

Termine par un compte rendu clair et les preuves utiles. Ne commence aucune autre correction. Ne fais ni commit ni push ; attends le prompt B de cette fiche.
````

## Prompt B — commit et push de cette seule correction

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Publie uniquement la correction 05 — Permettre au visiteur de retrouver sa demande en sécurité, réalisée avec le prompt A de docs/prompts/audit-2026-10-20/05-rattachement-demande-invite.md.

Lis le cadre commun et son protocole « Publier B ». Vérifie que A est validé, que les dépendances et checkpoints précédents sont satisfaits et que le code n’a pas changé sans revalidation. Une recette externe non effectuée doit rester signalée ; ne transforme pas une limite en résultat positif.

Relis le diff et les fichiers non suivis. Sélectionne explicitement les seuls fichiers de cette correction, sa documentation, sa ligne de journal et les assets compilés nécessaires. Écarte notamment le changement de mode préexistant d’artisan, les secrets, bases et données privées. Ne mélange pas une autre correction ou l’archive initiale de l’audit dans ce commit.

Crée un commit descriptif, par défaut :
fix(quotes): add secure guest request recovery

Pousse normalement sur origin, vers la branche de travail autorisée de même nom (main si le lot est toujours sur main). Vérifie le SHA publié sur https://github.com/TIKMOS/tikmos.com et rapporte les checks CI réellement accessibles.

Si le push échoue, résous uniquement le blocage autorisé sans force-push ni perte de travail ; sinon explique précisément pourquoi la correction reste non publiée. Si le commit existe déjà, vérifie-le plutôt que créer un doublon.

Termine avec le numéro 05, la branche, le SHA, le lien du commit GitHub, les validations et les limites. Arrête-toi : ne commence pas la correction suivante.
````
