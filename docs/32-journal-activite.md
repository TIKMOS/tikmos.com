# TIKMOS — Journal d'activité & audit (granulaire)

> Système **complet** de traçabilité : lire les **logs et activités de chaque utilisateur et client,
> action par action, au plus fin**. Qui a fait quoi, quand, sur quel objet, avec quelles valeurs
> avant/après, depuis quelle IP/appareil. Consultable et filtrable depuis l'admin.
> Socle : **Spatie ActivityLog** (déjà présent) + tables d'événements métier + login logs.
> Dernière mise à jour : 2026-07-01.

---

## 1. Objectifs
- **Audit métier** : création/modification/suppression de chaque entité sensible (services, devis,
  commandes, produits, articles, contenus CMS, rôles, réglages, paiements) avec **diff avant/après**.
- **Journal d'accès** : connexions/déconnexions, 2FA, appareils de confiance, échecs (login_logs
  existants).
- **Traçabilité fine (« au minimètre »)** : chaque transition d'état (demande, commande, paiement)
  est déjà journalisée (`request_events`, `order_events`, `payment_events`) → unifiée dans une vue.
- **Par acteur** : filtrer le journal **par utilisateur** ou **par client**, avec chronologie.
- **Sécurité & conformité** : lecture réservée (`audit.view`), rétention configurable, export.

---

## 2. Sources de vérité (unifiées)
| Source | Contenu |
|---|---|
| `activity_log` (Spatie) | CRUD + diffs (`properties.old/attributes`), `causer` (auteur), `subject` (objet). |
| `login_logs` | connexions (IP, user-agent, résultat), 2FA. |
| `request_events` / `order_events` / `payment_events` | transitions d'état métier (timeline). |
| `trusted_devices` | appareils de confiance (auth). |

> Une **vue agrégée** (`ActivityFeed`) fusionne ces sources en une timeline normalisée
> (`{ at, actor, action, subject, context, source }`), triable/filtrable.

---

## 3. Ce qui est journalisé (« au minimètre »)
- **Auteur** : utilisateur connecté (`causer_id`) ou « invité » (demande sans compte).
- **Action** : `created | updated | deleted | restored | status_changed | login | logout |
  2fa_passed | payment_succeeded | role_assigned | settings_updated | media_uploaded …`
- **Objet** : type + id + libellé lisible (référence).
- **Diff** : valeurs **avant/après** sur les champs modifiés (mass-assignment surveillé).
- **Contexte** : IP, user-agent, canal (web/admin), horodatage précis.

Activation par modèle via le trait Spatie `LogsActivity` + `logOnly([...])` + `logOnlyDirty()` +
`->useLogName('metier')`. Réglages sensibles (prix, statuts, rôles, paiements) : log **systématique**.

---

## 4. Interfaces admin
- **Admin → Audit** (existe, à enrichir) : liste filtrable (acteur, type d'objet, action, période,
  IP), pagination, détail d'un événement (diff lisible).
- **Fiche utilisateur / client (CRM)** : onglet **« Activité »** = timeline de cet acteur (toutes
  sources) — répond à « les activités de chaque utilisateur et client par actions ».
- **Fiche objet** (devis, commande, paiement) : timeline déjà présente (events) + activités liées.

---

## 5. Modèle & service
```
app/Audit/
  ActivityFeed.php       # agrège activity_log + *_events + login_logs → timeline normalisée
  Concerns/Auditable.php # trait maison : configure LogsActivity + libellé + logName cohérents
  AuditContext.php       # capte IP/UA/canal et les attache à chaque activité (middleware)
```
- **Middleware** `RecordAuditContext` : injecte IP/UA/canal dans les propriétés d'activité.
- **Rétention** : commande `audit:prune --days=…` planifiée (config `RETENTION_DAYS`), en respectant
  les obligations légales (voir RGPD).

---

## 6. Sécurité, perf, RGPD
- Lecture **réservée** (`audit.view` ; détail sensible `audit.view.sensitive` en option).
- **Immuabilité** : le journal n'est pas éditable depuis l'app (append-only).
- **Perf** : index sur `causer_id`, `subject_type/id`, `created_at`, `log_name` ; pagination ; pas de
  N+1 (eager `causer`, `subject`).
- **RGPD** : l'export/effacement client (déjà en place) anonymise le `causer` sans détruire la
  traçabilité comptable ; rétention bornée.

---

## 7. Definition of Done (lot d'implémentation)
- [ ] Trait `Auditable` appliqué aux entités sensibles (diffs avant/après).
- [ ] `AuditContext` (IP/UA/canal) via middleware ; login_logs déjà branchés.
- [ ] `ActivityFeed` (timeline agrégée normalisée) + filtres.
- [ ] Admin Audit enrichi + onglet **Activité** sur fiche user/client + fiches objets.
- [ ] Rétention `audit:prune` planifiée ; export CSV.
- [ ] Tests : une action CRUD/paiement/login crée l'activité attendue (acteur, diff, contexte) ;
      filtrage par acteur ; accès refusé sans permission.
