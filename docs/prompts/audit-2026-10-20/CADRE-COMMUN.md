# Cadre commun — corrections de l’audit du 20 octobre 2026

## Contrat de travail

Une fiche correspond à une intervention bornée. L’utilisateur choisit **A — correction et validation**, puis **B — commit et push**. Après B, on s’arrête. Le fait qu’une fiche suivante existe n’autorise pas à l’exécuter.

Projet : `C:/Users/PC/Documents/Projet_claude_code/tikmos.com`. GitHub : https://github.com/TIKMOS/tikmos.com.
La branche constatée à la préparation de ces prompts est `main`. Respecter la branche courante autorisée ; ne pas changer de branche ou créer un historique parallèle sans nécessité.

Lire les instructions AGENTS.md applicables, le [prompt maître existant](../00-prompt-maitre.md), la documentation métier utile et le [rapport d’audit](../../audits/2026-10-20/README.md). Ces fiches ciblent la remédiation de l’état actuel, pas la reconstruction complète des anciens lots.

Deux précisions issues de l’audit corrigent les hypothèses anciennes : la boutique actuelle concerne équipements et accessoires ; `gold-600` n’assure pas automatiquement un contraste AA. Mesurer les contrastes réels et documenter les changements de tokens.

## Avant A

1. Vérifier la racine Git exacte, branche, remotes, statut, diff indexé et non indexé, fichiers non suivis et derniers commits.
2. Le checkpoint 00 publie séparément l’audit et cette bibliothèque. Une correction suivante suppose la publication vérifiée de la précédente, sauf étape explicitement différée par l’utilisateur. Les dépendances indiquées dans les fiches sont des dépendances techniques supplémentaires.
3. Identifier les modifications préexistantes. À la préparation de ce plan, `artisan` présente un changement de mode Windows ; ne pas le confondre avec du code à corriger et ne pas l’inclure automatiquement.
4. Vérifier les conditions du défaut dans le code actuel : les chemins sont des points d’entrée à confirmer, pas une liste exhaustive de fichiers autorisés. Si le défaut est déjà corrigé, contrôler sa validation et sa publication plutôt que refaire le travail.
5. Faire un plan court limité à la fiche. Choisir les détails techniques raisonnables, documenter les décisions et ne demander que les informations réellement indispensables.

## Réaliser et valider A

- Garder Laravel/Livewire/Volt, les composants `x-tk.*`, le CMS et les valeurs historiques. Pas de mise à niveau générale des dépendances, de nouveau service ou de refonte d’un autre module.
- Pour un défaut métier, ajouter dans `tests/` un vrai test qui échoue avec le défaut puis passe après correction. Les cinq tests sous `docs/audits/` sont des preuves historiques qui attendent le comportement défaillant ; leur succès n’est **jamais** une preuve de correction. Conserver l’audit historique.
- Réserver les tests nouveaux aux comportements et contrats utiles. Pour les modifications visuelles simples, privilégier recette navigateur, clavier, inspection DOM et captures plutôt que tests de recopie des classes ou du texte.
- Exécuter les tests ciblés puis la suite de non-régression avant de clore un lot. Larastan est requis pour les changements PHP concernés. Contrôler Pint sur le PHP modifié sans reformater des fichiers étrangers ; distinguer les erreurs antérieures de celles introduites.
- Utiliser une base SQLite en mémoire ou une copie locale nommée pour les scénarios avec écritures. Intercepter notifications et paiements. Aucune migration destructive, purge, modification de compte réel ou transaction réelle.
- Ajouter une **nouvelle migration** quand le schéma évolue ; ne pas réécrire une migration déjà déployée pour éviter la migration de l’existant. Prévoir un traitement explicite des données incompatibles.
- Pour l’UI, vérifier les largeurs pertinentes entre 360 et 1440 px, les états vide/chargement/erreur, le clavier et les erreurs console. Restaurer les dimensions du navigateur et arrêter les seuls serveurs temporaires créés.
- Réutiliser les commandes et outils disponibles. Vérifier les protocoles externes dans leurs sources officielles ; ne pas prétendre avoir testé MySQL, SMTP, sandbox ou Lighthouse si ces validations n’ont pas eu lieu.
- Les contenus et assets réels doivent être fournis ou déjà disponibles. Ne pas inventer photos de produits, personnes, garanties, chiffres ou témoignages. Avancer sur la partie indépendante et préciser les données manquantes ; un état partiel n’est pas un état validé.
- Ne pas ajouter de secret au dépôt. Les médias métier passent par le mécanisme prévu ; ne pas pousser une base SQLite, un export de clients ou des fichiers de stockage privés pour publier un contenu.
- Mettre à jour uniquement la ligne de la fiche dans le [journal](JOURNAL.md), avec preuves et limites. Avant commit : « Validé localement » ou « Partiel », jamais « Publié » par anticipation.
- Fin de A : expliquer le comportement corrigé, fichiers, commandes et résultats réellement obtenus, captures utiles et limites. **A ne fait ni commit ni push et n’exécute pas B.**

