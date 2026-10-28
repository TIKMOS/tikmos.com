# Accessibilité des parcours essentiels — 28 octobre 2026

Lot A ciblé des fiches [18](../../prompts/audit-2026-10-20/18-libelles-champs-accessibles.md),
[19](../../prompts/audit-2026-10-20/19-boutons-et-selecteurs-accessibles.md),
[20](../../prompts/audit-2026-10-20/20-focus-menus-panneaux.md) et
[21](../../prompts/audit-2026-10-20/21-contrastes-theme.md).
**État : partiel au regard des fiches complètes. Aucun commit ni push dans ce lot A.**
Base de travail : `main`, checkpoint publié `b1ee7d6547bb31d1e19d37d02ddee36ec668ec51`.
Index initial vide ; modification étrangère du mode d’`artisan` préservée.

## Défauts reproduits avant leur correction

- Checkout : les cinq champs de livraison n’avaient pas d’association de libellé ni d’identifiant ; aucune description d’erreur ni indication ARIA d’invalidité.
- Menu public mobile : absence de `aria-expanded` et de `aria-controls`.
- Administration mobile : sidebar visuellement hors écran mais encore tabulable ; le premier Tab plaçait le focus sur son logo, à une abscisse négative (environ −236 px).
- Détail de commande admin : aucun dialogue exposé, fermeture sans nom et focus restant dans la liste derrière le panneau.
- Navigation basse client : lien central à icône sans nom accessible.
- Inscription : passage à l’étape 2 laissant le focus sur le corps du document ; débordement horizontal des actions à 360 px, confirmé par capture avant leur empilement.
- Couleurs calculées dans le DOM : texte blanc du bouton WhatsApp sur `#25d366`, contraste 1,98:1 ; petits textes or et placeholders gris insuffisants sur leurs fonds clairs.

Ces constats DOM/clavier constituent les reproductions UI. Les tests historiques de l’audit n’ont pas été transformés en preuves de correction.

## Corrections et contrats des composants

- `field` expose des identifiants d’aide/erreur et un `role="alert"` pour l’erreur. L’astérisque reste visible et décoratif pour les technologies d’assistance.
- `input` et `phone` héritent du `for`, de l’aide, de l’erreur et du caractère requis du champ parent. Les descriptions explicites du consommateur sont conservées et combinées ; aucun renvoi vers une aide masquée n’est ajouté.
- Les consommateurs essentiels fournissent un `for` explicite et stable. Ce choix évite de dériver un identifiant du seul `wire:model`, qui peut être partagé par plusieurs champs. Le test vérifie aussi la stabilité après un rendu Livewire en erreur. Les autres consommateurs sans `for` ne sont pas considérés comme corrigés automatiquement.
- Les `textarea`, le choix de service et le groupe de paiement concernés ont des associations explicites. Les noms, téléphones, validations et valeurs métier restent conservés.
- Le sélecteur d’indicatif expose son nom et son état ; Entrée/Espace l’ouvrent, les flèches parcourent les pays, Échap le ferme et rend le focus au bouton. Son groupe de choix n’est pas déclaré comme un faux menu applicatif.
- Les menus public/client/admin ont des déclencheurs nommés, des états liés aux panneaux et une fermeture au clavier. La sidebar admin fermée est invisible et `inert` ; ouverte sur mobile, elle contient le focus, se ferme avec Échap et rend le focus au déclencheur. Le passage bureau/mobile libère correctement le verrouillage.
- Le panneau commande admin est un dialogue nommé ; le focus entre sur sa fermeture, Tab/Maj+Tab restent dans le panneau et Échap revient à la commande déclencheuse. Le plugin Focus est déjà livré avec Livewire, sans ajout de dépendance.
- Un lien d’évitement est ajouté aux quatre layouts. L’étape d’inscription place le focus sur le premier champ de la nouvelle étape ; ses actions s’empilent sur petit écran.
- Les quantités et le retrait du panier disposent de noms contextualisés et de cibles de 44 px. Les actions essentielles à icône et la navigation basse sont nommées. Les boutons partagés de petite taille atteignent 44 px ; cela ne signifie pas que tous les liens de l’application mesurent 44 px.
- Or foncé pour les textes sur fonds clairs, texte encre sur vert WhatsApp, placeholders et contours de contrôles renforcés, focus indigo sur clair et blanc dans les navigations encre. Les décorations et l’identité graphique sont conservées.

Aucun changement de règles métier, permissions, montants, paiement, stock ou authentification serveur. Aucun nouveau schéma ni migration.

## Mesures de contraste

