# Correction 09 — Limiter les abus des formulaires publics

Validation locale du 27 octobre 2026. Branche `main`, checkpoint 08 vérifié sur
GitHub : `23c49870a0e3b3c0c9ac68e210a55628cf428e1a`. Aucun commit ni push pendant A.

## Cause et reproduction avant correction

Les actions `submit()` du contact et des fiches service validaient les champs puis
créaient leurs données sans quota métier. Le honeypot du service ne limitait pas
les soumissions répétées correctement renseignées. Un contrôle du bouton seul
n'aurait pas protégé les appels directs à l'action Livewire.

Les deux nouveaux tests exigeant le refus du quatrième appel direct, après trois
succès avec des emails déclarés différents, échouaient avant correction :
`Component has no errors`. Après correction, ils passent : **2 tests,
20 assertions**, sans quatrième enregistrement ni notification supplémentaire.
Il s'agit de tests de non-régression dans `tests/`, pas des reproductions historiques
de l'audit, qui restent inchangées.

## Règles et clés

Les deux formulaires et toutes les fiches service utilisent le même limiteur.

| Dimension | Maximum | Durée |
|---|---|---|
| Compte connecté, sinon session serveur du visiteur | 3 | 600 secondes |
| IP réseau fournie par `Request::ip()` | 20 | 600 secondes |
| Chaque email déclaré | 5 | 1 800 secondes |
| Chaque téléphone déclaré | 5 | 1 800 secondes |

Fenêtres fixes démarrant au premier envoi valide admis, sans prolongation lors des
refus. Ce ne sont pas des fenêtres glissantes. Les seuils sont définis dans
`PublicFormSubmissionLimiter`, sans nouvelle configuration secrète.

Un compte est identifié par l'authentification serveur, même si son adresse n'est
pas vérifiée. Aucune identité ni aucun droit d'accès n'est déduit des coordonnées
déclarées. L'email est normalisé en minuscules après suppression des espaces de
bord ; le téléphone retire les séparateurs sans deviner son pays. Si aucun chiffre
n'existe, le texte normalisé est conservé pour le quota, sans élargir la validation
métier actuelle. Les IPv6 équivalentes sont normalisées par leur représentation
binaire. Les valeurs enregistrées dans les formulaires ne sont pas réécrites.

Les clés `public-forms:v1:actor:*`, `network:*`, `email:*` et `phone:*` contiennent
un HMAC SHA-256 avec `APP_KEY`, pas les valeurs brutes. Les coordonnées ne sont
jamais des destinataires supplémentaires : les notifications métier existantes
sont conservées. Deux visiteurs distincts sur la même IP ont chacun trois envois
dans le plafond réseau commun. Changer l'email ou la fiche ne suffit pas à contourner
le quota ; supprimer les cookies peut changer le visiteur invité, mais pas le
plafond réseau. Une attaque distribuée n'est pas éliminée par ces quotas.

## Ordre du contrôle et refus

1. Effacement de l'ancien message de quota et du précédent indicateur de succès,
   sans vider les champs.
2. Validations existantes, honeypot du service et exigence d'au moins un contact.
3. Contrôle de tous les quotas et réservation d'une tentative sous verrou de cache.
4. Seulement après admission : création métier et notifications existantes.

Le verrou commun `public-forms:v1:reservation` a une durée de 10 secondes. La section
protégée ne comprend que les compteurs : aucun enregistrement métier ni email sous
ce verrou. Un quota bloqué affiche le plus long délai restant parmi les dimensions
qui bloquent actuellement. Un refus n'incrémente aucun autre compteur. Un verrou
occupé refuse sans attente avec réessai dans 2 secondes. Une panne ou un cache
inadapté refuse avec réessai dans 30 secondes ; seul le type d'exception est
journalisé, sans recopier les détails de connexion.

Les quotas réservent des **tentatives valides admises**, pas uniquement les écritures
réussies : un échec métier ultérieur peut consommer la tentative. Aucun remboursement
de compteur qui pourrait entrer en concurrence avec d'autres soumissions. Ce lot
n'ajoute pas d'idempotence métier générale ni de protection DDoS.

Le contact crée toujours un `Lead` sans ajout de notification. Le service conserve
sa demande, son événement et les notifications équipe/client. La récupération
invitée de la correction 05 et les décisions historiques restent inchangées.
Validation et honeypot ne consomment pas les quotas d'envoi. Aucun CAPTCHA ajouté.

## Interface

