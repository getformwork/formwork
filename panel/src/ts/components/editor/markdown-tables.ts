import { chainCommands } from "prosemirror-commands";
import type { Node, NodeRange, NodeType, ResolvedPos } from "prosemirror-model";
import type { Command, EditorState, Transaction } from "prosemirror-state";
import { Plugin, TextSelection } from "prosemirror-state";
import { addColumnAfter, addColumnBefore, addRowAfter, addRowBefore, CellSelection, deleteCellSelection, deleteColumn, deleteRow, goToNextCell, isInTable, selectedRect, selectionCell, TableMap, type TableRect } from "prosemirror-tables";
import { findWrapping, liftTarget } from "prosemirror-transform";
import { canInsert } from "./markdown-commands";
import { type Alignment, schema } from "./markdown-schema";
import { removeTable, TableGap } from "./markdown-table-gap";

/**
 * Rows or columns of a table
 */
export type Axis = "row" | "column";

/**
 * Get the cell where the selection ends
 */
export function getHeadCell(state: EditorState) {
    return state.selection instanceof CellSelection ? state.selection.$headCell : selectionCell(state);
}

/**
 * Get the selection of the row or the column of a cell
 */
export function selectAxis($cell: ResolvedPos, axis: Axis) {
    return axis === "row" ? CellSelection.rowSelection($cell) : CellSelection.colSelection($cell);
}

/**
 * Return whether a cell is empty, as the paragraph it holds
 */
function isEmptyCell(cell: Node) {
    return (cell.firstChild?.content.size ?? 0) === 0;
}

/**
 * Remove the table of a cell
 */
function removeTableOf(tr: Transaction, $cell: ResolvedPos) {
    return removeTable(tr, $cell.before(-1), $cell.node(-1), 1);
}

/**
 * Place the cursor in a cell of a table, given by its row and column
 */
function enterCell(tr: Transaction, tableStart: number, row: number, column: number) {
    const map = TableMap.get(tr.doc.nodeAt(tableStart - 1) as Node);
    const cellPos = tableStart + map.map[row * map.width + column];
    return tr.setSelection(TextSelection.near(tr.doc.resolve(cellPos + 1))).scrollIntoView();
}

/**
 * Insert a table with a header row and a body row of two columns, with the cursor in the first cell
 */
export const insertTable: Command = (state, dispatch) => {
    if (isInTable(state) || !canInsert(state, schema.nodes.table)) {
        return false;
    }

    if (dispatch) {
        const { table, table_row, table_header, table_cell } = schema.nodes;
        const row = (cellType: NodeType) => table_row.create(null, [cellType.createAndFill() as Node, cellType.createAndFill() as Node]);
        const node = table.create(null, [row(table_header), row(table_cell)]);

        const tr = state.tr.replaceSelectionWith(node);

        let position = -1;

        tr.doc.descendants((child, pos) => {
            if (child === node) {
                position = pos;
            }
            return position < 0 && !child.isTextblock;
        });

        if (position >= 0) {
            // A paragraph after the table lets the cursor leave it
            const after = position + node.nodeSize;
            if (!tr.doc.resolve(after).nodeAfter) {
                tr.insert(after, schema.nodes.paragraph.create());
            }
            // Positions of table, row, cell and paragraph are entered to reach the text
            tr.setSelection(TextSelection.create(tr.doc, position + 4));
        }

        dispatch(tr.scrollIntoView());
    }

    return true;
};

/**
 * Move the cursor after the table, adding a paragraph if nothing follows it
 */
export const exitTable: Command = (state, dispatch) => {
    if (!isInTable(state)) {
        return false;
    }

    if (dispatch) {
        const after = selectionCell(state).after(-1);
        const tr = state.tr;

        if (!tr.doc.resolve(after).nodeAfter?.isTextblock) {
            tr.insert(after, schema.nodes.paragraph.create());
        }

        dispatch(tr.setSelection(TextSelection.create(tr.doc, after + 1)).scrollIntoView());
    }

    return true;
};

