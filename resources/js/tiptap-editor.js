// =============================================================================
//  Module d'édition riche PORTABLE — cœur agnostique (aucune dépendance à
//  Livewire, Alpine ou Laravel). Copiable tel quel sur un autre projet : il
//  suffit de fournir un élément DOM + les callbacks (onUpdate, uploadImage).
//
//  Deux règles de survie (voir tikmos-tiptap-alpine-gotcha) :
//   1. Ne jamais stocker l'instance dans un état réactif (Alpine/Vue proxy) —
//      ProseMirror casse (« mismatched transaction »). Le wrapper la garde en
//      variable de clôture.
//   2. Tout TipTap est importé ICI (un seul chunk) → une seule copie de
//      ProseMirror.
// =============================================================================
import { Editor } from '@tiptap/core'
import StarterKit from '@tiptap/starter-kit'
import Image from '@tiptap/extension-image'
import TextAlign from '@tiptap/extension-text-align'
import { TableKit } from '@tiptap/extension-table'

/**
 * @param {object}   opts
 * @param {Element}  opts.element            Élément hôte de l'éditeur.
 * @param {string}   opts.content            HTML initial.
 * @param {function} opts.onUpdate           ({ editor }) => void — à chaque frappe.
 * @param {function} opts.onSelectionUpdate  () => void — changement de sélection.
 * @param {function=} opts.uploadImage       async (File) => string(url) — collage/glisser d'images.
 * @returns {Editor}
 */
export function createEditor({ element, content, onUpdate, onSelectionUpdate, uploadImage }) {
    return new Editor({
        element,
        extensions: [
            // StarterKit v3 : gras, italique, souligné, barré, code, bloc de code,
            // titres, citations, listes, filet, lien, annuler/rétablir.
            StarterKit.configure({
                heading: { levels: [2, 3] },
                link: {
                    openOnClick: false,
                    autolink: true,
                    HTMLAttributes: { rel: 'noopener noreferrer nofollow', target: '_blank' },
                },
            }),
            Image.configure({ inline: false, allowBase64: false, HTMLAttributes: { class: 'tk-img' } }),
            TextAlign.configure({ types: ['heading', 'paragraph'] }),
            TableKit.configure({ table: { resizable: true, HTMLAttributes: { class: 'tk-table' } } }),
        ],
        content: content || '',
        editorProps: {
            attributes: { class: 'prose-tk max-w-none min-h-[220px] focus:outline-none' },
            // Collage / glisser-déposer d'images → upload puis insertion de l'URL.
            handlePaste(view, event) {
                if (!uploadImage) return false
                const files = [...(event.clipboardData?.files || [])].filter((f) => f.type.startsWith('image/'))
                if (!files.length) return false
                event.preventDefault()
                files.forEach((file) => insertUploadedImage(view, file, uploadImage))
                return true
            },
            handleDrop(view, event) {
                if (!uploadImage) return false
                const files = [...(event.dataTransfer?.files || [])].filter((f) => f.type.startsWith('image/'))
                if (!files.length) return false
                event.preventDefault()
                files.forEach((file) => insertUploadedImage(view, file, uploadImage))
                return true
            },
        },
        onUpdate,
        onSelectionUpdate,
    })
}

async function insertUploadedImage(view, file, uploadImage) {
    try {
        const url = await uploadImage(file)
        if (!url) return
        const { schema } = view.state
        const node = schema.nodes.image.create({ src: url })
        view.dispatch(view.state.tr.replaceSelectionWith(node))
    } catch (e) {
        console.error('Upload image échoué', e)
    }
}
