# Correction 06 — Provisionnement explicite du premier administrateur

Validation locale du 24 octobre 2026, phase A uniquement. Checkpoint 05
`64fa40ee2355dc2d5c670f19a8290e2f9a5ebf4e` vérifié sur `origin/main` avant intervention.
L'[audit historique F06](../../audits/2026-10-20/README.md) reste inchangé.

## Cause et reproduction avant correction

`DatabaseSeeder` appelait systématiquement `AdminSeeder`. Ce dernier comportait
trois identités administrateur, un secret de repli faible et un `syncRoles`
réattribuant le rôle SuperAdmin à une adresse déjà présente. L'idempotence de
`firstOrCreate` préservait le mot de passe, mais pas les rôles supplémentaires.

Le nouveau test `ne crée aucun compte privilégié lors du seeding courant sans
configuration` a été exécuté avant modification sur SQLite en mémoire :
**0 compte attendu, 3 obtenus**. Il passe après correction avec zéro compte créé.
Ce constat ne démontre pas que les comptes actuellement déployés utilisent le
secret de repli : aucun compte réel ni aucune configuration de production n'a
été examiné.

## Comportement obtenu

- `DatabaseSeeder` initialise les rôles/permissions et ne crée aucun utilisateur,
  même si les variables de provisionnement sont renseignées.
- L'appel volontaire `php artisan db:seed --class=AdminSeeder` exige
  `TIKMOS_ADMIN_NAME`, `TIKMOS_ADMIN_EMAIL`, `TIKMOS_ADMIN_PASSWORD`, sans valeur
  de repli. Seuls les fichiers `config/` appellent `env()` ; le seeder lit la
  configuration chargée, conformément au fonctionnement du cache Laravel.
- Nom non vide de 255 caractères maximum, email valide normalisé en minuscules,
  mot de passe de 16 caractères minimum avec majuscule, minuscule, chiffre et
  symbole, de 72 octets UTF-8 maximum et sans octet nul. La limite en octets
  protège la compatibilité avec bcrypt. Le secret n'est jamais affiché.
- La création est réservée au premier SuperAdmin : en présence d'un SuperAdmin
  sous une autre adresse, le seeder refuse et oriente vers la gestion du personnel.
  Aucune identité secondaire personnelle n'est prédéfinie.
- Un SuperAdmin existant à l'adresse configurée reste intégralement inchangé :
  nom, email, mot de passe, statut, 2FA et rôles supplémentaires. Un compte suspendu
  n'est pas réactivé. Les trois données de configuration restent requises et
  valides pour une répétition ; ce mécanisme ne réinitialise pas un mot de passe.
- Une adresse déjà utilisée par un client ou un autre rôle, un compte supprimé,
  ou plusieurs correspondances historiques de casse différente provoque un refus
  sans promotion, restauration ou sélection arbitraire.
- Transaction avec verrou commun du rôle SuperAdmin, vérification des comptes
  puis création et affectation du rôle. L'email conserve sa contrainte unique.
  L'échec d'affectation du rôle annule aussi la création du compte. Les erreurs
  SQL sont remplacées par un message sans requête, paramètres ou hachage.
- Le nouvel administrateur est actif, avec secret haché, sans marquer son email
  comme vérifié et sans appareil de confiance. Le challenge 2FA existant reste
  requis selon la configuration du site ; le test simule la notification.

