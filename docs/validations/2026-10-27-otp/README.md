# Correction 10 — Challenge OTP et expérience 2FA

Date : 27 octobre 2026. Étape A uniquement, sans commit, push ou déploiement.
Checkpoint précédent vérifié sur GitHub : `0c46a653ee73ef2ce64df96c76fc72dd26741b97`
sur `main`. Modification préexistante du mode d'`artisan` préservée, hors lot.

## Reproduction avant correction

Trois vrais tests de non-régression ajoutés dans
`tests/Feature/Auth/TwoFactorTest.php`, avant modification du service et des vues :

| Test | Défaut observé avant correction |
|---|---|
| F10 — renvoi direct immédiat | Aucune erreur `resend`, deuxième notification autorisée |
| F10 — tentatives et renvoi | Après trois codes incorrects, le renvoi remet les tentatives à zéro |
| F10 — confiance par défaut | `remember_device` vaut `true` |

Commande : `php vendor/bin/pest tests/Feature/Auth/AuthenticationTest.php tests/Feature/Auth/TwoFactorTest.php --filter F10 --compact`.
Résultat avant correction : **3 échecs, 14 assertions**, SQLite en mémoire,
notifications interceptées et HTTP externe interdit. Ces échecs sont les preuves
du défaut initial ; ils ne constituent pas une validation de correction.

## Comportement et garanties

- Code à usage unique, valable 600 secondes ; refus dès l'instant d'expiration.
- Renvoi au plus tôt après 60 secondes ; 5 envois maximum sur 900 secondes,
  premier envoi inclus. Les pannes de transport consomment la réservation.
- Trois comparaisons incorrectes maximum sur 900 secondes, puis blocage de
  la validation et du renvoi jusqu'à la fin de la fenêtre.
- Fenêtres fixes par compte : ni changement de session ou d'IP, ni renvoi,
  nouvelle connexion ou succès ne les effacent. Aucun allongement sur refus.
- Un compte différent sur la même IP conserve ses propres quotas. Les clés
  reposent sur l'identifiant serveur du compte, pas sur un email déclaré.
- État et verrou dans le même cache persistant ; clés et code protégés par HMAC.
  Refus temporaire si cache indisponible ou inadapté. Pas de code ni de message
  d'exception du fournisseur dans les journaux.
- L'ancien code reste utilisable si le transport du renvoi lève une exception.
  Après remise sans exception au canal mail et vérification du verrou, seul le
  nouveau code est accepté. Une remise au transport ne prouve pas la délivrance.
- Un transport ayant perdu son bail ne réécrit pas des quotas ou un code plus
  récents. Le scénario de perte du bail est simulé avec horloge figée ; ce n'est
  pas une mesure de concurrence en production.
- La confiance de l'appareil est décochée. La création d'un appareil approuvé
  exige OTP valide et choix explicite. Cookies et appareils historiques conservés.
- Code dev uniquement en environnement `local`, avec option d'affichage active.
  Une valeur résiduelle de session n'est pas affichée en production et est effacée.
- Les délais sont recalculés côté serveur à chaque action. Le compteur visuel
  repart également lorsque sa durée est identique à la valeur serveur précédente.
  Il ne remplace aucune protection serveur.

