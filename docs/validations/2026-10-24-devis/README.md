# Correction 04 — Validité des devis

Validation locale du 24 octobre 2026, avant publication. Checkpoint précédent vérifié
sur `origin/main` : `88cc961ff7a8050d64932c3b646817a58e2ee419`.
L'[audit historique F04](../../audits/2026-10-20/README.md) reste inchangé.

## Cause et preuve avant/après

L'action client contrôlait uniquement le statut « Envoyé » ; `valid_until` n'était
pas vérifié. L'envoi administratif acceptait également une date passée.

Le nouveau test `refuse un devis expiré même par appel Livewire direct` a été exécuté
avant correction : statut attendu **Sent**, obtenu **Accepted**. Après correction,
il vérifie le refus, un montant inchangé, aucune date de décision ni nouvel événement,
et une demande restant « Devis envoyé ». Les anciens tests de reproduction sous
`docs/audits/` n'ont pas été utilisés comme preuve de correction.

## Règle et comportement obtenus

- Une date de validité inclut le jour indiqué jusqu'à **23:59:59.999999**, dans
  `config('app.timezone')`, actuellement **UTC**. La décision est refusée dès le
  lendemain à minuit dans ce fuseau. Sans date, aucune échéance automatique.
- `Quote::isExpired()` et `displayStatus()` calculent l'état affiché. L'expiration
  d'un devis encore envoyé ne réécrit pas son statut stocké, son montant ou l'historique.
  Les décisions « Accepté » et « Refusé » conservent leur affichage après la date limite.
- `QuoteService` centralise la décision et l'envoi. La demande puis son devis sont
  relus sous verrou dans une transaction ; propriété, permissions, statut et date
  sont revérifiés au moment de l'action. Décision et événement sont atomiques.
  Un rejeu ne crée pas de deuxième décision ni de deuxième événement.
- Un devis expiré ne peut plus être accepté ni refusé. L'espace client indique
  « Expiré » et propose **Demander le renouvellement**, vers le formulaire de contact,
  avec la référence à préciser. Aucune demande ou notification n'est envoyée par
  le simple affichage de cette page.
- Un envoi exige une date au format `Y-m-d`, du jour ou future selon le fuseau de
  l'application, ou un champ vide. Le calendrier affiche une date minimale ; le
  formulaire délègue la validation au serveur pour présenter une erreur de champ
  compréhensible. Une correction de date efface l'ancienne erreur après réussite.
- Un devis déjà accepté ou refusé reste en lecture seule, y compris depuis un ancien
  formulaire administratif. Un devis envoyé mais expiré peut être renouvelé par un
  envoi explicite avec une nouvelle date valide ; ce n'est pas une modification
  automatique des données historiques.

