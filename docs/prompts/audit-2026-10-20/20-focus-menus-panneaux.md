# 20 — Gérer le focus des menus et panneaux

Priorité : **P2** · Référence : **Accessibilité : focus et sidebar**.

Dépendances : aucune dépendance technique supplémentaire.

Lire le [cadre commun](CADRE-COMMUN.md). Utiliser A, vérifier le résultat, puis utiliser B. La publication vérifiée précède le passage à la suite.

## Prompt A — réaliser et valider

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Lis docs/prompts/audit-2026-10-20/CADRE-COMMUN.md, les instructions applicables et docs/audits/2026-10-20/README.md. Exécute uniquement la correction 20 — Gérer le focus des menus et panneaux.

Constat de référence : Accessibilité : focus et sidebar. Priorité : P2.
Dépendances techniques : Aucune dépendance technique supplémentaire ; respecter les checkpoints de l’ordre choisi.

Avant de modifier, confirme l’état actuel du défaut et les checkpoints Git précédents. Les points d’entrée ci-dessous sont à vérifier dans le dépôt :
- `resources/views/components/layouts/public.blade.php`
- `resources/views/components/layouts/admin.blade.php`
- `resources/views/livewire/pages/admin/services.blade.php`
- `resources/views/components/tk/nav-bottom.blade.php`

Travail limité à cette fiche :
1. Rendre les liens de la sidebar mobile fermée inaccessibles au focus sans masquer la sidebar desktop.
2. Ajouter aria-expanded/aria-controls au menu public et aux déclencheurs concernés.
3. Donner aux panneaux réellement modaux un rôle approprié, un nom, une gestion de focus, fermeture Échap et retour au déclencheur.
4. Ajouter un lien d’évitement vers main et réutiliser le même comportement sur les panneaux similaires sans réécriture du CMS.

Critères d’acceptation :
- Tab n’atteint pas une sidebar hors écran ou le contenu derrière un vrai modal.
- Ouverture, fermeture, Échap et restitution du focus fonctionnent sans piège clavier.
- Les changements de breakpoint et wire:navigate ne laissent pas la page bloquée.

Validation attendue :
- Recette clavier aux largeurs 360, 768 et 1440 px et après navigation Livewire.
- Vérifier les panneaux service/commande et les tests existants impactés.
- Applique également les contrôles transversaux du cadre commun pertinents à cette modification.
- Actualise la ligne 20 de docs/prompts/audit-2026-10-20/JOURNAL.md avec les résultats réels et les limites.

Termine par un compte rendu clair et les preuves utiles. Ne commence aucune autre correction. Ne fais ni commit ni push ; attends le prompt B de cette fiche.
````

## Prompt B — commit et push de cette seule correction

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Publie uniquement la correction 20 — Gérer le focus des menus et panneaux, réalisée avec le prompt A de docs/prompts/audit-2026-10-20/20-focus-menus-panneaux.md.

Lis le cadre commun et son protocole « Publier B ». Vérifie que A est validé, que les dépendances et checkpoints précédents sont satisfaits et que le code n’a pas changé sans revalidation. Une recette externe non effectuée doit rester signalée ; ne transforme pas une limite en résultat positif.

Relis le diff et les fichiers non suivis. Sélectionne explicitement les seuls fichiers de cette correction, sa documentation, sa ligne de journal et les assets compilés nécessaires. Écarte notamment le changement de mode préexistant d’artisan, les secrets, bases et données privées. Ne mélange pas une autre correction ou l’archive initiale de l’audit dans ce commit.

Crée un commit descriptif, par défaut :
fix(a11y): manage navigation and dialog focus

Pousse normalement sur origin, vers la branche de travail autorisée de même nom (main si le lot est toujours sur main). Vérifie le SHA publié sur https://github.com/TIKMOS/tikmos.com et rapporte les checks CI réellement accessibles.

Si le push échoue, résous uniquement le blocage autorisé sans force-push ni perte de travail ; sinon explique précisément pourquoi la correction reste non publiée. Si le commit existe déjà, vérifie-le plutôt que créer un doublon.

Termine avec le numéro 20, la branche, le SHA, le lien du commit GitHub, les validations et les limites. Arrête-toi : ne commence pas la correction suivante.
````
