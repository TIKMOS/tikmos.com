# Correction 13 — Livraison et montant final

27 octobre 2026 · **Validé localement, non publié pendant A.**
Checkpoint 12 `5a17a0e869795123e40a898c137f776efedef0c2` vérifié sur
`origin/main` au début de l'intervention. Aucun commit ni push.

## Défaut reproduit avant correction

Le panier annonçait une livraison « À convenir » avec un « Total » composé
uniquement des articles lorsque `default_delivery_fee=0`. La commande ne conservait
pas le sens de ce zéro. Ni la création de commande en ligne ni l'ouverture directe
du paiement ne distinguaient un prix complet d'une livraison inconnue.

Les trois nouveaux tests F13 ont d'abord échoué sur cette version : **3 échecs,
3 assertions**, 5,135 s. Ils exigeaient « Total hors livraison », le refus d'une
commande en ligne incomplète et le refus d'ouverture directe d'un paiement sur
une commande historique ambiguë. Les reproductions de l'audit historique ne sont
ni modifiées ni présentées comme des corrections validées.

## Règles retenues et garanties

| État | Réglage serveur | Récapitulatif | Paiement en ligne |
| --- | --- | --- | --- |
| `unknown` | Frais encore à déterminer, ou configuration incohérente | « À déterminer », « Total hors livraison », accord commercial préalable et contact | Refusé côté serveur |
| `free` | Choix explicite de la gratuité et frais zéro | « Gratuite », total final des articles | Autorisé si les autres conditions de paiement/réservation sont remplies |
| `fixed` | Tarif entier strictement positif en FCFA | Frais affichés, total final articles + frais | Autorisé selon les mêmes protections |

Les réglages sont accessibles uniquement avec `settings.manage`. Les prix et
frais transmis par le client ne sont pas retenus. Le checkout conserve une
empreinte verrouillée des conditions ; si elles changent avant confirmation,
aucune commande n'est créée et les champs restent saisis. Le nouveau récapitulatif
doit être confirmé volontairement. L'état, les frais et le total sont conservés
dans la transaction de commande. Les changements ultérieurs de réglage ne
recalculent pas la commande ; sa reprise utilise son montant enregistré.

Les protections sont présentes dans `OrderService::place()`, l'ouverture d'un
paiement et sa reprise, ainsi que dans les actions Livewire. Un refus de livraison
inconnue ne crée ni commande, ni paiement, ni notification supplémentaire et
préserve panier/stock. L'expiration existante peut toujours restituer une
réservation arrivée à échéance ; cette restitution reste validée avant le refus.
Le paiement nominal conserve réservation, unicité de transaction, vérification
de montant/devise et idempotence. Les fixtures de paiement nominales ont été
rendues explicitement gratuites ; les tests historiques ambigus sont distincts.

Pour les installations existantes, deux migrations additives : setting dans un
groupe `delivery` séparé et colonne nullable `orders.delivery_pricing`. Un ancien
tarif entier positif initialise `fixed` pour les **nouvelles** commandes ; un zéro
ne prouve jamais une gratuité et initialise `unknown`. Aucun montant ou paiement
historique n'est réécrit. Les commandes existantes gardent `NULL`, annoncent
« Montant enregistré » et « livraison à vérifier » et ne reprennent pas un
paiement en ligne incomplet. Le groupe séparé permet aux anciennes migrations
qui chargent `ShopSettings` de continuer à s'exécuter sur une installation neuve.

Une confirmation bancaire ancienne réellement reçue reste enregistrée comme
payée, avec revue manuelle et événement `delivery_review_required` si la livraison
n'est pas documentée. Aucun double événement au rejeu ni confirmation automatique
de commande dans ce cas. Aucun remboursement ou supplément automatique.

La commande COD à frais inconnus reste en attente d'une validation commerciale.
L'équipe doit communiquer les frais et le montant final et recueillir l'accord
avant paiement. Cette étape reste **manuelle** : pas de nouveau workflow de
négociation, de saisie/approbation par commande ou de conversion automatique en
paiement en ligne. Les actions administratives COD existantes et leur gestion de
stock sont conservées ; cette correction ne prétend pas automatiser cet accord.

