# Correction 05 — Récupération sécurisée des demandes invitées

Validation locale du 24 octobre 2026, sans commit ni push. Checkpoint 04
`c37fb7b7720612f674dca4fc9145ba2676709ce2` vérifié sur `origin/main` avant intervention.
Le mode préexistant d'`artisan` est préservé et l'index reste vide.
L'[audit historique F05](../../audits/2026-10-20/README.md) n'est pas modifié.

## Cause et reproduction

Le formulaire public crée une demande avec `user_id = null`. L'email de devis
dirigeait vers le détail client, lequel exige à juste titre `user_id = Auth::id()`.
La création d'un compte avec la même adresse ne fournissait aucun parcours
de rattachement. Supprimer le contrôle de propriété aurait exposé les demandes.

Le nouveau test de récupération légitime a été exécuté avant correction :
**HTTP 200 attendu, HTTP 403 obtenu** à l'ouverture du lien de l'email. Après
correction, il vérifie le rattachement et maintient le 403 pour un autre compte.
Les assertions historiques sous `docs/audits/` ne servent pas de preuve de réussite.

## Parcours et garanties

- Emails de réception, devis, statut et renouvellement : lien signé temporaire
  de 24 heures pour une demande invitée ; lien direct existant pour une demande
  déjà liée. Les jetons sont créés au rendu du mail, pour conserver leur durée
  utile avec la queue. Ils sont aléatoires (64 caractères), stockés uniquement
  par hachage SHA-256 et associés à l'adresse destinataire hachée.
- Connexion et inscription conservent la destination. L'inscription régénère
  aussi la session. La consultation du lien ne dévoile ni nom, message, référence,
  email destinataire, ni devis avant le rattachement.
- Un compte avec adresse non vérifiée ne peut pas réclamer la demande. Il demande
  un email de vérification séparé, signé pour son identifiant et l'adresse actuelle,
  valable une heure. Un autre compte, une modification de l'adresse ou une signature
  invalide/expirée ne valide rien. La vérification seule ne rattache rien.
  Ce parcours emploie les méthodes natives de vérification du modèle sans imposer
  le contrat global `MustVerifyEmail` ni modifier l'inscription générale du site.
- La confirmation est un POST protégé par CSRF. `GuestRequestRecoveryService`
  verrouille d'abord la demande commune aux différents liens, puis le compte et
  le jeton. Il revérifie compte actif/vérifié, destinataire, échéance en base et
  absence de propriétaire. Une mise à jour conditionnelle `user_id IS NULL`
  complète le verrouillage. Le rattachement, la consommation de tous les jetons
  et un événement sans changement de statut sont atomiques, avec rollback testé.
- L'événement de rattachement n'est pas affiché comme un nouvel envoi de devis.
  Aucun montant, date de validité, décision ou événement historique n'est réécrit.
  Aucun transfert d'une demande déjà liée n'est autorisé. Le contrôle de propriété
  du détail et le service de décision de la correction 04 sont conservés.
- Le refus d'un mauvais compte ou d'une adresse non vérifiée ne consomme pas le
  lien. Les états invalide/expiré, déjà utilisé et mauvais compte proposent une
  action concrète. Le renouvellement exige référence et adresse initiale et
  répond de façon neutre, sans énumérer les demandes. Aucun lien n'est envoyé
  vers une adresse saisie différente de celle enregistrée.
- Renvois : plafond de 6 POST/minute par IP et 1/minute par couple référence/email ;
  vérification : 1 envoi/minute par compte, plus la limite de route. Un renvoi
  n'invalide pas les liens encore valides, pour éviter un blocage par un tiers.
  Un rattachement réussi les invalide tous. Une nouvelle signature ne prolonge
  pas l'expiration du jeton en base.
- Pages de récupération : `Cache-Control: no-store, private`, `Referrer-Policy:
  no-referrer`, `X-Robots-Tag: noindex, nofollow`. Aucun jeton ou email n'est ajouté
  à un journal applicatif par cette correction.
- Anciennes demandes : aucune réaffectation en masse. Le formulaire de récupération
  fonctionne sur une demande historique sans propriétaire disposant d'un email.
  Sans email ou sans référence, l'équipe doit vérifier l'identité manuellement.