Calcul sRGB selon la luminance relative, à partir des couleurs CSS effectivement calculées dans les vues concernées. Ratios arrondis à deux décimales ; les seuils sont évalués avant arrondi.

| Usage et fond | Avant | Après |
|---|---:|---:|
| Petit texte or / blanc | `#a07f44` : 3,74:1 | `#6e5524` : 7,03:1 |
| Petit texte or / ivoire `#faf8f3` | 3,52:1 | 6,62:1 |
| Petit texte or / sable `#f6f1e8` | 3,32:1 | 6,25:1 |
| Placeholder / blanc | `#9a958a` : 2,98:1 | `#6f6f76` : 4,99:1 |
| Contour de champ / blanc | `#cfcabd` : 1,64:1 | `#8a857a` : 3,67:1 |
| Contour de champ / ivoire | 1,54:1 | 3,46:1 |
| Texte/glyphe WhatsApp / vert `#25d366` | blanc : 1,98:1 | encre `#0b0b0c` : 9,92:1 |
| Focus / blanc | indigo : 13,92:1 | 13,92:1, désormais conservé sur les champs |
| Focus / encre | indigo : 1,41:1 | blanc : 19,67:1 |

États conservés vérifiés : erreur `#c0362c` / blanc 5,52:1 et / ivoire 5,20:1 ; badge attente `#7a5806` / `#f6efd9` 5,66:1 ; texte de navigation `#9a958a` / encre 6,60:1. Les choix de paiement exposent leur focus sur la carte contenant le radio visuellement masqué.

Les seuils W3C utilisés sont 4,5:1 pour le texte courant et 3:1 pour les informations visuelles nécessaires des contrôles. Les filets purement décoratifs ne sont pas assimilés aux contours de champs. `muted` sur sable n’atteint que 4,43:1 : ce token n’est donc pas approuvé pour tous les fonds. Le mode sombre opt-in conserve des valeurs adaptées, sans recette globale du thème sombre.

