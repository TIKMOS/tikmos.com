# TIKMOS — Éditeur de texte riche (module portable)

> Éditeur WYSIWYG **solide, réactif et copiable** sur d'autres projets. Socle **TipTap v3**
> (ProseMirror) + Alpine + Livewire, rendu assaini côté serveur (mews/purifier) et stylé via
> `.prose-tk`. Dernière mise à jour : 2026-07-09.

---

## 1. Fonctionnalités
Gras, italique, souligné, barré · titres H2/H3 · alignement gauche/centre/droite · listes à
puces / numérotées · citation · bloc de code · lien (édition d'URL) · **image** (upload +
collage + glisser-déposer) · **tableau** (insertion + ajout/suppression de lignes/colonnes,
en-tête, redimensionnement) · filet horizontal · effacer la mise en forme · annuler/rétablir
(avec états désactivés). Collage depuis Word/Docs **assaini automatiquement** au save.

---

## 2. Anatomie (4 pièces)
| Fichier | Rôle | Portable ? |
|---|---|---|
| `resources/js/tiptap-editor.js` | **Cœur agnostique** : `createEditor()` — toutes les extensions TipTap, un seul import (un seul chunk = une seule copie de ProseMirror). Aucune dépendance framework. | ✅ tel quel |
| `resources/js/app.js` → `window.szEditor` | Glue **Alpine + Livewire** : barre d'outils, upload, sync `wire:model`. | ✅ (Alpine+Livewire) |
| `resources/views/components/tk/editor.blade.php` | Composant `<x-tk.editor wire:model="…" />` : UI de la barre d'outils. | adapter le markup |
| `app/Http/Controllers/EditorImageController.php` + route `admin.editor.image` | Endpoint upload : `POST file → { url }`. | contrat réplicable |

Deux règles **impératives** (sinon « Applying a mismatched transaction ») :
1. L'instance TipTap vit dans une **variable de clôture**, jamais dans un état réactif
   (Alpine/Vue proxifient en profondeur → ProseMirror casse). Seuls `active`, `canUndo`,
   `canRedo`, `inTable` sont réactifs.
2. **Un seul point d'import** de TipTap (`tiptap-editor.js`) — plusieurs `import()` séparés
   dupliquent `prosemirror-model` sur deux chunks Vite → deux classes `NodeType` → même crash.

---

## 3. Contrat d'upload d'image (portable)
- Requête : `POST <uploadUrl>` multipart, champ `file`, en-têtes `X-CSRF-TOKEN` + `Accept: json`.
- Réponse : `200 { "url": "https://…/storage/editor/AAAA/MM/xxxx.webp" }`.
- Ici : `EditorImageController` → `ImageService::storeInlineImage()` (disque `public`,
  validation `image` + mimes/poids depuis `MediaSettings`). Réservé aux rôles de gestion.
- Sans `uploadUrl`, le bouton image retombe sur une **saisie d'URL** (fonctionne partout).

---

## 4. Sécurité — assainissement serveur (source de vérité)
Le HTML de l'éditeur n'est JAMAIS stocké tel quel : chaque `save()` applique `clean()`
(`config/purifier.php`). L'allowlist autorise la mise en forme utile et **retire tout le
reste** (script, `on*=`, `javascript:`, iframe…) :
```
div,b,strong,i,em,u,s,a[href|title|target|rel],ul,ol,li,p[style],br,span[style],
img[src|alt|width|height],h2[style],h3[style],h4[style],blockquote,code,pre,hr,
table,colgroup,col[span|style],thead,tbody,tr,td[colspan|rowspan|style],th[colspan|rowspan|style]
```
Styles CSS autorisés : `text-align`, `width`, couleurs, police… (voir `CSS.AllowedProperties`).
Le front rend ce HTML en `{!! $model->body !!}` dans un conteneur `.prose-tk`.

---

## 5. Rendu (`.prose-tk`)
Typographie de marque dans `resources/css/app.css` (titres display, listes à puces or,
citations bordées or, code, **tableaux** bordés/zébrés, images arrondies responsives, états
d'édition ProseMirror : cellule sélectionnée, poignée de colonne, nœud sélectionné).
La **même classe** stylise l'éditeur (WYSIWYG) et la vitrine → aperçu fidèle.

---

## 6. Copier sur un autre projet
1. Copier `resources/js/tiptap-editor.js` + le bloc `szEditor` de `app.js` + `x-tk.editor`.
2. `npm i @tiptap/core @tiptap/starter-kit @tiptap/extension-image @tiptap/extension-text-align @tiptap/extension-table` (mêmes versions, exactes, pour éviter le skew de peer-deps).
3. Copier le bloc `.prose-tk` de `app.css`.
4. Fournir un endpoint upload respectant le contrat §3 (ou omettre `uploadUrl` → saisie d'URL).
5. Assainir à l'enregistrement avec l'allowlist §4 (purifier ou équivalent), rendre en `.prose-tk`.

---

## 7. Definition of Done — ✅ livré
- [x] Extensions image / alignement / tableau + collage-glisser d'images.
- [x] Endpoint upload sécurisé (`ImageService::storeInlineImage`), CSRF, validation `MediaSettings`.
- [x] Allowlist purifier élargie (tables, styles) sans régression XSS.
- [x] `.prose-tk` : tableaux/images/alignement stylés (éditeur + front).
- [x] Tests : sanitize (table/img/align conservés, script/onerror/javascript retirés) ;
      upload (image OK, non-image 422, invité redirigé, client 403). Vérifié au navigateur.
