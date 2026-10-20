# 18 — Relier les libellés, aides et erreurs aux champs

Priorité : **P2** · Référence : **Accessibilité : formulaires**.

Dépendances : aucune dépendance technique supplémentaire.

Lire le [cadre commun](CADRE-COMMUN.md). Utiliser A, vérifier le résultat, puis utiliser B. La publication vérifiée précède le passage à la suite.

## Prompt A — réaliser et valider

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Lis docs/prompts/audit-2026-10-20/CADRE-COMMUN.md, les instructions applicables et docs/audits/2026-10-20/README.md. Exécute uniquement la correction 18 — Relier les libellés, aides et erreurs aux champs.

Constat de référence : Accessibilité : formulaires. Priorité : P2.
Dépendances techniques : Aucune dépendance technique supplémentaire ; respecter les checkpoints de l’ordre choisi.

Avant de modifier, confirme l’état actuel du défaut et les checkpoints Git précédents. Les points d’entrée ci-dessous sont à vérifier dans le dépôt :
- `resources/views/components/tk/field.blade.php`
- `resources/views/components/tk/input.blade.php`
- `resources/views/components/tk/phone.blade.php`
- `resources/views/livewire/pages/public/checkout.blade.php`
- `resources/views/livewire/pages/app/compte.blade.php`

Travail limité à cette fiche :
1. Définir une convention d’identifiants uniques entre field et son contrôle, y compris dans les listes et panneaux répétés.
2. Associer label/for, aide et erreur par aria-describedby ; exposer aria-invalid et l’état requis lorsque pertinent.
3. Appliquer la correction au checkout, téléphone du profil, demandes et formulaires admin concernés sans casser wire:model/entangle.
4. Conserver une API de composant simple ; éviter les identifiants générés instables entre rendus Livewire.

Critères d’acceptation :
- Chaque contrôle concerné possède un nom accessible correspondant au libellé visible.
- Les erreurs sont reliées au bon champ, sans identifiant dupliqué.
- La saisie, validation, sélection d’indicatif et sauvegarde continuent de fonctionner.

Validation attendue :
- Inspection DOM et parcours clavier des formulaires ciblés.
- Tests de composants/comportement uniquement si nécessaires ; exécuter les tests de formulaires existants.
- Applique également les contrôles transversaux du cadre commun pertinents à cette modification.
- Actualise la ligne 18 de docs/prompts/audit-2026-10-20/JOURNAL.md avec les résultats réels et les limites.

Termine par un compte rendu clair et les preuves utiles. Ne commence aucune autre correction. Ne fais ni commit ni push ; attends le prompt B de cette fiche.
````

## Prompt B — commit et push de cette seule correction

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Publie uniquement la correction 18 — Relier les libellés, aides et erreurs aux champs, réalisée avec le prompt A de docs/prompts/audit-2026-10-20/18-libelles-champs-accessibles.md.

Lis le cadre commun et son protocole « Publier B ». Vérifie que A est validé, que les dépendances et checkpoints précédents sont satisfaits et que le code n’a pas changé sans revalidation. Une recette externe non effectuée doit rester signalée ; ne transforme pas une limite en résultat positif.

Relis le diff et les fichiers non suivis. Sélectionne explicitement les seuls fichiers de cette correction, sa documentation, sa ligne de journal et les assets compilés nécessaires. Écarte notamment le changement de mode préexistant d’artisan, les secrets, bases et données privées. Ne mélange pas une autre correction ou l’archive initiale de l’audit dans ce commit.

Crée un commit descriptif, par défaut :
fix(a11y): associate form labels and validation feedback

Pousse normalement sur origin, vers la branche de travail autorisée de même nom (main si le lot est toujours sur main). Vérifie le SHA publié sur https://github.com/TIKMOS/tikmos.com et rapporte les checks CI réellement accessibles.

Si le push échoue, résous uniquement le blocage autorisé sans force-push ni perte de travail ; sinon explique précisément pourquoi la correction reste non publiée. Si le commit existe déjà, vérifie-le plutôt que créer un doublon.

Termine avec le numéro 18, la branche, le SHA, le lien du commit GitHub, les validations et les limites. Arrête-toi : ne commence pas la correction suivante.
````