Règles détaillées : [authentification](../../02-stack-et-auth.md#8-challenge-otp--correction-10-27-octobre-2026).
Conditions d'exploitation : [cache et transport](../../DEPLOIEMENT.md#conditions-du-cache-et-du-transport-otp-correction-10).

## Isolation et commandes

Fichiers de cette seule correction :

- `app/Services/TwoFactorService.php`
- `config/tikmos.php`
- `resources/views/livewire/pages/auth/login.blade.php`
- `resources/views/livewire/pages/auth/two-factor-challenge.blade.php`
- `tests/Feature/Auth/TwoFactorTest.php`
- `docs/02-stack-et-auth.md`
- `docs/DEPLOIEMENT.md`
- `docs/prompts/audit-2026-10-20/JOURNAL.md` : ligne 10 et note 10 seulement
- le présent rapport et les sept captures listées plus bas
- `public/build/manifest.json`
- `public/build/assets/app-DpDeyuAx.css`
- suppression de `public/build/assets/app-r-zsVh6O.css`

L'index reste vide. `artisan`, les fichiers privés de recette et les métadonnées
locales de validation ne font pas partie du lot.

Les processus de test ont reçu explicitement `APP_ENV=testing`,
`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`, `DB_URL` vide,
`CACHE_STORE=array`, `SESSION_DRIVER=array`, `MAIL_MAILER=array` et
`QUEUE_CONNECTION=sync`. Chemin de cache de configuration absent et répertoire
de vues compilées distincts dans un dossier temporaire dédié. Aucun cache habituel
n'a été purgé. Les suites Pest ont été exécutées séquentiellement.

Les tests OTP interceptent les notifications et interdisent les requêtes HTTP
non simulées. Les cas production et recréation d'adaptateur utilisent le cache
database sur la connexion SQLite en mémoire, sans toucher la base habituelle.
La recréation d'adaptateur ne supprime pas les données de cache.

```powershell
php vendor/bin/pest tests/Feature/Auth --compact
php vendor/bin/pest --compact
php -d memory_limit=1G vendor/bin/phpstan analyse --no-progress --memory-limit=1G --debug
php vendor/bin/pint --test app/Services/TwoFactorService.php config/tikmos.php resources/views/livewire/pages/auth/login.blade.php resources/views/livewire/pages/auth/two-factor-challenge.blade.php tests/Feature/Auth/TwoFactorTest.php
php -l app/Services/TwoFactorService.php
php -l config/tikmos.php
php -l resources/views/livewire/pages/auth/login.blade.php
php -l resources/views/livewire/pages/auth/two-factor-challenge.blade.php
php -l tests/Feature/Auth/TwoFactorTest.php
npm.cmd run build
git diff --check
```

Ces commandes supposent les variables et chemins isolés décrits ci-dessus.
Ne pas substituer la base ou le cache de l'application à ces ressources de test.

## Résultats

| Contrôle | Résultat réellement obtenu |
|---|---|
| Tests d'authentification finaux | **58 tests, 256 assertions**, réussis |
| Suite Pest finale après contrôle du bail | **521 tests, 2 219 assertions**, réussis (263,721 s) |
| Larastan sur `app`, séquentiel `--debug` | **0 erreur**, après le contrôle du bail |
| Pint | Conforme sur les cinq fichiers PHP concernés |
| Syntaxe | Conforme sur les cinq fichiers PHP concernés |
| Build de production | Réussi ; build final 7,02 s ; manifeste vérifié |
| CSS final | `public/build/assets/app-DpDeyuAx.css` |
| JavaScript et polices | Bundles inchangés ; manifeste sans référence manquante |

Les tests couvrent les appels directs, seuils exacts, expirations, renvois, compteurs
entre challenges et après succès, reprise après délai, absence de notification
supplémentaire sur refus, absence de confiance implicite, confiance volontaire,
appareils encore approuvés et expirés, cache inadapté, contention simulée, transport
en erreur, perte du bail, et absence de code de développement en production.

## Recette navigateur réalisée

Serveur PHP lié uniquement à la boucle locale, avec SQLite, cache fichier,
sessions, vues compilées et journaux dans un répertoire temporaire privé distinct.
Compte staff fictif, notifications interceptées, HTTP serveur externe interdit,
transport mail mémoire. Aucune saisie de mot de passe réel ni livraison d'email.
La recette chargeait l'application en mode production, avec affichage dev désactivé ;
uniquement ses URLs de boucle locale étaient alignées sur son serveur HTTP sans TLS.
Cela ne valide ni HTTPS ni le cache de configuration du déploiement réel.

L'horloge était figée, avec avances explicites depuis un guide privé, sans purge.
Les routes, codes interceptés, clé applicative, compte, scripts et fichiers de ce
guide restent hors dépôt. Les captures montrent les champs vides, sans code OTP.

Constats effectués :

1. À 360 × 800 : confiance décochée, renvoi désactivé, délai visible.
2. Après avance au-delà de la validité : message de code expiré/déjà utilisé.
3. Sur code incorrect : nombre d'essais restants, `aria-invalid=true`, association
   du champ à l'erreur, `role=alert` et focus sur l'erreur.
4. Sur la page restée ouverte : renvoi après délai, champ vidé, erreurs effacées,
   statut de succès et nouveau compteur de 60 secondes. Un défaut de remise à
   zéro du compteur a été détecté pendant cette recette, corrigé, puis rejoué.
5. Après renvoi, les essais précédents restent comptabilisés ; le troisième code
   incorrect provoque le blocage des deux actions. Un nouvel onglet conserve ce blocage.
6. À 1440 × 1000 : état bloqué puis reprise après avance de l'horloge ; Tab atteint
   la case, Espace la coche/décoche, Tab atteint le renvoi et Entrée le déclenche.
7. Un dernier code valide saisi puis confirmé avec Entrée ouvre `/admin` pour le
   compte fictif, sans choix de confiance. La persistance d'appareils est vérifiée
   par les tests automatisés, pas déduite de la seule redirection navigateur.
8. Aucun débordement horizontal mesuré aux deux largeurs. Aucun avertissement ou
   erreur console observé sur les onglets du challenge contrôlés.

**Confirmation personnelle de l'utilisateur :** confiance décochée et renvoi correct
après le délai, avec effacement du champ/de l'erreur et nouveau compteur. Les autres
cas techniques ci-dessus ont été vérifiés par l'agent, pas personnellement validés
par l'utilisateur.

Les dimensions temporaires ont été réinitialisées, les onglets de test fermés et
le seul serveur F10 créé a été arrêté après cette confirmation. Le guide local
n'est donc plus servi à la fin de la recette. Les fichiers privés de recette
n'ont pas été publiés.

### Captures

Les tailles dans les noms indiquent les largeurs de viewport demandées ; une barre
de défilement peut réduire la zone capturée. Certaines captures se concentrent sur
les actions et messages après défilement automatique. Elles ne constituent pas un
audit complet d'accessibilité ou de contraste.

- [Challenge initial, mobile 360](otp-initial-mobile-360.png)
- [Code incorrect, mobile 360](otp-incorrect-mobile-360.png)
- [Code expiré, mobile 360](otp-expire-mobile-360.png)
- [Renvoi réussi, mobile 360](otp-renvoi-mobile-360.png)
- [Blocage, mobile 360](otp-blocage-mobile-360.png)
- [Blocage conservé dans un nouvel onglet, bureau 1440](otp-blocage-desktop-1440.png)
- [Renvoi au clavier, bureau 1440](otp-renvoi-desktop-1440.png)

La capture d'expiration précède le dernier ajustement de synchronisation du compteur ;
la règle et le message d'expiration n'ont pas changé ensuite. Les captures du renvoi
et du blocage suivent cet ajustement. Le garde-fou ultérieur de perte du bail ne
modifie ni les vues ni les assets ; ses tests automatisés sont exécutés ensuite.

## Parcours manuel reproductible

Sur une nouvelle recette isolée, sans reprendre de compte réel : ouvrir un challenge
staff fictif, vérifier la confiance décochée, soumettre un code incorrect, attendre
le délai puis renvoyer sans fermer la page. Vérifier champ/erreur effacés et délai
réinitialisé. Cumuler trois codes incorrects entre plusieurs envois/challenges,
vérifier le blocage, attendre la fenêtre et demander un code neuf. Utiliser ce
dernier pour la connexion. Rejouer séparément l'option de confiance volontaire.
Les tests ci-dessus rendent les horloges, transport et données reproductibles sans
dépendre d'un email réel ni du guide temporaire.

## Limites

- MySQL et concurrence réelle non validés.
- Cache partagé entre processus/serveurs, Redis/Memcached/DynamoDB et charge de
  production non validés. SQLite avec recréation d'adaptateur ne prouve pas une
  concurrence distribuée ; la perte du bail est simulée, sans quatre processus réels.
- SMTP réel, timeouts du fournisseur et délivrabilité non validés. Le timeout effectif
  doit rester inférieur au bail de 60 secondes, avec une marge pour le cache.
- Une perte/éviction/purge de cache ou une modification de store, préfixe ou `APP_KEY`
  peut faire perdre les quotas et invalider les codes. Préserver ce cache en exploitation.
- Pas de recette de déploiement HTTPS, sessions réparties ou cache de configuration réel.
- Aucun compte, appareil approuvé, mot de passe ou rôle habituel modifié ; aucune
  migration sur la base habituelle, aucun email réel, commit, push ou déploiement.
- La correction 11 n'est pas commencée.

Références techniques consultées : [verrous de cache Laravel](https://laravel.com/framework/docs/cache/config)
et [sécurité des propriétés/actions Livewire 3](https://livewire.laravel.com/docs/3.x/security),
avec vérification des APIs dans les versions installées.
