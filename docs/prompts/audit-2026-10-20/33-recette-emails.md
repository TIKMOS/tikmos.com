# 33 — Valider les emails sans contacter de vrais destinataires par défaut

Priorité : **Recette** · Référence : **Exploitation : SMTP non validé**.

Dépendances : [07](07-reprise-paiement-reservation.md), [10](10-otp-delai-et-experience.md), [12](12-parcours-devis-unifie.md).

Lire le [cadre commun](CADRE-COMMUN.md). Utiliser A, vérifier le résultat, puis utiliser B. La publication vérifiée précède le passage à la suite.

## Prompt A — réaliser et valider

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Lis docs/prompts/audit-2026-10-20/CADRE-COMMUN.md, les instructions applicables et docs/audits/2026-10-20/README.md. Exécute uniquement la correction 33 — Valider les emails sans contacter de vrais destinataires par défaut.

Constat de référence : Exploitation : SMTP non validé. Priorité : Recette.
Dépendances techniques : 07 — Gérer l’abandon et la reprise du paiement; 10 — Sécuriser et clarifier le challenge 2FA; 12 — Unifier le bouton Demander un devis et le suivi client

Avant de modifier, confirme l’état actuel du défaut et les checkpoints Git précédents. Les points d’entrée ci-dessous sont à vérifier dans le dépôt :
- `config/mail.php`
- `app/Notifications`
- `routes/console.php`
- `docs/DEPLOIEMENT.md`
- `tests/Feature/NotificationsTest.php`

Travail limité à cette fiche :
1. Vérifier templates, liens, destinataires calculés, queue et erreurs avec un transport de capture local et des comptes factices.
2. Documenter le fonctionnement du worker/cron et le choix sync/database adapté à l’environnement.
3. Ne pas afficher de mots de passe SMTP ni envoyer de message à un contact réel ; une livraison externe exige un destinataire de test explicitement autorisé.
4. Préparer la recette SMTP réelle si la configuration manque, en distinguant rendu correct, message émis et délivrabilité reçue.

Critères d’acceptation :
- Les emails de devis, commandes, reset et 2FA sont cohérents et contiennent des liens exploitables.
- Les jobs ne restent pas bloqués silencieusement en configuration normale.
- Aucune livraison SMTP réelle non effectuée n’est déclarée validée.

Validation attendue :
- NotificationsTest et parcours isolés de queue/transport.
- Rapport de recette précisant ce qui reste dépendant d’un compte ou destinataire autorisé.
- Applique également les contrôles transversaux du cadre commun pertinents à cette modification.
- Actualise la ligne 33 de docs/prompts/audit-2026-10-20/JOURNAL.md avec les résultats réels et les limites.

Termine par un compte rendu clair et les preuves utiles. Ne commence aucune autre correction. Ne fais ni commit ni push ; attends le prompt B de cette fiche.
````

## Prompt B — commit et push de cette seule correction

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Publie uniquement la correction 33 — Valider les emails sans contacter de vrais destinataires par défaut, réalisée avec le prompt A de docs/prompts/audit-2026-10-20/33-recette-emails.md.

Lis le cadre commun et son protocole « Publier B ». Vérifie que A est validé, que les dépendances et checkpoints précédents sont satisfaits et que le code n’a pas changé sans revalidation. Une recette externe non effectuée doit rester signalée ; ne transforme pas une limite en résultat positif.

Relis le diff et les fichiers non suivis. Sélectionne explicitement les seuls fichiers de cette correction, sa documentation, sa ligne de journal et les assets compilés nécessaires. Écarte notamment le changement de mode préexistant d’artisan, les secrets, bases et données privées. Ne mélange pas une autre correction ou l’archive initiale de l’audit dans ce commit.

Crée un commit descriptif, par défaut :
test(mail): document and verify transactional delivery flow

Pousse normalement sur origin, vers la branche de travail autorisée de même nom (main si le lot est toujours sur main). Vérifie le SHA publié sur https://github.com/TIKMOS/tikmos.com et rapporte les checks CI réellement accessibles.

Si le push échoue, résous uniquement le blocage autorisé sans force-push ni perte de travail ; sinon explique précisément pourquoi la correction reste non publiée. Si le commit existe déjà, vérifie-le plutôt que créer un doublon.

Termine avec le numéro 33, la branche, le SHA, le lien du commit GitHub, les validations et les limites. Arrête-toi : ne commence pas la correction suivante.
````
