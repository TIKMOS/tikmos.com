# TIKMOS — Conventions d'architecture & de structuration

> Règles : arborescence, code, composants, « API-like », sécurité, SEO, perf, PWA, a11y, git, tests,
> déploiement. Principe directeur : **structure Laravel par défaut + couche métier légère quand elle
> se mérite**. Pas de sur-ingénierie (« no extra layers before the app earns them »).
> Dernière mise à jour : 2026-06-30.

---

## 1. Philosophie
- **Lisible pour un humain d'abord.** Un nouveau dev ouvre le projet et s'y retrouve.
- **Conventions Laravel standard** (onboarding, upgrades, packages plus sûrs).
- **Évolutif** : on extrait en Service/Action **quand la logique se duplique ou se couple**.
- **TALL stack** : Tailwind + Alpine + Livewire/Volt + Laravel. Server-driven, peu de JS custom.
- **Réutiliser le socle hérité** (auth, RBAC, settings, medialibrary, composants UI) ; remplacer le
  métier sezy (colis/vols) par le métier Tikmos (services/devis/boutique).

---

## 2. Arborescence cible
```
app/
  Actions/            # actions à responsabilité unique (ex. Requests/SendQuote.php)
  Enums/              # Role, ServiceRequestStatus, OrderStatus, PaymentStatus, Publication...
  Data/               # DTO (readonly) pour sorties de services
  Models/             # User, Service, ServiceRequest, Quote, Project, TeamMember, Product,
                      #   ProductCategory, Order, OrderItem, Testimonial, FaqItem, LegalDocument...
  Services/           # logique métier réutilisée (RequestService, OrderService, NotificationService)
  Support/            # helpers, generators (ReferenceGenerator), value objects
  Notifications/      # QuoteSent, RequestStatusChanged, OrderStatusChanged, LoginCode...
  Policies/           # autorisations fines (scoping client, affectations équipe)
  Settings/           # Spatie settings (GeneralSettings, SeoSettings, ContactSettings...)
  Http/
    Controllers/      # minimal : exports, webhooks, endpoints API-like
    Requests/         # Form Requests
    Middleware/       # EnsureTwoFactorVerified, ForceHttps...
    Resources/        # JSON resources (API-like, futur mobile)
  Livewire/           # composants classe si besoin (sinon Volt en views)
config/
  permissions.php     # catalogue des permissions (source de vérité, seedé)
routes/
  web.php             # vitrine publique
  auth.php            # connexion / 2FA / reset
  app.php             # espace client (auth)
  admin.php           # back-office (auth + role + 2fa)
  api.php             # API-like (futur)
  console.php         # scheduler
resources/
  css/app.css         # @theme (tokens charte — voir 01)
  js/app.js           # Alpine + (SW register si PWA)
  views/
    components/layouts/   # public, app, admin, auth
    components/tk/         # bibliothèque UI réutilisable (x-tk.*)
    livewire/pages/        # Volt single-file (auth, public, app, admin)
    livewire/partials/
database/  migrations/  seeders/  factories/
public/   (manifest.webmanifest  sw.js  icons/  si PWA)  build/
tests/  Feature/  Unit/
lang/fr/
```

> **Nettoyage du socle hérité** : renommer `resources/views/components/sz/` → `components/tk/`
> (composants `x-tk.*`), retirer le métier sezy non réutilisé (Parcel, Flight, Courier, Delivery,
> `routes/livreur.php`…) **au fil des lots**, pas d'un bloc — garder une suite verte.

---

## 3. Conventions de code
- **PSR-12**, formaté par **Laravel Pint** (CI + pre-commit). Analyse statique : **Larastan**.
- **Typage strict** : propriétés/retours typés, `declare(strict_types=1)` sur les classes métier.
- **Enums PHP** pour tout statut/constante (jamais de strings magiques).
- **Nommage** : Models singulier (`Service`), tables pluriel snake (`services`), pivots singulier
  ordonné (`order_product` si besoin), colonnes snake_case, routes kebab, composants `tk-*`/`x-tk.*`,
  Volt pages en kebab. Méthodes verbe-action.
- **Contrôleurs maigres** → validation en Form Request, métier en Service/Action.
- **Eloquent** : `$fillable` explicite (jamais `$guarded = []`), relations typées, scopes pour les
  requêtes récurrentes, `casts()` pour enums/dates/montants. **Montants** : entiers (centimes) ou
  decimal — fixer l'unité par devise et la documenter (05).
- **Commits** : Conventional Commits (`feat:`, `fix:`, `refactor:`…). Branches `feat/services-devis`,
  `fix/…`. Pas de commit direct sur `main` sans revue (phasage docs-first).

---

## 4. Composants réutilisables (UI)
- **Blade components `x-tk.*`** = brique de base (sans overhead Livewire). Voir liste en `01` §6.
- **Livewire/Volt** = composants à état (formulaires, listes filtrables, panier, suivi).
- **Règles** : un composant = une responsabilité ; props descendantes, événements montants ; Blade
  pour le statique imbriqué, Livewire pour l'interactif ; **pas de logique métier dans les vues**.
- **États standard** : toute liste gère *loading (skeleton)*, *vide (empty-state)*, *erreur*.

---

## 5. Conventions « API-like »
Même en monolithe Livewire, on structure comme une API (futur mobile/intégrations) :
- **Service layer** retourne des **DTO** (`app/Data`), pas des tableaux anonymes.
- **API Resources** pour toute sortie JSON : enveloppe `{ data, meta }`, erreurs `{ message, errors }`.
- **Versionnage prêt** : préfixe `/api/v1` réservé. **Auth API future** : Laravel Sanctum.
- **Validation serveur systématique** — ne jamais faire confiance au client.