La règle métier est aussi inscrite dans le [cadre fonctionnel](../../03-cadre-fonctionnel.md).
Les règles de validation s'appuient sur la documentation officielle Laravel :
[date_format](https://laravel.com/docs/13.x/validation#rule-date-format) et
[after_or_equal](https://laravel.com/docs/13.x/validation#rule-after-or-equal).
La fin de journée est calculée avec `Carbon::endOfDay()` ; les limites sont vérifiées
à la microseconde dans les tests.

## Tests et contrôles réellement exécutés

Environnement : `APP_ENV=testing`, SQLite en mémoire, `DB_URL` vide,
`MAIL_MAILER=array`, `QUEUE_CONNECTION=sync`, session/cache de test. Les tests ciblés
interceptent les notifications et interdisent les appels HTTP imprévus.

| Contrôle | Résultat |
|---|---|
| `php vendor/bin/pest tests/Feature/App/EspaceClientDevisTest.php tests/Feature/ServicesDevisTest.php --compact` | **45 tests, 244 assertions**, réussis ; relancés sur les vues finales |
| `php artisan test --compact` | **326 tests, 1 192 assertions**, réussis |
| `php -d memory_limit=1G vendor/bin/phpstan analyse --no-progress --memory-limit=1G` | **0 erreur** |
| `php vendor/bin/pint --test app/Models/Quote.php app/Services/QuoteService.php tests/Feature/App/EspaceClientDevisTest.php tests/Feature/ServicesDevisTest.php` | Conforme |
| `npm.cmd run build` | Réussi ; CSS `app-CV94gIVv.css`, manifeste actualisé |

Cas couverts : date passée ; début et toute fin du jour limite ; lendemain à minuit ;
UTC, Africa/Lagos et America/New_York ; page ouverte avant expiration ; date changée
en base après ouverture ; absence de date ; montants et décisions historiques ;
rejeux ; changement de propriétaire ; rollback si l'écriture de l'événement échoue ;
dates administratives invalides/passées ; envoi le jour même, futur ou sans date ;
formulaire ouvert avant minuit ; renouvellement explicite et permissions.

La suite complète précède uniquement les derniers ajustements de présentation et
du formulaire Blade ; les tests ciblés ont été relancés sur les vues finales.
Les fichiers JavaScript et les polices compilés n'ont pas changé.

## Recette navigateur

Une **nouvelle base SQLite temporaire**, distincte de la base habituelle, a été créée
avec ses migrations et des données fictives. Serveur `127.0.0.1:8012`, cookie de
session distinct, cache en base temporaire, mail `array` et queue synchrone.
Deux comptes fictifs client/commercial sont utilisés ; aucun compte réel n'est modifié.
La double authentification commerciale est conservée, avec le code local de
développement ; aucun appareil de confiance n'a été enregistré.

Vérifications réalisées :

1. **Client, 360 px :** état Expiré, absence de l'acceptation, référence et action de
   renouvellement visibles. Le lien activé avec Entrée ouvre la page de contact.
   Le bouton a été adapté pour éviter le débordement : largeur du document et du
   contenu **345 / 345 px** après correction.
2. **Client, 1440 px :** devis valable aujourd'hui avec actions de décision ; devis
   sans date avec actions disponibles ; décision historique acceptée conservée.
3. **Page périmée :** la date du seul devis fictif ouvert a été passée en base de
   recette au jour précédent. L'action d'acceptation aboutit au refus serveur avec
   l'erreur explicite et l'état Expiré, sans acceptation ni date de décision.
   Cette simulation de changement de date complète les tests à horloge figée ;
   elle ne prétend pas avoir attendu le passage réel à minuit dans le navigateur.
   La date du jour a ensuite été rétablie sur ce seul devis fictif pour les essais manuels.
4. **Administration, 360 px :** date passée refusée avec message visible ; quantité
   monétaire saisie conservée. Date corrigée au jour même, tabulation du détail
   vers le bouton, puis Entrée : devis fictif de **10 000 XOF** envoyé, erreur
   effacée et événement unique ajouté. Largeurs **345 / 345 px**.
5. **Administration, 1440 px :** devis déjà accepté en lecture seule, montant et
   décision conservés. Largeurs **1440 / 1440 px**.

Aucune erreur ni aucun avertissement de console capturé sur les pages contrôlées.
Les dimensions ont été restaurées et les onglets automatisés fermés. Le serveur
habituel sur 8000 a été redémarré à la demande de l'utilisateur et répond HTTP 200.
Le serveur 8012 est **laissé disponible pour la recette manuelle demandée**, puis
devra être arrêté après celle-ci. Ses données et identifiants restent hors Git.

| Preuve | Capture |
|---|---|
| Expiration côté client, mobile | [devis-expire-mobile-360.jpg](devis-expire-mobile-360.jpg) |
| Expiration côté client, bureau 1280 px | [devis-expire-desktop-1280.jpg](devis-expire-desktop-1280.jpg) |
| Devis valable le jour limite | [devis-valable-desktop-1440.jpg](devis-valable-desktop-1440.jpg) |
| Refus après changement de date sur une page ouverte | [devis-refus-page-ouverte-desktop-1440.jpg](devis-refus-page-ouverte-desktop-1440.jpg) |
| Date administrative passée refusée | [admin-date-passee-mobile-360.jpg](admin-date-passee-mobile-360.jpg) |
| Envoi valide après correction | [admin-envoi-valide-mobile-360.jpg](admin-envoi-valide-mobile-360.jpg) |
| Décision acceptée conservée | [admin-decision-preservee-desktop-1440.jpg](admin-decision-preservee-desktop-1440.jpg) |

## Parcours manuel à vérifier ensemble

Sur **http://127.0.0.1:8012**, avec les identifiants fictifs communiqués dans le chat :

1. Client : `/app/devis/TKM-DEV-F04-EXPIRE` → Expiré, aucun bouton Accepter,
   bouton de renouvellement ouvrant le contact. Préciser la référence du devis
   dans le message de renouvellement ; le formulaire n'est pas prérempli automatiquement.
2. Client : `/app/devis/TKM-DEV-F04-AUJOURDHUI` → valable le **24/10/2026 UTC**
   inclus ; accepter et confirmer doit enregistrer une seule décision.
3. Client : `/app/devis/TKM-DEV-F04-SANS-DATE` → acceptation disponible sans échéance.
4. Client : références `TKM-DEV-F04-ACCEPTE` et `TKM-DEV-F04-REFUSE` → décisions
   conservées malgré leur ancienne date limite.
5. Après déconnexion, commercial : `/admin/devis/TKM-DEV-F04-ENVOI` → saisir une
   date passée puis renvoyer : erreur, devis conservé. Corriger au jour courant ou
   futur : envoi autorisé, ancienne erreur effacée. Les emails restent en mémoire.
6. Commercial : `/admin/devis/TKM-DEV-F04-ACCEPTE` → devis en lecture seule.

Ces références sont propres à la recette fictive et n'existent pas nécessairement
sur le serveur habituel 8000. L'utilisateur a confirmé le 24 octobre l'état Expiré,
l'absence du bouton Accepter et la présence du bouton de renouvellement sur le premier
scénario. Les autres cas restent disponibles pour la suite de la recette ensemble.

## Fichiers et limites

- `app/Models/Quote.php` et nouveau `app/Services/QuoteService.php`.
- Vues `resources/views/livewire/pages/app/requests-show.blade.php` et
  `resources/views/livewire/pages/admin/service-request-show.blade.php`.
- `tests/Feature/App/EspaceClientDevisTest.php`, `tests/Feature/ServicesDevisTest.php`.
- `docs/03-cadre-fonctionnel.md`, ligne/note 04 du journal, cette note et ses captures.
- `public/build/manifest.json`, CSS `app-CV94gIVv.css` remplaçant `app-t2tCysDR.css`.

**Concurrence MySQL non validée** : règles métier et rollback validés sur SQLite ;
aucune preuve de concurrence InnoDB. Aucune recette SMTP réel ni livraison d'email.
Le formulaire de contact existant sert à solliciter un renouvellement ; aucun nouveau
module de messagerie ou de versionnement des devis n'est ajouté dans cette fiche.

Aucun changement de schéma, aucune migration de la base habituelle, aucun déploiement,
aucun commit/push. Le mode préexistant d'`artisan` est préservé. Aucune autre fiche commencée.
