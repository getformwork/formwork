import type { Node, ResolvedPos } from "prosemirror-model";
import { Slice } from "prosemirror-model";
import type { Command, EditorState, Transaction } from "prosemirror-state";
import { Plugin, Selection, TextSelection } from "prosemirror-state";
import { Decoration, DecorationSet, type EditorView } from "prosemirror-view";

/**
 * Mapping of positions, as taken by the selections
 */
type Mappable = Parameters<Selection["map"]>[1];

/**
 * Return whether a node is a table
 */
function isTable(node: Node | null | undefined): node is Node {
    return node?.type.spec.tableRole === "table";
}

/**
 * Side of a table gap: `before` the table after it, or `after` the table before it
 *
 * Between two tables the position is the same for both sides, which tell the table of the gap
 */
export type TableGapSide = "before" | "after";

/**
 * Return whether a position has a table on a side, or on any side when none is given
 */
function hasTable($pos: ResolvedPos, side?: TableGapSide) {
    if ($pos.parent.isTextblock) {
        return false;
    }
    const before = isTable($pos.nodeBefore);
    const after = isTable($pos.nodeAfter);
    return side === "before" ? after : side === "after" ? before : before || after;
}

/**
 * Get a table gap at a position, on the given side if there is a table there, or on the other one
 */
function gapAt($pos: ResolvedPos, side?: TableGapSide): Selection {
    if (side && hasTable($pos, side)) {
        return new TableGap($pos, side);
    }
    return hasTable($pos) ? new TableGap($pos) : Selection.near($pos);
}

/**
 * Cursor right before or right after a table, drawn as tall as the table
 *
 * Backspace after the table and Delete before it remove the table
 */
export class TableGap extends Selection {
    /**
     * Side of the table of the gap
     */
    readonly side: TableGapSide;

    /**
     * @param side Side of the gap, after the table before it by default
     */
    constructor($pos: ResolvedPos, side?: TableGapSide) {
        super($pos, $pos);
        this.side = side ?? (isTable($pos.nodeBefore) ? "after" : "before");
    }

    /**
     * Return whether a position is next to a table
     */
    static valid($pos: ResolvedPos) {
        return hasTable($pos);
    }

    /**
     * Table of the gap, with its position
     */
    get table(): { node: Node; pos: number } {
        const { $head } = this;
        return this.side === "after" ? { node: $head.nodeBefore as Node, pos: $head.pos - ($head.nodeBefore as Node).nodeSize } : { node: $head.nodeAfter as Node, pos: $head.pos };
    }

    map(doc: Node, mapping: Mappable): Selection {
        return gapAt(doc.resolve(mapping.map(this.head)), this.side);
    }

    content() {
        return Slice.empty;
    }

    eq(other: Selection): boolean {
        return other instanceof TableGap && other.head === this.head && other.side === this.side;
    }

    toJSON() {
        return { type: "tablegap", pos: this.head, side: this.side };
    }

    static fromJSON(doc: Node, json: { pos: number; side?: TableGapSide }) {
        return new TableGap(doc.resolve(json.pos), json.side);
    }

    getBookmark() {
        return new TableGapBookmark(this.head, this.side);
    }
}

// The cursor is drawn by the table, not by the browser
TableGap.prototype.visible = false;

Selection.jsonID("tablegap", TableGap);

/**
 * Keeps the position of a table gap in the history
 */
class TableGapBookmark {
    readonly pos: number;

    readonly side: TableGapSide;

    constructor(pos: number, side: TableGapSide) {
        this.pos = pos;
        this.side = side;
    }

    map(mapping: Mappable) {
        return new TableGapBookmark(mapping.map(this.pos), this.side);
    }

    resolve(doc: Node) {
        return gapAt(doc.resolve(this.pos), this.side);
    }
}

/**
 * Remove the table at a position, placing the cursor before or after where it was
 */