---

## 6. Sécurité (défense en profondeur)
- **Validation** : Form Requests / règles Livewire sur **toute** entrée ; `->validated()` only.
- **Mass-assignment** : `$fillable` explicite partout.
- **AuthZ** : Spatie permissions (`config/permissions.php`) + Policies (scoping client, affectations).
  Middleware `role:` + `permission:` sur les routes.
- **Auth** : 2FA OTP email (gestion), device-remember signé HMAC, rate limiting login, reset sécurisé,
  sessions en DB.
- **Transport** : `ForceHttps` en prod, HSTS, en-têtes (`X-Content-Type-Options`, `X-Frame-Options`/
  CSP, `Referrer-Policy`), cookies `secure`+`httponly`+`samesite`.
- **CSRF** natif ; **XSS** : échappement Blade + **purifier** (`mews/purifier`) sur tout HTML riche
  (descriptions services/projets/produits) ; **SQLi** : Eloquent/bindings only.
- **Uploads** (pièces jointes devis, médias) : type/taille validés, hors webroot (`storage/app`),
  accès **URLs signées** + autorisation. Images publiques via medialibrary (conversions).
- **Secrets** en `.env`. **Audit** : `spatie/laravel-activitylog` + `login_logs`.
- **RGPD** : minimisation, consentement cookies, droit à l'effacement (clients).
- Pages d'erreur personnalisées (403/404/419/429/500/503).

---

## 7. SEO de base
- **Meta par page** (titre < 60c, description < 160c, canonical) via composant `x-tk.seo` (ou
  `ralphjsmit/laravel-seo`). OpenGraph + Twitter Cards.
- **Sitemap** auto (`spatie/laravel-sitemap`) + `robots.txt`.
- **JSON-LD** (`spatie/schema-org`) : **`Person`** ([le Professeur]) + **`ProfessionalService`** /
  `Organization`, **`Product`** (boutique), `BreadcrumbList`, `FAQPage`.
- **URLs propres** (kebab, slugs), Hn structurés, `alt` partout, langue `fr`. Perf = SEO (§8).

---

## 8. Performance
- **Build Vite** (Tailwind 4), CSS/JS minifiés, **fonts `swap`** (self-host).
- **Images** : webp/avif, `loading="lazy"`, dimensions explicites, blur-up (medialibrary conversions).
- **Eloquent** : eager loading (`with`), zéro N+1 (Larastan/Debugbar en dev), index DB (voir 05).
- **Cache prod** : `config:cache route:cache view:cache event:cache`. Cache applicatif **DB/file**.
- **Livewire `wire:navigate`** pour des transitions SPA-like.
- **Queue** : notifications/emails en `database`.
- Budget : LCP < 2.5 s, CLS < 0.1, Lighthouse mobile ≥ 90.

---

## 9. PWA (optionnelle)
Si activée : **manifest** + **service worker custom** (précache app-shell, network-first données,
cache-first assets, fallback offline), icônes maskables (logo officiel), bouton d'install custom.
Web Push (`minishlink/web-push`) en option. **Décision PWA = question ouverte** (cadrage §10).

---

## 10. Accessibilité (AA)
Contraste ≥ 4.5:1 (**or texte = `gold-600`**), focus visibles (anneau indigo), cibles ≥ 44 px,
navigation clavier, ARIA sur composants interactifs, `prefers-reduced-motion`, `alt` partout,
formulaires labellisés.

---

## 11. Tests & qualité
- **Pest 4** : Feature tests sur les flux critiques (auth/2FA, demande→devis, commande boutique,
  permissions/scoping, rendu des pages publiques). Unit sur Services/Actions (génération référence,
  transitions d'état, calcul panier).
- **Factories** pour toutes les entités. CI : **Pint + Larastan + Pest** avant merge.
- Voir le **prompt maître** (`docs/prompts/00-prompt-maitre.md`) pour la discipline « test de A à Z ».

---

## 12. Déploiement (Hostinger shared / VPS-ready, PHP 8.3)
- **composer.json** : `"php": "^8.3"` + `config.platform.php = "8.3.0"`.
- **Document root** = `public/`. Pointer `tikmos.com` (et éventuel `app.tikmos.com`) sur `.../public`.
- **DB** : MySQL 8 (prod) / SQLite (dev).
- **Queue** : `QUEUE_CONNECTION=database`. **Scheduler** : cron
  `* * * * * cd /home/UXXXX/app && php artisan schedule:run >> /dev/null 2>&1` (queue work,
  sitemap, nettoyages).
- **Cache/Session** : `database` ou `file` (pas de Redis en mutualisé).
- **Prod** : `APP_ENV=production`, `APP_DEBUG=false`, caches activés, `storage:link`, HTTPS forcé,
  `npm run build` → déployer `public/build`.
- **Sauvegardes** : DB + `storage`.

---

## 13. Sources de référence (recherche, 2026)
- Laravel 13 (sortie 17/03/2026, PHP 8.3 min) — laravel.com/docs/13.x, laravel-news.
- Livewire 3 / Volt (livewire.laravel.com/docs/3.x) ; Livewire 4 (v4.2.0) comme upgrade futur.
- Tailwind 4 (`@theme`), Vite 8 ; Pest 4.
- SEO : ralphjsmit/laravel-seo, spatie/laravel-sitemap, spatie/schema-org.
- Tendances site personnel premium 2026 : système d'identité flexible, typo forte, micro-interactions,
  storytelling, marqueurs de crédibilité.
