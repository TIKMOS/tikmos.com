# Correction 12 — devis suivi et contact général

Validation locale du 27 octobre 2026. Checkpoint précédent
`26395a1e7f45c7600cc8a5a8d72c9a32135e9cb2` vérifié sur `origin/main` avant
modification. Bases, caches, sessions, vues compilées et stockage isolés ;
notifications simulées et appels HTTP imprévus interdits dans les nouveaux tests.
Pas de commit, push ou déploiement dans cette étape A.

## Cause et reproduction

Les boutons « Demander un devis » de l'accueil et du menu conduisaient à
`/contact`, dont le traitement créait un `Lead`. La fiche service créait déjà une
`ServiceRequest`, avec événement, notifications et suivi. Le contact proposait
« Demande de devis » comme sujet initial, sans alimenter ce pipeline.

Avant correction, trois tests exigeant le bon comportement ont échoué :
**3 échecs, 4 assertions**. L'accueil n'offrait pas le lien attendu, l'entrée
générale `/demander-un-devis` répondait 404, et un objet authentifié de compte fermé
déjà chargé pouvait encore soumettre. Ce dernier test ne prétend pas démontrer
un contournement HTTP du middleware. L'audit historique reste inchangé.

## Parcours et garanties

- Tous les boutons explicites de devis des vues concernées mènent à
  `/demander-un-devis`. Choix d'un service publié ou besoin sur mesure, en
  réutilisant `pages.public.services-show` et le pipeline existant. La fiche service
  conserve son contexte et sa publication est revérifiée avant création.
- Une soumission de devis crée uniquement une demande, son événement et les
  notifications équipe/client existantes. Confirmation focalisée avec référence ;
  propriétaire connecté : « Suivre ma demande ». Invité : instructions de
  récupération sécurisée de la correction 05, sans attribution sur email déclaré.
  Téléphone seul : référence à conserver et recontact par l'équipe.
- Le « Contact général » conserve uniquement le pipeline Lead. Sujet initial
  « Question générale », lien distinct vers un devis suivi. Pas de nouvelle
  notification ni conversion des anciens messages.
- Validations, champs conservés après refus, honeypot service et quotas de la
  correction 09 préservés, y compris en appels Livewire directs. Compte fermé ou
  inactif refusé côté serveur. Droits, décisions et expiration des devis inchangés.
- Nouveau reçu persistant par jeton de formulaire verrouillé. Empreinte du jeton,
  HMAC du compte/session, intention et identifiant métier ; pas de coordonnées ou
  texte copié dans cette table. Reçu, donnée métier et événement dans la même
  transaction. Rejeu du même jeton/acteur : même résultat, sans quota ni notification
  supplémentaires. Autre acteur : 403, sans dévoiler le résultat. Donnée disparue :
  refus sans recréation silencieuse. La clé primaire arbitre les jetons concurrents ;
  la concurrence MySQL reste non testée.
- Après succès, un nouveau jeton permet un nouvel envoi volontaire. Aucun mécanisme
  ne prétend dédupliquer des contenus identiques avec des jetons différents.
  Session invitée renouvelée ou connexion entre l'envoi et son rejeu : reçu refusé,
  récupération par le parcours prévu. Une erreur après réservation du quota peut
  consommer une tentative, mais les écritures métier sont annulées ensemble.

La migration additive
`2026_10_27_020000_create_public_form_submissions_table.php` crée uniquement la
table des reçus. Le `service_id` nullable existant suffit pour le sur-mesure.
Anciennes migrations et données historiques inchangées. Nouvelle migration testée
sur structure existante fictive et appliquée à la seule base de recette isolée ;
**non appliquée à la base habituelle**.

## Fichiers du lot

- `app/Services/PublicFormSubmissionService.php` ; nouvelle migration ci-dessus.
- `routes/web.php` ; `resources/views/livewire/pages/public/services-show.blade.php`
  et `contact.blade.php` : formulaire réutilisé, sélection, reçus et confirmations.
- Boutons : `resources/views/home.blade.php`, `components/layouts/public.blade.php`,
  `public/about.blade.php`, `public/temoignages.blade.php`,
  `livewire/pages/public/services-index.blade.php`, `projects-index.blade.php`,
  `livewire/pages/app/dashboard.blade.php` et `requests-index.blade.php`, tous sous
  `resources/views/` lorsqu'un préfixe est abrégé.
- `tests/Feature/QuoteRequestEntryTest.php` ; documentation fonctionnelle, schéma,
  déploiement, ligne/note 12 du journal et présent rapport avec sept captures.
- `public/build/manifest.json`, nouveau CSS `app-DXEy4714.css` et suppression de
  `app-DpDeyuAx.css`. JavaScript, polices et leurs manifestes inchangés.

## Tests et contrôles

Environnement : SQLite mémoire, cache/session de test, configuration et vues
compilées sur chemins privés. Les suites Pest sont exécutées séparément.

