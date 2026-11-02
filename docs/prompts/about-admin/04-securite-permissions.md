# Prompt 04 — Sécurité et permissions

Audite le module après les prompts 01–03. Les points d'entrée sont la route `/admin/reglages/a-propos`, son composant Volt, les modèles About, `MediaService` et la vue publique.

Confirme auth, approbation du compte, rôle de gestion et permission `cms.pages.manage` sur la lecture et chaque action, aussi en appel Livewire direct. Revalide les identifiants de lignes, champs, limites, icônes, booléens, URL et uploads côté serveur. Autorise chemins internes à barre unique et HTTPS; refuse URL protocol-relative et schémas dangereux. Le contenu reste échappé, sans HTML arbitraire. Fichiers d'image publics uniquement par le disque prévu; ne révèle pas de chemin local ou contenu privé.

Ajoute tests d'accès, d'appel direct, XSS échappé, URL hostile, upload invalide/trop volumineux et succès autorisé. Utilise base et stockage fakes; ne modifie aucun compte réel. Rapporte précisément les garanties non testées.
