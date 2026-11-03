# Prompt 05 — Tests et assurance qualité

Relis l'audit About et les prompts 01–04. Couvre migrations/defaults sans écrasement, photo, sauvegarde/rechargement, ajout/suppression/ordre des paragraphes, ajout/modification/masquage/restauration/ordre des valeurs, CTA, visibilité, SEO, échappement, droits, validation des URL et erreurs d'upload.

Exécute d'abord les tests About sur une DB/stockage isolés, ensuite tests Admin/Settings/SEO impactés, puis Pest complet séparément. Exécute Larastan, Pint et syntaxe sur le PHP concerné, puis build distinct des tests; contrôle le manifeste si les assets changent. Simule email/réseau et n'utilise ni base/cache habituels, ni compte réel, ni paiement/email réel. Si une dépendance manque ou un contrôle échoue, documente-le; ne prétends pas à une validation verte.

Fournis commandes exactes, nombres de tests/ assertions réellement observés, vérifications navigateur et limites MySQL/stockage/cache partagé.