/**
 * Move the cursor to the cell above or below, leaving the table after its last row
 */
function goToVerticalCell(direction: -1 | 1): Command {
    return (state, dispatch) => {
        if (!isInTable(state)) {
            return false;
        }

        // A selection of cells is reduced to the cursor in its last cell
        if (state.selection instanceof CellSelection) {
            const $head = state.selection.$headCell;
            dispatch?.(state.tr.setSelection(TextSelection.near(state.doc.resolve($head.pos + 1))));
            return true;
        }

        const $cell = selectionCell(state);
        const tableStart = $cell.start(-1);
        const map = TableMap.get($cell.node(-1));
        const { top, left } = map.findCell($cell.pos - tableStart);
        const row = top + direction;

        if (row >= map.height) {
            return exitTable(state, dispatch);
        }

        if (row >= 0) {
            dispatch?.(enterCell(state.tr, tableStart, row, left));
        }

        return true;
    };
}

/**
 * Move to the next cell, adding a row after the last one
 */
const goToNextCellOrAddRow: Command = (state, dispatch, view) => {
    if (goToNextCell(1)(state, dispatch, view)) {
        return true;
    }

    if (!isInTable(state) || !dispatch || !view) {
        return isInTable(state);
    }

    addRowAfter(state, dispatch);
    return goToNextCell(1)(view.state, dispatch, view);
};

/**
 * Change the structure around the table of the cursor before or after it, keeping the cursor on
 * the same side of the table
 *
 * @param change Function changing the range of the table, returning whether it can
 */
function changeAroundTable(change: (tr: Transaction, range: NodeRange) => boolean): Command {
    return (state, dispatch) => {
        const { selection, doc } = state;

        if (!(selection instanceof TableGap)) {
            return false;
        }

        const { node, pos } = selection.table;
        const range = doc.resolve(pos).blockRange(doc.resolve(pos + node.nodeSize));
        const tr = state.tr;

        if (!range || !change(tr, range)) {
            return false;
        }

        if (dispatch) {
            const start = tr.mapping.map(pos, 1);
            const gap = selection.side === "before" ? start : start + node.nodeSize;
            dispatch(tr.setSelection(new TableGap(tr.doc.resolve(gap), selection.side)).scrollIntoView());
        }

        return true;
    };
}

/**
 * Run a command for the tables with the cursor before or after a table, and another one otherwise
 *
 * The other command does not run in the gap, where it would change the blocks around the table
 *
 * @param tableCommand Command for the tables
 * @param command      Command for the rest, none by default
 */
export function atTableGap(tableCommand: Command, command?: Command): Command {
    return (state, dispatch, view) => (state.selection instanceof TableGap ? tableCommand(state, dispatch, view) : (command?.(state, dispatch, view) ?? false));
}

/**
 * Lift the table next to the cursor before or after it out of its parent, like a list item
 *
 * The cells cannot be lifted out of the table, so only the whole table is
 */
export const liftTable = changeAroundTable((tr, range) => {
    const target = liftTarget(range);
    if (target === null) {
        return false;
    }
    tr.lift(range, target);
    return true;
});

/**
 * Wrap the table next to the cursor before or after it in a list
 *
 * A table already in a list item is not wrapped again, like the paragraphs at the start of an item
 *
 * @param listType Type of the list
 */
export function wrapTableInList(listType: NodeType): Command {
    return changeAroundTable((tr, range) => {
        const wrapping = range.parent.type === schema.nodes.list_item ? null : findWrapping(range, listType);
        if (!wrapping) {
            return false;
        }
        tr.wrap(range, wrapping);
        return true;
    });
}

/**
 * Run a command changing the rows or the columns, then place the cursor in a cell
 *
 * @param command Command changing the table
 * @param cell    Function getting the row and the column of the cell from the rectangle of the
 *                selection before the change and the map of the table after it
 */
