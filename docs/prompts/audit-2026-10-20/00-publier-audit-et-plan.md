# 00 — Publier l’audit et le plan avant les corrections

Cette étape archive les documents déjà préparés. Elle n’applique aucune correction applicative. Elle fournit une base GitHub identifiable avant 01.

## Prompt de publication initiale

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Lis docs/prompts/audit-2026-10-20/CADRE-COMMUN.md, puis prépare et publie uniquement la documentation de l’audit UI/UX/fonctionnel et sa bibliothèque de prompts.

1. Vérifie la racine Git, la branche courante, origin et les fichiers déjà indexés. Préserve les modifications étrangères, notamment le changement de mode d’artisan.
2. Relis docs/audits/2026-10-20 et docs/prompts/audit-2026-10-20, ainsi que les liens ajoutés dans docs/prompts/README.md et docs/README.md.
3. Vérifie les liens locaux, les captures, l’absence de secrets/données réelles sensibles et la syntaxe PHP du fichier de reproduction. Les cinq reproductions sont historiques et attendent les anciens défauts : ne les présente pas comme des tests de correction ni comme des tests CI.
4. Sélectionne explicitement seulement ces documents et leurs captures ; ne touche ni au code applicatif ni aux lockfiles. Si le dépôt distant contient déjà une partie de ces documents, ne recrée pas de doublons.
5. Crée le commit « docs: archive UI UX audit and correction playbook ».
6. Pousse normalement la branche de travail vers origin sur GitHub TIKMOS/tikmos.com, sans force-push et sans publier de commits étrangers.
7. Vérifie le SHA distant et fournis le lien du commit, la branche et le bilan. Si rien n’est nouveau, vérifie que les documents sont déjà publiés.

N’effectue aucune correction applicative, aucun déploiement et ne commence pas 01. Arrête-toi après le reçu de publication.
````