Le [guide de déploiement](../../DEPLOIEMENT.md#21-créer-explicitement-le-premier-administrateur)
décrit la préparation des rôles, l'appel explicite, le retrait des variables
sensibles après réussite et la reconstruction du cache sans le secret initial.
La revue des comptes déjà déployés reste une opération distincte autorisée,
sans export de mots de passe ou de hachages et sans modification automatique.

Références officielles : [seeders explicites Laravel](https://laravel.com/framework/docs/seeding#running-seeders),
[cache de configuration](https://laravel.com/framework/docs/configuration#configuration-caching).

## Tests et contrôles

Environnement : `APP_ENV=testing`, SQLite en mémoire, `DB_URL` vide, cache/session
`array`, mail mémoire et queue synchrone. Les nouveaux tests simulent les
notifications et interdisent les appels HTTP imprévus. Les variables admin sont
retirées du seul processus de test puis remplacées par de la configuration fictive ;
le `.env` réel n'est pas modifié. Les secrets nominaux des tests sont générés
aléatoirement en mémoire, sans affichage.

| Contrôle | Résultat |
|---|---|
| Provisionnement initial | **40 tests, 174 assertions**, réussis |
| Provisionnement + UserManagementTest + Auth | **76 tests, 283 assertions**, réussis |
| Démonstration ciblée F06 | **1 test, 18 assertions**, réussi |
| Suite Pest complète, sur la version finale | **395 tests, 1 530 assertions**, réussis |
| Larastan sur `app`, les deux seeders et `config/admin.php` | **0 erreur**, niveau 6, exécution séquentielle |
| Pint sur les quatre fichiers PHP modifiés | Conforme |

Commande d'analyse exécutée :

```powershell
php -d memory_limit=1G vendor/bin/phpstan analyse app database/seeders/AdminSeeder.php database/seeders/DatabaseSeeder.php config/admin.php --debug --no-progress --memory-limit=1G
```

`--debug` désactive le parallélisme. Le premier lancement avec plusieurs processus
restait sans résultat sous Windows ; seuls ses processus d'analyse ont été arrêtés.
L'analyse séquentielle a permis de corriger un contrôle de présence de la commande
console : le parcours documenté `db:seed` fournit toujours sa commande au seeder.
Le contrôle final passe sans suppression d'erreur ni ajout à la baseline.

Cas couverts : configuration absente/incomplète/invalide, types invalides,
mot de passe de repli refusé, politique de mot de passe et limite UTF-8, seeding
courant avec ou sans configuration, création explicite unique, répétition sans
réinitialisation, normalisation des coordonnées, conservation des attributs et
rôles supplémentaires d'un SuperAdmin actif/suspendu, collisions client/personnel/
compte sans rôle, compte supprimé, autre SuperAdmin existant, ambiguïté de casse,
rôle manquant, rollback d'affectation, absence de secret ou hachage dans la sortie,
configuration chargée différente de l'environnement, challenge 2FA conservé.

## Démonstration reproductible sans compte réel

Depuis la racine du projet, dans un terminal de test dédié :

```powershell
$env:APP_ENV = 'testing'
$env:DB_CONNECTION = 'sqlite'
$env:DB_DATABASE = ':memory:'
$env:DB_URL = ''
$env:MAIL_MAILER = 'array'
$env:QUEUE_CONNECTION = 'sync'
$env:CACHE_STORE = 'array'
$env:SESSION_DRIVER = 'array'
php vendor/bin/pest tests/Feature/Admin/AdministratorProvisioningTest.php --filter='demonstration F06' --compact
```

Cette commande est la démonstration exécutée : elle refuse le provisionnement
sans configuration, exécute le seeding courant avec zéro utilisateur, génère un
secret fictif en mémoire, crée un seul administrateur, puis rejoue le seeder avec
une autre configuration pour vérifier que nom et secret existants restent inchangés.
Les messages de création et de conservation sont vérifiés sans imprimer le secret.
La sortie attendue est un succès Pest de **1 test, 18 assertions**. Fermer ce
terminal après l'essai pour ne pas conserver ses variables de test.

Ne pas exécuter le seeder de provisionnement directement sur la base habituelle
pour reproduire cette recette. Les commandes de production du guide sont des
instructions de déploiement destinées à un opérateur autorisé ; elles n'ont pas
été exécutées sur la plateforme de l'utilisateur pendant cette intervention.

## Revalidation avant publication — 26 octobre 2026

Les sept fichiers présents correspondent aux empreintes de la version validée
en phase A. Le fichier non suivi `config/admin.php` était absent lors de la
préparation de B ; il a été rétabli avec les trois variables obligatoires, sans
valeur de repli. Un contrôle direct confirme leur chargement et leur absence de
valeur par défaut, avec des valeurs fictives en mémoire et sans afficher le secret.

Après ce rétablissement, les contrôles ont été relancés : ciblés provisionnement,
gestion utilisateur et authentification **76 tests, 283 assertions** ; suite Pest
complète **395 tests, 1 530 assertions** ; Larastan séquentiel sur les chemins
ci-dessus **0 erreur** ; Pint et syntaxe des quatre fichiers PHP conformes.
Les cas de provisionnement et la démonstration font partie des suites relancées.
SQLite en mémoire, mail mémoire et notifications simulées : aucune intervention
sur les comptes, la base ou le cache de configuration de l'application habituelle.

Les liens locaux du lot existent ; les deux liens officiels Laravel répondent
HTTP 200. Les variables administrateur de `.env.example` restent vides et le
contrôle des fichiers à publier n'a relevé aucun secret ou identifiant personnel.
Les limites MySQL, SMTP et production ci-dessous restent inchangées.

## Fichiers du lot

```text
.env.example
config/admin.php
database/seeders/AdminSeeder.php
database/seeders/DatabaseSeeder.php
tests/Feature/Admin/AdministratorProvisioningTest.php
docs/DEPLOIEMENT.md
docs/prompts/audit-2026-10-20/JOURNAL.md
docs/validations/2026-10-24-admin/README.md
```

Aucune vue, aucun asset et aucune migration modifiés. Aucun build ou contrôle
visuel supplémentaire nécessaire pour ce changement de provisionnement CLI.
Le changement de mode préexistant d'`artisan` reste étranger au lot.

## Limites et état

- Concurrence réelle MySQL : **non validée** ; les tests utilisent SQLite en mémoire.
- Configuration de production et secrets des comptes déployés : **non examinés**.
  Les comptes déjà créés par l'ancien seeder ne sont ni modifiés ni supprimés.
- SMTP/délivrabilité du code 2FA : **non validés**, notifications simulées.
- Cache de configuration réel de l'application : **non modifié**. L'usage de la
  configuration chargée est testé ; aucune commande de cache n'a été exécutée
  sur l'instance habituelle pour cet essai.
- Aucun compte réel créé, aucun rôle révoqué, aucun mot de passe réel changé.
  Aucune migration de la base habituelle, aucun déploiement, commit ou push.
- Validé localement avec les contrôles ci-dessus ; aucune autre correction commencée.
