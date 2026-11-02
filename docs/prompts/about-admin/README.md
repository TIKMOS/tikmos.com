# Missions — administration de la page À propos

Les prompts sont dérivés de l'audit [`docs/audits/about-admin-audit.md`](../../audits/about-admin-audit.md) et des conventions réelles (`docs/04-conventions-architecture.md`, `docs/05-base-de-donnees.md`, `docs/31-module-images.md`, `docs/deploiement-hostinger.md`).

Ordre : 01 données; 02 administration; 03 intégration publique; 04 sécurité; 05 assurance qualité; 06 documentation et livraison. Les prompts 02 et 03 dépendent de 01; 04 dépend de 02–03; 05 de 01–04; 06 de la réussite de 05. Le module existant est déjà à `/admin/reglages/a-propos`; ne créez pas de CMS parallèle.

Le prompt 06 prépare une branche et un push seulement si toutes les validations passent; il ne fusionne pas dans `main` et ne déploie pas chez l'hébergeur.
