import { chainCommands, lift } from "prosemirror-commands";
import type { Node, NodeType } from "prosemirror-model";
import { liftListItem, wrapInList } from "prosemirror-schema-list";
import type { Command, EditorState, Transaction } from "prosemirror-state";
import { Selection } from "prosemirror-state";
import { isInTable } from "prosemirror-tables";
import { schema } from "./markdown-schema";
import { atTableGap, liftTable, wrapTableInList } from "./markdown-tables";

/**
 * Get the nearest list around the start of the selection, with its position
 */
export function findList(state: EditorState): { node: Node; pos: number } | null {
    const { $from } = state.selection;

    for (let depth = $from.depth; depth > 0; depth--) {
        const node = $from.node(depth);
        if (node.type === schema.nodes.bullet_list || node.type === schema.nodes.ordered_list) {
            return { node, pos: $from.before(depth) };
        }
    }

    return null;
}

/**
 * Return whether the selection is in a list of a type, the nearest one around it
 */
export function isInList(state: EditorState, listType: NodeType) {
    return findList(state)?.node.type === listType;
}

/**
 * Run a command lifting the selection until it is out of all the lists, as a single change
 */
function liftOutOfLists(command: Command): Command {
    return (state, dispatch) => {
        if (!findList(state) || !command(state)) {
            return false;
        }

        if (dispatch) {
            const tr = state.tr;
            let current = state;

            // Each run lifts a level, the limit guards against a command that does not change it
            for (let level = 0; level < 100 && findList(current); level++) {
                let applied = null as Transaction | null;
                const lifted = command(current, (transaction) => {
                    applied = transaction;
                });
                if (!lifted || !applied) {
                    break;
                }
                for (const step of applied.steps) {
                    tr.step(step);
                }
                current = current.apply(applied);
            }

            dispatch(tr.setSelection(Selection.fromJSON(tr.doc, current.selection.toJSON())).scrollIntoView());
        }

        return true;
    };
}

/**
 * Run a command only out of the cells of the tables, which cannot be lifted or wrapped
 *
 * The tables are lifted and wrapped whole with the cursor before or after them
 */
function outOfCells(command: Command): Command {
    return (state, dispatch, view) => !isInTable(state) && command(state, dispatch, view);
}

/**
 * Toggle a list type, as the buttons of the lists do
 *
 * - Out of a list, the selection is wrapped in a list of the type
 * - In a list of the type, the list is removed: its items become blocks out of all the lists
 * - In a list of another type, the whole list changes to the type
 *
 * A table with the cursor before or after it is wrapped and lifted whole
 *
 * @param listType Type of the list
 */
export function toggleList(listType: NodeType): Command {
    const wrap = atTableGap(wrapTableInList(listType), wrapInList(listType));
    const remove = liftOutOfLists(atTableGap(liftTable, liftListItem(schema.nodes.list_item)));

    return outOfCells((state, dispatch, view) => {
        const list = findList(state);

        if (!list) {
            return wrap(state, dispatch, view);
        }

        if (list.node.type === listType) {
            return remove(state, dispatch, view);
        }

        dispatch?.(state.tr.setNodeMarkup(list.pos, listType, { tight: list.node.attrs.tight }).scrollIntoView());
        return true;
    });
}

/**
 * Decrease the indent: a list item goes up a level, becoming an item of the parent list or a block
 * out of the list, a table with the cursor before or after it is lifted whole, and the other blocks
 * are lifted out of their parent
 */
export const decreaseIndent: Command = outOfCells(atTableGap(liftTable, chainCommands(liftListItem(schema.nodes.list_item), lift)));
