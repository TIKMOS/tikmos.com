# 35 — Vérifier la PWA sans l’activer en production

Priorité : **Optionnel** · Référence : **Module PWA : recette différée**.

Dépendances : aucune dépendance technique supplémentaire.

Lire le [cadre commun](CADRE-COMMUN.md). Utiliser A, vérifier le résultat, puis utiliser B. La publication vérifiée précède le passage à la suite.

## Prompt A — réaliser et valider

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Lis docs/prompts/audit-2026-10-20/CADRE-COMMUN.md, les instructions applicables et docs/audits/2026-10-20/README.md. Exécute uniquement la correction 35 — Vérifier la PWA sans l’activer en production.

Constat de référence : Module PWA : recette différée. Priorité : Optionnel.
Dépendances techniques : Aucune dépendance technique supplémentaire ; respecter les checkpoints de l’ordre choisi.

Avant de modifier, confirme l’état actuel du défaut et les checkpoints Git précédents. Les points d’entrée ci-dessous sont à vérifier dans le dépôt :
- `public/sw.js`
- `public/manifest.webmanifest`
- `resources/js/app.js`
- `resources/views/partials/pwa.blade.php`
- `resources/views/components/tk/install-button.blade.php`
- `resources/views/livewire/pages/app/compte.blade.php`
- `tests/Feature/PwaTest.php`

Travail limité à cette fiche :
1. Conserver la décision actuelle de fonctionnalité désactivée sur l’environnement habituel.
2. Sur une copie locale isolée, vérifier installation, mise à jour du service worker, mode hors-ligne et cache des assets.
3. Corriger les invitations à installer affichées lorsque la fonctionnalité est désactivée, si ce comportement est confirmé.
4. Éviter tout cache de réponses privées, données de compte ou pages de paiement ; documenter les limites des navigateurs testés.

Critères d’acceptation :
- PWA désactivée : pas de promesse d’installation trompeuse.
- PWA active en recette : repli hors-ligne compréhensible et mise à jour maîtrisée.
- Aucune donnée privée n’est exposée à un autre utilisateur via le cache.

Validation attendue :
- PwaTest et recette isolée en ligne/hors-ligne sur les navigateurs accessibles.
- Aucune activation live ou installabilité non observée n’est revendiquée.
- Applique également les contrôles transversaux du cadre commun pertinents à cette modification.
- Actualise la ligne 35 de docs/prompts/audit-2026-10-20/JOURNAL.md avec les résultats réels et les limites.

Termine par un compte rendu clair et les preuves utiles. Ne commence aucune autre correction. Ne fais ni commit ni push ; attends le prompt B de cette fiche.
````

## Prompt B — commit et push de cette seule correction

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Publie uniquement la correction 35 — Vérifier la PWA sans l’activer en production, réalisée avec le prompt A de docs/prompts/audit-2026-10-20/35-recette-pwa.md.

Lis le cadre commun et son protocole « Publier B ». Vérifie que A est validé, que les dépendances et checkpoints précédents sont satisfaits et que le code n’a pas changé sans revalidation. Une recette externe non effectuée doit rester signalée ; ne transforme pas une limite en résultat positif.

Relis le diff et les fichiers non suivis. Sélectionne explicitement les seuls fichiers de cette correction, sa documentation, sa ligne de journal et les assets compilés nécessaires. Écarte notamment le changement de mode préexistant d’artisan, les secrets, bases et données privées. Ne mélange pas une autre correction ou l’archive initiale de l’audit dans ce commit.

Crée un commit descriptif, par défaut :
fix(pwa): align feature prompts and offline behavior

Pousse normalement sur origin, vers la branche de travail autorisée de même nom (main si le lot est toujours sur main). Vérifie le SHA publié sur https://github.com/TIKMOS/tikmos.com et rapporte les checks CI réellement accessibles.

Si le push échoue, résous uniquement le blocage autorisé sans force-push ni perte de travail ; sinon explique précisément pourquoi la correction reste non publiée. Si le commit existe déjà, vérifie-le plutôt que créer un doublon.

Termine avec le numéro 35, la branche, le SHA, le lien du commit GitHub, les validations et les limites. Arrête-toi : ne commence pas la correction suivante.
````
