# Lot 03 — Authentification & RBAC

> Hérite du **prompt maître**. Réf : `02-stack-et-auth.md` (cœur), `03`, `04` §6.

## Objectif
Adapter l'auth héritée à Tikmos : **clients** (email + mot de passe, vérification email) et **équipe/
admin** (création par invitation, **2FA OTP email**), avec **rôles & permissions** (Spatie) et
**espaces gardés** (client / admin).

## Périmètre / livrables
1. **Enum `Role`** (`admin`, `commercial`, `support`, `boutique`, `editor`, `client`) +
   **`config/permissions.php`** (catalogue, voir `02` §3) + **seeder** rôles/permissions.
2. **Pages auth** (Volt, `routes/auth.php`) : login, register (client), reset, vérif email,
   **2FA** (saisie OTP, renvoi limité), repalettisées charte.
3. **2FA OTP email** : `TwoFactorService`, middleware `EnsureTwoFactorVerified` sur `/admin`.
4. **Device remember** (cookie signé) + **login log** (réutiliser l'existant).
5. **Espaces & gardes** : routes `app.php` (`auth`,`verified`), `admin.php` (`auth`,`role:…`,2FA).
6. **Invitation équipe** : flux admin → création compte membre (rôle), activation, 1er mot de passe.
7. **Policies** de base + scoping client (un client ne voit que ses ressources).

## Étapes (PDAR)
1. **Plan** : mapping rôles→permissions, écrans auth, middlewares par route group.
2. **Do** : enum + config + seed → pages auth → 2FA → invitations → gardes/policies.
3. **Assess** : tests Pest (login OK/KO, throttle, 2FA requis sur admin, vérif email, scoping client,
   invitation crée le bon rôle). Pint/Larastan. Parcours réels.
4. **Review** : sécurité (rate limit, sessions DB, cookies sécurisés), messages d'erreur soignés.

## Tests (Pest)
- Client : register → verify → login ; accès `/espace` refusé si non vérifié.
- Admin : login sans 2FA → bloqué hors `/admin` sensible ; avec 2FA → accès.
- Permissions : un `support` n'accède pas aux écrans `roles.manage`.
- Scoping : client A ne peut pas ouvrir une demande de client B (403).

## Definition of Done
DoD du maître + : rôles/permissions seedés, 2FA effective sur l'admin, espaces gardés, scoping client
testé.