function thenEnterCell(command: Command, cell: (rect: TableRect, map: TableMap) => [number, number]): Command {
    return (state, dispatch) => {
        if (!isInTable(state)) {
            return false;
        }

        if (!dispatch) {
            return command(state);
        }

        const rect = selectedRect(state);
        let tr = null as Transaction | null;

        const changed = command(state, (transaction) => {
            tr = transaction;
        });

        if (!changed || !tr) {
            return false;
        }

        const [row, column] = cell(rect, TableMap.get(tr.doc.nodeAt(rect.tableStart - 1) as Node));
        dispatch(enterCell(tr, rect.tableStart, row, column));

        return true;
    };
}

/**
 * Add a row or a column moving the cursor into it, in the column or the row of the selection
 */
export const addRowAbove = thenEnterCell(addRowBefore, (rect) => [rect.top, rect.left]);
export const addRowBelow = thenEnterCell(addRowAfter, (rect) => [rect.bottom, rect.left]);
export const addColumnLeft = thenEnterCell(addColumnBefore, (rect) => [rect.top, rect.left]);
export const addColumnRight = thenEnterCell(addColumnAfter, (rect) => [rect.top, rect.right]);

/**
 * Return whether the selected rows or columns, or the row or the column of the cursor, are all
 * those of the table
 */
export function deletesAll(state: EditorState, axis: Axis) {
    if (!isInTable(state)) {
        return false;
    }
    const { top, bottom, left, right, map } = selectedRect(state);
    return axis === "row" ? top === 0 && bottom === map.height : left === 0 && right === map.width;
}

/**
 * Delete the selected rows or columns keeping the cursor in the table, in the row or the column
 * that takes their place
 *
 * The last row and the last column are not deleted, as the commands tell only when they are run
 */
function deleteAndStay(axis: Axis): Command {
    const command = thenEnterCell(axis === "row" ? deleteRow : deleteColumn, (rect, map) => [Math.min(rect.top, map.height - 1), Math.min(rect.left, map.width - 1)]);
    return (state, dispatch, view) => !deletesAll(state, axis) && command(state, dispatch, view);
}

export const deleteRows = deleteAndStay("row");
export const deleteColumns = deleteAndStay("column");

/**
 * Delete the table of the cursor
 */
export const deleteWholeTable: Command = (state, dispatch) => {
    if (!isInTable(state)) {
        return false;
    }
    dispatch?.(removeTableOf(state.tr, selectionCell(state)));
    return true;
};

/**
 * Get the header cells of the selected columns, or of the column of the cursor, with their positions
 */
function selectedHeaderCells(state: EditorState) {
    const { left, right, map, table, tableStart } = selectedRect(state);
    const cells: { node: Node; pos: number }[] = [];

    for (let column = left; column < right; column++) {
        cells.push({ node: table.nodeAt(map.map[column]) as Node, pos: tableStart + map.map[column] });
    }

    return cells;
}

/**
 * Return whether the selected columns, or the column of the cursor, have an alignment
 */
export function isColumnAligned(state: EditorState, align: Alignment) {
    return isInTable(state) && selectedHeaderCells(state).every(({ node }) => node.attrs.align === align);
}

/**
 * Align the selected columns, or the column of the cursor
 *
 * The alignment is given to the header cells, from which the other cells of the columns take it
 */
export function alignColumn(align: Alignment): Command {
    return (state, dispatch) => {
        if (!isInTable(state) || isColumnAligned(state, align)) {
            return false;
        }

        if (dispatch) {
            const tr = state.tr;
            for (const { node, pos } of selectedHeaderCells(state)) {
                tr.setNodeMarkup(pos, null, { ...node.attrs, align });
            }
            dispatch(tr);
        }

        return true;
    };
}

/**
 * Empty the cells of the selected rows or columns, or of the row or the column of the cursor
 */
