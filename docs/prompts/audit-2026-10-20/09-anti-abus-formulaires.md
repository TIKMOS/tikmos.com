# 09 — Limiter les abus des formulaires publics

Priorité : **P2** · Référence : **Autres risques : contact et demandes**.

Dépendances : aucune dépendance technique supplémentaire.

Lire le [cadre commun](CADRE-COMMUN.md). Utiliser A, vérifier le résultat, puis utiliser B. La publication vérifiée précède le passage à la suite.

## Prompt A — réaliser et valider

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Lis docs/prompts/audit-2026-10-20/CADRE-COMMUN.md, les instructions applicables et docs/audits/2026-10-20/README.md. Exécute uniquement la correction 09 — Limiter les abus des formulaires publics.

Constat de référence : Autres risques : contact et demandes. Priorité : P2.
Dépendances techniques : Aucune dépendance technique supplémentaire ; respecter les checkpoints de l’ordre choisi.

Avant de modifier, confirme l’état actuel du défaut et les checkpoints Git précédents. Les points d’entrée ci-dessous sont à vérifier dans le dépôt :
- `resources/views/livewire/pages/public/contact.blade.php`
- `resources/views/livewire/pages/public/services-show.blade.php`
- `tests/Feature/ServicesDevisTest.php`

Travail limité à cette fiche :
1. Ajouter une limitation serveur raisonnable aux soumissions contact et demandes, avec une clé documentée respectant les utilisateurs partageant une IP.
2. Conserver la validation existante et le honeypot du formulaire service ; ne pas ajouter de CAPTCHA bloquant par défaut.
3. Présenter un délai de réessai clair sans perdre le texte saisi.
4. Vérifier les appels Livewire directs et éviter la multiplication des enregistrements/emails au-delà de la limite.

Critères d’acceptation :
- Des appels répétés sont limités côté serveur, même sans JavaScript navigateur.
- Les utilisateurs légitimes peuvent soumettre normalement et reprendre après le délai.
- Aucun email réel ni service tiers supplémentaire n’est requis pour tester.

Validation attendue :
- Tester seuil, délai et absence d’effets secondaires après refus, avec horloge figée.
- Exécuter les tests contact/demandes et la suite.
- Applique également les contrôles transversaux du cadre commun pertinents à cette modification.
- Actualise la ligne 09 de docs/prompts/audit-2026-10-20/JOURNAL.md avec les résultats réels et les limites.

Termine par un compte rendu clair et les preuves utiles. Ne commence aucune autre correction. Ne fais ni commit ni push ; attends le prompt B de cette fiche.
````

## Prompt B — commit et push de cette seule correction

````text
Travaille dans C:/Users/PC/Documents/Projet_claude_code/tikmos.com.

Publie uniquement la correction 09 — Limiter les abus des formulaires publics, réalisée avec le prompt A de docs/prompts/audit-2026-10-20/09-anti-abus-formulaires.md.

Lis le cadre commun et son protocole « Publier B ». Vérifie que A est validé, que les dépendances et checkpoints précédents sont satisfaits et que le code n’a pas changé sans revalidation. Une recette externe non effectuée doit rester signalée ; ne transforme pas une limite en résultat positif.

Relis le diff et les fichiers non suivis. Sélectionne explicitement les seuls fichiers de cette correction, sa documentation, sa ligne de journal et les assets compilés nécessaires. Écarte notamment le changement de mode préexistant d’artisan, les secrets, bases et données privées. Ne mélange pas une autre correction ou l’archive initiale de l’audit dans ce commit.

Crée un commit descriptif, par défaut :
fix(forms): rate limit public submissions

Pousse normalement sur origin, vers la branche de travail autorisée de même nom (main si le lot est toujours sur main). Vérifie le SHA publié sur https://github.com/TIKMOS/tikmos.com et rapporte les checks CI réellement accessibles.

Si le push échoue, résous uniquement le blocage autorisé sans force-push ni perte de travail ; sinon explique précisément pourquoi la correction reste non publiée. Si le commit existe déjà, vérifie-le plutôt que créer un doublon.

Termine avec le numéro 09, la branche, le SHA, le lien du commit GitHub, les validations et les limites. Arrête-toi : ne commence pas la correction suivante.
````
