# Correction 03 — Stock global entre variantes

Validation locale du 24 octobre 2026, avant publication. Le checkpoint précédent
`6ad360bcda5d778c93d3c70a166cc9a3e86da654` a été vérifié sur `origin/main`.
L'[audit historique](../../audits/2026-10-20/README.md) reste inchangé : ses tests
attendent les défauts historiques et ne constituent pas une validation des corrections.

## Cause et reproduction avant correction

Le panier plafonnait chaque ligne séparément au stock du produit. Deux variantes
du même produit pouvaient donc demander chacune deux unités pour un stock global
de deux. La commande décrémentait ensuite le stock pour chacune de ces lignes,
sans vérification du cumul sous verrou.

Le nouveau test `refuse un panier historique de quatre unités sur deux variantes
pour un stock global de deux` reprend ce panier en session (`41 => 2`, `42 => 2`).
Avant correction, il échouait : stock attendu **2**, obtenu **−2**. Après correction,
il vérifie un refus de validation, un stock inchangé, aucune commande, un panier
conservé et aucune notification. Aucune donnée réelle n'est utilisée.

## Comportement obtenu

- Le modèle reste un stock **global par produit**, partagé entre toutes les tailles.
  L'ajout et l'augmentation d'une quantité contrôlent le cumul des variantes avec
  le stock relu en base. Un refus ne modifie pas la session.
- Une baisse de stock ne tronque plus silencieusement les quantités demandées.
  Le client peut diminuer progressivement les lignes ou les retirer, même si une
  première diminution ne suffit pas encore. La commande reste interdite tant que
  le cumul dépasse le disponible. Les produits retirés du catalogue restent purgés.
- À la commande, les produits sont relus dans une transaction avec `lockForUpdate()`,
  dans l'ordre des identifiants. Prix et disponibilités proviennent de cette lecture.
  Tous les cumuls sont vérifiés **avant** de créer la commande et ses lignes ; le
  stock est décrémenté une seule fois par produit. Une erreur annule les écritures.
  La session est vidée et la notification déclenchée après réussite de la transaction.
- Un refus indique le produit, le stock global disponible et la quantité demandée.
  Le panier reste corrigeable ; la fiche produit et la confirmation proposent un
  lien vers celui-ci. Au checkout, l'erreur apparaît près du bouton de confirmation.
- Les stocks `NULL` restent non gérés ; la limite existante de 20 unités par ligne,
  les variantes valides et les commandes nominales sont préservées. Le récapitulatif
  distingue les tailles et utilise une clé Livewire par ligne de variante.