Références officielles utilisées :
[URLs signées temporaires Laravel](https://laravel.com/framework/docs/urls#signed-urls),
[vérification d'adresse Laravel](https://laravel.com/framework/docs/13.x/verification).
Le [cadre fonctionnel](../../03-cadre-fonctionnel.md) décrit également ces règles.

## Migration

Nouvelle migration :
`database/migrations/2026_10_24_010000_create_guest_request_claims_table.php`.
Elle ajoute une table de jetons avec FK de demande, hachage unique, destinataire
haché, échéance et date de consommation. Elle ne réécrit aucune demande, aucun
paiement et aucune ancienne migration. Sa suppression ne touche qu'à cette table.

Migration exécutée uniquement sur les bases SQLite de test et de recette créées
pour cette intervention. **Elle n'est pas appliquée à la base habituelle de
l'application.** Le nouveau parcours nécessite cette migration avant utilisation
sur cette base ; la recette utilise donc exclusivement le port 8013.

## Contrôles exécutés

Environnement Pest : SQLite en mémoire, mail `array`, queue synchrone. Notifications
simulées et HTTP imprévu interdit dans les nouveaux tests. Aucun paiement réel.

| Contrôle | Résultat |
|---|---|
| `php vendor/bin/pest tests/Feature/GuestRequestRecoveryTest.php --compact` | **29 tests, 164 assertions**, réussis |
| Récupération + EspaceClientDevisTest + ServicesDevisTest + Auth + NotificationsTest | **107 tests, 501 assertions**, réussis |
| `php vendor/bin/pest --compact` | **355 tests, 1 356 assertions**, réussis ; suite entière réexécutée sur les vues finales avant publication |
| Récupération + EspaceClientDevisTest, relancés sur les vues finales | **51 tests, 282 assertions**, réussis |
| Larastan, mémoire 1 Go | **0 erreur** |
| Pint sur les 11 fichiers PHP concernés | Conforme |
| `npm.cmd run build` | Réussi ; bundle final `app-CV94gIVv.css` et manifeste identiques au checkpoint 04 |

La première suite complète de A a été lancée avant le dernier ajustement du libellé
d'événement dans la vue client ; les tests concernés ont ensuite été relancés.
À la demande de l'utilisateur, la suite complète, Larastan et Pint ont de nouveau
été exécutés avant publication sur l'état final : tous réussis, résultats ci-dessus.

Le build initial de A avait produit le CSS provisoire `app-BOXzao7U.css`. Tailwind
lit aussi `storage/framework/views/*.php`, modifié pendant les tests. Après la fin
de la suite, le build final et un second build isolé ont produit des manifestes et
des assets identiques, avec `app-CV94gIVv.css` déjà présent au checkpoint 04.
Le bundle provisoire n'est donc pas retenu pour publication ; aucun fichier de
`public/build` n'a finalement de différence avec le checkpoint précédent.
Le JavaScript et les polices compilés ne changent pas. `public/hot` est absent.

Cas couverts : dépôt invité public puis envoi simulé du devis ; accès légitime et
croisés ; absence de mutation GET ; hachage des jetons ; signature absente/altérée ;
échéance de signature et de base ; page ouverte avant expiration ; compte erroné ;
adresse non vérifiée, vérification valide/invalide/expirée et changement d'adresse ;
connexion et inscription avec retour au lien ; tentative de transfert ; deux liens
de la même demande et rejeu ; montants/décisions/historique conservés ; échec de
journalisation avec rollback ; compte suspendu, demande supprimée ou destinataire
modifié ; renouvellement, réponse neutre, adresse erronée, absence d'email, demande
déjà liée et limitation des renvois.

## Recette navigateur

Nouvelle base SQLite temporaire, indépendante de la base habituelle, sur
`127.0.0.1:8013`, cookie distinct, mail `array` et queue synchrone. Comptes et demandes
fictifs uniquement. Les messages sont interceptés dans une boîte locale temporaire,
sans livraison SMTP. Les fichiers de recette, identifiants, jetons et journaux
restent hors du dépôt.

Vérifications réalisées par l'agent :

1. À 360 px : accueil invité, connexion et retour vers la confirmation, absence
   de détails privés avant rattachement. Tabulation et Entrée sur la confirmation
   ouvrent le devis fictif de 10 000 XOF sans accepter celui-ci.
2. À 1280/1440 px : devis récupéré, état lien utilisé après réouverture, état expiré
   avec renouvellement et état mauvais compte avec déconnexion. Aucune mutation
   causée par ces refus.
3. Adresse non vérifiée : demande d'email, message de succès, lecture de la boîte
   locale, clic sur la vérification, retour à la confirmation, puis rattachement.
4. À 360 px : formulaire de renouvellement avec libellés associés, tabulation de
   la référence à l'email puis au bouton et envoi par Entrée ; réponse neutre,
   nouveau lien présent dans la boîte locale.
5. Aucun débordement horizontal observé : 360/360 px à l'accueil, 345/345 px sur
   les pages longues mobiles. Aucun avertissement ni erreur de console capturé
   dans les deux onglets de recette. Viewport restauré et onglets automatisés fermés.

Avant publication, les vues de renouvellement à 360 px et de lien utilisé à 1440 px
ont été revérifiées avec le bundle final ; captures correspondantes actualisées.
Largeurs du formulaire mobile : 360/360 px, aucune erreur/avertissement console
capturé. Viewport restauré et nouvel onglet automatisé fermé.

Recette utilisateur : page de guidage locale ouverte dans Chrome, avec un cas
distinct encore disponible au début de l'essai. **L'utilisateur confirme le devis
de 10 000 XOF après rattachement et le message « déjà utilisé » à la réouverture
du même lien.** Les autres cas restent des validations de l'agent et ne sont pas
présentés comme personnellement vérifiés par l'utilisateur. L'instance 8013 reste
temporairement disponible pour reprendre la recette.

### Captures sans identifiants de connexion

- [Accueil invité, 360 px](accueil-invite-mobile-360.jpg)
- [Confirmation, 360 px](confirmation-mobile-360.jpg)
- [Adresse à vérifier, 360 px](adresse-a-verifier-mobile-360.jpg)
- [Adresse vérifiée, 1280 px](adresse-verifiee-desktop-1280.jpg)
- [Demande retrouvée, 360 px](demande-retrouvee-mobile-360.jpg)
- [Demande retrouvée, 1280 px](demande-retrouvee-desktop-1280.jpg)
- [Mauvais compte, 1440 px](compte-different-desktop-1440.jpg)
- [Lien expiré, 1440 px](lien-expire-desktop-1440.jpg)
- [Lien utilisé, 1440 px](lien-utilise-desktop-1440.jpg)
- [Renouvellement, 360 px](renouvellement-mobile-360.jpg)
- [Réponse de renouvellement, 360 px](renouvellement-envoye-mobile-360.jpg)

## Limites et état

- Concurrence réelle et migration MySQL : **non validées**. Les verrous et la mise
  à jour conditionnelle sont présents ; les tests SQLite ne démontrent pas le
  comportement de deux connexions MySQL concurrentes.
- SMTP réel, délivrabilité et délai réel d'un worker de queue : **non validés**.
  Les notifications sont simulées dans Pest, le transport mémoire est utilisé
  dans le navigateur. Aucun email ni compte réel n'a été sollicité.
- Migration de la base habituelle et déploiement : **non effectués**.
- La vérification manuelle des demandes sans email reste un traitement humain,
  sans automatisation de transfert. Les jetons expirés restent inutilisables en
  base ; aucune purge de données n'est exécutée dans cette correction.
- Correction validée localement par les contrôles décrits ; rattachement et refus
  de rejeu confirmés personnellement par l'utilisateur. Aucun commit ni push,
  aucune correction suivante commencée.

## Inventaire du lot

Chemins relatifs à la racine du projet ; seul le mode préexistant d’`artisan`
reste étranger à ce lot.

```text
app/Http/Controllers/GuestRequestRecoveryController.php
app/Http/Middleware/SecurityHeaders.php
app/Notifications/GuestRequestEmailVerification.php
app/Notifications/GuestRequestRecoveryLink.php
app/Notifications/QuoteSent.php
app/Notifications/ServiceRequestStatusChanged.php
app/Notifications/ServiceRequestSubmitted.php
app/Services/GuestRequestRecoveryService.php
database/migrations/2026_10_24_010000_create_guest_request_claims_table.php
docs/03-cadre-fonctionnel.md
docs/prompts/audit-2026-10-20/JOURNAL.md
docs/validations/2026-10-24-rattachement/README.md
docs/validations/2026-10-24-rattachement/accueil-invite-mobile-360.jpg
docs/validations/2026-10-24-rattachement/adresse-a-verifier-mobile-360.jpg
docs/validations/2026-10-24-rattachement/adresse-verifiee-desktop-1280.jpg
docs/validations/2026-10-24-rattachement/compte-different-desktop-1440.jpg
docs/validations/2026-10-24-rattachement/confirmation-mobile-360.jpg
docs/validations/2026-10-24-rattachement/demande-retrouvee-desktop-1280.jpg
docs/validations/2026-10-24-rattachement/demande-retrouvee-mobile-360.jpg
docs/validations/2026-10-24-rattachement/lien-expire-desktop-1440.jpg
docs/validations/2026-10-24-rattachement/lien-utilise-desktop-1440.jpg
docs/validations/2026-10-24-rattachement/renouvellement-envoye-mobile-360.jpg
docs/validations/2026-10-24-rattachement/renouvellement-mobile-360.jpg
resources/views/livewire/pages/app/requests-index.blade.php
resources/views/livewire/pages/app/requests-show.blade.php
resources/views/livewire/pages/auth/register.blade.php
resources/views/livewire/pages/public/services-show.blade.php
resources/views/request-recovery/renew.blade.php
resources/views/request-recovery/show.blade.php
routes/web.php
tests/Feature/GuestRequestRecoveryTest.php
```