## Actifs frontend versionnés

Le projet versionne `public/build` pour son déploiement sans Node. Une modification CSS/JS doit inclure un build cohérent et son manifeste dans le commit de la correction. Un build de contrôle peut d’abord cibler un dossier isolé ; vérifier ensuite le bundle destiné à être publié et les références de son manifeste.

Ne pas écraser des changements de build préexistants. Ne pas inclure `public/hot`, les caches de développement, `node_modules` ou `vendor`. Éviter qu’un script de build embarque un `VITE_*` sensible.

## Publier B

B est l’autorisation explicite de publier **cette correction** sur GitHub ; aucun déploiement du site n’est inclus.

1. Relire le compte rendu de A, le journal, l’état actuel de Git et les changements intervenus depuis les tests.
2. Vérifier les contrôles manquants ou devenus invalides ; ne pas répéter les contrôles inchangés sans raison. Un défaut non corrigé ou un test requis en échec interdit de déclarer la fiche validée.
3. Examiner les fichiers et les lignes effectivement destinés au commit. Inclure le journal et la documentation nécessaires à ce lot. Écarter tout travail préexistant sans rapport et tout contenu privé.
4. Ajouter explicitement les chemins ou hunks concernés. **Pas de `git add .`, `git add -A`, `git commit -a` ou commit d’un index étranger au lot.** Ne pas vider un index préexistant pour simplifier.
5. Vérifier le diff indexé et ses espaces ; créer le commit annoncé. Si rien ne reste à committer, chercher le commit existant et vérifier sa publication ; pas de commit vide.
6. Vérifier qu’`origin` cible TIKMOS/tikmos.com. Faire un fetch et examiner les commits qui seraient poussés. Ne pas publier des commits étrangers à la demande.
7. Pousser normalement la branche de travail vers la branche de même nom sur `origin`. La branche initiale est `main` ; une branche différente doit être nommée explicitement dans le bilan. **Aucun force-push, reset destructif, auto-stash ou réécriture d’historique pour contourner un refus.**
8. Si une divergence bloque l’envoi, intégrer les changements sans écraser le travail existant ; résoudre les conflits compréhensibles de ce lot. Si des changements étrangers rendent l’opération ambiguë, expliquer le point bloquant et demander seulement l’information nécessaire.
9. Vérifier le SHA effectivement présent sur GitHub. Normalement la référence distante égale le HEAD publié ; si un tiers a poussé ensuite, vérifier que le commit de la correction est bien ancêtre de la branche distante.
10. Consulter les checks CI s’ils sont disponibles. Un résultat absent ou en attente reste signalé comme tel ; un échec connu doit être traité ou expliqué avant de considérer la validation complète.

B se termine par un reçu : **fiche, branche, SHA du commit, référence distante vérifiée, lien GitHub, état des tests/CI et limites**. Ne pas modifier ensuite un fichier suivi uniquement pour y écrire le SHA du commit qui le contient : cela créerait un changement non publié. Le reçu final et Git font foi pour la publication ; le journal versionné décrit la validation locale.

**Ne pas commencer la fiche suivante.** Si le push échoue, dire « non publié » ; ne pas annoncer de publication sur la seule réussite du commit local.

## Commandes de référence à adapter au lot

Depuis la racine du projet, les tests doivent cibler l’environnement de test isolé. Vérifier le `phpunit.xml` et les variables héritées avant toute exécution.

```powershell
php artisan test --compact
php -d memory_limit=1G vendor/bin/phpstan analyse --no-progress --memory-limit=1G
npm run build -- --outDir storage/framework/testing/verification-build
git status --short
git diff --check
git diff --cached --check
```

Le dernier build de contrôle ci-dessus ne remplace pas le bundle `public/build` à publier quand il change. Pour Pint et les tests ciblés, choisir les chemins réellement modifiés. Ne pas utiliser `migrate:fresh` sur la base locale courante.
