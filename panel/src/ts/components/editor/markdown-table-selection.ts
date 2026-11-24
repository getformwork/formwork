import type { Node, ResolvedPos } from "prosemirror-model";
import type { EditorState, Transaction } from "prosemirror-state";
import { Plugin, Selection, TextSelection } from "prosemirror-state";
import { CellSelection } from "prosemirror-tables";
import { Decoration, DecorationSet, type EditorView } from "prosemirror-view";
import { schema } from "./markdown-schema";

/**
 * Get the text position beyond a table in a direction, or at the far edge of its text when there
 * is no text beyond it
 *
 * @param before    Position before the table
 * @param after     Position after the table
 * @param direction `1` to go past the end of the table, `-1` past its start
 */
function beyondTable(doc: Node, before: number, after: number, direction: -1 | 1): ResolvedPos | null {
    const $edge = doc.resolve(direction > 0 ? after : before);
    const target = Selection.findFrom($edge, direction, true) ?? Selection.findFrom($edge, -direction as -1 | 1, true);
    return target?.$head ?? null;
}

/**
 * Move an end of a text selection out of a table that the other end is not in, past the table on
 * the side away from the other end, so that the selection covers the whole table
 *
 * The ends are kept out of the tables, where the table editing plugin would reduce the selection
 * to a single cell
 *
 * @param $pos   End of the selection to move
 * @param $other Other end of the selection
 */
function moveOutOfTable($pos: ResolvedPos, $other: ResolvedPos): ResolvedPos {
    for (let depth = 1; depth <= $pos.depth; depth++) {
        const before = $pos.before(depth);
        const shared = $other.depth >= depth && $other.before(depth) === before;

        if ($pos.node(depth).type !== schema.nodes.table || shared) {
            continue;
        }

        const $target = beyondTable($pos.doc, before, $pos.after(depth), $other.pos < before ? 1 : -1);
        return $target && !$target.parent.eq($pos.parent) ? $target : $pos;
    }

    return $pos;
}

/**
 * Return whether a selection covers a table, starting or ending outside it
 */
function selectsTable(state: EditorState) {
    const { selection } = state;

    if (selection.empty || selection instanceof CellSelection) {
        return false;
    }

    let found = false;

    state.doc.nodesBetween(selection.from, selection.to, (node, pos) => {
        found ||= node.type === schema.nodes.table && (selection.from < pos || selection.to > pos + node.nodeSize);
        return !found && !node.isTextblock;
    });

    return found;
}

/**
 * Remove a selection covering a table without joining the blocks around it: an end of the
 * selection at the edge of a block next to a table leaves the block out
 */
function deleteSelectedTable(tr: Transaction) {
    const { $from, $to } = tr.selection;
    const isTable = (node: Node | null | undefined) => node?.type === schema.nodes.table;

    const from = $from.parent.isTextblock && $from.parentOffset === $from.parent.content.size && isTable(tr.doc.resolve($from.after()).nodeAfter) ? $from.after() : $from.pos;
    const to = $to.parent.isTextblock && $to.parentOffset === 0 && isTable(tr.doc.resolve($to.before()).nodeBefore) ? $to.before() : $to.pos;

    tr.delete(from, to);

    // The cursor goes where the removed text was, or past the removed tables
    return tr.setSelection(Selection.near(tr.doc.resolve(tr.mapping.map(from)), from === $from.pos ? -1 : 1));
}

/**
 * Sides of the textblocks and directions of the arrows
 */
const arrows: Record<string, ["up" | "down" | "left" | "right", -1 | 1]> = {
    ArrowUp: ["up", -1],
    ArrowDown: ["down", 1],
    ArrowLeft: ["left", -1],
    ArrowRight: ["right", 1],
};

/**
 * Get the selection moving the head of a text selection over the table next to it, with Shift and
 * an arrow at the edge of a block before or after a table
 */
