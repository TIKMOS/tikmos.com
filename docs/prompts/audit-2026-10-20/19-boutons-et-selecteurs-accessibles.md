# 19 — Nommer les actions à icône et le tri du catalogue

Priorité : **P2** · Référence : **Accessibilité : contrôles sans nom**.

Dépendances : aucune dépendance technique supplémentaire.

Lire le [cadre commun](CADRE-COMMUN.md). Utiliser A, vérifier le résultat, puis utiliser B. La publication vérifiée précède le passage à la suite.

## Prompt A — réaliser et valider

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Lis docs/prompts/audit-2026-10-20/CADRE-COMMUN.md, les instructions applicables et docs/audits/2026-10-20/README.md. Exécute uniquement la correction 19 — Nommer les actions à icône et le tri du catalogue.

Constat de référence : Accessibilité : contrôles sans nom. Priorité : P2.
Dépendances techniques : Aucune dépendance technique supplémentaire ; respecter les checkpoints de l’ordre choisi.

Avant de modifier, confirme l’état actuel du défaut et les checkpoints Git précédents. Les points d’entrée ci-dessous sont à vérifier dans le dépôt :
- `resources/views/components/tk/nav-bottom.blade.php`
- `resources/views/livewire/pages/public/shop-index.blade.php`
- `resources/views/livewire/pages/admin/services.blade.php`
- `resources/views/components/tk/editor.blade.php`

Travail limité à cette fiche :
1. Donner un nom accessible au lien + central et au sélecteur de tri.
2. Nommer les actions modifier/supprimer avec leur contexte et les boutons de fermeture dans les écrans concernés.
3. Vérifier les autres listes admin utilisant le même schéma ; éviter des libellés dupliqués inutiles ou contradictoires.
4. Fournir des cibles tactiles confortables sans modifier l’action métier ni déclencher de suppression pendant la recette.

Critères d’acceptation :
- Aucun contrôle inspecté n’est présenté comme un bouton/lien sans nom.
- Le nom distingue les actions et l’objet concerné.
- Le tri, l’édition et l’ouverture/fermeture restent fonctionnels.

Validation attendue :
- Inspection des noms accessibles et recette clavier, mobile et desktop.
- Ne pas créer de tests qui ne font que recopier les libellés ; utiliser les tests fonctionnels existants.
- Applique également les contrôles transversaux du cadre commun pertinents à cette modification.
- Actualise la ligne 19 de docs/prompts/audit-2026-10-20/JOURNAL.md avec les résultats réels et les limites.

Termine par un compte rendu clair et les preuves utiles. Ne commence aucune autre correction. Ne fais ni commit ni push ; attends le prompt B de cette fiche.
````

## Prompt B — commit et push de cette seule correction

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Publie uniquement la correction 19 — Nommer les actions à icône et le tri du catalogue, réalisée avec le prompt A de docs/prompts/audit-2026-10-20/19-boutons-et-selecteurs-accessibles.md.

Lis le cadre commun et son protocole « Publier B ». Vérifie que A est validé, que les dépendances et checkpoints précédents sont satisfaits et que le code n’a pas changé sans revalidation. Une recette externe non effectuée doit rester signalée ; ne transforme pas une limite en résultat positif.

Relis le diff et les fichiers non suivis. Sélectionne explicitement les seuls fichiers de cette correction, sa documentation, sa ligne de journal et les assets compilés nécessaires. Écarte notamment le changement de mode préexistant d’artisan, les secrets, bases et données privées. Ne mélange pas une autre correction ou l’archive initiale de l’audit dans ce commit.

Crée un commit descriptif, par défaut :
fix(a11y): name icon actions and catalogue sorting

Pousse normalement sur origin, vers la branche de travail autorisée de même nom (main si le lot est toujours sur main). Vérifie le SHA publié sur https://github.com/TIKMOS/tikmos.com et rapporte les checks CI réellement accessibles.

Si le push échoue, résous uniquement le blocage autorisé sans force-push ni perte de travail ; sinon explique précisément pourquoi la correction reste non publiée. Si le commit existe déjà, vérifie-le plutôt que créer un doublon.

Termine avec le numéro 19, la branche, le SHA, le lien du commit GitHub, les validations et les limites. Arrête-toi : ne commence pas la correction suivante.
````