export function removeTable(tr: Transaction, pos: number, table: Node, direction: -1 | 1) {
    tr.delete(pos, pos + table.nodeSize);
    const $pos = tr.doc.resolve(Math.min(pos, tr.doc.content.size));
    return tr.setSelection(TableGap.valid($pos) ? new TableGap($pos) : Selection.near($pos, direction)).scrollIntoView();
}

/**
 * Get the cursor of the selection if it is at the start (`-1`) or at the end (`1`) of a textblock
 */
function cursorAtEdge(state: EditorState, direction: -1 | 1) {
    const { $cursor } = state.selection as TextSelection;
    if (!$cursor) {
        return null;
    }
    return (direction < 0 ? $cursor.parentOffset === 0 : $cursor.parentOffset === $cursor.parent.content.size) ? $cursor : null;
}

/**
 * Move to a table gap, or remove the table next to it
 *
 * - With the cursor in the gap on the side of the direction of the table, the table is removed
 * - With the cursor in the gap on the other side, the cursor leaves the gap
 * - With the cursor at the edge of a textblock next to a table, the cursor moves to the gap, and an
 *   empty textblock is removed
 *
 * @param direction `-1` for Backspace, `1` for Delete
 */
function deleteTowardsTable(direction: -1 | 1): Command {
    return (state, dispatch) => {
        const { selection } = state;

        if (selection instanceof TableGap) {
            const { $head } = selection;
            const table = direction < 0 ? $head.nodeBefore : $head.nodeAfter;

            if (isTable(table)) {
                dispatch?.(removeTable(state.tr, direction < 0 ? $head.pos - table.nodeSize : $head.pos, table, direction));
                return true;
            }

            const target = Selection.findFrom($head, direction, true);
            if (target) {
                dispatch?.(state.tr.setSelection(target).scrollIntoView());
            }
            return true;
        }

        const $cursor = cursorAtEdge(state, direction);

        if (!$cursor) {
            return false;
        }

        const before = $cursor.before();
        const after = $cursor.after();
        const $edge = state.doc.resolve(direction < 0 ? before : after);

        if (!isTable(direction < 0 ? $edge.nodeBefore : $edge.nodeAfter)) {
            return false;
        }

        if (dispatch) {
            const tr = state.tr;
            let gap = $edge.pos;

            if ($cursor.parent.content.size === 0) {
                tr.delete(before, after);
                gap = before;
            }

            dispatch(tr.setSelection(new TableGap(tr.doc.resolve(gap), direction < 0 ? "after" : "before")).scrollIntoView());
        }

        return true;
    };
}

/**
 * Move the cursor across a table gap with the arrows
 *
 * The gap is reached from the textblocks next to the table and from its first and last cells
 *
 * @param direction `-1` for left and up, `1` for right and down
 * @param vertical  Whether the arrow is up or down, which reach the gap only from inside it
 */
function arrow(direction: -1 | 1, vertical = false): Command {
    return (state, dispatch) => {
        const { selection } = state;

        if (selection instanceof TableGap) {
            const { $head, side } = selection;

            // Between two tables, the cursor goes from one table to the other first
            if (side === "after" && direction > 0 && isTable($head.nodeAfter)) {
                dispatch?.(state.tr.setSelection(new TableGap($head, "before")).scrollIntoView());
                return true;
            }
            if (side === "before" && direction < 0 && isTable($head.nodeBefore)) {
                dispatch?.(state.tr.setSelection(new TableGap($head, "after")).scrollIntoView());
                return true;
            }

            // Entering a table, the cursor goes to its first or last cell
            const target = Selection.findFrom($head, direction, true);
            if (target) {
                dispatch?.(state.tr.setSelection(target).scrollIntoView());
            }
            return true;
        }

        if (vertical) {
            return false;
        }

        const $cursor = cursorAtEdge(state, direction);

        if (!$cursor) {
            return false;
        }

        // The textblock next to a table
        const $edge = state.doc.resolve(direction < 0 ? $cursor.before() : $cursor.after());
        if (isTable(direction < 0 ? $edge.nodeBefore : $edge.nodeAfter)) {
            dispatch?.(state.tr.setSelection(new TableGap($edge, direction < 0 ? "after" : "before")).scrollIntoView());
            return true;
        }

        // The first or the last cell of a table
        if ($cursor.depth >= 3 && isTable($cursor.node(-3))) {
            const table = $cursor.node(-3);
            const row = $cursor.node(-2);
            const first = $cursor.index(-3) === 0 && $cursor.index(-2) === 0;
            const last = $cursor.index(-3) === table.childCount - 1 && $cursor.index(-2) === row.childCount - 1;

            if ((direction < 0 && first) || (direction > 0 && last)) {
                const pos = direction < 0 ? $cursor.before(-3) : $cursor.after(-3);
                dispatch?.(state.tr.setSelection(new TableGap(state.doc.resolve(pos), direction < 0 ? "before" : "after")).scrollIntoView());
                return true;
            }
        }

        return false;
    };
}