function selectOverTable(view: EditorView, key: string): TextSelection | null {
    const { selection, doc } = view.state;

    if (!(key in arrows) || !(selection instanceof TextSelection) || !selection.$head.parent.isTextblock) {
        return null;
    }

    const [side, direction] = arrows[key];
    const { $head } = selection;
    const $boundary = doc.resolve(direction > 0 ? $head.after() : $head.before());
    const table = direction > 0 ? $boundary.nodeAfter : $boundary.nodeBefore;

    if (table?.type !== schema.nodes.table || !view.endOfTextblock(side)) {
        return null;
    }

    const before = direction > 0 ? $boundary.pos : $boundary.pos - table.nodeSize;
    const $target = beyondTable(doc, before, before + table.nodeSize, direction);

    return $target ? new TextSelection(selection.$anchor, moveOutOfTable($target, selection.$anchor)) : null;
}

/**
 * Get the plugin selecting the tables as whole blocks: text selections entering a table are
 * extended over all of it, Shift and the arrows go over the tables, the tables in a selection are
 * shown as selected, and removing, typing or pasting over them replaces them whole
 */
export function tableSelection() {
    return new Plugin({
        appendTransaction(_transactions, _oldState, state) {
            const { selection } = state;

            if (!(selection instanceof TextSelection) || selection.empty) {
                return null;
            }

            let { $anchor, $head } = selection;

            // Moving an end past a table may enter the next table
            for (let moved = true; moved; ) {
                const $newAnchor = moveOutOfTable($anchor, $head);
                const $newHead = moveOutOfTable($head, $newAnchor);
                moved = $newAnchor.pos !== $anchor.pos || $newHead.pos !== $head.pos;
                $anchor = $newAnchor;
                $head = $newHead;
            }

            if ($anchor.pos === selection.anchor && $head.pos === selection.head) {
                return null;
            }

            return state.tr.setSelection(new TextSelection($anchor, $head));
        },

        props: {
            decorations(state) {
                const { selection, doc } = state;

                if (selection.empty || selection instanceof CellSelection) {
                    return null;
                }

                const { from, to } = selection;
                const decorations: Decoration[] = [];

                doc.nodesBetween(from, to, (node, pos) => {
                    if (node.type !== schema.nodes.table) {
                        return !node.isTextblock;
                    }

                    // The text of the table is all in the selection, which goes beyond the table
                    const end = pos + node.nodeSize;
                    const first = Selection.findFrom(doc.resolve(pos), 1, true)?.from ?? pos;
                    const last = Selection.findFrom(doc.resolve(end), -1, true)?.from ?? pos;

                    if ((from < pos || to > end) && from <= first && to >= last) {
                        decorations.push(Decoration.node(pos, end, { class: "table-selected" }));
                    }

                    return false;
                });

                return DecorationSet.create(doc, decorations);
            },

            handleKeyDown(view, event) {
                if (event.ctrlKey || event.metaKey || event.altKey) {
                    return false;
                }

                const selection = event.shiftKey ? selectOverTable(view, event.key) : null;

                if (selection) {
                    view.dispatch(view.state.tr.setSelection(selection).scrollIntoView());
                    return true;
                }

                if (!event.shiftKey && (event.key === "Backspace" || event.key === "Delete") && selectsTable(view.state)) {
                    view.dispatch(deleteSelectedTable(view.state.tr).scrollIntoView());
                    return true;
                }

                return false;
            },

            handleDOMEvents: {
                // The text is inserted before the browser changes the document, whose selection may
                // not cover the whole table yet right after it is extended with the mouse
                beforeinput(view, event) {
                    const { inputType, data, isComposing } = event as InputEvent;

                    if (inputType !== "insertText" || !data || isComposing || !selectsTable(view.state)) {
                        return false;
                    }

                    event.preventDefault();
                    view.dispatch(deleteSelectedTable(view.state.tr).insertText(data).scrollIntoView());
                    return true;
                },
            },

            handlePaste(view, _event, slice) {
                if (!selectsTable(view.state)) {
                    return false;
                }

                view.dispatch(deleteSelectedTable(view.state.tr).replaceSelection(slice).scrollIntoView());
                return true;
            },
        },
    });
}