export function clearCells(axis: Axis): Command {
    return (state, dispatch) => {
        if (!isInTable(state)) {
            return false;
        }

        const { selection } = state;
        const selected = selection instanceof CellSelection && (axis === "row" ? selection.isRowSelection() : selection.isColSelection());
        const cells = selected ? selection : selectAxis(getHeadCell(state), axis);
        const tr = state.tr;

        // The text of the paragraph of each cell is removed, so that a cursor stays in its cell
        cells.forEachCell((cell, pos) => {
            if (!isEmptyCell(cell)) {
                tr.delete(tr.mapping.map(pos + 2), tr.mapping.map(pos + cell.nodeSize - 2));
            }
        });

        if (!tr.docChanged) {
            return false;
        }

        dispatch?.(tr);
        return true;
    };
}

/**
 * Delete the selected rows or columns, or the whole table, once their cells are empty
 *
 * The cells with content are emptied by the next command first, so that the selection is deleted
 * at the second press of the key
 */
const deleteEmptySelection: Command = (state, dispatch, view) => {
    const { selection } = state;

    if (!(selection instanceof CellSelection)) {
        return false;
    }

    const rows = selection.isRowSelection();
    const columns = selection.isColSelection();

    let empty = true;
    selection.forEachCell((cell) => {
        empty &&= isEmptyCell(cell);
    });

    if (!(rows || columns) || !empty) {
        return false;
    }

    if (rows && columns) {
        dispatch?.(removeTableOf(state.tr, selection.$anchorCell));
        return true;
    }

    return (rows ? deleteRows : deleteColumns)(state, dispatch, view);
};

/**
 * Empty the selected cells keeping them selected, so that the selected rows, columns or table can
 * be deleted next
 */
export const deleteSelectedCells = chainCommands(deleteEmptySelection, deleteCellSelection);

/**
 * Get the keys to move between the cells
 *
 * Enter moves to the cell below as GFM cells cannot hold more than a line
 */
export function buildTableKeymap(): Record<string, Command> {
    return {
        Tab: goToNextCellOrAddRow,
        "Shift-Tab": goToNextCell(-1),
        Enter: goToVerticalCell(1),
        "Shift-Enter": goToVerticalCell(-1),
        // Line breaks cannot be added to the cells, so the keys adding them leave the table
        "Mod-Enter": exitTable,
        "Ctrl-Enter": exitTable,
        // In the gap of a table the key is handled even when it cannot lift the table, so that the
        // other commands do not change the blocks around it
        "Mod-[": atTableGap(chainCommands(liftTable, () => true)),
        Backspace: deleteSelectedCells,
        Delete: deleteSelectedCells,
    };
}

/**
 * Get the plugin keeping the tables as GFM ones: the first row is the header and the cells of a
 * column have the alignment of the header cell
 */
export function normalizeTables() {
    return new Plugin({
        appendTransaction(transactions, _oldState, state) {
            if (!transactions.some((tr) => tr.docChanged)) {
                return null;
            }

            let tr = null as Transaction | null;

            state.doc.descendants((node, pos) => {
                if (node.type !== schema.nodes.table) {
                    return !node.isTextblock;
                }

                const header = node.firstChild as Node;

                node.forEach((row, rowOffset, rowIndex) => {
                    row.forEach((cell, cellOffset, cellIndex) => {
                        const type = rowIndex === 0 ? schema.nodes.table_header : schema.nodes.table_cell;
                        const align = rowIndex === 0 ? cell.attrs.align : (header.maybeChild(cellIndex)?.attrs.align ?? null);

                        if (cell.type !== type || cell.attrs.align !== align) {
                            tr ??= state.tr;
                            tr.setNodeMarkup(pos + 1 + rowOffset + 1 + cellOffset, type, { ...cell.attrs, align });
                        }
                    });
                });

                return false;
            });

            return tr;
        },
    });
}
