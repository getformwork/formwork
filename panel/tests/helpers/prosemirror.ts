import { baseKeymap } from "prosemirror-commands";
import { history } from "prosemirror-history";
import { keymap } from "prosemirror-keymap";
import { defaultMarkdownParser, defaultMarkdownSerializer, schema } from "prosemirror-markdown";
import { EditorState, type Plugin, TextSelection } from "prosemirror-state";
import { EditorView } from "prosemirror-view";
import { buildInputRules } from "../../src/ts/components/editor/markdown-input-rules";
import { buildKeymap } from "../../src/ts/components/editor/markdown-keymap";

/**
 * Get the transaction removing the markers of an editor state and selecting between them
 */
function selectMarkers(state: EditorState, marker: string) {
    const markers: number[] = [];

    state.doc.descendants((node, position) => {
        if (node.isText) {
            for (const [index, char] of Array.from(node.text as string).entries()) {
                if (char === marker) {
                    markers.push(position + index);
                }
            }
        }
    });

    // Remove the markers from the last one, so that the positions of the others do not change
    let tr = state.tr;
    for (const position of [...markers].reverse()) {
        tr = tr.delete(position, position + 1);
    }
    if (markers.length > 0) {
        tr.setSelection(TextSelection.create(tr.doc, markers[0], markers.length > 1 ? markers[1] - 1 : markers[0]));
    }
    return tr.setMeta("addToHistory", false);
}

/**
 * Make an editor in the document with a Markdown content and the plugins of the panel
 *
 * The cursor is placed where the content has `|`, or a selection between two `|`
 */
export function makeView(markdown: string, plugins: Plugin[] = [history(), buildInputRules(schema), keymap(buildKeymap(schema)), keymap(baseKeymap)]) {
    // The marker is not parsed as Markdown
    const marker = "\u2063";
    const state = EditorState.create({ doc: defaultMarkdownParser.parse(markdown.replaceAll("|", marker)), plugins });

    const target = document.createElement("div");
    document.body.append(target);

    return new EditorView(target, { state: state.apply(selectMarkers(state, marker)) });
}

/**
 * Place the cursor of an existing editor where its content has `|`, or select between two `|`
 */
export function placeCursor(view: EditorView) {
    view.dispatch(selectMarkers(view.state, "|"));
}

/**
 * Get the content of an editor as Markdown
 */
export const markdown = (view: EditorView) => defaultMarkdownSerializer.serialize(view.state.doc);

/**
 * Type a text in an editor as the user does, applying the input rules
 */
export function type(view: EditorView, text: string) {
    for (const char of text) {
        const { from, to } = view.state.selection;
        const handled = view.someProp("handleTextInput", (handle) => handle(view, from, to, char, () => view.state.tr.insertText(char, from, to)));
        if (!handled) {
            view.dispatch(view.state.tr.insertText(char, from, to));
        }
    }
}

/**
 * Press a key in an editor, returning whether it was handled
 */
export function press(view: EditorView, key: string, init: KeyboardEventInit = {}) {
    const event = new KeyboardEvent("keydown", { key, bubbles: true, cancelable: true, ...init });
    return view.someProp("handleKeyDown", (handle) => handle(view, event)) ?? false;
}