## Contrôles exécutés

PHP 8.4.26 ; bases SQLite isolées, cache/sessions array pour Pest, vues compilées
dans un répertoire privé. Notifications et appels bancaires simulés ; aucune
migration, purge ou écriture dans la base/cache habituels.

| Contrôle | Résultat observé |
| --- | --- |
| Première non-régression après les gardes serveur et migrations | 118 tests, 598 assertions, 35,769 s |
| Cas livraison intermédiaires | 27 tests, 161 assertions, 12,583 s |
| Ciblés finaux : livraison, panier, commande, paiement, reprise, migration de réservation et settings | **149 tests, 784 assertions**, 62,106 s, tous réussis |
| Suite Pest complète | **591 tests, 2 629 assertions**, 302,968 s, tous réussis |
| Larastan séquentiel `--debug`, `app`, nouvelles migrations et actions Volt concernées | **0 erreur** |
| Analyse élargie de `config/settings.php` | 1 erreur préexistante : classe optionnelle `Spatie\LaravelData\Data` absente ; même erreur reproduite sur le fichier de HEAD dans `config/`, puis copie temporaire retirée |
| Pint `--test` et `php -l` | Conformes sur les **24 fichiers PHP/Blade** concernés |
| `npm run build` final | Réussi, 11,99 s ; manifeste et fichiers vérifiés |

Les suites Pest ont été exécutées séparément. La suite complète précède les
derniers ajustements purement Blade : deux boutons raccourcis sur mobile et le
récapitulatif placé avant le formulaire dans l'ordre du DOM. Les **149 tests
ciblés ont été relancés sur ces vues finales**, ainsi que Pint/syntaxe et le build.
L'erreur de dépendance optionnelle de la configuration n'a pas été masquée ou
attribuée au nouveau code ; aucun résultat global « zéro erreur » n'est revendiqué
pour ce périmètre élargi. Les enregistrements d'analyse de référence restent locaux.

Couverture : les trois états, configurations incohérentes, paramètres monétaires
client falsifiés, appels directs, reprise, changement de conditions avant
confirmation, maintien d'une commande connue après changement, paiements anciens,
rejeux, migration/rollback sans réécriture, réglages et droits, modes de paiement
indisponibles. La suite complète préserve les corrections précédentes.

## Recette navigateur et preuves

Base SQLite, cache fichier persistant, sessions et stockage privés. Guide et faux
SDK hors dépôt. Comptes/produit/adresses fictifs, notifications interceptées et
vérification bancaire simulée ; aucun secret ou identifiant de connexion dans les
captures. Le routeur de recette a été réparé pour servir les réponses binaires
Livewire avant de valider les parcours ; ce défaut du banc d'essai n'est pas un
défaut de l'application.

L'agent a parcouru les trois scénarios panier → checkout → commande :

- livraison inconnue : récapitulatif avant le formulaire sur mobile, explication,
  lien de contact, absence du choix en ligne, transmission COD et commande hors
  livraison sans bouton de reprise ;
- gratuité : total 1 000 FCFA et COD conservé ;
- tarif fixe : 250 FCFA de frais, total 1 250 FCFA, même commande reprise, faux
  widget à 1 250 FCFA, succès simulé, état Confirmée/Payée après rechargement ;
- passage ultérieur des réglages à 300 FCFA puis inconnus : commande payée
  toujours à 1 250 FCFA ; réglage au clavier, formulaire sans adresse refusé,
  chargement visible, texte et prochain champ accessibles par Tab/Entrée.

Largeurs 360 et 1440 px vérifiées, aucun débordement horizontal observé sur les
vues concernées après raccourcissement des boutons. L'ordre de tabulation mobile
va du contact du récapitulatif au nom du formulaire. Aucune erreur console
observée pendant les parcours finaux. Le bouton WhatsApp peut ponctuellement
recouvrir du contenu mobile comme déjà signalé dans les lots précédents ; aucun
changement de ce composant hors lot. Les barres fixes peuvent apparaître à leur
position de capture dans les images pleine page.

