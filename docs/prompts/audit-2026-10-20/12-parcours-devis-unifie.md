# 12 — Unifier le bouton Demander un devis et le suivi client

Priorité : **P1** · Référence : **UX : CTA contact vs ServiceRequest**.

Dépendances : [05](05-rattachement-demande-invite.md).

Lire le [cadre commun](CADRE-COMMUN.md). Utiliser A, vérifier le résultat, puis utiliser B. La publication vérifiée précède le passage à la suite.

## Prompt A — réaliser et valider

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Lis docs/prompts/audit-2026-10-20/CADRE-COMMUN.md, les instructions applicables et docs/audits/2026-10-20/README.md. Exécute uniquement la correction 12 — Unifier le bouton Demander un devis et le suivi client.

Constat de référence : UX : CTA contact vs ServiceRequest. Priorité : P1.
Dépendances techniques : 05 — Permettre au visiteur de retrouver sa demande en sécurité

Avant de modifier, confirme l’état actuel du défaut et les checkpoints Git précédents. Les points d’entrée ci-dessous sont à vérifier dans le dépôt :
- `resources/views/components/layouts/public.blade.php`
- `resources/views/home.blade.php`
- `resources/views/livewire/pages/public/contact.blade.php`
- `resources/views/livewire/pages/public/services-show.blade.php`
- `app/Models/Lead.php`
- `app/Models/ServiceRequest.php`

Travail limité à cette fiche :
1. Faire mener tous les CTA Demander un devis à une demande suivie ServiceRequest, avec un choix de service ou un besoin sur mesure.
2. Conserver un contact général qui crée un Lead et le nommer comme tel ; ne pas dupliquer une même soumission dans deux pipelines.
3. Réutiliser les composants, validations, protections et mécanismes de rattachement déjà livrés.
4. Préserver les messages et demandes historiques ; rendre la référence et la prochaine étape visibles après envoi.

Critères d’acceptation :
- Une demande de devis depuis l’accueil rejoint le pipeline admin et le suivi légitime du client.
- Un message général reste un message, clairement distinct d’un devis.
- Le parcours fonctionne pour client connecté et visiteur, sans perte de contenu.

Validation attendue :
- Tester les deux intentions et l’absence de doublon.
- Vérifier accueil → devis → suivi dans le navigateur ; exécuter ServicesDevisTest et la suite.
- Applique également les contrôles transversaux du cadre commun pertinents à cette modification.
- Actualise la ligne 12 de docs/prompts/audit-2026-10-20/JOURNAL.md avec les résultats réels et les limites.

Termine par un compte rendu clair et les preuves utiles. Ne commence aucune autre correction. Ne fais ni commit ni push ; attends le prompt B de cette fiche.
````

## Prompt B — commit et push de cette seule correction

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Publie uniquement la correction 12 — Unifier le bouton Demander un devis et le suivi client, réalisée avec le prompt A de docs/prompts/audit-2026-10-20/12-parcours-devis-unifie.md.

Lis le cadre commun et son protocole « Publier B ». Vérifie que A est validé, que les dépendances et checkpoints précédents sont satisfaits et que le code n’a pas changé sans revalidation. Une recette externe non effectuée doit rester signalée ; ne transforme pas une limite en résultat positif.

Relis le diff et les fichiers non suivis. Sélectionne explicitement les seuls fichiers de cette correction, sa documentation, sa ligne de journal et les assets compilés nécessaires. Écarte notamment le changement de mode préexistant d’artisan, les secrets, bases et données privées. Ne mélange pas une autre correction ou l’archive initiale de l’audit dans ce commit.

Crée un commit descriptif, par défaut :
fix(ux): unify tracked quote request entry points

Pousse normalement sur origin, vers la branche de travail autorisée de même nom (main si le lot est toujours sur main). Vérifie le SHA publié sur https://github.com/TIKMOS/tikmos.com et rapporte les checks CI réellement accessibles.

Si le push échoue, résous uniquement le blocage autorisé sans force-push ni perte de travail ; sinon explique précisément pourquoi la correction reste non publiée. Si le commit existe déjà, vérifie-le plutôt que créer un doublon.

Termine avec le numéro 12, la branche, le SHA, le lien du commit GitHub, les validations et les limites. Arrête-toi : ne commence pas la correction suivante.
````