Nouveau composant `x-tk.submission-error` : rôle d'alerte, focus à l'apparition,
message de délai, confirmation que le texte reste présent. Tous les champs sont
conservés lors d'un refus. Une ancienne confirmation de succès n'est pas affichée
comme si le nouvel envoi refusé avait réussi. Le bouton est désactivé pendant
l'envoi ; cette aide visuelle ne remplace pas le contrôle serveur.

Le formulaire peut être soumis de nouveau après expiration, même sur une page
déjà ouverte. Aucun rafraîchissement ne doit être nécessaire pour reprendre.

## Cache et exploitation

Utilisation de `cache.limiter` s'il est défini, sinon de `cache.default`. Pilotes
admis : database, file, Redis, Memcached, DynamoDB ; array uniquement sous `testing`.
Null, failover et les autres pilotes sont refusés. Le même stockage est utilisé
pour les compteurs et le verrou. Aucun nouveau schéma requis ; database emploie
les tables Laravel existantes `cache` et `cache_locks`.

Tous les processus/serveurs doivent partager réellement compteurs, verrous, préfixe
et clé d'application, avec des horloges synchronisées et des sessions persistantes.
Des fichiers locaux indépendants sur plusieurs machines ne conviennent pas. La
configuration des proxies et la fiabilité de l'IP sur la cible doivent être
vérifiées séparément. Le verrou bref peut devenir un point de contention ; la
charge et le stockage de production ne sont pas recettés ici.

