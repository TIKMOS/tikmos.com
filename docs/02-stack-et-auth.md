# TIKMOS — Stack technique & authentification

> Stack **standard Alinkka = Laravel / TALL**. Ce doc fixe les versions, les packages et la stratégie
> **auth + RBAC**. Le projet réutilise l'infrastructure héritée de Sezy (auth, Spatie, settings,
> medialibrary) ; on reciblе le métier, on garde le socle.
> Dernière mise à jour : 2026-06-30.

---

## 1. Stack (versions verrouillées)

| Couche | Choix | Notes |
|---|---|---|
| Langage | **PHP 8.3** | `composer.json: "php": "^8.3"` + `config.platform.php = "8.3.0"` (alignement prod). |
| Framework | **Laravel 13** | Sorti 17/03/2026, PHP 8.3 min. Attributs natifs (models/jobs…) optionnels. |
| Front interactif | **Livewire 3.6 + Volt 1.7** | Single-file components. *(Livewire 4 dispo — voir §6.)* |
| JS | **Alpine.js** | Fourni par Livewire ; JS custom minimal. |
| CSS | **Tailwind CSS 4** | Tokens en `@theme` (voir charte). Build Vite. |
| Build | **Vite 8** + `laravel-vite-plugin` | `npm run dev` / `npm run build`. |
| DB | **MySQL 8+** (prod) / **SQLite** (dev) | Migrations agnostiques. |
| Éditeur riche | **TipTap 3** + **mews/purifier** | Contenu CMS sécurisé (XSS). |

### Packages applicatifs (déjà présents)
- `spatie/laravel-permission` — **RBAC** (rôles + permissions).
- `spatie/laravel-settings` — **configuration globale** (coordonnées, WhatsApp, SEO, feature flags).
- `spatie/laravel-medialibrary` — **médias** (images services/projets/produits/équipe, avec
  conversions/thumbnails).
- `mallardduck/blade-lucide-icons` — **icônes** (Lucide, zéro emoji — voir conventions).
- `mews/purifier` — purification HTML.
- Dev : **Pest 4** (+ plugins Laravel/Livewire), **Pint**, **Larastan**, **Pail**, Faker, Collision.

### À ajouter selon les lots (au besoin)
- SEO : `ralphjsmit/laravel-seo` **ou** composant maison `x-tk.seo` + `spatie/laravel-sitemap` +
  `spatie/schema-org` (JSON-LD `Person` / `Organization` / `Product`).
- Audit : `spatie/laravel-activitylog`.
- PWA : SW custom + manifest (si PWA activée) ; `minishlink/web-push` (push) en option.

---

## 2. Stratégie d'authentification

Deux familles d'utilisateurs, **un seul modèle `User`**, séparés par **rôles** :

1. **Clients** — inscription/connexion **email + mot de passe**. Parcours simple (pas de 2FA imposée).
2. **Équipe & Admin** — comptes **créés/invités** par l'admin (pas d'auto-inscription), **2FA OTP
   email obligatoire**, device-remember signé, login log.

### Composants d'auth
- **Connexion / inscription / reset** : Volt pages (`routes/auth.php`), rate-limited (`throttle`).
- **2FA OTP email** : `TwoFactorService` — code à usage unique, expiration courte, renvoi limité,
  middleware `EnsureTwoFactorVerified` sur les routes gestion.
- **Device remember** : cookie signé HMAC + table `trusted_devices` (déjà présente).
- **Login log** : table `login_logs` (IP, UA, résultat) pour l'audit.
- **Sessions** en **base** (driver `database`) — compatible hébergement mutualisé.

### Vérification & approbation
- **Vérification d'email** clients (Laravel natif) — recommandée avant actions sensibles.
- Pas d'« approbation manuelle » côté client (friction inutile). Côté équipe : compte activé par
  l'admin uniquement.

---

## 3. RBAC — rôles & permissions

> Source de vérité : **`config/permissions.php`** (catalogue seedé). Rôles typés en **enum**
> (`app/Enums/Role.php`).

### Rôles (proposition)
| Rôle | Périmètre |
|---|---|
| `admin` | Tout (fondateur / [le Professeur]). |
| `commercial` | Demandes/devis, fiches clients, services. |
| `support` | Suivi demandes & commandes, échanges clients. |
| `boutique` | Produits, stock, commandes boutique. |
| `editor` | CMS / contenu vitrine (sans accès financier sensible). |
| `client` | Espace client (ses devis & commandes). |

### Permissions (extraits — catalogue complet dans `config/permissions.php`)
```
services.view services.manage
requests.view requests.manage requests.assign quotes.send
shop.products.manage shop.stock.manage shop.orders.view shop.orders.manage
cms.manage settings.manage media.manage
clients.view clients.manage
team.view team.manage roles.manage
dashboard.view reports.view
```

- Middleware de routes : `role:` et `permission:` ; **Policies** pour le fin (ex. un commercial ne
  voit que les demandes qui lui sont affectées si on le décide).
- Le **client** n'accède qu'à **ses** ressources (scoping par `user_id`, vérifié par Policy).

---

## 4. Espaces & gardes (middleware)
| Espace | Préfixe / domaine | Middleware |
|---|---|---|
| Vitrine publique | `/` | `web` |
| Auth | `/login`, `/register`, `/2fa`… | `web`, `guest`/`auth` |
| Espace client | `/espace` (ou `app.tikmos.com`) | `auth`, `verified` |
| Back-office | `/admin` | `auth`, `role:admin\|commercial\|support\|boutique\|editor`, `EnsureTwoFactorVerified` |

---