**L'utilisateur a confirmé les trois parcours et leurs montants.** Les tests de
falsification, migration, autorisation et idempotence restent des vérifications
techniques de l'agent, sans lui attribuer leur validation personnelle.
Dimensions restaurées, onglets temporaires fermés et seul serveur F13 arrêté.

| Preuve | Capture |
| --- | --- |
| Panier hors livraison, bureau | [Image](panier-inconnu-desktop-1440.jpg) |
| Checkout hors livraison, mobile, bouton final | [Image](checkout-inconnu-mobile-360.jpg) |
| Commande hors livraison, mobile | [Image](commande-inconnue-mobile-360.jpg) |
| Panier explicitement gratuit | [Image](panier-gratuit-desktop-1440.jpg) |
| Commande explicitement gratuite | [Image](commande-gratuite-desktop-1440.jpg) |
| Checkout avec frais inclus | [Image](checkout-fixe-desktop-1440.jpg) |
| Commande payée par simulation, total conservé | [Image](commande-fixe-payee-desktop-1440.jpg) |
| Réglages des nouvelles commandes, mobile | [Image](reglages-livraison-mobile-360.jpg) |

## Fichiers du lot

- Application : `app/Enums/DeliveryPricing.php`, `app/Settings/DeliverySettings.php`,
  `app/Settings/ShopSettings.php`, `app/Models/Order.php`,
  `app/Notifications/OrderReceived.php`, `app/Services/OrderService.php`,
  `app/Services/PaymentService.php`, `app/Services/OrderReservationService.php`.
- Configuration/routes : `config/settings.php`, `routes/admin.php`.
- Migrations : `database/settings/2026_10_27_030000_add_delivery_pricing_mode.php`,
  `database/migrations/2026_10_27_030100_add_delivery_pricing_to_orders.php`.
- Vues : `resources/views/components/tk/delivery-notice.blade.php`,
  `resources/views/components/tk/delivery-summary.blade.php`,
  `resources/views/livewire/pages/public/cart.blade.php`,
  `resources/views/livewire/pages/public/checkout.blade.php`,
  `resources/views/livewire/pages/app/orders-index.blade.php`,
  `resources/views/livewire/pages/app/orders-show.blade.php`,
  `resources/views/livewire/pages/admin/boutique/orders.blade.php`,
  `resources/views/livewire/pages/admin/reglages.blade.php`,
  `resources/views/livewire/pages/admin/reglages/delivery.blade.php`.
- Tests : `tests/Feature/DeliveryPricingTest.php`, `tests/Feature/PaymentTest.php`,
  `tests/Feature/PaymentRecoveryTest.php`.
- Documentation : `docs/03-cadre-fonctionnel.md`, `docs/05-base-de-donnees.md`,
  `docs/30-systeme-paiement.md`, `docs/DEPLOIEMENT.md`, uniquement la ligne 13 et
  la note 13 de `docs/prompts/audit-2026-10-20/JOURNAL.md`, ce rapport et huit images.
- Build : `public/build/manifest.json`, ajout `assets/app-CvatQWas.css`, suppression
  `assets/app-DXEy4714.css`. JavaScript, polices et manifeste des polices inchangés.

## Limites et futur déploiement

MySQL, migration/concurrence MySQL, concurrence des réglages, cache partagé et
cache de configuration en production, SMTP réel/délivrabilité et KKiaPay sandbox
**non validés**. SQLite et le faux widget ne les remplacent pas. La migration n'a
pas été appliquée à la base habituelle. Aucun déploiement, paiement ou email réel.
L'index est resté vide et le mode étranger d'`artisan` est préservé.

Avant un déploiement autorisé, livrer le code, les deux migrations, la configuration
du nouveau groupe et les assets ensemble ; choisir explicitement le mode pour les
nouvelles commandes, examiner séparément les commandes historiques et définir le
traitement commercial manuel des frais inconnus. Voir les instructions de
[déploiement](../../DEPLOIEMENT.md#livraison-et-total-final--correction-13).
Aucune correction 14 n'a été commencée.
