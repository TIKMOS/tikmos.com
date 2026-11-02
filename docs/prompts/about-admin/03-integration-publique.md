# Prompt 03 — Intégration publique

Complète la page Tikmos `/a-propos` sans modifier sa composition visuelle ni inventer de contenu. Lis l'audit, `resources/views/public/about.blade.php`, `resources/views/components/tk/seo.blade.php` et les réglages/modèles About.

Lis les champs réglés et les paragraphes/valeurs relationnels ordonnés. Préserve le texte actuel comme contenu initial, affiche seulement les valeurs visibles, échappe tout texte avec Blade et garde les fallbacks d'image existants. Les liens externes s'ouvrent avec `noopener noreferrer`; les CTA gardent leurs destinations valides. Passe les métadonnées spécifiques au composant SEO partagé, avec canonical `/a-propos`; image Open Graph About puis fallback SEO commun. Le JSON-LD reste un schéma fixe ProfilePage/Person sérialisé avec les flags JSON_HEX_* appropriés, sans script fourni par l'admin.

Ajoute des tests de rendu, fallback, ordre/visibilité, métadonnées et échappement. Vérifie les écrans responsive sans toucher aux règles métier. Rapporte les chemins, validations et limites.
