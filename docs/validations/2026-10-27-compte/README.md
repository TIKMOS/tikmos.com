# Correction 11 — fermeture du compte et données conservées

Validation locale du 27 octobre 2026, sur bases, caches, sessions, vues compilées
et stockages isolés. Aucun compte réel fermé, restauré ou anonymisé ; aucun email
réel. Checkpoint précédent `49b7ae245654fc87562a9a7ecfd54bf349339234` vérifié sur
`origin/main` avant les modifications. Aucun commit ni push dans cette étape A.

## Constat avant correction

La vue promettait une suppression définitive et l'effacement, alors que `User`
utilise `SoftDeletes`. Le traitement mettait `deleted_at`, déconnectait le compte
et invalidait la session courante. Le profil et ses relations restaient présents.
La confirmation était seulement visuelle, sans mot de passe ni confirmation
vérifiés par l'action serveur.

Deux tests exigeant le comportement sûr ont été exécutés avant correction :
**2 échecs, 2 assertions**. L'appel direct sans confirmation ne produisait aucune
erreur ; l'export depuis une instance Volt dont l'utilisateur avait été fermé
entre-temps retournait 200 au lieu de 403. Ce second test concerne un objet
authentifié déjà chargé : il ne démontre pas un contournement HTTP du middleware.
Le fournisseur Eloquent excluait déjà les comptes supprimés logiquement lors
d'une nouvelle résolution d'identité. Les preuves historiques de l'audit restent
inchangées et ne sont pas présentées comme des tests de correction.

## Fonctionnement retenu

- « Fermer mon compte » : mot de passe actuel et case de compréhension exigés
  côté serveur. Mauvaise confirmation : refus, compte inchangé, mot de passe
  retiré du composant. Annulation : accès conservé, champs et erreurs effacés.
- Fermeture logique, déconnexion, invalidation de la session courante et nouveau
  jeton CSRF. Redirection vers la connexion avec résultat explicite : les données
  n'ont pas été automatiquement effacées. Aucune promesse de réactivation.
- Profil, mot de passe, rôles et valeur métier `status` conservés ; c'est
  `deleted_at` qui ferme l'accès. Demandes, devis et décisions, événements,
  commandes, articles, paiements et appareils approuvés conservés. Le logout
  conserve le comportement Laravel de rotation du jeton de connexion mémorisée.
- Autres sessions non purgées physiquement ; accès refusé à la prochaine requête
  par la résolution d'identité. Les actions du compte font aussi une nouvelle
  lecture de l'utilisateur actif avant consultation, modification ou export.
  Un appareil approuvé ne permet pas de reconnecter un utilisateur fermé.
- Export existant conservé : profil, demandes/devis, commandes/articles du compte
  autorisé. Pas de promesse d'export exhaustif de tous les stockages et historiques.
- Demande d'effacement/anonymisation distincte, orientée vers `/contact` ; pas de
  purge, nouvelle durée de conservation ou procédure juridique inventée.

## Confidentialité et contenu CMS

`/confidentialite` affiche le document publié `LegalDocument.key=privacy`, son
titre, sa version et son corps. La correction ajoute un encadré technique avant
le corps, partagé avec la page du compte ; elle ne remplace aucun contenu CMS.
Un document dépublié reste inaccessible (404), sans création d'un texte de repli.

Les données initiales contiennent encore des formulations sur la suppression
depuis « Mon compte » et la suppression/anonymisation après conservation. Le
contenu réellement déployé n'a pas été vérifié. Une révision éditoriale et une
validation séparées sont à réaliser via `/admin/reglages/pages`. Les migrations
historiques et les contenus réels n'ont pas été réécrits.

À décider avec les responsables concernés : catégories et copies à conserver,
durées et conditions applicables, vérification d'identité pour une demande,
périmètre des transactions/documents/sauvegardes, personnes responsables, réponse
et exécution de la décision d'effacement ou d'anonymisation. Cette vérification
technique ne constitue pas une validation juridique ni une certification RGPD.

## Contrôles automatisés

Commandes exécutées séquentiellement pour les suites Pest, sous `APP_ENV=testing`,
SQLite isolé, cache et sessions de test, notifications simulées et HTTP imprévu
interdit dans `LegalRgpdTest`. Vues compilées et configuration de test dédiées.

