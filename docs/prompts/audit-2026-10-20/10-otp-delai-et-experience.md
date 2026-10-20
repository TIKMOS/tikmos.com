# 10 — Sécuriser et clarifier le challenge 2FA

Priorité : **P2** · Référence : **Anti-abus OTP + UX connexion**.

Dépendances : aucune dépendance technique supplémentaire.

Lire le [cadre commun](CADRE-COMMUN.md). Utiliser A, vérifier le résultat, puis utiliser B. La publication vérifiée précède le passage à la suite.

## Prompt A — réaliser et valider

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Lis docs/prompts/audit-2026-10-20/CADRE-COMMUN.md, les instructions applicables et docs/audits/2026-10-20/README.md. Exécute uniquement la correction 10 — Sécuriser et clarifier le challenge 2FA.

Constat de référence : Anti-abus OTP + UX connexion. Priorité : P2.
Dépendances techniques : Aucune dépendance technique supplémentaire ; respecter les checkpoints de l’ordre choisi.

Avant de modifier, confirme l’état actuel du défaut et les checkpoints Git précédents. Les points d’entrée ci-dessous sont à vérifier dans le dépôt :
- `app/Services/TwoFactorService.php`
- `resources/views/livewire/pages/auth/two-factor-challenge.blade.php`
- `tests/Feature/Auth/TwoFactorTest.php`

Travail limité à cette fiche :
1. Ajouter un délai serveur entre renvois et un plafond global adapté ; un simple renvoi ne doit pas permettre des essais illimités.
2. Décocher par défaut la confiance de 30 jours ; distinguer code incorrect, expiré et blocage temporaire.
3. Effacer les erreurs périmées après renvoi et afficher un délai de réessai accessible.
4. Garder le code de développement strictement réservé à APP_ENV=local et la 2FA active pour les rôles concernés.

Critères d’acceptation :
- Les appels directs respectent les limites de renvoi et de validation.
- Un nouveau code invalide l’ancien selon une règle documentée.
- La connexion légitime, les appareils déjà approuvés et le mode production restent fonctionnels.

Validation attendue :
- Tester limites, expiration, renvoi, confiance opt-in et mode production.
- Vérifier le parcours navigateur avec cache persistant sur base isolée, puis exécuter la suite.
- Applique également les contrôles transversaux du cadre commun pertinents à cette modification.
- Actualise la ligne 10 de docs/prompts/audit-2026-10-20/JOURNAL.md avec les résultats réels et les limites.

Termine par un compte rendu clair et les preuves utiles. Ne commence aucune autre correction. Ne fais ni commit ni push ; attends le prompt B de cette fiche.
````

## Prompt B — commit et push de cette seule correction

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Publie uniquement la correction 10 — Sécuriser et clarifier le challenge 2FA, réalisée avec le prompt A de docs/prompts/audit-2026-10-20/10-otp-delai-et-experience.md.

Lis le cadre commun et son protocole « Publier B ». Vérifie que A est validé, que les dépendances et checkpoints précédents sont satisfaits et que le code n’a pas changé sans revalidation. Une recette externe non effectuée doit rester signalée ; ne transforme pas une limite en résultat positif.

Relis le diff et les fichiers non suivis. Sélectionne explicitement les seuls fichiers de cette correction, sa documentation, sa ligne de journal et les assets compilés nécessaires. Écarte notamment le changement de mode préexistant d’artisan, les secrets, bases et données privées. Ne mélange pas une autre correction ou l’archive initiale de l’audit dans ce commit.

Crée un commit descriptif, par défaut :
fix(auth): improve OTP throttling and challenge UX

Pousse normalement sur origin, vers la branche de travail autorisée de même nom (main si le lot est toujours sur main). Vérifie le SHA publié sur https://github.com/TIKMOS/tikmos.com et rapporte les checks CI réellement accessibles.

Si le push échoue, résous uniquement le blocage autorisé sans force-push ni perte de travail ; sinon explique précisément pourquoi la correction reste non publiée. Si le commit existe déjà, vérifie-le plutôt que créer un doublon.

Termine avec le numéro 10, la branche, le SHA, le lien du commit GitHub, les validations et les limites. Arrête-toi : ne commence pas la correction suivante.
````
