// Enregistrement du service worker (PWA installable + offline).
if ('serviceWorker' in navigator) {
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
    key: 'sezy_cookie_consent',
    init() {
        const saved = this.read()
        if (!saved) {
            this.visible = true
        } else {
            this.prefs = { analytics: !!saved.analytics, marketing: !!saved.marketing }
            this.apply()
        }
        window.addEventListener('sz-open-cookie-prefs', () => { this.showPrefs = true; this.visible = true })
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
window.szEditor = (modelName) => ({
    editor: null,
    active: {},
    async init() {
        // Import dynamique : TipTap (~127 Ko gzip) n'est chargé que sur les écrans
        // d'édition (admin), jamais sur la vitrine publique.
        const [{ Editor }, { default: StarterKit }, { default: Link }] = await Promise.all([
            import('@tiptap/core'),
            import('@tiptap/starter-kit'),
            import('@tiptap/extension-link'),
        ])
        const wire = this.$wire
        this.editor = new Editor({
            element: this.$refs.editor,
            extensions: [
                StarterKit.configure({ heading: { levels: [2, 3] } }),
                Link.configure({ openOnClick: false, autolink: true }),
            ],
            content: wire.get(modelName) || '',
            editorProps: {
                attributes: { class: 'prose-sz max-w-none min-h-[180px] focus:outline-none' },
            },
            onUpdate: ({ editor }) => {
                // Defer (3e arg false) : pas de round-trip serveur à chaque frappe.
                wire.set(modelName, editor.getHTML(), false)
                this.refresh()
            },
            onSelectionUpdate: () => this.refresh(),
        })
        this.refresh()
    },
    refresh() {
        const e = this.editor
        this.active = {
            bold: e.isActive('bold'),
            italic: e.isActive('italic'),
            h2: e.isActive('heading', { level: 2 }),
            h3: e.isActive('heading', { level: 3 }),
            bullet: e.isActive('bulletList'),
            ordered: e.isActive('orderedList'),
            link: e.isActive('link'),
        }
    },
    run(name) {
        if (!this.editor) return
        const c = this.editor.chain().focus()
        const actions = {
            bold: () => c.toggleBold().run(),
            italic: () => c.toggleItalic().run(),
            h2: () => c.toggleHeading({ level: 2 }).run(),
            h3: () => c.toggleHeading({ level: 3 }).run(),
            bullet: () => c.toggleBulletList().run(),
            ordered: () => c.toggleOrderedList().run(),
            undo: () => c.undo().run(),
            redo: () => c.redo().run(),
        }
        actions[name]?.()
    },
    toggleLink() {
        if (!this.editor) return
        if (this.editor.isActive('link')) {
            this.editor.chain().focus().unsetLink().run()
            return
        }
        const url = window.prompt('URL du lien :')
        if (url) this.editor.chain().focus().setLink({ href: url }).run()
    },
    destroy() {
        this.editor?.destroy()
    },
})