| Contrôle | Résultat |
|---|---|
| `php vendor/bin/pest tests/Feature/LegalRgpdTest.php tests/Feature/Admin/LegalDocsTest.php tests/Feature/Auth --compact` | 84 tests, 377 assertions, réussis |
| Test ciblé de deux sessions database sur SQLite | 1 test, 7 assertions, réussi (également inclus ci-dessus) |
| `php vendor/bin/pest --compact` | 538 tests, 2 315 assertions, réussis (241,755 s) |
| Larastan séquentiel `--debug`, `app` et vues Volt compte/connexion | 0 erreur |
| Pint `--test` et `php -l` sur les six fichiers PHP/Blade du lot | Conformes |
| `npm run build` | Réussi, 7,32 s ; aucun changement du bundle versionné |
| Manifeste compilé | Toutes les références existent ; CSS `app-DpDeyuAx.css`, JS et polices inchangés |

Cas couverts : fermeture nominale et données liées conservées, confirmation
absente/incomplète/incorrecte, annulation, appel direct avec identité devenue
obsolète, deuxième session déjà ouverte, reconnexion avec appareil approuvé,
comptes actifs et autres utilisateurs inchangés, export sans données croisées,
corps/version CMS conservés et publication respectée. Les difficultés de fixtures
(valeurs par défaut des modèles et sérialisation JSON des sessions Laravel 13)
ont été corrigées dans les tests ; elles ne constituent pas des défauts applicatifs.

## Recette navigateur

Instance temporaire sur boucle locale, base SQLite fictive dédiée, cache fichier,
sessions, vues et stockage privés. Notifications interceptées. Le guide et ses
identifiants sont restés hors du dépôt ; aucune capture ne contient d'identifiant
de connexion, de mot de passe ou d'export personnel.

Parcours vérifié par l'agent : texte de fermeture, préparation au clavier,
mot de passe focalisé, refus sans confirmation avec focus sur l'alerte,
annulation avec retour du focus, fermeture fictive, message de résultat,
reconnexion refusée et ancien onglet redirigé vers la connexion. Compte mobile
360 px et bureau 1440 px, confidentialité mobile 360 px. Aucun débordement
horizontal observé ni erreur/avertissement console sur les pages contrôlées.
Le bouton WhatsApp existant recouvre ponctuellement le texte de confidentialité
sur mobile ; défaut préexistant signalé en 09, hors de cette correction.

L'utilisateur a confirmé sur un second compte fictif : **textes, confirmation et
refus de reconnexion corrects**. Les autres tests techniques ne sont pas présentés
comme personnellement validés par lui. Dimensions réinitialisées, onglets de
recette fermés, seul serveur temporaire F11 arrêté après cette confirmation.

| Preuve | Capture |
|---|---|
| Confirmation, champs vides sur bureau | [1440 px](confirmation-desktop-1440.png) |
| Refus sans mot de passe ni case cochée | [360 px](confirmation-refusee-mobile-360.png) |
| Message après fermeture fictive | [1440 px](compte-ferme-desktop-1440.png) |
| Reconnexion refusée, identifiants retirés avant capture | [1440 px](reconnexion-refusee-desktop-1440.png) |
| Encadré ajouté avant le contenu CMS | [360 px](confidentialite-mobile-360.png) |

Pour une nouvelle vérification ensemble, préparer une nouvelle instance isolée
et un compte fictif jetable : ouvrir Mon compte, lire l'information, préparer
puis annuler, refuser une confirmation incomplète, fermer avec mot de passe et
case cochée, constater le résultat, tenter une reconnexion et recharger un ancien
onglet. Vérifier les données conservées uniquement dans la base de cette recette.
Ne jamais effectuer cette fermeture de démonstration sur un compte réel.

## Limites

MySQL, SMTP réel et délivrabilité, sessions et caches distribués **non validés**.
Le test de deux sessions database sur une base SQLite isolée ne prouve pas le
fonctionnement ou la concurrence d'un déploiement distribué. Aucun examen des
comptes, données, contenus CMS ou sauvegardes en production. Les requêtes déjà
en cours au moment de la fermeture ne constituent pas un mécanisme de révocation
distribuée instantanée. Aucune nouvelle politique de conservation ni implémentation
d'effacement/anonymisation automatique. Base et cache habituels inchangés, aucune
migration habituelle, aucun paiement/email réel, aucun déploiement. Le changement
de mode préexistant d'`artisan` est préservé ; aucune autre correction commencée.

Documentation métier : [cadre fonctionnel](../../03-cadre-fonctionnel.md#fermeture-du-compte--correction-11-27-octobre-2026).