Références officielles : [contraste du texte](https://www.w3.org/WAI/WCAG22/Understanding/contrast-minimum.html),
[contraste non textuel](https://www.w3.org/WAI/WCAG22/Understanding/non-text-contrast.html),
[association des libellés](https://www.w3.org/WAI/tutorials/forms/labels/),
[dialogues modaux](https://www.w3.org/WAI/ARIA/apg/patterns/dialog-modal/),
[plugin Focus Alpine](https://alpinejs.dev/plugins/focus).
Ce contrôle ciblé ne constitue aucune certification globale WCAG/RGAA.

## Validation automatique

Les suites Pest sont exécutées séparément, sur SQLite en mémoire, cache et sessions `array`, mail `array`, stockage et vues compilées isolés. La recette HTTP utilise sa propre base SQLite, son cache fichier persistant et ses sessions/stockages privés. Notifications simulées et requêtes HTTP applicatives non simulées interdites dans les nouveaux scénarios. Aucun email ou paiement réel.

- Première série fonctionnelle : 31 tests, 152 assertions, réussis.
- Nouveaux contrats d’accessibilité : 3 tests, 46 assertions, réussis. Ils contrôlent les associations, l’unicité, les rendus Livewire et l’absence d’effet métier d’un refus ; ils ne recopient pas les classes CSS.
- Série ciblée : 197 tests, 1 097 assertions, réussis avant les derniers ajustements de présentation/navigation.
- Série ciblée finale, après les derniers ajustements et le build (mêmes chemins plus `AdminSmokeTest`) : **198 tests, 1 098 assertions, réussis**.
- Suite complète relancée : **594 tests, 2 675 assertions, réussis** (355,998 s). Elle avait démarré avant le dernier ajustement de mise en page d’inscription ; la série ciblée finale couvre les vues finales.
- Larastan, `app` et les neuf actions Volt listées : **0 erreur**, confirmé sur les fichiers finaux. L’analyse est séquentielle avec `--debug`.
- Pint `--test` et `php -l` : **23 fichiers PHP/Blade conformes**, sans reformater de fichier étranger.
- Build final : réussi, **8,64 s** ; CSS `app-BmuCkny5.css`. Le dernier ajustement de mise en page utilise des classes déjà présentes : le CSS est identique à celui de la recette. JavaScript, fontes et bundle TipTap inchangés.
- Manifeste : toutes ses ressources existent ; 12 liens locaux de ce rapport vérifiés. `git diff --check` conforme. Huit captures inspectées.

Commandes (après définition des chemins de test isolés) :

```powershell
php vendor/bin/pest tests/Feature/AccessibleFieldsTest.php tests/Feature/Auth tests/Feature/CartSizeTest.php tests/Feature/OrderFlowTest.php tests/Feature/ServicesDevisTest.php tests/Feature/App/EspaceClientDevisTest.php tests/Feature/QuoteRequestEntryTest.php tests/Feature/PublicFormSubmissionTest.php tests/Feature/LegalRgpdTest.php --compact
php vendor/bin/pest --compact
php -d memory_limit=1G vendor/bin/phpstan analyse --no-progress --memory-limit=1G --debug app resources/views/livewire/pages/admin/boutique/orders.blade.php resources/views/livewire/pages/admin/service-request-show.blade.php resources/views/livewire/pages/app/compte.blade.php resources/views/livewire/pages/auth/login.blade.php resources/views/livewire/pages/auth/register.blade.php resources/views/livewire/pages/auth/two-factor-challenge.blade.php resources/views/livewire/pages/public/cart.blade.php resources/views/livewire/pages/public/checkout.blade.php resources/views/livewire/pages/public/services-show.blade.php
php vendor/bin/pint --test <chemins-PHP-et-Blade-du-lot>
php -l <chaque-fichier-PHP-du-lot>
npm run build
git diff --check
```

Une première suite complète a produit 593 succès sur 594 tests : `AdminSmokeTest` rencontrait le manifeste Vite momentanément absent pendant le build. Le test admin relancé après le build passe (1 test, 1 assertion). La suite complète relancée sans build simultané passe ensuite intégralement ; le premier résultat n’est pas présenté comme une suite réussie. Le build final a précédé la dernière série ciblée, sans autre suite Pest simultanée.

## Recette navigateur et confirmation utilisateur

Instance locale fictive dédiée ; aucun identifiant de connexion, OTP, cookie, jeton ou export n’est conservé dans ces preuves. Les boutons du guide préparent le rôle fictif nécessaire, sans modifier de compte réel.

| Écran / interaction | Vérification effectuée par l’agent |
|---|---|
| Connexion / 360 px | Libellés requis, erreur après identifiants inexistants fictifs liée à l’email, saisie conservée |
| Inscription / 360 px | Focus Email après Continuer, retour sur Nom, indicatif au clavier, actions sans débordement horizontal |
| 2FA / 360 px | Code requis et aide liée, confiance décochée, focus visible ; aucun OTP utilisé ou capturé |
| Devis public / 768 px | Labels, choix service, erreur téléphone liée après absence des deux moyens de contact, texte conservé |
| Panier / 360 px | Noms des trois actions, cibles 44×44, augmentation avec Espace et total recalculé ; WhatsApp mesuré sans ouvrir le service |
| Checkout / 360 px | Cinq IDs et labels, adresse excessive refusée, invalidité/erreur liée, texte conservé ; téléphone et menu avec Échap |
| Devis client / 1440 px | Or calculé `rgb(110,85,36)`, menu compte avec focus/Échap, navigation Livewire vers le profil |
| Devis admin / 1440 px | Montant/devise requis, date liée à son aide, note nommée ; aucune décision ni notification déclenchée |
| Commande admin / 360, 768, 1440 px | Sidebar fermée hors focus, ouverture Enter, Maj+Tab bouclé, panneau nommé, Tab/Maj+Tab bouclés, Échap et retour à la ligne ; changement de largeur libérant le piège et le scroll |

Tab, Maj+Tab, Entrée, Espace, flèches et Échap ont été exercés sur les contrôles pertinents. Pas de débordement horizontal constaté après correction sur les écrans essentiels mesurés. Console consultée sans avertissement/erreur pendant ces parcours. Les temps de transition Livewire/Alpine sont laissés se terminer avant l’inspection du focus.

**Confirmation utilisateur du 28/10/2026 :** « Oui, les interactions fonctionnent » après le parcours demandé : libellé Téléphone et indicatif du checkout, puis ouverture, tabulation contenue, fermeture Échap et retour au déclencheur du panneau commande admin. Cette confirmation ne vaut pas validation personnelle de tous les autres contrôles techniques.

La consultation de la commande client fictive et le nom « Devis » du lien central ont également été observés dans le DOM. Dimensions du navigateur restaurées, onglet temporaire fermé et seul serveur de recette arrêté après confirmation. Les autres serveurs locaux sont préservés.

### Captures fictives

- [Checkout : champ, focus et erreur — 360 px](checkout-erreur-mobile-360.jpg)
- [Inscription : étape 2 — 360 px](inscription-etape-2-mobile-360.jpg)
- [Challenge 2FA sans code — 360 px](challenge-2fa-mobile-360.jpg)
- [Devis : erreur et saisie conservée — 768 px](devis-erreurs-tablette-768.jpg)
- [Panier et bouton WhatsApp — 360 px](panier-mobile-360.jpg)
- [Devis client — 1440 px](devis-client-desktop-1440.jpg)
- [Navigation admin — 360 px](navigation-admin-mobile-360.jpg)
- [Dialogue commande admin — 1440 px](commande-dialogue-desktop-1440.jpg)

## Éléments reportés et limites

- Fiche 18 : autres formulaires CRUD/admin, blocs répétés et formulaires hors des parcours essentiels ; inventaire exhaustif de tous les consommateurs encore à effectuer.
- Fiche 19 : tri du catalogue et actions d’éditeurs/services/CRUD hors lot, notamment toutes les commandes TipTap. Aucun nom accessible global revendiqué.
- Fiche 20 : autres panneaux d’édition CMS et dialogues hors devis/commandes ; recette exhaustive des autres pages non effectuée.
- Fiche 21 : couleurs éditoriales/CMS hors périmètre, contenus historiques et thème sombre complet. Logo sombre de la sidebar et encombrement de l’en-tête tablette relèvent d’autres fiches reportées.
- Le bouton WhatsApp flottant peut encore recouvrir ponctuellement un contrôle sur mobile ; ce problème préexistant est conservé comme limite, sans annoncer une correction de positionnement dans ce lot.
- Pas de test avec lecteur d’écran réel (NVDA, VoiceOver ou TalkBack), autre moteur de navigateur, zoom système ou préférences contrastées ; l’arbre d’accessibilité ne remplace pas ces recettes.
- MySQL, concurrence/cache/sessions distribués, SMTP réel et KKiaPay sandbox non validés. Les tests automatiques existants couvrent les protections métier sans constituer une recette de ces services externes.
- Les cibles annoncées et les rapports de contraste se limitent aux éléments contrôlés. Aucun audit légal, RGAA complet, Lighthouse ou conformité globale n’est déclaré.

## Fichiers du lot

Chemins relatifs à la racine ; aucune sélection Git effectuée pendant A.

- `resources/css/app.css`
- `resources/views/components/layouts/admin.blade.php`
- `resources/views/components/layouts/app.blade.php`
- `resources/views/components/layouts/auth.blade.php`
- `resources/views/components/layouts/public.blade.php`
- `resources/views/components/tk/button.blade.php`
- `resources/views/components/tk/field.blade.php`
- `resources/views/components/tk/input.blade.php`
- `resources/views/components/tk/kicker.blade.php`
- `resources/views/components/tk/nav-bottom.blade.php`
- `resources/views/components/tk/phone.blade.php`
- `resources/views/components/tk/skip-link.blade.php` (nouveau)
- `resources/views/components/tk/whatsapp-button.blade.php`
- `resources/views/livewire/pages/admin/boutique/orders.blade.php`
- `resources/views/livewire/pages/admin/service-request-show.blade.php`
- `resources/views/livewire/pages/app/compte.blade.php`
- `resources/views/livewire/pages/app/requests-show.blade.php`
- `resources/views/livewire/pages/auth/login.blade.php`
- `resources/views/livewire/pages/auth/register.blade.php`
- `resources/views/livewire/pages/auth/two-factor-challenge.blade.php`
- `resources/views/livewire/pages/public/cart.blade.php`
- `resources/views/livewire/pages/public/checkout.blade.php`
- `resources/views/livewire/pages/public/services-show.blade.php`
- `tests/Feature/AccessibleFieldsTest.php` (nouveau)
- `docs/01-charte-graphique.md`
- `docs/04-conventions-architecture.md` (rectification de la référence de contraste or)
- `docs/prompts/00-prompt-maitre.md` (même rectification)
- `docs/prompts/audit-2026-10-20/JOURNAL.md` (lignes 18–21 et leurs notes seulement)
- ce `README.md` et les huit captures ci-dessus
- `public/build/manifest.json`
- `public/build/assets/app-BmuCkny5.css` (nouveau)
- suppression de `public/build/assets/app-CvatQWas.css`

Les empreintes finales sont conservées exclusivement dans `.git/codex-validation/essentiels-accessibilite.json`. Ce fichier, le routeur de recette, ses bases, sessions, caches et clés restent privés et ne font pas partie du lot. Aucun `.env`, `vendor`, `node_modules`, `public/hot`, stockage personnel ou changement de mode d’`artisan` à publier.