Voir les [conditions de déploiement](../../DEPLOIEMENT.md#protection-des-formulaires-publics--correction-09)
et les [règles fonctionnelles](../../03-cadre-fonctionnel.md#formulaires-publics--correction-09-27-octobre-2026).
Références officielles : [limitation Laravel](https://laravel.com/framework/docs/13.x/rate-limiting)
et [verrous de cache Laravel](https://laravel.com/framework/docs/13.x/cache#atomic-locks).

## Tests exécutés

PHP 8.4.26, SQLite isolé, cache array propre aux tests ou tables de cache dans cette
SQLite, notifications simulées et HTTP imprévu interdit dans les nouveaux scénarios.
Les vues compilées des tests sont séparées de celles de la recette.

```powershell
$env:APP_ENV='testing'
$env:DB_CONNECTION='sqlite'
$env:DB_DATABASE=':memory:'
$env:DB_URL=''
$env:MAIL_MAILER='array'
$env:QUEUE_CONNECTION='sync'
$env:CACHE_STORE='array'
$env:SESSION_DRIVER='array'

php vendor/bin/pest tests/Feature/PublicFormSubmissionTest.php tests/Feature/Public/VitrineTest.php tests/Feature/ServicesDevisTest.php tests/Feature/GuestRequestRecoveryTest.php --compact
php vendor/bin/pest --compact
php -d memory_limit=1G vendor/bin/phpstan analyse app --debug --no-progress --memory-limit=1G
php vendor/bin/pint --test app/Services/PublicFormSubmissionLimiter.php tests/Feature/PublicFormSubmissionTest.php resources/views/livewire/pages/public/contact.blade.php resources/views/livewire/pages/public/services-show.blade.php
npm.cmd run build
```

Exécuter les suites Pest séparément, sur les environnements de test indiqués.

- Ciblés finaux : **91 tests, 493 assertions**, tous réussis.
- Suite Pest complète sur le PHP et les vues finales : **491 tests,
  2 048 assertions**, tous réussis.
- Larastan séquentiel sur `app` : **0 erreur** sur la version finale.
- Pint conforme et syntaxe PHP valide sur les quatre fichiers PHP concernés.
- Build de production réussi ; manifeste et fichiers référencés vérifiés.

Les 22 nouveaux cas couvrent les deux formulaires, seuils, délai exact, appels
directs, conservation des champs, validation/honeypot, contact par téléphone seul,
quota commun aux fiches et au contact, sessions distinctes sur IP commune, rotation
des coordonnées/IP, compte non vérifié et changement de session, plafond réseau,
normalisation des coordonnées, contention du verrou, panne du cache et clés sans
données brutes. Le pilote database est testé avec SQLite et recréation de son
adaptateur, sans présenter cela comme une concurrence interprocessus.

Une première extension des tests utilisait le mauvais nom de table d'événements ;
les assertions ont été corrigées vers `request_events`, sans changement métier.

## Recette navigateur

Instance locale séparée sur le port 8015, nouvelle SQLite fictive, stockage/cache/
sessions/logs dédiés hors dépôt, notifications interceptées, mail array et HTTP
externe interdit. Aucun compte nécessaire. Guide, routes de recette et horloge
réglable sont hors dépôt ; ils ne font pas partie de la correction applicative.

L'horloge figée initiale provoquait des cookies déjà expirés pour le navigateur.
La recette seule utilise désormais des cookies de session sans date absolue.
Les quatre premiers messages de ce diagnostic restent dans la base fictive ; les
compteurs de la recette finale sont appréciés par différence, sans suppression.

Recette finale réalisée à 360 et 1 440 px, sur le bundle compilé :

- Contact : trois envois autorisés dans la même session, quatrième refusé malgré
  changement d'email. Compteur fictif passé de 4 à 7, puis resté à 7 au refus.
  Message et coordonnées conservés, focus sur l'alerte, Tab vers le bouton.
- Avance de 600 secondes dans le guide, puis renvoi du texte au clavier sur la
  page déjà ouverte : succès, champs remis à zéro et compteur contact à 8.
- Même session, service : deux demandes admises après ce contact, puis refus de la
  troisième demande de service, quatrième envoi de la fenêtre commune. Deux demandes
  et deux événements seulement ; besoin, entreprise et budget conservés, ancienne
  confirmation de succès retirée, focus sur l'alerte.
- Avance de 600 secondes, renvoi par Tab/Entrée sans recharger : troisième demande
  et troisième événement, succès et champs remis à zéro. Récupération invitée toujours
  proposée dans la confirmation ; aucune décision de devis enregistrée.
- Pas de débordement horizontal observé : largeur de contenu 345 pour viewport 360,
  et 1 425 pour viewport 1 440. Aucune erreur/avertissement console capturé. Ces
  contrôles ne sont pas une certification exhaustive du site. Le bouton WhatsApp
  flottant préexistant peut recouvrir une partie des champs/textes à certaines
  positions de défilement mobile ; l'alerte focalisée a été vérifiée lisible.

Captures fictives, sans identifiant de connexion :

- [Contact refusé, bureau 1 440 px](contact-refus-desktop-1440.jpg).
- [Contact refusé, mobile 360 px](contact-refus-mobile-360.jpg).
- [Service refusé, bureau 1 440 px](service-refus-desktop-1440.jpg).
- [Service refusé, mobile 360 px](service-refus-mobile-360.jpg).
- [Service repris après délai, mobile 360 px](service-reprise-mobile-360.jpg).

Dimensions restaurées et onglet automatisé fermé. Le guide sur
`http://127.0.0.1:8015/__recette-f09` et son serveur ont été conservés pour l'essai
demandé ensemble, puis le seul serveur de recette a été arrêté après confirmation
de l'utilisateur. Les autres instances n'ont pas été arrêtées. L'horloge avait
été avancée de 30 minutes sans purge pour rendre les quotas disponibles. Aucun
compte ni mot de passe requis. Les fichiers de recette hors dépôt permettent une
relance séparée si un nouvel essai est demandé ; l'URL n'est plus servie après arrêt.

Parcours manuel : ouvrir un formulaire depuis le guide, saisir des données
fictives et effectuer trois envois, vérifier le refus du quatrième et le texte
conservé. Garder cette page ouverte, ouvrir le guide dans un autre onglet, cliquer
« Avancer de 10 minutes », puis renvoyer sans recharger le formulaire. Le quota
étant commun, ne pas effectuer trois envois sur chaque formulaire dans la même
fenêtre. L'utilisateur a confirmé : **refus, texte conservé et reprise fonctionnent**.
Les autres scénarios techniques ne sont pas présentés comme personnellement validés
par lui.

## Fichiers du lot et limites

- Nouveau service `PublicFormSubmissionLimiter`, composant `submission-error`,
  actions/vues contact et service, tests `PublicFormSubmissionTest`.
- Cadre fonctionnel, déploiement, ce rapport et seulement la ligne/note 09 du journal.
- Les cinq captures listées ci-dessus.
- CSS `app-r-zsVh6O.css` remplace `app-Cz4owPm2.css`, avec manifeste cohérent.
  JavaScript et polices inchangés ; aucun `public/hot` ajouté.
- MySQL, SMTP réel, délivrabilité, cache partagé entre processus ou serveurs et charge
  de production **non validés**. Pilotes Redis/Memcached/DynamoDB non exécutés.
  Les tests cache database sont limités à SQLite ; la recette file est locale.
- Aucun email/paiement réel, aucune migration ou purge du cache habituel, aucun compte
  réel modifié, aucune autre correction commencée. Mode étranger d'`artisan` conservé.
  Aucun commit ni push pendant A.