## 5. Sécurité (rappel — détail dans 04)
Validation serveur systématique (Form Requests / règles Livewire), `$fillable` explicite, Policies +
permissions, CSRF natif, XSS (échappement Blade + purifier sur HTML riche), SQLi (Eloquent/bindings),
rate limiting login, uploads hors webroot + URLs signées, en-têtes de sécurité, HTTPS forcé en prod,
secrets en `.env`, audit (`activitylog` + `login_logs`), RGPD (consentement cookies, minimisation,
droit à l'effacement client).

---

## 6. Note Livewire 4 (chemin d'upgrade)
Livewire **4** (v4.2.0, support Laravel 13) est disponible et apporte des single-file components
natifs + durcissements sécurité. **Décision : rester en Livewire 3 + Volt au lancement** (base
copiée stable et testée). Upgrade L4 envisageable en phase 5 :
1. Geler une suite Pest verte sur L3.
2. Lire le guide d'upgrade officiel, bumper `livewire/livewire`, ajuster Volt/syntaxe.
3. Re-tester de A à Z avant bascule. **Ne pas mélanger** L3/L4 en cours de chantier.

---

## 7. Environnement
- **Dev** : SQLite (`database/database.sqlite`), `composer dev` (serve + queue + pail + vite).
- **Prod** : MySQL, `APP_ENV=production`, `APP_DEBUG=false`, caches Laravel, `storage:link`, HTTPS,
  queue `database` + cron `schedule:run`. Détail : `04-conventions-architecture.md` §12.
- `.env` : adapter `APP_NAME="Tikmos"`, `APP_URL=https://tikmos.com`, coordonnées WhatsApp, mail.

## 8. Challenge OTP — correction 10 (27 octobre 2026)

Les limites de `config/tikmos.php` sont appliquées par `TwoFactorService`, y compris
aux appels Livewire directs et aux nouvelles connexions avec un mot de passe correct :

| Règle par compte | Valeur par défaut |
|---|---|
| Validité du dernier code | 600 secondes ; refus à l'instant d'expiration |
| Intervalle minimal entre envois | 60 secondes |
| Plafond d'envois, premier envoi compris | 5 sur 900 secondes |
| Codes incorrects avant blocage | 3 sur 900 secondes |

Les fenêtres fixes commencent respectivement au premier envoi réservé et au premier
code incorrect. Ni un renvoi, ni un nouveau challenge, ni une connexion réussie
n'effacent ces compteurs. Un refus ne prolonge pas les fenêtres. Les formats invalides
sont rejetés par la validation du formulaire avant comparaison du code ; un code absent,
expiré ou déjà consommé n'ouvre aucune session et ne compte pas comme une comparaison
incorrecte. Après trois comparaisons incorrectes, validation **et** renvoi sont bloqués
jusqu'à la fin de la fenêtre. À sa fin, demander un nouveau code si le précédent a expiré.

Les clés sont liées à l'identifiant serveur du compte, sous la forme
`2fa:v2:<HMAC-SHA256(APP_KEY, identifiant)>`, avec un verrou `:lock`.
Aucune adresse email ou IP déclarée ne sert d'identité. Deux comptes sur la même IP
ont des compteurs distincts ; changer de session ou d'IP ne réinitialise pas ceux d'un
compte. Aucun code en clair n'est stocké dans cet état : son empreinte est un HMAC.

Le service utilise `cache.limiter` si configuré, sinon `cache.default`. Un cache
persistant avec verrous est obligatoire hors tests ; `array` n'est accepté qu'en
environnement `testing`. Les pilotes acceptés sont database, file, Redis, Memcached
et DynamoDB. Un cache non adapté, inaccessible ou sans clé applicative entraîne un
refus temporaire, sans authentification. Les logs ne contiennent que la classe de
l'exception, jamais le code, les identifiants ou le message du transport.

Envoi et vérification utilisent le même verrou par compte. Le quota et le délai
d'envoi sont réservés avant le transport ; un échec consomme cette réservation pour
éviter des appels illimités au fournisseur. Le dernier code reste valable si le
transport lève une exception. Un nouveau code remis sans exception au canal mail
remplace l'ancien uniquement si le verrou appartient toujours à l'appel ; cela
**ne prouve pas sa délivrance**. Un transport ayant perdu son bail ne réécrit pas un
code ni des quotas plus récents. La réussite consomme le code
une seule fois. Une panne après remise au transport peut rendre la réception ambiguë :
attendre le délai puis demander un nouveau code. Voir les exigences de timeout dans
[le déploiement](DEPLOIEMENT.md).

Le champ de confiance est décoché par défaut. Les cookies et appareils déjà approuvés
gardent leur fonctionnement ; un nouvel appareil n'est enregistré qu'après OTP valide
et choix explicite. Le code de développement, également verrouillé contre les
modifications de propriété Livewire, ne peut être affiché qu'en `APP_ENV=local` avec
`TIKMOS_DEV_SHOW_CODE=true`. Une valeur de session résiduelle n'est pas affichée dans
les autres environnements et est effacée.

Le challenge distingue code incorrect, expiré/déjà utilisé, renvoi indisponible et
validation bloquée. Le délai est annoncé dans l'erreur puis affiché par un compteur
visuel sans annonce vocale chaque seconde. Le serveur reste l'autorité à chaque action.
Un renvoi réussi vide le champ et efface les erreurs précédentes ; la case de confiance
conserve le choix volontaire. Le format historique des cookies et la table des appareils
ne changent pas. Les codes encore présents dans l'ancien espace de cache `2fa:code:*`
ne sont pas repris : ils expirent naturellement, sans purge ; demander un nouveau code.

Preuves et limites : [recette OTP](validations/2026-10-27-otp/README.md).
