import type { EditorState } from "prosemirror-state";
import { Plugin } from "prosemirror-state";
import { Decoration, DecorationSet } from "prosemirror-view";

/**
 * Get the plugin that shows a placeholder when the document is empty
 *
 * @param text Text of the placeholder
 */
export function placeholderPlugin(text: string) {
    if (!text) {
        return new Plugin({});
    }

    return new Plugin({
        props: {
            decorations(state: EditorState) {
                const isEmpty = state.doc.childCount === 1 && state.doc?.firstChild?.isTextblock && state.doc?.firstChild.content.size === 0;

                if (!isEmpty) {
                    return null;
                }

                const placeholder = document.createElement("span");
                placeholder.className = "pm-placeholder";
                placeholder.textContent = text;

                return DecorationSet.create(state.doc, [Decoration.widget(1, placeholder, { side: 1 })]);
            },
        },
    });
}
