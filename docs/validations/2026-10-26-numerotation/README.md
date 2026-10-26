# Correction 08 — Numérotation atomique des commandes

Validation locale du 26 octobre 2026, sans commit, push ni déploiement pendant A.
Checkpoint 07 vérifié sur `origin/main` :
`eba6da6df8995689907f22cb14f410c610077111`.

## Défaut reproduit avant correction

`OrderService::generateNumber()` utilisait le nombre de commandes dont `created_at`
appartenait à l'année courante, augmenté de un. Ce total ne représente pas le
dernier numéro attribué. Deux écrivains pouvaient aussi calculer le même résultat.

Le nouveau test exigeant un numéro inédit a été lancé avant le remplacement : avec
les commandes fictives `TKM-CMD-2026-00001` et `TKM-CMD-2026-00003`, l'ancien code
tentait `00003` et échouait sur `UNIQUE constraint failed: orders.number`, au lieu
d'attribuer `00004`. Cette preuve utilise SQLite isolé ; elle ne prétend pas
reproduire une concurrence MySQL. L'audit historique est conservé.

## Allocation et garanties

- Nouvelle table `order_number_sequences`, clé primaire annuelle `year`, compteur
  persistant `last_number`. Format conservé : `TKM-CMD-YYYY-00001`. Cinq chiffres
  minimum ; après `99999`, la séquence continue à `100000` sans troncature.
- `OrderService` commence sa transaction par un `upsert` du compteur annuel.
  Sur conflit, seule l'année est réécrite à l'identique : le compteur existant
  n'est jamais remis à zéro. Cette première instruction SQL obtient le verrou
  d'écriture avant toute lecture sous SQLite DEFERRED. La ligne est ensuite lue
  sous `lockForUpdate()` pour les moteurs qui prennent en charge ce verrou.
- L'année est capturée une fois dans `app.timezone`. L'incrément, la commande,
  les articles et le stock utilisent la même transaction. Le changement d'année
  sélectionne un autre compteur ; aucune dépendance au nombre de lignes restantes.
- La sérialisation par année couvre toute la création de commande. C'est un choix
  simple qui peut réduire le débit sous forte charge ; aucun appel fournisseur
  n'est ajouté dans cette transaction. Les verrous client et produit existants
  ainsi que les trois tentatives transactionnelles Laravel sont conservés.
- La contrainte unique `orders.number` reste présente. Une erreur non récupérable
  est propagée et la transaction annulée, sans commande partielle ni stock perdu.
  Aucun conflit d'un autre domaine n'est transformé en succès.
- Un rejeu de checkout retourne la commande existante sans nouvel incrément ni
  notification. Une suppression après validation ne libère pas le numéro.
  Un rollback annule aussi l'allocation non validée : ce numéro peut être réutilisé
  par une transaction ultérieure. Les suppressions et plafonds peuvent créer des
  trous ; aucune continuité n'est promise.
- Un compteur absent devant des références historiques, négatif ou épuisé provoque
  un refus explicite. Aucun recalcul ou abaissement silencieux n'est effectué.
- Les devis et demandes conservent leur mécanisme actuel : `ReferenceGenerator`
  n'est pas modifié. Aucune vue ni aucun asset n'est modifié par cette correction.

