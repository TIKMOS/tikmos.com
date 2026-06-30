# Lot 15 — PWA (optionnelle)

> Hérite du **prompt maître**. Réf : `04` §9, `01` §9. **Conditionnel** : n'exécuter que si
> `FeatureSettings.pwa = true` (question ouverte — cadrage §10).

## Objectif
Rendre Tikmos **installable** et résilient hors-ligne (app-shell), sans dépendre d'infra lourde.

## Périmètre / livrables
1. **Manifest** `public/manifest.webmanifest` : nom, icônes maskables (logo officiel), couleurs
   (encre/ivoire), `display: standalone`, start_url, scope.
2. **Service worker custom** `public/sw.js` : précache app-shell, **cache-first** assets,
   **network-first** données, **fallback offline** soigné (à la charte).
3. **Enregistrement SW** (app.js) + **bouton d'install** custom (`x-tk.install-button`, masqué en
   `display-mode: standalone`).
4. *(Option)* **Web Push** (`minishlink/web-push`) + table `push_subscriptions` + opt-in.
5. **Safe-area** (iOS), icônes/splash.

## Étapes (PDAR)
1. **Plan** : périmètre offline (quelles pages), stratégie de cache, icônes.
2. **Do** : manifest → SW → register/install → (push option).
3. **Assess** : tests/inspection (manifest valide, SW enregistré, install possible, offline =
   fallback). Lighthouse PWA. Pint/Larastan.
4. **Review** : pas de cache agressif cassant les mises à jour (versionner le SW).

## Tests (Pest / manuel)
- `/manifest.webmanifest` 200 et valide ; SW se enregistre ; install dispo (critères remplis).

## Definition of Done
DoD du maître + : installable, offline gracieux, SW versionné, Lighthouse PWA OK — **si activée**.
