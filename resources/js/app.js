// Enregistrement du service worker (PWA installable + offline) — activé seulement
// si la fonctionnalité PWA est activée dans l'admin (méta `tk-pwa`).
if ('serviceWorker' in navigator && document.querySelector('meta[name="tk-pwa"]')) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {})
    })
}

// --- Fix « reveal » + Livewire ---
// Les éléments `.reveal` deviennent visibles via la classe `.is-revealed` ajoutée en JS.
// Lors d'un re-render Livewire, le morph du DOM retire cette classe (absente du HTML serveur)
// → le contenu repasse à opacity:0 et « disparaît ». On la ré-applique après chaque morph.
document.addEventListener('livewire:init', () => {
    const reveal = (root) => {
        if (!root) return
        if (root.classList && root.classList.contains('reveal')) root.classList.add('is-revealed')
        root.querySelectorAll && root.querySelectorAll('.reveal').forEach((n) => n.classList.add('is-revealed'))
    }
    window.Livewire.hook('morphed', ({ el }) => reveal(el))
    window.Livewire.hook('morph', ({ el }) => reveal(el))
})

// --- Consentement cookies (RGPD) — composant x-tk.cookie-consent ---
// Stocke le consentement par catégorie dans un cookie 6 mois. Aucun script tiers
// (analytics/marketing) ne doit s'exécuter avant un consentement explicite.
window.szCookieConsent = () => ({
    visible: false,
    showPrefs: false,
    prefs: { analytics: false, marketing: false },
    key: 'tikmos_cookie_consent',
    init() {
        const saved = this.read()
        if (!saved) {
            this.visible = true
        } else {
            this.prefs = { analytics: !!saved.analytics, marketing: !!saved.marketing }
            this.apply()
        }
        window.addEventListener('tk-open-cookie-prefs', () => { this.showPrefs = true; this.visible = true })
    },
    read() {
        const m = document.cookie.match(new RegExp('(?:^|; )' + this.key + '=([^;]*)'))
        if (!m) return null
        try { return JSON.parse(decodeURIComponent(m[1])) } catch (e) { return null }
    },
    write(value) {
        const v = encodeURIComponent(JSON.stringify(value))
        document.cookie = `${this.key}=${v}; path=/; max-age=${60 * 60 * 24 * 183}; SameSite=Lax`
    },
    persist(prefs) {
        this.write({ necessary: true, analytics: prefs.analytics, marketing: prefs.marketing, v: 1 })
        this.prefs = prefs
        this.visible = false
        this.showPrefs = false
        this.apply()
    },
    acceptAll() { this.persist({ analytics: true, marketing: true }) },
    rejectAll() { this.persist({ analytics: false, marketing: false }) },
    saveChoices() { this.persist({ analytics: this.prefs.analytics, marketing: this.prefs.marketing }) },
    apply() {
        // Point d'extension : déclenche les scripts tiers selon le consentement.
        window.dispatchEvent(new CustomEvent('sz-consent', { detail: this.prefs }))
    },
})

// --- Liste réordonnable par glisser-déposer (composant x-tk.sortable-list) ---
// Réordonne les <li data-id> enfants et appelle la méthode Livewire `onReorder(ids)`.
window.szSortable = (onReorder) => ({
    dragId: null,
    init() {
        this.$root.querySelectorAll('[data-id]').forEach((el) => this.bind(el))
    },
    bind(el) {
        el.setAttribute('draggable', 'true')
        el.addEventListener('dragstart', () => { this.dragId = el.dataset.id; el.classList.add('opacity-50') })
        el.addEventListener('dragend', () => { el.classList.remove('opacity-50') })
        el.addEventListener('dragover', (e) => {
            e.preventDefault()
            const dragging = this.$root.querySelector(`[data-id="${this.dragId}"]`)
            if (!dragging || dragging === el) return
            const rect = el.getBoundingClientRect()
            const after = (e.clientY - rect.top) / rect.height > 0.5
            el.parentNode.insertBefore(dragging, after ? el.nextSibling : el)
        })
        el.addEventListener('drop', (e) => { e.preventDefault(); this.commit() })
    },
    commit() {
        const ids = [...this.$root.querySelectorAll('[data-id]')].map((el) => el.dataset.id)
        if (onReorder) this.$wire.call(onReorder, ids)
    },
})

