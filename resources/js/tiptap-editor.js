// Module unique d'édition riche — TOUT TipTap/ProseMirror est importé ici, de sorte
// que Vite le bundle dans UN SEUL chunk. Auparavant, trois import() dynamiques séparés
// dupliquaient prosemirror-model dans deux chunks → deux classes NodeType → l'erreur
// « Applying a mismatched transaction » à chaque frappe. Un seul point d'entrée = une
// seule copie de ProseMirror = éditeur fonctionnel.
import { Editor } from '@tiptap/core'
import StarterKit from '@tiptap/starter-kit'

export function createEditor({ element, content, onUpdate, onSelectionUpdate }) {
    return new Editor({
        element,
        extensions: [
            // StarterKit v3 inclut déjà : gras, italique, souligné, barré, code,
            // bloc de code, titres, citations, listes, filet, lien, annuler/rétablir.
            StarterKit.configure({
                heading: { levels: [2, 3] },
                link: {
                    openOnClick: false,
                    autolink: true,
                    HTMLAttributes: { rel: 'noopener noreferrer nofollow', target: '_blank' },
                },
            }),
        ],
        content: content || '',
        editorProps: {
            attributes: { class: 'prose-tk max-w-none min-h-[200px] focus:outline-none' },
        },
        onUpdate,
        onSelectionUpdate,
    })
}