Le comportement de verrouillage visé s'appuie sur les documentations officielles :
[transactions SQLite](https://www3.sqlite.org/lang_transaction.html),
[verrous InnoDB pour les écritures](https://dev.mysql.com/doc/refman/8.4/en/innodb-locks-set.html),
[upsert et verrouillage Laravel](https://laravel.com/framework/docs/queries).
Ces sources expliquent la stratégie ; elles ne remplacent pas une recette MySQL.

## Migration de l'existant

La nouvelle migration
`2026_10_26_010000_create_order_number_sequences_table.php` ajoute uniquement les
compteurs. Elle prévalide la configuration et l'historique **avant le DDL**, qui
n'est pas annulable comme une transaction de données sur MySQL. Elle ne modifie
aucun numéro, paiement, statut, date ou URL historique. Aucune ancienne migration
n'a été réécrite.

La lecture des numéros détermine les maxima par année depuis leur référence,
même si `created_at` appartient à une autre année. Les références d'un autre format
restent intactes. Les variantes de casse et de remplissage numérique restent
intactes et contribuent au maximum. Un préfixe `TKM-CMD-` mal formé, une année zéro
ou une séquence nulle/hors limites provoque un refus sans recopier la référence
dans le message d'erreur.

Une installation neuve sans historique fonctionne avec
`ORDER_NUMBER_SEQUENCE_FLOORS` vide. Sur une installation existante, un objet JSON
doit fournir un plafond vérifié pour **chaque année historique présente**, supérieur
ou égal au maximum connu. Des années entièrement supprimées peuvent être ajoutées.
Les nombres doivent être des entiers non négatifs, les années sur quatre chiffres.
Un plafond absent ou insuffisant, un JSON invalide ou des types inadéquats arrêtent
la migration avant la création de table.

La vérification humaine des archives demeure nécessaire : un numéro définitivement
supprimé ne peut pas être deviné depuis les lignes restantes. Les plafonds doivent
inclure ces numéros avant l'ouverture des écritures. Leur vérité ne peut pas être
attestée par le seul programme. Un import futur doit réconcilier explicitement les
compteurs vers le haut sous interruption des écritures.

La [procédure de déploiement](../../DEPLOIEMENT.md#numérotation-atomique-des-commandes--correction-08)
prévoit sauvegarde, arrêt de tous les écrivains, configuration des plafonds et prise
en compte du cache avant migration. Les tables concernées doivent être
transactionnelles sur MySQL. Ne pas utiliser simultanément ancien et nouvel
écrivain. La variable de configuration ne modifie plus les compteurs après cette
initialisation. Un rollback de schéma conserve les commandes/paiements mais supprime
la mémoire des compteurs : sauvegarde et plafonds vérifiés indispensables avant
réinstallation.

**La migration n'a pas été appliquée à la base habituelle**, ni aux anciennes
instances de recette. Ce code exige la nouvelle table avant d'y créer une commande ;
il ne faut pas le tester sur une base non préparée. Tous les scénarios ci-dessous
utilisent des bases de test dédiées.

## Tests et preuves

Nouveaux tests : `OrderNumberingTest`, `OrderNumberMigrationTest`,
`OrderNumberConcurrencyTest` et processus auxiliaire `tests/Support/order-number-worker.php`.

Les tests couvrent les trous, la suppression de toutes les dernières commandes,
les anciens numéros élevés, les plafonds incluant des suppressions, le passage
à six chiffres, les autres formats, la conservation des attributs historiques et
l'accès HTTP du propriétaire à son ancienne URL. Horloge figée : passage de
2026 à 2027 dans `Africa/Lagos`, alors que l'heure UTC appartient encore à 2026.
Rollback métier, compteur épuisé, rejeu sans double notification et erreurs de
migration sont également contrôlés.

La concurrence est réellement exécutée dans **quatre processus PHP distincts**,
avec quatre connexions à un fichier SQLite temporaire, quatre clients et quatre
produits distincts. Une barrière libère les quatre créations ensemble et chaque
processus maintient le verrou pendant 200 ms. Le test vérifie le chevauchement des
opérations, les PID distincts, quatre numéros uniques, quatre commandes/articles,
un seul retrait par produit et le compteur final. Deux scénarios : première
allocation annuelle et compteur déjà initialisé à 1000. Cela teste la concurrence
SQLite, sans présenter les verrous client/produit comme preuve d'allocation.

Commandes depuis la racine, PHP 8.4.26, avec environnement forcé :

```powershell
$env:APP_ENV='testing'
$env:DB_CONNECTION='sqlite'
$env:DB_DATABASE=':memory:'
$env:DB_URL=''
$env:MAIL_MAILER='array'
$env:QUEUE_CONNECTION='sync'
$env:CACHE_STORE='array'
$env:SESSION_DRIVER='array'

php vendor/bin/pest tests/Feature/OrderNumberingTest.php tests/Feature/OrderNumberMigrationTest.php tests/Feature/OrderNumberConcurrencyTest.php tests/Feature/OrderFlowTest.php tests/Feature/CartSizeTest.php tests/Feature/PaymentTest.php tests/Feature/PaymentRecoveryTest.php tests/Feature/OrderReservationMigrationTest.php --compact
php vendor/bin/pest --compact
php -d memory_limit=1G vendor/bin/phpstan analyse app config/orders.php database/migrations/2026_10_26_010000_create_order_number_sequences_table.php tests/Support/order-number-worker.php --debug --no-progress --memory-limit=1G
```

- Ciblés numérotation/migration/commande/stock/paiement/reprise : **157 tests,
  746 assertions**, tous réussis sur le code final.
- Concurrence seule : **3 tests, 32 assertions**, réussis (contrat transactionnel
  et deux scénarios à quatre processus).
- Suite Pest complète finale exécutée seule : **469 tests, 1 868 assertions**,
  tous réussis. Les trois nouveaux fichiers de tests ajoutent **41 tests et
  149 assertions** aux contrôles de la correction précédente.
- Démonstration seule rejouée après la suite finale : **1 test, 8 assertions**,
  réussi sur SQLite en mémoire.
- Larastan sur `app`, configuration, migration et processus auxiliaire : **0 erreur**.
- Syntaxe `php -l` et Pint `--test` conformes sur les **huit fichiers PHP** du lot.
  Aucun build nécessaire : aucun changement de vue, CSS ou JavaScript.

Un premier lancement complet a échoué : la démonstration comparait les attributs
d'un modèle nouvellement créé, sans les valeurs par défaut de la base, à un modèle
relu. Le test capture désormais les deux états après lecture en base, sans retirer
d'assertion. Ce lancement simultané à une autre suite a aussi rencontré un fichier
Blade compilé manquant. Les contrôles Pest finaux sont exécutés séparément ; aucun
code applicatif étranger n'a été modifié pour contourner ces erreurs.

## Démonstration locale reproductible

Après avoir forcé l'environnement de test ci-dessus, lancer séparément :

```powershell
php vendor/bin/pest tests/Feature/OrderNumberingTest.php --filter='demonstration 08' --compact
```

Le scénario crée un historique fictif `00001` et `00003`, initialise un plafond
vérifié fictif à 10, crée `00011` et `00012`, supprime `00012`, puis obtient
`00013`. Les deux anciennes commandes restent identiques, quatre commandes restent
en base et trois notifications simulées sont comptées. Aucune donnée n'est
persistée dans la base habituelle. Ce test permet de refaire la preuve ensemble
sans compte réel ni interface temporaire ; il n'est pas présenté comme une
validation personnelle de l'utilisateur.

## Revalidation avant publication — 26 octobre 2026

Le fichier temporaire des empreintes de A n'étant plus disponible, les fichiers
ont été relus et les contrôles requis réexécutés avant le commit, sans changement
de code applicatif : ciblés **157 tests, 746 assertions**, puis suite complète
seule **469 tests, 1 868 assertions**, tous réussis. La concurrence SQLite et la
démonstration font partie de ces suites. Larastan : **0 erreur** ; Pint et syntaxe
conformes sur les huit fichiers PHP. Les vues compilées et la configuration des
tests utilisent des chemins temporaires dédiés, avec SQLite isolé.

Liens locaux présents, journal limité à sa ligne/note 08, variables administrateur
et plafond vides dans `.env.example`, aucun secret détecté dans les ajouts revus.
Les liens officiels SQLite et Laravel répondent HTTP 200 ; le serveur Oracle/MySQL
répond HTTP 403 à la vérification automatisée, sans permettre de conclure que la
page est absente. La référence documentaire est conservée. Aucun asset ajouté.
Les limites externes et l'absence de migration de la base habituelle restent
inchangées.

## Périmètre et limites

- Fichiers applicatifs : `app/Services/OrderService.php`,
  `app/Support/OrderNumberAllocator.php`, `config/orders.php`, nouvelle migration,
  `.env.example` (variable vide). Tests : les trois fichiers Feature et le processus
  auxiliaire ci-dessus. Documentation : [schéma](../../05-base-de-donnees.md),
  [déploiement](../../DEPLOIEMENT.md), ce rapport et seulement la ligne/note 08 du
  [journal](../../prompts/audit-2026-10-20/JOURNAL.md).
- Migration MySQL et concurrence MySQL **non validées**. Les tests SQLite ne
  prouvent pas les verrous InnoDB, les permissions ni les paramètres de production.
  Le réglage du délai d'attente SQLite de la cible reste à vérifier si nécessaire.
- Anciennes suppressions absentes de toute archive : plafond historique impossible
  à établir automatiquement. Aucune promesse d'absence de réutilisation d'un
  numéro effacé avant migration sans plafond fiable.
- KKiaPay sandbox, emails réels/SMTP et déploiement **non testés** ; HTTP et
  notifications simulés. Aucun paiement, compte réel, migration de la base habituelle,
  commande d'expiration ou commande de cache habituelle exécuté.
- Mode étranger d'`artisan` conservé ; index laissé inchangé. Aucun commit/push
  pendant A, aucune autre correction commencée.
