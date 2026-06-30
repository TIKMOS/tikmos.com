# TIKMOS

Plateforme web personnelle **premium** de **[le Professeur]** — professeur certifié en sciences
mathématiques, ingénieur data science & IA. Une seule adresse (`tikmos.com`) pour **présenter
l'homme et son équipe**, **vendre ses services** (formation, conception de solutions informatiques,
installation, assistance administrative, orientation professionnelle…) et sa **boutique de chaussures
de luxe**, avec **espace client** (suivi devis & commandes) et **espace administration** (CMS + gestion).

> Projet **dérivé de l'installation Sezy** (copie complète Laravel), repalettisé et reciblé sur Tikmos.
> Démarche **docs-first** : la spec et la charte sont écrites avant le code.

## Stack
Laravel 13 · Livewire 3 + Volt · Tailwind CSS 4 · Alpine.js · MySQL (prod) / SQLite (dev) · PHP 8.3.
Spatie (Permission / Settings / MediaLibrary / ActivityLog) · mews/purifier · TipTap · Pest 4.

## Documentation
Toute la documentation est dans [`docs/`](docs/README.md) :
- Cadrage produit, **charte graphique (Encre + Or champagne)**, stack & auth, cadre fonctionnel,
  conventions d'architecture, base de données, interface d'accueil.
- **Bibliothèque de prompts 360** (`docs/prompts/`) : prompt maître + 18 lots, de la fondation à la
  mise en production.

Commencer par lire [`docs/README.md`](docs/README.md), puis le **prompt maître**
[`docs/prompts/00-prompt-maitre.md`](docs/prompts/00-prompt-maitre.md).

## Démarrage (dev)
```bash
composer install
npm install
cp .env.example .env   # puis adapter (DB, mail, WhatsApp)
php artisan key:generate
php artisan migrate
composer dev           # serve + queue + logs + vite
```

## Identité visuelle
Direction **« Encre + Or champagne »** : clair, aéré, noir & blanc dominant + **filets or** (luxe) +
**indigo nuit** (data/IA). Vert WhatsApp imposé. Détail : [`docs/01-charte-graphique.md`](docs/01-charte-graphique.md).

## Licence
Projet privé Alinkka. Socle Laravel sous licence MIT.
