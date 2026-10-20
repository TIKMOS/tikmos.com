# 25 — Améliorer la présentation de l’équipe

Priorité : **P2** · Référence : **Visuel : portraits et initiales**.

Dépendances : aucune dépendance technique supplémentaire.

Lire le [cadre commun](CADRE-COMMUN.md). Utiliser A, vérifier le résultat, puis utiliser B. La publication vérifiée précède le passage à la suite.

## Prompt A — réaliser et valider

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Lis docs/prompts/audit-2026-10-20/CADRE-COMMUN.md, les instructions applicables et docs/audits/2026-10-20/README.md. Exécute uniquement la correction 25 — Améliorer la présentation de l’équipe.

Constat de référence : Visuel : portraits et initiales. Priorité : P2.
Dépendances techniques : Aucune dépendance technique supplémentaire ; respecter les checkpoints de l’ordre choisi.

Avant de modifier, confirme l’état actuel du défaut et les checkpoints Git précédents. Les points d’entrée ci-dessous sont à vérifier dans le dépôt :
- `resources/views/components/tk/team-card.blade.php`
- `resources/views/public/team.blade.php`
- `resources/views/livewire/pages/admin/reglages/team.blade.php`
- `app/Models/TeamMember.php`

Travail limité à cette fiche :
1. Utiliser les portraits disponibles et autorisés avec un cadrage cohérent et un texte alternatif pertinent.
2. Conserver un fallback d’initiales élégant lorsqu’une photo manque.
3. Distinguer clairement rôle public et droits d’accès administratifs ; ne pas créer de comptes pour illustrer l’équipe.
4. Préserver l’édition depuis le CMS et les informations réellement fournies.

Critères d’acceptation :
- Les cartes restent cohérentes avec photo, sans photo et avec nom long.
- Aucun faux membre ou portrait présenté comme réel n’est introduit.
- Accueil et page équipe sont utilisables sur mobile et desktop.

Validation attendue :
- Recette visuelle et tests équipe/partenaires existants.
- Lister les portraits manquants si la partie contenu ne peut pas être achevée.
- Applique également les contrôles transversaux du cadre commun pertinents à cette modification.
- Actualise la ligne 25 de docs/prompts/audit-2026-10-20/JOURNAL.md avec les résultats réels et les limites.

Termine par un compte rendu clair et les preuves utiles. Ne commence aucune autre correction. Ne fais ni commit ni push ; attends le prompt B de cette fiche.
````

## Prompt B — commit et push de cette seule correction

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Publie uniquement la correction 25 — Améliorer la présentation de l’équipe, réalisée avec le prompt A de docs/prompts/audit-2026-10-20/25-portraits-equipe.md.

Lis le cadre commun et son protocole « Publier B ». Vérifie que A est validé, que les dépendances et checkpoints précédents sont satisfaits et que le code n’a pas changé sans revalidation. Une recette externe non effectuée doit rester signalée ; ne transforme pas une limite en résultat positif.

Relis le diff et les fichiers non suivis. Sélectionne explicitement les seuls fichiers de cette correction, sa documentation, sa ligne de journal et les assets compilés nécessaires. Écarte notamment le changement de mode préexistant d’artisan, les secrets, bases et données privées. Ne mélange pas une autre correction ou l’archive initiale de l’audit dans ce commit.

Crée un commit descriptif, par défaut :
fix(team): improve portraits and member cards

Pousse normalement sur origin, vers la branche de travail autorisée de même nom (main si le lot est toujours sur main). Vérifie le SHA publié sur https://github.com/TIKMOS/tikmos.com et rapporte les checks CI réellement accessibles.

Si le push échoue, résous uniquement le blocage autorisé sans force-push ni perte de travail ; sinon explique précisément pourquoi la correction reste non publiée. Si le commit existe déjà, vérifie-le plutôt que créer un doublon.

Termine avec le numéro 25, la branche, le SHA, le lien du commit GitHub, les validations et les limites. Arrête-toi : ne commence pas la correction suivante.
````
