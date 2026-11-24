import { mock } from "node:test";
import { baseKeymap } from "prosemirror-commands";
import { history } from "prosemirror-history";
import { keymap } from "prosemirror-keymap";
import { EditorState, type Plugin, TextSelection } from "prosemirror-state";
import { tableEditing } from "prosemirror-tables";
import { EditorView } from "prosemirror-view";
import { buildInputRules } from "../../src/ts/components/editor/markdown-input-rules";
import { buildKeymap } from "../../src/ts/components/editor/markdown-keymap";
import { markdownParser, markdownSerializer, schema } from "../../src/ts/components/editor/markdown-schema";
import { buildTableGapKeymap, tableGap } from "../../src/ts/components/editor/markdown-table-gap";
import { tableSelection } from "../../src/ts/components/editor/markdown-table-selection";
import { buildTableKeymap, normalizeTables } from "../../src/ts/components/editor/markdown-tables";
import { MarkdownView } from "../../src/ts/components/editor/markdown-view";
import { Toolbar } from "../../src/ts/components/editor/toolbar";

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
 *
 * @param cursor Character marking the cursor in place of `|`, which is part of the tables
 */
export function makeView(markdown: string, plugins: Plugin[] = [history(), buildInputRules(schema), keymap(buildKeymap(schema)), keymap(baseKeymap)], cursor = "|") {
    // The marker is not parsed as Markdown
    const marker = "\u2063";
    const state = EditorState.create({ doc: markdownParser.parse(markdown.replaceAll(cursor, marker)), plugins });

    const target = document.createElement("div");
    document.body.append(target);

    return new EditorView(target, { state: state.apply(selectMarkers(state, marker)) });
}

/**
 * Make an editor with the plugins of the panel for the tables, placing the cursor at `^`
 */
export function makeTableView(markdown: string) {
    const plugins = [history(), tableSelection(), keymap(buildTableGapKeymap()), keymap(buildTableKeymap()), keymap(buildKeymap(schema)), keymap(baseKeymap), normalizeTables(), tableGap(), tableEditing()];
    return makeView(markdown, plugins, "^");
}

/**
 * Make a Markdown editor with its toolbar and the handles of the tables, as the editor input does,
 * placing the cursor at `^` and focusing it
 */
export function makeTableEditor(content: string) {
    document.body.innerHTML = '<div class="editor-toolbar"></div><div class="editor-wrap"></div>';
    const toolbar = new Toolbar(document.querySelector(".editor-toolbar") as HTMLElement);
    const editor = new MarkdownView("content", document.querySelector(".editor-wrap") as HTMLElement, content, mock.fn(), { toolbar });
    editor.view.focus();
    placeCursor(editor.view, "^");
    return editor;
}

/**
 * Get the positions of the cells of the first table of an editor
 */
export function cellPositions(view: EditorView) {
    const positions: number[] = [];
    view.state.doc.descendants((node, pos) => {
        if (node.type.spec.tableRole === "cell" || node.type.spec.tableRole === "header_cell") {
            positions.push(pos);
        }
        return !node.isTextblock;
    });
    return positions;
}

/**
 * Place the cursor of an existing editor where its content has `|`, or select between two `|`
 *
 * @param cursor Character marking the cursor in place of `|`, which is part of the tables
 */
export function placeCursor(view: EditorView, cursor = "|") {
    view.dispatch(selectMarkers(view.state, cursor));
}

/**
 * Get the content of an editor as Markdown
 */
export const markdown = (view: EditorView) => markdownSerializer.serialize(view.state.doc);

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
