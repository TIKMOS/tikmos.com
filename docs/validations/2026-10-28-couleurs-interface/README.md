# Harmonisation des couleurs — 28–29 octobre 2026

Complément ciblé de la fiche [21](../../prompts/audit-2026-10-20/21-contrastes-theme.md),
après le [lot d'accessibilité essentiel](../2026-10-28-accessibilite-essentielle/README.md).
**Rendu représentatif validé localement ; fiche complète toujours partielle. Aucun commit, push ou déploiement.**

## État de départ et périmètre

Racine Git vérifiée, branche `main`, HEAD `b1ee7d6547bb31d1e19d37d02ddee36ec668ec51`,
index vide. Les corrections d'accessibilité précédentes étaient non committées.
Leurs 39 empreintes ont été vérifiées et leurs fichiers archivés dans un répertoire
privé avant modification. Le changement de mode d'`artisan` est préservé.

Accueil, cartes et sections configurables, services/devis/contact, boutique et
produit, panier/checkout, blog, pages d'information/légales, authentification,
consultation client et devis admin ont servi à la vérification des couleurs.
Les règles métier, permissions, contenus réels et fichiers d'images/logos ne changent pas.

## Constats reproduits et corrections

Les couleurs ont été relevées dans le DOM avant modification, sur une nouvelle
base SQLite privée. Captures avant conservées pour les cartes d'accueil et le contact.

- `text-gold-600` persistait sur les catégories, rôles, marques, prix de service et liens riches : contraste insuffisant pour le petit texte sur blanc/ivoire. Les libellés passent à `gold-ink` ; les liens riches deviennent indigo et soulignés, avec un état de survol indigo-600.
- Dates, durées, prix barrés, libellés d'étapes futures et rubrique partenaires employaient `tk-400` sur clair. Ils utilisent désormais le texte secondaire renforcé.
- `muted` à `#6f6f76` restait insuffisant sur sable/or pâle. Il reprend le neutre déjà existant `tk-600`, sans nouvelle palette. Le gris clair `tk-400` est conservé sur les surfaces encre.
- Les titres et mentions du footer utilisaient au contraire `tk-500` sur encre. Ils passent à `tk-400`, avec focus blanc sur fond sombre.
- Les numéros d'approche de l'accueil étaient en encre à 15 % ; ils deviennent or foncé. Les références de fonds `sky/mint/lilac/peach` sans token correspondant sont remplacées, dans les valeurs de repli des composants, par les pastels existants. Les valeurs CMS réelles ne sont pas réécrites.
- Contact, tri catalogue, choix de taille et confirmation de fermeture du profil reprennent `control-line` pour les contours concernés. Le contact conserve un focus visible et des placeholders lisibles. La carte WhatsApp du contact rejoint le couple encre/vert des boutons existants et utilise le fond sage.
- Les étoiles non sélectionnées reprennent `control-line`, et leur focus n'est plus masqué. Le suivi de commande conserve ses textes et distingue ses états par forme/remplissage et libellé.
- Le voile du panneau photo d'authentification est renforcé ; ses textes blancs sont moins transparents. L'image et les textes sont conservés, sans mode sombre automatique.

L'annotation du tableau retourné par `shop-index::with()` manquait déjà sur HEAD.
L'analyse élargie l'a signalée ; un PHPDoc décrit précisément le paginateur et la
collection retournés. Pint a également mis en forme les deux vues boutique touchées.
Ces ajustements ne modifient ni requête, ni validation, ni traitement d'ajout au panier.

## Mesures

Rapports de luminance relative sRGB, calculés avec les couleurs CSS calculées dans
le navigateur et les fonds correspondants. Ratios arrondis pour la lecture,
comparaison des seuils sans arrondi. Les animations de révélation doivent être
terminées avant de juger un rendu ; les captures transitoires ont été écartées.

| Combinaison | Avant | Après |
|---|---:|---:|
| Catégorie/rôle or sur blanc | 3,74:1 | 7,03:1 |
| Catégorie or sur ivoire | 3,52:1 | 6,62:1 |
| Date/durée grise sur blanc | 2,98:1 | 7,92:1 |
| Texte secondaire sur blanc | 4,99:1 | 7,92:1 |
| Texte secondaire sur ivoire | 4,70:1 | 7,46:1 |
| Texte secondaire sur sable | 4,43:1 | 7,04:1 |
| Texte secondaire sur or pâle | 4,25:1 | 6,74:1 |
| Texte secondaire sur indigo pâle | 4,41:1 | 7,00:1 |
| Lien riche sur blanc | 3,74:1 | 13,92:1 |
| Lien riche sur ivoire | 3,52:1 | 13,12:1 |
| Lien riche au survol sur blanc | encre : 19,67:1 | indigo-600 : 10,94:1 |
| Titre/mention du footer sur encre | 3,95:1 | 6,60:1 |
| Contour du contact sur blanc | 1,64:1 | 3,67:1 |
| Placeholder du contact sur blanc | 2,98:1 | 7,92:1 |
| Glyphe WhatsApp du contact sur vert | 1,98:1 | 9,92:1 |
| Focus sur encre, sans surcharge locale | indigo : 1,41:1 | blanc : 19,67:1 |

Les numéros d'approche deviennent `gold-ink` : 6,22:1 sur indigo pâle,
5,99:1 sur or pâle et 6,25:1 sur sable. L'encre à 15 % sur ivoire était d'environ
1,39:1. L'or décoratif, les séparateurs et les images ne sont pas assimilés à du texte informatif.

Pour le panneau photo, une **borne conservative calculée**, distincte d'une mesure
pixel par pixel, utilise une image entièrement blanche sous le voile : l'ancien
texte blanc à 50 % sous voile encre 55 % pouvait descendre à 2,30:1 ; le nouveau
petit texte blanc à 80 % sous voile 75 % reste à au moins 6,56:1. Le paragraphe à
85 % reste à au moins 7,16:1. Le gradient et ses opacités ont été contrôlés dans le DOM.

Le bouton WhatsApp partagé garde encre/vert, 9,92:1. Les états d'erreur gardent leur
message et icône, les badges leur libellé. Les contrôles désactivés conservent
l'attribut `disabled` ; ils ne sont pas annoncés comme des états actifs conformes.
Les couleurs normales/survol ont été vérifiées dans les règles compilées ; focus,
sélection, erreurs et disponibilité 2FA ont aussi été observés au clavier/DOM.

Références W3C : [texte et contrastes](https://www.w3.org/WAI/WCAG22/Understanding/contrast-minimum.html),
[indicateurs non textuels](https://www.w3.org/WAI/WCAG22/Understanding/non-text-contrast.html),
[information sans dépendre uniquement de la couleur](https://www.w3.org/WAI/WCAG22/Understanding/use-of-color.html).
Seuils utilisés : 4,5:1 pour le texte courant et 3:1 pour les informations visuelles
nécessaires des contrôles. Il ne s'agit pas d'une certification globale WCAG/RGAA.

## Contenus CMS et réserves éditoriales

La vitrine lit notamment `HomepageSettings`, les modèles Service/Post/Product et
les documents légaux. La page de confidentialité affiche le document CMS publié,
en complément de la notice technique de fermeture. `LegalDocument::parsed()`
conserve le HTML et ajoute les ancres de sommaire ; ce n'est pas une normalisation
des couleurs. La configuration Purifier autorise `color` et `background-color`
sur certains éléments éditoriaux. Des styles intégrés peuvent donc primer sur le thème.

Aucun contenu de la base habituelle n'a été lu pour cette recette ni remplacé.
Les pages fictives inspectées ne présentaient pas de couleur intégrée dominante.
**À revoir séparément dans les contenus réels :** couleurs explicites, fonds,
anciennes classes de teinte, images contenant du texte et associations atypiques.
Les chiffres, services et témoignages issus des données de démonstration ne sont
pas attestés comme des faits commerciaux par cette validation visuelle.

## Validation automatique

- Tests ciblés : **240 tests, 1 197 assertions réussis**, 134,390 s.
- Suite complète : **594 tests, 2 675 assertions réussis**, 308,679 s.
- Après format/annotation des vues boutique : **23 tests, 76 assertions réussis**.
- Dernier contrôle formulaires après alignement du glyphe WhatsApp : **69 tests, 445 assertions réussis**, 37,082 s.
- Larastan : **0 erreur**, sur `app` et les 15 actions Volt localement modifiées ; contrôle séquentiel `--debug`. L'erreur d'annotation préexistante découverte dans le catalogue est décrite ci-dessus. Les trois dernières vues ont aussi été réanalysées après formatage avec 0 erreur.
- Pint et syntaxe : **41 fichiers PHP/Blade conformes** sur le périmètre local cumulé, sans formatage d'autres fichiers.
- Build initial 6,78 s ; dernier build **5,62 s**, même CSS `app-C3PPqFtE.css`. JavaScript, fontes et bundle TipTap inchangés.

La suite complète précédait les seuls derniers ajustements de format/annotation
boutique et de couleur de la carte WhatsApp contact. Les tests concernés ont été
relancés ensuite. Aucun build n'a été lancé pendant une suite Pest.

Les tests emploient SQLite en mémoire, cache/sessions `array`, mail `array`, vues
compilées et stockage privés. La recette HTTP dispose de sa propre SQLite, d'un
cache fichier, de sessions et de disques privés ; notifications simulées et appels
HTTP applicatifs non simulés interdits. Aucun email, paiement ou modification de compte réel.

Commandes principales, avec les chemins d'isolation définis avant exécution :

```powershell
npm run build
php vendor/bin/pest tests/Feature/AccessibleFieldsTest.php tests/Feature/Auth tests/Feature/CartSizeTest.php tests/Feature/OrderFlowTest.php tests/Feature/ServicesDevisTest.php tests/Feature/App/EspaceClientDevisTest.php tests/Feature/QuoteRequestEntryTest.php tests/Feature/PublicFormSubmissionTest.php tests/Feature/LegalRgpdTest.php tests/Feature/AdminSmokeTest.php tests/Feature/ClientSpaceSmokeTest.php tests/Feature/ShopTest.php tests/Feature/ShopLuxuryTest.php tests/Feature/BlogTest.php tests/Feature/ReviewsTest.php tests/Feature/PublicPagesTest.php tests/Feature/Public/VitrineTest.php tests/Feature/Admin/LegalDocsTest.php --compact
php vendor/bin/pest --compact --cache-directory <cache-phpunit-privé>
php vendor/bin/pest tests/Feature/ShopTest.php tests/Feature/ShopLuxuryTest.php tests/Feature/CartSizeTest.php tests/Feature/PublicPagesTest.php --compact
php vendor/bin/pest tests/Feature/PublicFormSubmissionTest.php tests/Feature/QuoteRequestEntryTest.php tests/Feature/ServicesDevisTest.php --compact
$voltPaths = @(git diff --name-only -- 'resources/views/livewire/**/*.php')
php -d memory_limit=1G vendor/bin/phpstan analyse --no-progress --memory-limit=1G --debug app @voltPaths
$phpPaths = @(git diff --name-only -- '*.php') + @('resources/views/components/tk/skip-link.blade.php', 'tests/Feature/AccessibleFieldsTest.php')
php vendor/bin/pint --test @phpPaths
foreach ($phpPath in $phpPaths) { php -l $phpPath }
git diff --check
```

Les chemins ciblés sont : `AccessibleFieldsTest`, `Auth/`, `CartSizeTest`,
`OrderFlowTest`, `ServicesDevisTest`, `App/EspaceClientDevisTest`,
`QuoteRequestEntryTest`, `PublicFormSubmissionTest`, `LegalRgpdTest`,
`AdminSmokeTest`, `ClientSpaceSmokeTest`, `ShopTest`, `ShopLuxuryTest`,
`BlogTest`, `ReviewsTest`, `PublicPagesTest`, `Public/VitrineTest`, `Admin/LegalDocsTest`
sous `tests/Feature/` (fichiers `.php` sauf le dossier Auth).
Pas de nouveaux tests recopiant les couleurs ou classes CSS.

## Recette navigateur

- **360 px :** accueil, service fictif, produit, panier, checkout, inscription et 2FA. Textes/fonds, noms de champs, focus, menu ouvert avec Espace puis fermé avec Échap ; passage inscription vers Email préservé. Aucun OTP saisi/capturé, confiance décochée.
- **768 px :** services, catalogue, blog, à propos, équipe fictive, FAQ, confidentialité, CGV, contact et devis admin. Erreur du contact reproduite avec nom manquant : texte saisi conservé. Sidebar admin au clavier avec focus blanc, fermeture Échap.
- **1440 px :** accueil et cartes, service, connexion sur panneau photo, commande client et suivi. Métadonnées et étapes futures lisibles ; lien produit historique souligné/indigo.
- Pas de débordement horizontal sur les pages représentatives mesurées. Console consultée sans avertissement/erreur durant ces parcours. Les états normaux/survol sont aussi contrôlés dans le CSS compilé ; pas de revendication d'une recette tactile de chaque état sur chaque page.

**Confirmation utilisateur :** « Oui, le rendu est lisible et cohérent », pour
l'accueil, la page service fictive, le panier et le checkout ouverts depuis le guide.
Les autres vérifications techniques ne sont pas présentées comme sa validation personnelle.
Validation commencée le 28 et clôturée le 29 octobre 2026.
Dimensions restaurées, onglet temporaire fermé et seul serveur fictif du port 8021 arrêté.

### Captures inspectées

- [Cartes accueil avant](accueil-cartes-avant-desktop-1440.jpg) / [après — 1440 px](accueil-cartes-apres-desktop-1440.jpg)
- [Accueil après — 1440 px](accueil-apres-desktop-1440.jpg) / [360 px](accueil-apres-mobile-360.jpg)
- [Service après — 1440 px](service-apres-desktop-1440.jpg) / [360 px](service-apres-mobile-360.jpg)
- [Contact avant — 1440 px](contact-avant-desktop-1440.jpg) / [champs après — 768 px](contact-apres-tablette-768.jpg) / [erreur et texte conservé](contact-erreurs-tablette-768.jpg)
- [Blog — 768 px](blog-apres-tablette-768.jpg)
- [Panier — 360 px](panier-apres-mobile-360.jpg) / [checkout — 360 px](checkout-apres-mobile-360.jpg)
- [Connexion — 1440 px](connexion-apres-desktop-1440.jpg) / [2FA sans code — 360 px](2fa-apres-mobile-360.jpg)

Les portraits/logos déjà livrés dans les assets sont conservés. Les noms et données
client/commande utilisés sont fictifs. Aucun identifiant de connexion ou secret publié.

## Fichiers de ce complément couleurs

Le lot précédent reste intact dans l'historique de validation ; certains fichiers
ci-dessous prolongent ses modifications locales. Aucune sélection Git n'est effectuée pendant A.

- `resources/css/app.css`
- `resources/views/components/layouts/public.blade.php`
- `resources/views/components/layouts/auth.blade.php`
- `resources/views/components/tk/kicker.blade.php`
- `resources/views/components/tk/legal.blade.php`
- `resources/views/components/tk/post-card.blade.php`
- `resources/views/components/tk/product-card.blade.php`
- `resources/views/components/tk/project-card.blade.php`
- `resources/views/components/tk/rating.blade.php`
- `resources/views/components/tk/service-card.blade.php`
- `resources/views/components/tk/stat.blade.php`
- `resources/views/components/tk/stepper.blade.php`
- `resources/views/components/tk/team-card.blade.php`
- `resources/views/livewire/pages/app/compte.blade.php`
- `resources/views/livewire/pages/public/blog-show.blade.php`
- `resources/views/livewire/pages/public/cart.blade.php`
- `resources/views/livewire/pages/public/contact.blade.php`
- `resources/views/livewire/pages/public/projects-show.blade.php`
- `resources/views/livewire/pages/public/shop-index.blade.php`
- `resources/views/livewire/pages/public/shop-show.blade.php`
- `resources/views/partials/home/services.blade.php`
- `resources/views/partials/home/steps.blade.php`
- `resources/views/partials/home/team.blade.php`
- `resources/views/public/faq.blade.php`
- `docs/01-charte-graphique.md`
- `docs/prompts/audit-2026-10-20/JOURNAL.md` : ligne 21 et sa note seulement pour ce complément
- ce rapport et ses captures
- `public/build/manifest.json` et `public/build/assets/app-C3PPqFtE.css`

Le CSS local précédent `app-BmuCkny5.css` est remplacé ; il n'était pas encore suivi
par Git. La suppression du CSS versionné `app-CvatQWas.css`, déjà prévue au lot
précédent, reste à inclure lors de la publication du périmètre autorisé.

## Empreintes, réserves et mise en production

Les anciennes métadonnées `.git/codex-validation/essentiels-accessibilite.json` et
le rapport précédent sont conservés sans réécriture. Leurs empreintes des fichiers
partagés modifiés et du CSS remplacé ne décrivent plus la version courante.
Les résultats et captures historiques ne sont pas attribués au nouveau bundle.
Les nouvelles empreintes, y compris du périmètre local cumulé revalidé, vont dans
`.git/codex-validation/couleurs-interface.json`, exclusivement privé.

**Réserves :** contenus CMS réels et leurs couleurs intégrées non examinés,
thème sombre complet, autres moteurs et lecteurs d'écran réels non validés.
Recouvrement WhatsApp mobile ponctuel, logo sombre peu visible dans le panneau
auth/admin et en-tête tablette dense restent des limites préexistantes hors de ce
complément de couleurs ; aucun nouveau logo ni changement de disposition n'est introduit.

MySQL, SMTP/délivrabilité, KKiaPay sandbox, cache/sessions distribués, configuration
de production et hébergement non validés. Ce lot valide un rendu local représentatif,
pas l'aptitude globale à la production. HTTPS, configuration/secrets, migrations,
sauvegardes, files/cron, contenus commerciaux et services externes restent à
contrôler dans leur étape de préparation à la mise en ligne, sans déploiement ici.