/**
 * Add an empty paragraph at a table gap: before the table the cursor stays in the gap, after the
 * table it moves to the paragraph, also between two tables
 */
const splitAtGap: Command = (state, dispatch) => {
    const { selection } = state;

    if (!(selection instanceof TableGap)) {
        return false;
    }

    if (dispatch) {
        const paragraph = state.schema.nodes.paragraph.create();
        const pos = selection.head;
        const tr = state.tr.insert(pos, paragraph);

        if (selection.side === "before") {
            tr.setSelection(new TableGap(tr.doc.resolve(pos + paragraph.nodeSize), "before"));
        } else {
            tr.setSelection(TextSelection.create(tr.doc, pos + 1));
        }

        dispatch(tr.scrollIntoView());
    }

    return true;
};

/**
 * Get the keys moving to the table gaps and removing the tables from them
 */
export function buildTableGapKeymap(): Record<string, Command> {
    return {
        Backspace: deleteTowardsTable(-1),
        Delete: deleteTowardsTable(1),
        ArrowLeft: arrow(-1),
        ArrowRight: arrow(1),
        ArrowUp: arrow(-1, true),
        ArrowDown: arrow(1, true),
        Enter: splitAtGap,
    };
}

/**
 * Get the table gap at a point beside a table, if any
 */
function gapAtCoords(view: EditorView, x: number, y: number) {
    // The callback assigns it, which the type narrowing does not see
    let gap = null as { pos: number; side: TableGapSide } | null;

    view.state.doc.descendants((node, pos) => {
        if (gap !== null || node.isTextblock) {
            return false;
        }

        if (!isTable(node)) {
            return true;
        }

        const rect = (view.nodeDOM(pos) as HTMLElement | null)?.getBoundingClientRect();

        if (rect && y >= rect.top && y <= rect.bottom) {
            if (x < rect.left) {
                gap = { pos, side: "before" };
            } else if (x > rect.right) {
                gap = { pos: pos + node.nodeSize, side: "after" };
            }
        }

        return false;
    });

    return gap;
}

/**
 * Get the plugin drawing the table gaps and placing them where the editor is clicked beside a table
 */
export function tableGap() {
    return new Plugin({
        props: {
            decorations(state) {
                const { selection } = state;

                if (!(selection instanceof TableGap)) {
                    return null;
                }

                const { node, pos } = selection.table;
                const decoration = Decoration.node(pos, pos + node.nodeSize, { class: `table-gap-${selection.side}` });

                return DecorationSet.create(state.doc, [decoration]);
            },

            handleDOMEvents: {
                mousedown(view, event) {
                    if (event.button !== 0 || !view.editable) {
                        return false;
                    }

                    const gap = gapAtCoords(view, event.clientX, event.clientY);

                    if (gap === null) {
                        return false;
                    }

                    event.preventDefault();
                    view.focus();
                    view.dispatch(view.state.tr.setSelection(new TableGap(view.state.doc.resolve(gap.pos), gap.side)));
                    return true;
                },
            },
        },
    });
}