Le choix de verrouillage suit les mécanismes Laravel de
[verrouillage pessimiste](https://laravel.com/docs/13.x/queries#pessimistic-locking)
et de [transactions](https://laravel.com/docs/13.x/database#database-transactions).
Il ne constitue pas une preuve d'exécution concurrente sur MySQL.

## Contrôles exécutés

Les tests utilisent `APP_ENV=testing`, `DB_CONNECTION=sqlite`,
`DB_DATABASE=:memory:`, `DB_URL` vide, `MAIL_MAILER=array`,
`QUEUE_CONNECTION=sync` et les pilotes de session/cache de test.
Les deux fichiers ciblés interceptent les notifications et interdisent tout appel
HTTP imprévu. Aucun paiement réel ni email externe n'a été envoyé.

| Contrôle | Résultat réel |
|---|---|
| `php vendor/bin/pest tests/Feature/CartSizeTest.php tests/Feature/OrderFlowTest.php --compact` | **24 tests, 125 assertions**, réussis ; relancé après les derniers ajustements Blade |
| `php artisan test --compact` | **297 tests, 995 assertions**, réussis |
| `php -d memory_limit=1G vendor/bin/phpstan analyse --no-progress --memory-limit=1G` | **0 erreur** |
| `php vendor/bin/pint --test app/Services/CartService.php app/Services/OrderService.php tests/Feature/CartSizeTest.php tests/Feature/OrderFlowTest.php` | Conforme |
| `npm.cmd run build` | Réussi ; manifeste et CSS compilé actualisés |

Cas couverts : cumul à l'ajout, refus sans mutation du panier, augmentation et
correction des quantités, baisse de stock avant confirmation (dont zéro), lecture
initiale périmée, refus global d'une commande de plusieurs produits, rollback
pendant le décrément, stocks non gérés, commandes nominales et conservation des tailles.
La suite complète couvre aussi les corrections de paiement précédentes.

Le bundle CSS devient `public/build/assets/app-t2tCysDR.css` et remplace
`app-dfHp-K84.css`. Les classes de disposition mobile sont compilées ; les fichiers
JavaScript et les polices n'ont pas changé. La suite complète précède uniquement
les derniers ajustements de présentation Blade ; les tests ciblés ont été relancés
sur les vues finales.

## Recette navigateur isolée

Un nouveau fichier SQLite temporaire, distinct de la base de l'application et créé
avec les migrations de test, contient un compte et un produit de recette fictifs,
en plus du catalogue de démonstration créé par les migrations.
Le serveur de recette utilisait `127.0.0.1:8011`, un cookie de session distinct,
le paiement à la livraison et le transport mail `array`.

1. À **360 × 800**, ajouter les tailles 41 et 42, une unité chacune, pour un stock
   de deux. Un ajout supplémentaire est refusé ; le panier reste à deux unités.
2. Dans le panier, augmenter une variante : refus avec quantité disponible et
   conservation des deux lignes. La disposition mobile a été ajustée pour éviter
   le débordement des cartes ; largeur de document et largeur de contenu observées :
   **345 / 345 px**, sans débordement horizontal.
3. À **1440 × 1000**, ouvrir la confirmation avec les deux variantes puis abaisser
   le stock fictif à un. Confirmer : refus indiquant une unité disponible pour deux
   demandées ; aucune commande n'est créée et le panier est conservé. Largeurs
   observées : **1425 / 1425 px**, sans débordement horizontal.
4. Suivre « Modifier mon panier », retirer une variante, puis confirmer à nouveau :
   commande fictive créée avec **une ligne, quantité 1, taille 42** ; stock final
   **0**, aucun paiement en ligne et panier vide. La console du parcours ne
   présentait aucune erreur ni aucun avertissement capturé.
5. Au clavier, sélectionner une taille et ajouter avec Entrée, augmenter jusqu'à
   deux puis tenter trois : refus et quantité conservée. Diminuer avec Entrée,
   tabuler de « Moins » vers « Plus » puis « Retirer », et activer ce dernier :
   panier vide et message de retrait. Le stock fictif temporairement rétabli à deux
   pour cette recette a ensuite été remis à zéro ; aucune nouvelle commande créée.

Le viewport a été restauré et le serveur temporaire arrêté. Le serveur habituel
sur 8000 et Vite sur 5173 ont été conservés.

| Preuve | Capture |
|---|---|
| Refus sur la fiche produit, mobile | [produit-mobile-360.jpg](produit-mobile-360.jpg) |
| Panier et contrôles de quantité, mobile | [panier-mobile-360.jpg](panier-mobile-360.jpg) |
| Refus après baisse du stock, bureau | [commande-desktop-1440.jpg](commande-desktop-1440.jpg) |

## Fichiers du lot

- `app/Services/CartService.php`, `app/Services/OrderService.php`.
- `resources/views/components/tk/cart-error.blade.php`.
- `resources/views/livewire/pages/public/cart.blade.php`, `checkout.blade.php`, `shop-show.blade.php`.
- `tests/Feature/CartSizeTest.php`, `tests/Feature/OrderFlowTest.php`.
- `public/build/manifest.json` et remplacement du CSS précité.
- Cette note et ses trois captures ; ligne/note 03 de `docs/prompts/audit-2026-10-20/JOURNAL.md`.

## Limites et état

**Concurrence MySQL non validée** : aucun serveur MySQL local disponible sur 3306 ;
SQLite valide les règles métier et le rollback mais ne prouve pas les verrous InnoDB.
La recette navigateur et clavier ne constitue pas un audit exhaustif d'accessibilité
ou des boutons flottants préexistants. Aucun service de paiement ou SMTP réel testé.

Aucun changement de schéma pour cette fiche ; aucune migration exécutée sur la
base de l'application. Les migrations de la base temporaire de recette ne sont
pas un déploiement. Le changement de mode préexistant d'`artisan` est préservé.
**Aucun commit ni push pendant A ; aucune autre correction commencée.**
