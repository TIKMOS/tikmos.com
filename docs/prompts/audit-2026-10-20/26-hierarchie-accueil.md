# 26 — Rendre les offres visibles sur l’accueil

Priorité : **P2** · Référence : **Visuel : structure de l’accueil**.

Dépendances : [12](12-parcours-devis-unifie.md), [21](21-contrastes-theme.md), [24](24-realisations-et-preuves.md), [25](25-portraits-equipe.md).

Lire le [cadre commun](CADRE-COMMUN.md). Utiliser A, vérifier le résultat, puis utiliser B. La publication vérifiée précède le passage à la suite.

## Prompt A — réaliser et valider

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Lis docs/prompts/audit-2026-10-20/CADRE-COMMUN.md, les instructions applicables et docs/audits/2026-10-20/README.md. Exécute uniquement la correction 26 — Rendre les offres visibles sur l’accueil.

Constat de référence : Visuel : structure de l’accueil. Priorité : P2.
Dépendances techniques : 12 — Unifier le bouton Demander un devis et le suivi client; 21 — Corriger les contrastes sans changer l’identité; 24 — Présenter des réalisations et chiffres vérifiables; 25 — Améliorer la présentation de l’équipe

Avant de modifier, confirme l’état actuel du défaut et les checkpoints Git précédents. Les points d’entrée ci-dessous sont à vérifier dans le dépôt :
- `resources/views/home.blade.php`
- `app/Settings/HomepageSettings.php`
- `resources/views/livewire/pages/admin/reglages/homepage.blade.php`
- `docs/06-interface-accueil.md`

Travail limité à cette fiche :
1. Organiser l’accueil : promesse + CTA, offres principales, réalisations, méthode, équipe/preuves et contact.
2. Conserver la charte, le portrait et une hiérarchie éditoriale sobre ; éviter d’ajouter des sections sans données utiles.
3. Rendre ordre, visibilité et contenu configurables avec les réglages existants.
4. Préserver les CTA suivis, les sections activables et la lisibilité mobile, sans allonger inutilement la page.

Critères d’acceptation :
- Le visiteur identifie les offres principales avant de devoir quitter l’accueil.
- Les sections vides ou désactivées ne laissent pas de trous.
- Les réglages existants, liens, SEO et comportement sans animation restent cohérents.

Validation attendue :
- Recette 360/390/768/1440 px, états sans contenu et reduced-motion.
- Tests AccueilCmsTest/VitrineTest impactés et build.
- Applique également les contrôles transversaux du cadre commun pertinents à cette modification.
- Actualise la ligne 26 de docs/prompts/audit-2026-10-20/JOURNAL.md avec les résultats réels et les limites.

Termine par un compte rendu clair et les preuves utiles. Ne commence aucune autre correction. Ne fais ni commit ni push ; attends le prompt B de cette fiche.
````

## Prompt B — commit et push de cette seule correction

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Publie uniquement la correction 26 — Rendre les offres visibles sur l’accueil, réalisée avec le prompt A de docs/prompts/audit-2026-10-20/26-hierarchie-accueil.md.

Lis le cadre commun et son protocole « Publier B ». Vérifie que A est validé, que les dépendances et checkpoints précédents sont satisfaits et que le code n’a pas changé sans revalidation. Une recette externe non effectuée doit rester signalée ; ne transforme pas une limite en résultat positif.

Relis le diff et les fichiers non suivis. Sélectionne explicitement les seuls fichiers de cette correction, sa documentation, sa ligne de journal et les assets compilés nécessaires. Écarte notamment le changement de mode préexistant d’artisan, les secrets, bases et données privées. Ne mélange pas une autre correction ou l’archive initiale de l’audit dans ce commit.

Crée un commit descriptif, par défaut :
refactor(home): clarify offers and page hierarchy

Pousse normalement sur origin, vers la branche de travail autorisée de même nom (main si le lot est toujours sur main). Vérifie le SHA publié sur https://github.com/TIKMOS/tikmos.com et rapporte les checks CI réellement accessibles.

Si le push échoue, résous uniquement le blocage autorisé sans force-push ni perte de travail ; sinon explique précisément pourquoi la correction reste non publiée. Si le commit existe déjà, vérifie-le plutôt que créer un doublon.

Termine avec le numéro 26, la branche, le SHA, le lien du commit GitHub, les validations et les limites. Arrête-toi : ne commence pas la correction suivante.
````