| Contrôle | Résultat |
|---|---|
| Premiers tests : reproductions, ServicesDevisTest, PublicFormSubmissionTest | 48 tests, 314 assertions, réussis |
| Ciblés élargis : QuoteRequestEntryTest, ServicesDevisTest, PublicFormSubmissionTest, GuestRequestRecoveryTest, App/EspaceClientDevisTest, PublicPagesTest, LegalRgpdTest | 144 tests, 844 assertions, réussis |
| `php vendor/bin/pest --compact` | 562 tests, 2 454 assertions, réussis (210,654 s) |
| Ciblés sur les vues finales après Pint | 144 tests, 844 assertions, réussis (69,853 s) |
| Larastan `--debug` sur app, actions Volt devis/contact et migration | 0 erreur |
| Pint `--test` et syntaxe des 14 fichiers PHP/Blade concernés | Conformes |
| `npm run build` | Réussi, 4,70 s ; manifeste et fichiers référencés cohérents |

La suite complète précède uniquement la normalisation Pint de quatre vues dont
les boutons ont changé. Les ciblés ont été relancés sur ces vues finales ; aucun
changement de logique métier après la suite complète. Le premier test de liens
utilisait une expression régulière trop restrictive pour les SVG des boutons :
remplacée par une lecture DOM, sans supprimer les assertions de destination.

Cas supplémentaires : sur-mesure/service, invité/connecté, pipeline et accès croisés,
rattachement après vérification d'adresse, contact seul, rejeux sans doublon ni
nouvel email, quota non consommé par les rejeux, autre acteur refusé, service
brouillon/inexistant/retiré, honeypot, texte conservé, jeton verrouillé, migration
sans réécriture et rollback si la création d'historique échoue. Ces tests de
rollback et de rejeu ne constituent pas une recette de concurrence MySQL.

Références techniques consultées : [propriétés verrouillées Livewire](https://livewire.laravel.com/docs/3.x/locked)
et [transactions Laravel](https://laravel.com/framework/docs/database).

## Recette navigateur et preuves

Instance temporaire locale avec SQLite, cache fichier persistant, sessions et
stockage dédiés. Comptes fictifs, notifications interceptées ; le guide, les
adresses de connexion et les liens signés sont restés hors du dépôt. Le lien de
récupération était préparé par le mécanisme existant dans le guide, en simulation
de réception d'email : aucune délivrabilité réelle revendiquée.

L'agent a vérifié : accueil → bouton devis → choix d'un service → envoi → référence
et suivi connecté au clavier ; invité → sur-mesure → refus sans coordonnées avec
texte conservé → envoi avec téléphone ; contact général → message distinct ;
demande invitée avec email → compte destinataire déjà vérifié → confirmation de
rattachement → suivi ; présence des trois demandes dans le pipeline admin. Avant
l'essai utilisateur, le guide comptait 3 demandes suivies et 1 message général.

Mobile 360 px et bureau 1440 px, sélecteur natif, envoi et ouverture du suivi à
Entrée, focus sur la confirmation. Aucun débordement horizontal ni erreur ou
avertissement console observé sur les pages contrôlées. Le bouton WhatsApp peut
encore recouvrir ponctuellement un champ sur mobile, défaut préexistant signalé
en 09 et hors de ce lot. Le champ téléphone du composant existant peut conserver
sa représentation locale après remise à zéro serveur ; aucune refonte générale
de ce composant n'a été entreprise ici.

L'utilisateur a confirmé : **référence, suivi et contact distinct fonctionnent**.
Les autres cas techniques ne sont pas attribués à sa validation personnelle.
Dimensions restaurées, onglets de recette fermés et seul serveur F12 arrêté après
confirmation. Pour une nouvelle recette, préparer une nouvelle base fictive et
reprendre ce parcours ; ne pas réutiliser les comptes ou la base habituels.

| Preuve | Capture |
|---|---|
| Devis connecté : référence et lien de suivi | [1440 px](devis-envoye-desktop-1440.png) |
| Demande visible dans le suivi du propriétaire | [1440 px](suivi-client-desktop-1440.png) |
| Entrée générale avec choix sur mesure | [360 px](demande-sur-mesure-mobile-360.png) |
| Confirmation invitée et prochaine étape | [360 px](devis-invite-mobile-360.png) |
| Contact explicitement distinct du devis | [360 px](contact-general-mobile-360.png) |
| Message général envoyé, toast de résultat | [360 px](contact-envoye-mobile-360.png) |
| Demande sur mesure récupérée après confirmation | [360 px](rattachement-invite-mobile-360.png) |

## Limites et actions CMS

MySQL, migration et concurrence MySQL, SMTP/délivrabilité, cache partagé entre
processus/serveurs et charge de production **non validés**. L'unicité du reçu ne
garantit pas la livraison des notifications : une panne entre commit et mise en
queue peut empêcher un email ; le rejeu ne relivre pas. Pas de nouvelle purge
automatique des reçus ni de décision de conservation implicite.

Les boutons des vues sont corrigés ; titres/textes CMS de la section finale
restent éditables et inchangés. Les liens insérés dans des corps riches ou autres
contenus réels nécessitent une revue séparée s'ils proposent un devis vers le
contact. Le contenu effectivement déployé n'a pas été examiné. Aucun remplacement
de contenu réel, conversion de Lead ou réaffectation historique.

Aucun email/paiement réel, compte réel modifié, migration habituelle, purge du
cache habituel ou déploiement. Mode étranger d'`artisan` préservé et index inchangé.
Correction 13 non commencée. Voir [cadre fonctionnel](../../03-cadre-fonctionnel.md)
et [conditions de déploiement](../../DEPLOIEMENT.md#devis-suivi-et-recus-denvoi--correction-12).