// --- Éditeur riche TipTap (composant x-tk.editor) ---
// Factory Alpine : pilote un TipTap lié à une propriété Livewire (par nom).
window.szEditor = (modelName, uploadUrl = null) => {
    // IMPORTANT : l'instance TipTap est gardée dans une variable de CLÔTURE, jamais dans
    // une propriété réactive d'Alpine. Alpine proxifie en profondeur ses propriétés ;
    // or ProseMirror compare des identités de nœuds/états et casse si on lui passe des
    // objets proxifiés (« Applying a mismatched transaction »). Seuls les indicateurs
    // d'UI (active/canUndo/canRedo/inTable) sont réactifs. Patron officiel TipTap+Alpine.
    let editor = null

    // Upload d'image portable : POST multipart `file` → { url }. (null = pas d'upload,
    // on retombe sur la saisie d'URL.)
    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
    const uploadImage = uploadUrl
        ? async (file) => {
            const body = new FormData()
            body.append('file', file)
            const res = await fetch(uploadUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
                body,
            })
            if (!res.ok) throw new Error('upload ' + res.status)
            return (await res.json()).url
        }
        : null

    return {
        active: {},
        canUndo: false,
        canRedo: false,
        inTable: false,
        uploading: false,
        async init() {
            // Un seul module = un seul chunk = une seule copie de ProseMirror.
            const { createEditor } = await import('./tiptap-editor.js')
            const wire = this.$wire
            const self = this
            editor = createEditor({
                element: this.$refs.editor,
                content: wire.get(modelName) || '',
                uploadImage,
                onUpdate: ({ editor: e }) => {
                    // Defer (3e arg false) : pas de round-trip serveur à chaque frappe.
                    wire.set(modelName, e.getHTML(), false)
                    self.refresh()
                },
                onSelectionUpdate: () => self.refresh(),
            })
            this.refresh()
        },
        refresh() {
            if (!editor) return
            this.active = {
                bold: editor.isActive('bold'),
                italic: editor.isActive('italic'),
                underline: editor.isActive('underline'),
                strike: editor.isActive('strike'),
                h2: editor.isActive('heading', { level: 2 }),
                h3: editor.isActive('heading', { level: 3 }),
                bullet: editor.isActive('bulletList'),
                ordered: editor.isActive('orderedList'),
                blockquote: editor.isActive('blockquote'),
                codeBlock: editor.isActive('codeBlock'),
                link: editor.isActive('link'),
                alignLeft: editor.isActive({ textAlign: 'left' }),
                alignCenter: editor.isActive({ textAlign: 'center' }),
                alignRight: editor.isActive({ textAlign: 'right' }),
            }
            this.inTable = editor.isActive('table')
            this.canUndo = editor.can().undo()
            this.canRedo = editor.can().redo()
        },
        run(name) {
            if (!editor) return
            const c = editor.chain().focus()
            const actions = {
                bold: () => c.toggleBold().run(),
                italic: () => c.toggleItalic().run(),
                underline: () => c.toggleUnderline().run(),
                strike: () => c.toggleStrike().run(),
                h2: () => c.toggleHeading({ level: 2 }).run(),
                h3: () => c.toggleHeading({ level: 3 }).run(),
                bullet: () => c.toggleBulletList().run(),
                ordered: () => c.toggleOrderedList().run(),
                blockquote: () => c.toggleBlockquote().run(),
                codeBlock: () => c.toggleCodeBlock().run(),
                hr: () => c.setHorizontalRule().run(),
                clear: () => c.unsetAllMarks().clearNodes().run(),
                alignLeft: () => c.setTextAlign('left').run(),
                alignCenter: () => c.setTextAlign('center').run(),
                alignRight: () => c.setTextAlign('right').run(),
                table: () => c.insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run(),
                addRow: () => c.addRowAfter().run(),
                addCol: () => c.addColumnAfter().run(),
                delRow: () => c.deleteRow().run(),
                delCol: () => c.deleteColumn().run(),
                delTable: () => c.deleteTable().run(),
                undo: () => c.undo().run(),
                redo: () => c.redo().run(),
            }
            actions[name]?.()
        },
        // Image : upload si configuré, sinon saisie d'URL.
        pickImage() {
            if (uploadImage) this.$refs.imageInput?.click()
            else this.imageByUrl()
        },
        imageByUrl() {
            const url = window.prompt("URL de l'image :", 'https://')
            if (url) editor.chain().focus().setImage({ src: url }).run()
        },
        async onImagePicked(event) {
            const file = event.target.files?.[0]
            event.target.value = ''
            if (!file || !uploadImage) return
            this.uploading = true
            try {
                const url = await uploadImage(file)
                editor.chain().focus().setImage({ src: url }).run()
            } catch (e) {
                window.alert("Échec de l'upload de l'image.")
            } finally {
                this.uploading = false
            }
        },
        toggleLink() {
            if (!editor) return
            if (editor.isActive('link')) {
                editor.chain().focus().unsetLink().run()
                return
            }
            const prev = editor.getAttributes('link').href || 'https://'
            const url = window.prompt('URL du lien :', prev)
            if (url === null) return
            if (url === '') {
                editor.chain().focus().unsetLink().run()
                return
            }
            editor.chain().focus().extendMarkRange('link').setLink({ href: url }).run()
        },
        destroy() {
            editor?.destroy()
            editor = null
        },
    }
}
